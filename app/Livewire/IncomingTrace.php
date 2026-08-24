<?php

namespace App\Livewire;

use App\Services\IncomingTraceReport;
use Livewire\Component;
use Livewire\WithPagination;

class IncomingTrace extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterBy = 'no_lot';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 20;

    public function mount(): void
    {
        $today = now('Asia/Jakarta')->format('Y-m-d');
        $this->dateFrom = $today;
        $this->dateTo = $today;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBy(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function applyFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $today = now('Asia/Jakarta')->format('Y-m-d');
        $this->search = '';
        $this->filterBy = 'no_lot';
        $this->dateFrom = $today;
        $this->dateTo = $today;
        $this->resetPage();
    }

    public function render(IncomingTraceReport $report)
    {
        $rows = $report->query(
            $this->search,
            $this->filterBy,
            $this->dateFrom,
            $this->dateTo,
        )->paginate($this->perPage)->through(fn ($row) => (array) $row);

        return view('livewire.incoming-trace', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
            'filters' => IncomingTraceReport::FILTERS,
            'lastUpdated' => now('Asia/Jakarta'),
        ])->layout('layouts.app');
    }
}
