<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class RealtimeProductionReport
{
    public const FILTERS = [
        'no_lot' => 'LOT DTI',
        'no_lot_cust' => 'LOT CUST',
        'no_sj_cust' => 'SJ CUST',
        'customer' => 'CUSTOMER',
        'no_part' => 'NO PART',
        'nm_part' => 'NAMA PART',
    ];

    public const LIST_COLUMNS = [
        'no_lot',
        'no_srp',
        'no_part',
        'nm_part',
        'no_lot_cust',
        'no_sj_cust',
        'customer',
        'qty',
    ];

    public const DETAIL_COLUMNS = [
        'id',
        'no_lot',
        'no_srp',
        'seq',
        'id_seq',
        'nm_seq',
        'id_routing',
        'start_time',
        'finish_time',
        'duration',
        'tray_no',
        'tray_nokecil',
        'pic_01',
        'pic_02',
        'pic_03',
        'pic_04',
        'akakami',
        'ojt',
        'hold',
        'finish',
        'no_mesin',
        'updated_at',
        'no_part',
        'nm_part',
        'customer',
        'no_sj_cust',
        'no_lot_cust',
        'qty',
    ];

    public function listQuery(string $keyword, string $filterBy = 'no_lot'): Builder
    {
        $column = match ($filterBy) {
            'no_lot_cust' => 'no_lot_cust',
            'no_sj_cust' => 'no_sj_cust',
            'customer' => 'customer',
            'no_part' => 'no_part',
            'nm_part' => 'nm_part',
            default => 'no_lot',
        };

        return DB::table('tbl_seq_all')
            ->select(self::LIST_COLUMNS)
            ->where('seq', 1)
            ->when($keyword !== '', fn (Builder $query) => $query->where($column, 'like', '%'.$keyword.'%'))
            ->orderByDesc('no_lot');
    }

    public function detailQuery(string $noLot): Builder
    {
        return DB::table('tbl_seq_all')
            ->select(self::DETAIL_COLUMNS)
            ->where('no_lot', $noLot)
            ->orderBy('seq')
            ->orderBy('id');
    }
}
