<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LastSequenceReport
{
    public const FILTERS = [
        'no_lot' => 'LOT DTI',
        'no_part' => 'NO PART',
        'nm_part' => 'NAMA PART',
        'customer' => 'CUST',
        'no_sj_cust' => 'SJ CUST',
        'no_lot_cust' => 'LOT CUST',
    ];

    public const SORT_COLUMNS = [
        'no_lot' => 'no_lot',
        'nm_part' => 'nm_part',
        'customer' => 'customer',
        'no_sj_cust' => 'no_sj_cust',
        'no_lot_cust' => 'no_lot_cust',
        'nm_seq' => 'nm_seq',
        'updated_at' => 'updated_at',
    ];

    public const COLUMNS = [
        'no_lot',
        'no_part',
        'nm_part',
        'customer',
        'no_sj_cust',
        'no_lot_cust',
        'qty',
        'nm_seq',
        'start_time',
        'finish_time',
        'duration',
        'tray_no',
        'tray_nokecil',
        'pic_01',
        'no_mesin',
        'updated_at',
    ];

    public function query(
        string $keyword = '',
        string $filterBy = 'no_lot',
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortColumn = 'updated_at',
        string $sortDirection = 'desc',
    ): Builder {
        $filterColumn = array_key_exists($filterBy, self::FILTERS) ? $filterBy : 'no_lot';
        $orderColumn = self::SORT_COLUMNS[$sortColumn] ?? 'updated_at';
        $orderDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        $query = DB::table('view_seq_last')->select(self::COLUMNS);

        if ($keyword !== '') {
            $query->where($filterColumn, 'like', '%'.$keyword.'%');
        }

        [$from, $to] = $this->dateRange($dateFrom, $dateTo);
        if ($from && $to) {
            $query->where(function (Builder $dateQuery) use ($from, $to): void {
                $dateQuery->where(function (Builder $startQuery) use ($from, $to): void {
                    $startQuery->where('start_time', '>=', $from)
                        ->where('start_time', '<', $to);
                })->orWhere(function (Builder $finishQuery) use ($from, $to): void {
                    $finishQuery->where('finish_time', '>=', $from)
                        ->where('finish_time', '<', $to);
                });
            });
        }

        return $query->orderBy($orderColumn, $orderDirection)->orderBy('no_lot');
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
