<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class IncomingTraceReport
{
    public const FILTERS = [
        'no_lot' => 'LOT DTI',
        'no_lot_cust' => 'LOT CUST',
        'no_sj_cust' => 'SJ CUST',
        'customer' => 'CUST',
        'no_part' => 'NO PART',
        'nm_part' => 'NAMA PART',
    ];

    public const COLUMNS = [
        'tgl_input',
        'no_lot',
        'no_srp',
        'no_part',
        'nm_part',
        'no_lot_cust',
        'no_sj_cust',
        'qty',
        'customer',
        'tgl_delivery',
    ];

    public function query(
        string $keyword = '',
        string $filterBy = 'no_lot',
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): Builder {
        $filterColumn = match ($filterBy) {
            'no_lot_cust' => 'A.no_lot_cust',
            'no_sj_cust' => 'A.no_sj_cust',
            'customer' => 'B.nm_customer',
            'no_part' => 'B.no_brg_asli',
            'nm_part' => 'B.nm_part',
            default => 'A.no_lot',
        };

        $query = DB::table('tbllotdaily AS A')
            ->leftJoin('vw_srp_complete AS B', 'A.no_srps', '=', 'B.no_srp')
            ->select([
                'A.tgl_produksi AS tgl_input',
                'A.tgl_delivery',
                'A.no_lot',
                'A.no_srps AS no_srp',
                'B.no_brg_asli AS no_part',
                'B.nm_part',
                'A.no_sj_cust',
                'A.no_lot_cust',
                'B.nm_customer AS customer',
                'A.qty',
            ]);

        if ($keyword !== '') {
            $query->where($filterColumn, 'like', '%'.$keyword.'%');
        }

        [$from, $to] = $this->dateRange($dateFrom, $dateTo);
        if ($from && $to) {
            $query->where('A.tgl_produksi', '>=', $from)->where('A.tgl_produksi', '<', $to);
        }

        return $query->orderByDesc('A.tgl_produksi')->orderByDesc('A.no_lot');
    }

    public function dateRange(?string $dateFrom, ?string $dateTo): array
    {
        if (! $dateFrom || ! $dateTo) {
            return [null, null];
        }

        try {
            $from = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $dateTo)->startOfDay();
        } catch (\Throwable) {
            return [null, null];
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to->copy()->addDay()];
    }
}
