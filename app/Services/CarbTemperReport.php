<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CarbTemperReport
{
    public const MONITOR_SEQUENCE_IDS = ['S06', 'S07', 'S08', 'S10', 'S11'];
    public const TEMPER_LIMIT_MINUTES = 240;
    public const SAFE_REMAINING_MINUTES = 120;
    public const WARNING_REMAINING_MINUTES = 0;

    public function machineOptions(): array
    {
        return DB::table('tbl_seq_all')
            ->whereNotNull('no_mesin')
            ->where('no_mesin', '<>', '')
            ->distinct()
            ->orderBy('no_mesin')
            ->pluck('no_mesin')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    public function monitoringData(string $machine, Carbon $from, Carbon $to, string $status = 'ALL CONDITION'): array
    {
        $allRows = $this->baseQuery($machine, $from, $to)->get()->map(fn ($row) => $this->decorateRow($row));
        $details = $status === 'ALL CONDITION'
            ? $allRows
            : $allRows->where('level_status', $status)->values();

        return [
            'details' => $details,
            'all_details' => $allRows,
            'summary' => [
                'total' => $details->count(),
                'safe' => $allRows->where('level_status', 'SAFE')->count(),
                'warning' => $allRows->where('level_status', 'WARNING')->count(),
                'critical' => $allRows->where('level_status', 'CRITICAL')->count(),
                'done' => $allRows->where('level_status', 'DONE')->count(),
                'total_scope' => $allRows->count(),
            ],
        ];
    }

    public function baseQuery(string $machine, Carbon $from, Carbon $to): Builder
    {
        $carbLots = DB::table('tbl_seq_all AS v')
            ->whereIn('v.id_seq', self::MONITOR_SEQUENCE_IDS)
            ->where('v.finish_time', '>=', $from)
            ->where('v.finish_time', '<', $to)
            ->when($machine !== '' && $machine !== 'ALL', fn (Builder $query) => $query->where('v.no_mesin', $machine))
            ->select('v.no_lot')
            ->selectRaw('MAX(v.finish_time) AS start_monitor_time')
            ->groupBy('v.no_lot');

        $latestTime = DB::table('tbl_seq_all AS latest')
            ->selectRaw("latest.no_lot, MAX(COALESCE(NULLIF(latest.finish_time, '0000-00-00 00:00:00'), latest.start_time)) AS latest_at")
            ->whereIn('latest.no_lot', $carbLots->clone()->select('no_lot'))
            ->groupBy('latest.no_lot');

        $lastSequence = DB::table('tbl_seq_all AS last_seq')
            ->joinSub($latestTime, 'latest_time', function ($join): void {
                $join->on('latest_time.no_lot', '=', 'last_seq.no_lot');
            })
            ->whereRaw("COALESCE(NULLIF(last_seq.finish_time, '0000-00-00 00:00:00'), last_seq.start_time) = latest_time.latest_at")
            ->select([
                'last_seq.no_lot',
                'last_seq.no_mesin AS mesin',
                'last_seq.pic_01',
                'last_seq.nm_part',
                'last_seq.nm_seq',
            ]);

        $temperStart = DB::table('tbl_seq_all AS temper')
            ->where('temper.id_seq', 'S18')
            ->whereIn('temper.no_lot', $carbLots->clone()->select('no_lot'))
            ->select('temper.no_lot')
            ->selectRaw('MAX(temper.start_time) AS start_s18')
            ->groupBy('temper.no_lot');

        return DB::query()
            ->fromSub($carbLots, 'monitor')
            ->leftJoinSub($lastSequence, 'last_seq', function ($join): void {
                $join->on('monitor.no_lot', '=', 'last_seq.no_lot');
            })
            ->leftJoinSub($temperStart, 's18', function ($join): void {
                $join->on('monitor.no_lot', '=', 's18.no_lot');
            })
            ->select([
                'monitor.no_lot',
                'monitor.start_monitor_time',
                's18.start_s18',
                'last_seq.mesin',
                'last_seq.pic_01',
                'last_seq.nm_part',
                'last_seq.nm_seq',
            ])
            ->orderByDesc(DB::raw('COALESCE(s18.start_s18, monitor.start_monitor_time)'));
    }

    public function decorateRow(object $row): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $start = $this->parseDate($row->start_monitor_time ?? null);
        $temper = $this->parseDate($row->start_s18 ?? null);
        $end = $temper ?: $now;
        $totalMinutes = $start ? (int) floor(max(0, $start->diffInSeconds($end, false)) / 60) : 0;
        $remaining = self::TEMPER_LIMIT_MINUTES - $totalMinutes;

        if ($temper) {
            $level = 'DONE';
            $timing = $remaining < 0 ? 'Terlambat' : 'On Time';
        } elseif ($remaining > self::SAFE_REMAINING_MINUTES) {
            $level = 'SAFE';
            $timing = '-';
        } elseif ($remaining > self::WARNING_REMAINING_MINUTES) {
            $level = 'WARNING';
            $timing = '-';
        } else {
            $level = 'CRITICAL';
            $timing = '-';
        }

        return [
            'no_lot' => $row->no_lot,
            'mesin' => $row->mesin,
            'pic_01' => $row->pic_01,
            'nm_part' => $row->nm_part,
            'nm_seq' => $row->nm_seq,
            'start_monitor_time' => $row->start_monitor_time,
            'start_s18' => $row->start_s18,
            'total_minutes' => $totalMinutes,
            'sisa_menit' => $remaining,
            'keterangan_waktu' => $timing,
            'level_status' => $level,
        ];
    }

    public function alertMessage(Collection $rows): string
    {
        $alerts = $rows->whereIn('level_status', ['WARNING', 'CRITICAL'])->values();
        $lines = ['📡 <b>PAR MOBILE-Monitoring (Auto-Update)</b>', '', 'Terdapat '.$alerts->count().' Lot yang masih memerlukan penanganan Temper!', ''];
        foreach ($alerts as $row) {
            $emoji = ($row['level_status'] ?? '') === 'CRITICAL' ? '🚨 [CRITICAL]' : '⚠️ [WARNING]';
            $lines[] = $emoji;
            $lines[] = '📍 <b>Lot: '.e((string) ($row['no_lot'] ?? '-')).'</b>';
            $lines[] = '⚙️ <b>Part: '.e((string) ($row['nm_part'] ?? '-')).'</b>';
            $lines[] = 'Saat ini berada di: '.e((string) ($row['nm_seq'] ?? '-'));
            $lines[] = 'Mesin: '.e((string) ($row['mesin'] ?? '-')).' | PIC: '.e((string) ($row['pic_01'] ?? '-'));
            $lines[] = 'Sisa Waktu (Aft. Carb): <b>'.e((string) ($row['sisa_menit'] ?? 0)).' Menit</b>';
            $lines[] = '<i>Note: Jika angka minus artinya telat masuk</i> ';
            $lines[] = '----------------------------';
        }
        $lines[] = '<i>Waktu Update: '.now('Asia/Jakarta')->format('H:i:s').' WIB</i>';
        return implode("\n", $lines);
    }

    public function clearMessage(): string
    {
        return "✅ <b>Monitoring Clear</b>\nSemua lot sudah dalam kondisi SAFE atau DONE.";
    }

    private function parseDate($value): ?Carbon
    {
        if (! $value || in_array((string) $value, ['0', '0000-00-00', '0000-00-00 00:00:00'], true)) return null;
        try { return Carbon::parse($value, 'Asia/Jakarta'); } catch (\Throwable) { return null; }
    }
}
