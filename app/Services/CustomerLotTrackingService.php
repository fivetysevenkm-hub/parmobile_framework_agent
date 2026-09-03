<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CustomerLotTrackingService
{
    public function getCustomerName(int $customerId): ?string
    {
        $user = DB::table('tbl_customer_portal_users')
            ->where('id', $customerId)
            ->first();

        if (! $user) {
            return null;
        }

        return DB::table('tblcustomer')
            ->where('no_customer', $user->no_customer)
            ->value('nm_customer');
    }

    public function getLotsByCustomer(
        string $customerName,
        ?string $searchCol = null,
        ?string $search = null,
        ?string $status = null,
        int $perPage = 10,
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $fromDate = now()->subDays(30)->startOfDay();
        $toDate = now()->endOfDay();

        $query = DB::table('tbl_seq_all')
            ->select(
                'no_lot',
                'no_srp',
                'no_part',
                'nm_part',
                'no_lot_cust',
                'no_sj_cust',
                'customer',
                'qty',
                DB::raw("MIN(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) as all_finished"),
                DB::raw("MAX(CASE WHEN hold = 'yes' THEN 1 ELSE 0 END) as has_hold"),
                DB::raw("COUNT(DISTINCT seq) as total_seq"),
                DB::raw("SUM(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) as finished_seq"),
                DB::raw("MAX(CASE WHEN start_time IS NOT NULL AND start_time != '' THEN 1 ELSE 0 END) as has_started"),
                DB::raw("MAX(start_time) as last_start"),
                DB::raw("MAX(finish_time) as last_finish")
            )
            ->where('customer', $customerName)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('no_lot', 'no_srp', 'no_part', 'nm_part', 'no_lot_cust', 'no_sj_cust', 'customer', 'qty');

        $searchCol = $searchCol ?: 'no_lot';
        if ($search && in_array($searchCol, ['no_lot', 'no_part', 'nm_part', 'no_lot_cust', 'no_sj_cust'], true)) {
            $query->where($searchCol, 'LIKE', "%{$search}%");
        }

        if ($status) {
            $query->havingRaw(match ($status) {
                'selesai' => "MIN(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) = 1",
                'diproses' => "(MAX(CASE WHEN start_time IS NOT NULL AND start_time != '' THEN 1 ELSE 0 END) = 1 OR MAX(CASE WHEN hold = 'yes' THEN 1 ELSE 0 END) = 1) AND MIN(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) = 0",
                'belum' => "MAX(CASE WHEN start_time IS NOT NULL AND start_time != '' THEN 1 ELSE 0 END) = 0 AND MAX(CASE WHEN hold = 'yes' THEN 1 ELSE 0 END) = 0",
            });
        }

        $lots = $query->orderBy('no_lot', 'desc')->paginate($perPage);

        $mapped = $lots->getCollection()->map(function ($lot) {
            $status = $this->resolveStatus($lot);
            $progress = $lot->total_seq > 0
                ? round(($lot->finished_seq / $lot->total_seq) * 100)
                : 0;

            return [
                'no_lot' => $lot->no_lot,
                'no_srp' => $lot->no_srp,
                'no_part' => $lot->no_part,
                'nm_part' => $lot->nm_part,
                'no_lot_cust' => $lot->no_lot_cust,
                'no_sj_cust' => $lot->no_sj_cust,
                'customer' => $lot->customer,
                'qty' => $lot->qty,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'status_color' => $this->statusColor($status),
                'progress' => $progress,
                'finished_seq' => $lot->finished_seq,
                'total_seq' => $lot->total_seq,
            ];
        });

        return $lots->setCollection($mapped);
    }

    public function getLotDetail(string $noLot): array
    {        $rows = DB::table('tbl_seq_all')
            ->where('no_lot', $noLot)
            ->orderBy('seq')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $master = $rows->first();
        $processes = $rows->map(function ($row) {
            $status = 'waiting';
            if ($row->finish === 'yes') {
                $status = 'finished';
            } elseif ($row->hold === 'yes') {
                $status = 'hold';
            } elseif ($row->start_time && $row->start_time !== '') {
                $status = 'in_progress';
            }

            return [
                'seq' => $row->seq,
                'nm_seq' => $row->nm_seq,
                'start_time' => $row->start_time,
                'finish_time' => $row->finish_time,
                'duration' => $row->duration,
                'pic_01' => $row->pic_01,
                'pic_02' => $row->pic_02,
                'pic_03' => $row->pic_03,
                'pic_04' => $row->pic_04,
                'tray_no' => $row->tray_no,
                'no_mesin' => $row->no_mesin,
                'akakami' => $row->akakami,
                'ojt' => $row->ojt,
                'status' => $status,
            ];
        })->toArray();

        $total = count($processes);
        $finished = collect($processes)->where('status', 'finished')->count();
        $progress = $total > 0 ? round(($finished / $total) * 100) : 0;

        return [
            'master' => [
                'no_lot' => $master->no_lot,
                'no_srp' => $master->no_srp,
                'no_part' => $master->no_part,
                'nm_part' => $master->nm_part,
                'no_lot_cust' => $master->no_lot_cust,
                'no_sj_cust' => $master->no_sj_cust,
                'customer' => $master->customer,
                'qty' => $master->qty,
            ],
            'processes' => $processes,
            'total_seq' => $total,
            'finished_seq' => $finished,
            'progress' => $progress,
        ];
    }

    public function getNextProcess(string $noLot): ?string
    {
        $lot = DB::table('tbl_seq_all')
            ->where('no_lot', $noLot)
            ->select('no_srp')
            ->first();

        if (! $lot) {
            return null;
        }

        $srp = DB::table('tblsrp')
            ->where('no_srp', $lot->no_srp)
            ->first();

        if (! $srp || ! $srp->id_routing) {
            return null;
        }

        $routing = DB::table('tblrouting')
            ->where('id_routing', $srp->id_routing)
            ->first();

        if (! $routing) {
            return null;
        }

        $stepIds = [];
        for ($i = 1; $i <= 26; $i++) {
            $col = 'Seq_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $value = $routing->{$col} ?? null;
            if (! $value || $value === '') {
                break;
            }
            $stepIds[] = $value;
        }

        if (empty($stepIds)) {
            return null;
        }

        $processCount = DB::table('tbl_seq_all')
            ->where('no_lot', $noLot)
            ->count('seq');

        if ($processCount >= count($stepIds)) {
            return null;
        }

        $nextIdSeq = $stepIds[$processCount];

        return DB::table('tblsequence')
            ->where('id_seq', $nextIdSeq)
            ->value('nm_sequence');
    }

    private function resolveStatus(object $lot): string
    {
        if ($lot->all_finished) {
            return 'selesai';
        }
        if ($lot->has_started || $lot->has_hold) {
            return 'diproses';
        }

        return 'belum';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'selesai' => 'Selesai',
            'diproses' => 'Diproses',
            'belum' => 'Belum Diproses',
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'selesai' => 'teal',
            'diproses' => 'blue',
            'belum' => 'gray',
        };
    }
}
