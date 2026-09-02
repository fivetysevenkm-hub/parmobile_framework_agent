<?php

namespace App\Livewire;

use App\Services\CustomerLotTrackingService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class LotTracking extends Component
{
    use WithPagination;

    public string $search = '';

    public string $searchCol = 'no_lot';

    public string $statusFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

    public int $periodDays = 30;

    public int $selectedLotIndex = -1;

    public array $searchColumns = [
        'no_lot' => 'No. Lot (PAR)',
        'no_lot_cust' => 'No. Lot Customer',
        'no_sj_cust' => 'No. Surat Jalan',
        'no_part' => 'No. Part',
        'nm_part' => 'Nama Part',
    ];

    public function mount(): void
    {
        $this->fromDate = now()->subDays($this->periodDays)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function getPeriodLabelProperty(): string
    {
        $from = $this->fromDate ? Carbon::parse($this->fromDate)->format('d M Y') : '-';
        $to = $this->toDate ? Carbon::parse($this->toDate)->format('d M Y') : '-';

        return "H-{$this->periodDays}  ({$from} – {$to})";
    }

    public function resetPeriod(): void
    {
        $this->fromDate = now()->subDays($this->periodDays)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->resetPage();
        $this->selectedLotIndex = -1;
    }

    public function getCustomerNameProperty(): string
    {
        $user = auth()->guard('customer')->user();

        return $user ? $user->company : '';
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
            $this->fromDate ?: null,
            $this->toDate ?: null,
            perPage: 10,
        );
    }

    public function selectLot(string $noLot): void
    {
        $this->selectedLotIndex = $noLot;
    }

    public function clearSelection(): void
    {
        $this->selectedLotIndex = -1;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedLotIndex = -1;
    }

    public function updatingSearchCol(): void
    {
        $this->resetPage();
        $this->selectedLotIndex = -1;
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->selectedLotIndex = -1;
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
        $this->selectedLotIndex = -1;
    }

    public function updatingToDate(): void
    {
        $this->resetPage();
        $this->selectedLotIndex = -1;
    }

    public function render()
    {
        return view('livewire.lot-tracking')->layout('layouts.customer-portal');
    }
}
