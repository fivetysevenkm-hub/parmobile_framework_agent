<?php

namespace App\Livewire;

use App\Services\CustomerLotTrackingService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CustomerDashboard extends Component
{
    public int $totalLots = 0;

    public int $activeLots = 0;

    public int $completedLots = 0;

    public int $holdLots = 0;

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $user = auth()->guard('customer')->user();
        if (! $user) {
            return;
        }

        $service = app(CustomerLotTrackingService::class);
        $customerName = $service->getCustomerName($user->id);

        if (! $customerName) {
            return;
        }

        $stats = DB::table('tbl_seq_all')
            ->where('customer', $customerName)
            ->select(
                DB::raw('COUNT(DISTINCT no_lot) as total'),
                DB::raw("COUNT(DISTINCT CASE WHEN MAX(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) = 1 THEN no_lot END) as completed"),
                DB::raw("COUNT(DISTINCT CASE WHEN MAX(CASE WHEN hold = 'yes' THEN 1 ELSE 0 END) = 1 AND MAX(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) = 0 THEN no_lot END) as hold"),
                DB::raw("COUNT(DISTINCT CASE WHEN MAX(CASE WHEN start_time IS NOT NULL AND start_time != '' THEN 1 ELSE 0 END) = 1 AND MAX(CASE WHEN finish = 'yes' THEN 1 ELSE 0 END) = 0 AND MAX(CASE WHEN hold = 'yes' THEN 1 ELSE 0 END) = 0 THEN no_lot END) as active")
            )
            ->groupBy(DB::raw('1'))
            ->first();

        if ($stats) {
            $this->totalLots = $stats->total;
            $this->completedLots = $stats->completed;
            $this->holdLots = $stats->hold;
            $this->activeLots = $stats->active;
        }
    }

    public function render()
    {
        return view('livewire.customer-dashboard')->layout('layouts.customer-portal');
    }
}
