<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PairingRoutingSrpService
{
    public const FILTERS = [
        'no_srp' => 'No SRP',
        'nm_part' => 'Nama Part',
        'no_brg_asli' => 'No Part',
        'nm_customer' => 'Customer',
    ];

    public const COLUMNS = [
        'no_srp',
        'no_brg_asli',
        'nm_part',
        'nm_customer',
        'proses',
        'id_routing',
    ];

    public function query(string $filterBy = 'no_srp', string $keyword = ''): Builder
    {
        $column = array_key_exists($filterBy, self::FILTERS) ? $filterBy : 'no_srp';
        $keyword = trim($keyword);
        $query = DB::table('vw_srp_complete_routing')->select(self::COLUMNS);

        if ($keyword !== '') {
            $query->where($column, 'like', '%'.$keyword.'%');
        }

        return $query->orderBy('no_srp');
    }

    public function find(string $noSrp): ?array
    {
        $row = DB::table('vw_srp_complete_routing')
            ->select(self::COLUMNS)
            ->where('no_srp', $noSrp)
            ->first();

        return $row ? (array) $row : null;
    }

    public function routings(): array
    {
        return DB::table('tblrouting')
            ->select(['id_routing', 'nm_routing'])
            ->orderBy('id_routing')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function updateRouting(string $noSrp, string $routingId): void
    {
        $noSrp = trim($noSrp);
        $routingId = trim($routingId);
        if ($noSrp === '') {
            throw new RuntimeException('SRP belum dipilih.');
        }
        if ($routingId === '') {
            throw new RuntimeException('Routing belum dipilih.');
        }

        DB::transaction(function () use ($noSrp, $routingId): void {
            if (! DB::table('tblrouting')->where('id_routing', $routingId)->exists()) {
                throw new RuntimeException('ID Routing tidak terdaftar.');
            }

            $before = DB::table('tblsrp')->where('no_srp', $noSrp)->first();
            $updated = DB::table('tblsrp')
                ->where('no_srp', $noSrp)
                ->update(['id_routing' => $routingId]);

            if ($updated === 0 && ! $before) {
                throw new RuntimeException('Data SRP tidak ditemukan.');
            }

            $this->recordPairingChange($noSrp, $before ? (string) ($before->id_routing ?? '') : '', $routingId);
        });
    }

    private function recordPairingChange(string $noSrp, string $routingBefore, string $routingAfter): void
    {
        if (! Schema::hasTable('tbl_pairing_routing_history') || $routingBefore === $routingAfter) {
            return;
        }

        $srp = DB::table('vw_srp_complete_routing')
            ->select(['no_srp', 'no_brg_asli', 'nm_part'])
            ->where('no_srp', $noSrp)
            ->first();

        $names = DB::table('tblrouting')
            ->whereIn('id_routing', array_filter([$routingBefore, $routingAfter]))
            ->pluck('nm_routing', 'id_routing')
            ->map(fn ($nm) => (string) $nm)
            ->all();

        DB::table('tbl_pairing_routing_history')->insert([
            'no_srp' => $noSrp,
            'no_part' => $srp->no_brg_asli ?? null,
            'nm_part' => $srp->nm_part ?? null,
            'routing_before' => $routingBefore !== '' ? $routingBefore : null,
            'nama_before' => $routingBefore !== '' ? ($names[$routingBefore] ?? null) : null,
            'routing_after' => $routingAfter,
            'nama_after' => $names[$routingAfter] ?? null,
            'changed_by' => $this->currentActor(),
            'changed_at' => now('Asia/Jakarta'),
        ]);
    }

    private function currentActor(): string
    {
        return (string) (auth()->user()?->username ?? session('username') ?? 'System');
    }
}
