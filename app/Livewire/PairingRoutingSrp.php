<?php

namespace App\Livewire;

use App\Services\PairingRoutingSrpService;
use Livewire\Component;
use Livewire\WithPagination;

class PairingRoutingSrp extends Component
{
    use WithPagination;

    public string $filterBy = 'no_srp';
    public string $keyword = '';
    public int $perPage = 25;
    public string $selectedNoSrp = '';
    public array $selectedRow = [];
    public string $routingId = '';
    public string $routingName = '-';
    public array $routings = [];

    public function mount(PairingRoutingSrpService $service): void
    {
        $this->routings = $service->routings();
    }

    public function updatedFilterBy(): void
    {
        $this->resetPage();
    }

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function selectSrp(string $noSrp, PairingRoutingSrpService $service): void
    {
        $row = $service->find($noSrp);
        if (! $row) {
            $this->clearSelection();
            $this->addError('selectedNoSrp', 'Data SRP tidak ditemukan.');
            return;
        }

        $this->resetErrorBag();
        $this->selectedNoSrp = (string) ($row['no_srp'] ?? '');
        $this->selectedRow = $row;
        $this->routingId = (string) ($row['id_routing'] ?? '');
        $this->setRoutingName();
    }

    public function updatedRoutingId(): void
    {
        $this->setRoutingName();
    }

    public function updateRouting(PairingRoutingSrpService $service): void
    {
        $this->validate([
            'selectedNoSrp' => ['required', 'string', 'max:10'],
            'routingId' => ['required', 'string', 'max:4'],
        ], [
            'selectedNoSrp.required' => 'Pilih SRP terlebih dahulu.',
            'routingId.required' => 'Routing belum dipilih.',
        ]);

        try {
            $service->updateRouting($this->selectedNoSrp, $this->routingId);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('routingId', $exception->getMessage());
            return;
        }

        $this->selectedRow = $service->find($this->selectedNoSrp) ?? $this->selectedRow;
        $this->routingId = (string) ($this->selectedRow['id_routing'] ?? $this->routingId);
        $this->setRoutingName();
        session()->flash('success', 'Routing untuk SRP '.$this->selectedNoSrp.' berhasil diperbarui.');
    }

    public function clearSelection(): void
    {
        $this->selectedNoSrp = '';
        $this->selectedRow = [];
        $this->routingId = '';
        $this->routingName = '-';
        $this->resetErrorBag();
    }

    public function resetFilters(): void
    {
        $this->filterBy = 'no_srp';
        $this->keyword = '';
        $this->resetPage();
    }

    public function render(PairingRoutingSrpService $service)
    {
        $rows = $service->query($this->filterBy, $this->keyword)
            ->paginate($this->perPage)
            ->through(fn ($row) => (array) $row);

        return view('livewire.pairing-routing-srp', [
            'rows' => $rows,
            'filters' => PairingRoutingSrpService::FILTERS,
        ])->layout('layouts.app');
    }

    private function setRoutingName(): void
    {
        $routing = collect($this->routings)->first(fn (array $row) => (string) ($row['id_routing'] ?? '') === trim($this->routingId));
        $this->routingName = $routing ? (string) ($routing['nm_routing'] ?? '-') : '-';
    }
}
