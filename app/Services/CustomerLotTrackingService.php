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

        $searchCol = $searchCol ?: 'no_lot';

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
                DB::raw("SUM(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) as finished_seq"),
                DB::raw("MAX(CASE WHEN start_time IS NOT NULL AND start_time != '' THEN 1 ELSE 0 END) as has_started"),
                DB::raw("MAX(created_at) as last_created")
            )
            ->where('customer', $customerName)
            ->groupBy('no_lot', 'no_srp', 'no_part', 'nm_part', 'no_lot_cust', 'no_sj_cust', 'customer', 'qty')
            ->having('last_created', '>=', $fromDate);

        if ($search && in_array($searchCol, ['no_lot', 'no_part', 'nm_part', 'no_lot_cust', 'no_sj_cust'], true)) {
            $query->where($searchCol, 'LIKE', "%{$search}%");
        }

        $allRows = $query->get();

        $noSrpValues = $allRows->pluck('no_srp')->unique()->filter()->toArray();
        $routingStepCounts = $this->getRoutingStepCounts($noSrpValues);

        $mapped = $allRows->map(function ($lot) use ($routingStepCounts) {
            $finishedSeq = (int) $lot->finished_seq;
            $hasStarted = (bool) $lot->has_started;
            $hasHold = (bool) $lot->has_hold;

            $totalSeq = $routingStepCounts[$lot->no_srp] ?? 0;
            if ($totalSeq <= 0) {
                $totalSeq = $finishedSeq;
            }

            $progress = $totalSeq > 0
                ? round(($finishedSeq / $totalSeq) * 100)
                : 0;

            $lotStatus = $totalSeq > 0 && $finishedSeq >= $totalSeq
                ? 'selesai'
                : ($hasStarted || $hasHold ? 'diproses' : 'belum');

            return [
                'no_lot' => $lot->no_lot,
                'no_srp' => $lot->no_srp,
                'no_part' => $lot->no_part,
                'nm_part' => $lot->nm_part,
                'no_lot_cust' => $lot->no_lot_cust,
                'no_sj_cust' => $lot->no_sj_cust,
                'customer' => $lot->customer,
                'qty' => $lot->qty,
                'status' => $lotStatus,
                'status_label' => $this->statusLabel($lotStatus),
                'status_color' => $this->statusColor($lotStatus),
                'progress' => $progress,
                'finished_seq' => $finishedSeq,
                'total_seq' => $totalSeq,
            ];
        });

        $collection = $mapped
            ->sortByDesc('no_lot')
            ->values();

        if ($status) {
            $collection = $collection->filter(fn ($lot) => $lot['status'] === $status)->values();
        }

        $total = $collection->count();
        $page = (int) \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        if ($page < 1) {
            $page = 1;
        }

        $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    public function getLotDetail(string $noLot, ?string $customerName = null): array
    {
        $query = DB::table('tbl_seq_all')
            ->where('no_lot', $noLot);
        if ($customerName) {
            $query->where('customer', $customerName);
        }
        $rows = $query->orderBy('seq')
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
        $routingSteps = $this->countRoutingSteps($master->no_srp);
        $total = $routingSteps > 0 ? $routingSteps : $total;
        $finished = collect($processes)->where('status', 'finished')->count();
        $hasStarted = collect($processes)->contains(fn ($p) => $p['status'] === 'in_progress' || $p['status'] === 'hold' || $p['finish_time']);

        $status = $total > 0 && $finished >= $total
            ? 'selesai'
            : ($hasStarted ? 'diproses' : 'belum');

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
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_color' => $this->statusColor($status),
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

    private function getRoutingStepCounts(array $noSrp): array
    {
        if (empty($noSrp)) {
            return [];
        }

        $srps = DB::table('tblsrp')
            ->whereIn('no_srp', $noSrp)
            ->get()
            ->keyBy('no_srp');

        $routingIds = $srps->map(function ($srp) {
            return $srp->id_routing ?? null;
        })->filter()->unique()->toArray();

        if (empty($routingIds)) {
            return [];
        }

        $routings = DB::table('tblrouting')
            ->whereIn('id_routing', $routingIds)
            ->get()
            ->keyBy('id_routing');

        $counts = [];
        foreach ($srps as $srp) {
            $routing = $routings->get($srp->id_routing);
            if (! $routing) {
                $counts[$srp->no_srp] = 0;
                continue;
            }

            $count = 0;
            for ($i = 1; $i <= 26; $i++) {
                $col = 'Seq_' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $value = $routing->{$col} ?? null;
                if (! $value || $value === '') {
                    break;
                }
                $count++;
            }
            $counts[$srp->no_srp] = $count;
        }

        return $counts;
    }

    private function countRoutingSteps(?string $noSrp): int
    {
        $counts = $this->getRoutingStepCounts([$noSrp]);
        return $counts[$noSrp] ?? 0;
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