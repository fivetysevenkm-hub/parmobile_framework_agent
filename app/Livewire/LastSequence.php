<?php

namespace App\Livewire;

use App\Services\LastSequenceReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class LastSequence extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterBy = 'no_lot';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $sortColumn = 'updated_at';

    public string $sortDirection = 'desc';

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
        $this->sortColumn = 'updated_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! array_key_exists($column, LastSequenceReport::SORT_COLUMNS)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render(LastSequenceReport $report)
    {
        $rows = $report->query(
            $this->search,
            $this->filterBy,
            $this->dateFrom,
            $this->dateTo,
            $this->sortColumn,
            $this->sortDirection,
        )->paginate($this->perPage)->through(fn ($row) => (array) $row);

        return view('livewire.last-sequence', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
            'filters' => LastSequenceReport::FILTERS,
        ])->layout('layouts.app');
    }
}
