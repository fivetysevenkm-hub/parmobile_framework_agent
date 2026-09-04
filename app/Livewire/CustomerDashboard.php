<?php

namespace App\Livewire;

use App\Services\CustomerLotTrackingService;
use Livewire\Component;

class CustomerDashboard extends Component
{
    public int $totalLots = 0;

    public int $activeLots = 0;

    public int $completedLots = 0;

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

        $summary = $service->getStatusSummary($customerName);

        $this->totalLots = $summary['total'];
        $this->completedLots = $summary['selesai'];
        $this->activeLots = $summary['diproses'];
    }

    public function render()
    {
        return view('livewire.customer-dashboard')->layout('layouts.customer-portal');
    }
}
