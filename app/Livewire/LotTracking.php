<?php

namespace App\Livewire;

use App\Services\CustomerLotTrackingService;
use Livewire\Component;
use Livewire\WithPagination;

class LotTracking extends Component
{
    use WithPagination;

    public string $search = '';

    public string $searchCol = 'no_lot';

    public string $statusFilter = '';

    public bool $showModal = false;

    public array $lotDetail = [];

    public string $selectedLot = '';

    public string $nextProcess = '';

    public array $searchColumns = [
        'no_lot' => 'No. Lot (PAR)',
        'no_lot_cust' => 'No. Lot Customer',
        'no_sj_cust' => 'No. Surat Jalan',
        'no_part' => 'No. Part',
        'nm_part' => 'Nama Part',
    ];

    public function getPeriodLabelProperty(): string
    {
        $from = now()->subDays(30)->format('d M Y');
        $to = now()->format('d M Y');

        return "H-30  ({$from} – {$to})";
    }

    public function getCustomerNameProperty(): string
    {
        $user = auth()->guard('customer')->user();
        if (! $user) {
            return '';
        }

        $service = app(CustomerLotTrackingService::class);

        return $service->getCustomerName($user->id) ?? '';
    }

    public function getLotsProperty()
    {
        $user = auth()->guard('customer')->user();
        if (! $user) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        $service = app(CustomerLotTrackingService::class);
        $customerName = $service->getCustomerName($user->id);

        if (! $customerName) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        return $service->getLotsByCustomer(
            $customerName,
            $this->searchCol,
            $this->search ?: null,
            $this->statusFilter ?: null,
            perPage: 10,
        );
    }

    public function selectLot(string $noLot): void
    {
        $service = app(CustomerLotTrackingService::class);
        $this->lotDetail = $service->getLotDetail($noLot, $this->customerName);
        $this->nextProcess = $service->getNextProcess($noLot) ?? '';
        $this->selectedLot = $noLot;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->lotDetail = [];
        $this->selectedLot = '';
        $this->nextProcess = '';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSearchCol(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.lot-tracking')->layout('layouts.customer-portal');
    }
}
