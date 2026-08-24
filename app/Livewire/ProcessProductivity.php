<?php

namespace App\Livewire;

use App\Services\ProcessProductivityReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class ProcessProductivity extends Component
{
    use WithPagination;

    public string $process = '';
    public string $machine = '';
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
        if ($this->process === '') {
            $this->addError('process', 'Pilih proses terlebih dahulu sebelum menampilkan data.');
            $this->loaded = false;
            return;
        }
        $this->loaded = true;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->process=''; $this->machine=''; $this->dateFrom=''; $this->dateTo=''; $this->loaded=false;
        $this->resetErrorBag(); $this->resetPage();
    }

    public function updatedProcess(ProcessProductivityReport $report): void
    {
        if ($this->machine !== '' && ! in_array($this->machine, $report->machineOptions($this->process), true)) $this->machine='';
        $this->resetPage();
    }

    public function updatedMachine(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function render(ProcessProductivityReport $report)
    {
        $rows = new LengthAwarePaginator([],0,$this->perPage,LengthAwarePaginator::resolveCurrentPage());
        $summary=['total_qty'=>0,'avg_eff'=>0,'total_lot'=>0,'total_delay'=>0]; $chartRows=collect();
        if ($this->loaded && $this->process !== '') {
            $all=$report->rowsQuery($this->process,$this->machine,$this->dateFrom,$this->dateTo)->get();
            $summary=$report->summary($all); $chartRows=$all->filter(fn($row)=>$report->isEligible($row))->take(30);
            $rows=$report->rowsQuery($this->process,$this->machine,$this->dateFrom,$this->dateTo)->paginate($this->perPage)->through(fn($row)=>(array)$row);
        }
        return view('livewire.process-productivity', compact('rows', 'summary', 'chartRows'))->with(['processOptions' => $report->processOptions(), 'machineOptions' => $report->machineOptions($this->process)])->layout('layouts.app');
    }
}
