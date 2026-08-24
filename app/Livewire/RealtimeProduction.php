<?php

namespace App\Livewire;

use App\Services\RealtimeProductionReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class RealtimeProduction extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterBy = 'no_lot';

    public string $activeTab = 'list';

    public string $selectedLot = '';

    public array $selectedMaster = [];

    public array $processRows = [];

    public int $perPage = 20;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBy(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->activeTab = 'list';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterBy = 'no_lot';
        $this->activeTab = 'list';
        $this->resetPage();
    }

    public function selectLot(string $noLot): void
    {
        $rows = app(RealtimeProductionReport::class)->detailQuery($noLot)->get();

        if ($rows->isEmpty()) {
            $this->addError('selectedLot', 'Data posisi lot tidak ditemukan.');
            return;
        }

        $this->selectedLot = $noLot;
        $this->processRows = $rows->map(fn ($row) => (array) $row)->all();
        $this->selectedMaster = collect($this->processRows)->first(fn (array $row) => (int) ($row['seq'] ?? 0) === 1)
            ?? ($this->processRows[0] ?? []);
        $this->activeTab = 'position';
        $this->resetErrorBag();
    }

    public function showList(): void
    {
        $this->activeTab = 'list';
    }

    public function refreshFlow(): void
    {
        if ($this->selectedLot === '') {
            return;
        }

        $rows = app(RealtimeProductionReport::class)->detailQuery($this->selectedLot)->get();
        $this->processRows = $rows->map(fn ($row) => (array) $row)->all();
        $this->selectedMaster = collect($this->processRows)->first(fn (array $row) => (int) ($row['seq'] ?? 0) === 1)
            ?? ($this->processRows[0] ?? $this->selectedMaster);
    }

    public function render(RealtimeProductionReport $report)
    {
        $rows = $report->listQuery($this->search, $this->filterBy)
            ->paginate($this->perPage)
            ->through(fn ($row) => (array) $row);

        return view('livewire.realtime-production', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
            'filters' => RealtimeProductionReport::FILTERS,
            'lastUpdated' => now('Asia/Jakarta'),
        ])->layout('layouts.app');
    }
}
