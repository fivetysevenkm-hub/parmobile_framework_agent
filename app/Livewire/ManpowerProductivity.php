<?php

namespace App\Livewire;

use App\Services\ManpowerProductivityReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class ManpowerProductivity extends Component
{
    use WithPagination;

    public string $operator = '';
    public string $process = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public bool $loaded = false;
    public int $perPage = 20;

    public function loadData(): void
    {
        $this->resetErrorBag();

        if (($this->dateFrom !== '' && $this->dateTo === '') || ($this->dateFrom === '' && $this->dateTo !== '')) {
            $this->addError('dateFrom', 'Tanggal awal dan akhir harus dipilih bersama.');
            return;
        }

        if ($this->operator === '' && $this->process === '' && $this->dateFrom === '' && $this->dateTo === '') {
            $this->addError('filter', 'Pilih minimal satu filter sebelum menampilkan data.');
            $this->loaded = false;
            return;
        }

        $this->loaded = true;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->operator = '';
        $this->process = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->loaded = false;
        $this->resetErrorBag();
        $this->resetPage();
    }

    public function render(ManpowerProductivityReport $report)
    {
        $rows = new LengthAwarePaginator([], 0, $this->perPage, LengthAwarePaginator::resolveCurrentPage());
        $summary = ['total_records' => 0, 'total_lot' => 0, 'avg_eff' => 0, 'menit_efektif' => 0, 'yield_act' => 0];
        $chartRows = collect();

        if ($this->loaded) {
            $stats = $report->summaryQuery($this->operator, $this->process, $this->dateFrom, $this->dateTo);
            $summary = $report->summary($stats);
            $chartRows = $report->chartQuery($this->operator, $this->process, $this->dateFrom, $this->dateTo);
            $rows = $report->rowsQuery($this->operator, $this->process, $this->dateFrom, $this->dateTo)
                ->paginate($this->perPage)
                ->through(fn ($row) => (array) $row);
        }

        return view('livewire.productivity-process', [
            'rows' => $rows,
            'summary' => $summary,
            'chartRows' => $chartRows,
            'operatorOptions' => $report->operatorOptions(),
            'processOptions' => $report->processOptions(),
        ])->layout('layouts.app');
    }
}
