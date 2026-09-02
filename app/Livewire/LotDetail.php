<?php

namespace App\Livewire;

use App\Services\CustomerLotTrackingService;
use Livewire\Component;

class LotDetail extends Component
{
    public string $noLot = '';

    public array $detail = [];

    public bool $loading = true;

    public function mount(string $noLot): void
    {
        $this->noLot = $noLot;
        $this->loadDetail();
    }

    public function loadDetail(): void
    {
        $this->loading = true;
        $service = app(CustomerLotTrackingService::class);
        $this->detail = $service->getLotDetail($this->noLot);
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.lot-detail');
    }
}
