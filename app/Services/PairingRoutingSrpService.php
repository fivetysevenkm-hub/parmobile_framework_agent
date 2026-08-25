<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
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

            $updated = DB::table('tblsrp')
                ->where('no_srp', $noSrp)
                ->update(['id_routing' => $routingId]);

            if ($updated === 0 && ! DB::table('tblsrp')->where('no_srp', $noSrp)->exists()) {
                throw new RuntimeException('Data SRP tidak ditemukan.');
            }
        });
    }
}
