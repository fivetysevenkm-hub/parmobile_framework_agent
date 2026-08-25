<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CycleTimeService
{
    public const FILTERS = [
        'no_srp' => 'No SRP',
        'nm_part' => 'Nama Part',
        'no_brg_asli' => 'No Part',
        'nm_customer' => 'Customer',
    ];

    public const SRP_COLUMNS = [
        'no_srp',
        'no_brg_asli',
        'nm_part',
        'nm_customer',
        'proses',
        'id_routing',
    ];

    private const ROUTING_SEQUENCE_COLUMNS = [
        'Seq_01', 'Seq_02', 'Seq_03', 'Seq_04', 'Seq_05', 'Seq_06', 'Seq_07',
        'Seq_08', 'Seq_09', 'Seq_10', 'Seq_11', 'Seq_12', 'Seq_13', 'Seq_14',
        'Seq_15', 'Seq_16', 'Seq_17', 'Seq_18', 'Seq_19', 'Seq_20', 'Seq_21',
        'Seq_22', 'Seq_23', 'Seq_24', 'Seq_25', 'Seq_26',
    ];

    public function srpQuery(string $filterBy = 'no_srp', string $keyword = ''): Builder
    {
        $column = array_key_exists($filterBy, self::FILTERS) ? $filterBy : 'no_srp';
        $keyword = trim($keyword);
        $query = DB::table('vw_srp_complete_routing')->select(self::SRP_COLUMNS);

        if ($keyword !== '') {
            $query->where($column, 'like', '%'.$keyword.'%');
        }

        return $query->orderBy('no_srp');
    }

    public function findSrp(string $noSrp): ?array
    {
        $row = DB::table('vw_srp_complete_routing')
            ->select(self::SRP_COLUMNS)
            ->where('no_srp', $noSrp)
            ->first();

        return $row ? (array) $row : null;
    }

    public function summary(): array
    {
        $total = (int) DB::table('vw_srp_complete_routing')
            ->distinct()
            ->count('no_srp');
        $sequenceJoin = $this->sequenceJoinExpression();
        $completedQuery = DB::table('tblsrp as sr')
            ->join('tblrouting as r', 'sr.id_routing', '=', 'r.id_routing')
            ->join('tblsequence as s', fn ($join) => $join->whereRaw($sequenceJoin))
            ->leftJoin('tblsrp_detail as d', function ($join): void {
                $join->on('d.no_srp', '=', 'sr.no_srp')
                    ->on('d.id_sequence', '=', 's.id_seq');
            })
            ->select('sr.no_srp')
            ->groupBy('sr.no_srp')
            ->havingRaw('COUNT(DISTINCT s.id_seq) > 0')
            ->havingRaw('COUNT(DISTINCT CASE WHEN d.cycle_time IS NOT NULL THEN s.id_seq END) = COUNT(DISTINCT s.id_seq)');
        $completed = (int) DB::query()->fromSub($completedQuery, 'completed_srp')->count();
        $incomplete = max(0, $total - $completed);

        return [
            'total' => $total,
            'completed' => $completed,
            'incomplete' => $incomplete,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
        ];
    }

    public function completedSrpNos(array $noSrps): array
    {
        $noSrps = array_values(array_filter(array_map('strval', $noSrps), fn (string $noSrp) => $noSrp !== ''));
        if ($noSrps === []) {
            return [];
        }

        $sequenceJoin = $this->sequenceJoinExpression();

        return DB::table('tblsrp as sr')
            ->join('tblrouting as r', 'sr.id_routing', '=', 'r.id_routing')
            ->join('tblsequence as s', fn ($join) => $join->whereRaw($sequenceJoin))
            ->leftJoin('tblsrp_detail as d', function ($join): void {
                $join->on('d.no_srp', '=', 'sr.no_srp')
                    ->on('d.id_sequence', '=', 's.id_seq');
            })
            ->whereIn('sr.no_srp', $noSrps)
            ->groupBy('sr.no_srp')
            ->havingRaw('COUNT(DISTINCT s.id_seq) > 0')
            ->havingRaw('COUNT(DISTINCT CASE WHEN d.cycle_time IS NOT NULL THEN s.id_seq END) = COUNT(DISTINCT s.id_seq)')
            ->pluck('sr.no_srp')
            ->map(fn ($noSrp) => (string) $noSrp)
            ->all();
    }

    public function cycleRows(string $noSrp): array
    {
        $orderSql = collect(self::ROUTING_SEQUENCE_COLUMNS)
            ->map(fn (string $column, int $index) => 'WHEN r.'.$column.' THEN '.($index + 1))
            ->implode(' ');
        $sequenceJoin = $this->sequenceJoinExpression();

        return DB::table('tblsrp as sr')
            ->join('tblrouting as r', 'sr.id_routing', '=', 'r.id_routing')
            ->join('tblsequence as s', fn ($join) => $join->whereRaw($sequenceJoin))
            ->leftJoin('tblsrp_detail as d', function ($join): void {
                $join->on('d.no_srp', '=', 'sr.no_srp')
                    ->on('d.id_sequence', '=', 's.id_seq');
            })
            ->where('sr.no_srp', $noSrp)
            ->select([
                's.id_seq as id_sequence',
                's.nm_sequence',
                'd.cycle_time',
            ])
            ->orderByRaw('CASE s.id_seq '.$orderSql.' ELSE 999 END')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function sequenceJoinExpression(): string
    {
        return "FIND_IN_SET(s.id_seq, CONCAT_WS(',', r.Seq_01,r.Seq_02,r.Seq_03,r.Seq_04,r.Seq_05,r.Seq_06,r.Seq_07,r.Seq_08,r.Seq_09,r.Seq_10,r.Seq_11,r.Seq_12,r.Seq_13,r.Seq_14,r.Seq_15,r.Seq_16,r.Seq_17,r.Seq_18,r.Seq_19,r.Seq_20,r.Seq_21,r.Seq_22,r.Seq_23,r.Seq_24,r.Seq_25,r.Seq_26)) > 0";
    }

    public function saveCycleTimes(string $noSrp, array $cycleTimes): void
    {
        $noSrp = trim($noSrp);
        if ($noSrp === '') {
            throw new RuntimeException('SRP belum dipilih.');
        }

        DB::transaction(function () use ($noSrp, $cycleTimes): void {
            if (! DB::table('tblsrp')->where('no_srp', $noSrp)->exists()) {
                throw new RuntimeException('Data SRP tidak ditemukan.');
            }

            $expectedIds = collect($this->cycleRows($noSrp))
                ->pluck('id_sequence')
                ->map(fn ($id) => (string) $id)
                ->all();
            $expectedLookup = array_fill_keys($expectedIds, true);
            if ($expectedIds === []) {
                throw new RuntimeException('Routing SRP belum memiliki sequence proses.');
            }

            foreach ($cycleTimes as $idSequence => $cycleTime) {
                $idSequence = trim((string) $idSequence);
                if (! isset($expectedLookup[$idSequence])) {
                    throw new RuntimeException('Sequence '.$idSequence.' tidak termasuk routing SRP ini.');
                }
                if ($cycleTime === null || trim((string) $cycleTime) === '' || filter_var($cycleTime, FILTER_VALIDATE_INT) === false || (int) $cycleTime < 0) {
                    throw new RuntimeException('Cycle Time untuk sequence '.$idSequence.' harus berupa bilangan bulat tidak negatif.');
                }

                $detailQuery = DB::table('tblsrp_detail')
                    ->where('no_srp', $noSrp)
                    ->where('id_sequence', $idSequence);
                if ($detailQuery->exists()) {
                    $detailQuery->update(['cycle_time' => (int) $cycleTime]);
                } else {
                    DB::table('tblsrp_detail')->insert([
                        'no_srp' => $noSrp,
                        'id_sequence' => $idSequence,
                        'cycle_time' => (int) $cycleTime,
                    ]);
                }
            }
        });
    }
}
