<?php

namespace App\Livewire;

use App\Services\CycleTimeService;
use Livewire\Component;
use Livewire\WithPagination;

class AdjustCycleTime extends Component
{
    use WithPagination;

    public string $filterBy = 'no_srp';
    public string $keyword = '';
    public int $perPage = 25;
    public string $selectedNoSrp = '';
    public array $selectedSrp = [];
    public array $cycleRows = [];
    public array $cycleTimes = [];

    public function updatedFilterBy(): void
    {
        $this->resetPage();
    }

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function selectSrp(string $noSrp, CycleTimeService $service): void
    {
        $srp = $service->findSrp($noSrp);
        if (! $srp) {
            $this->clearSelection();
            $this->addError('selectedNoSrp', 'Data SRP tidak ditemukan.');
            return;
        }

        $this->resetErrorBag();
        $this->selectedNoSrp = (string) ($srp['no_srp'] ?? '');
        $this->selectedSrp = $srp;
        $this->loadCycleRows($service);
    }

    public function save(CycleTimeService $service): void
    {
        $this->validate([
            'selectedNoSrp' => ['required', 'string', 'max:10'],
            'cycleTimes' => ['required', 'array', 'min:1'],
            'cycleTimes.*' => ['required', 'integer', 'min:0'],
        ], [
            'selectedNoSrp.required' => 'Pilih SRP terlebih dahulu.',
            'cycleTimes.min' => 'Routing SRP belum memiliki sequence proses.',
            'cycleTimes.*.required' => 'Semua Cycle Time harus diisi.',
            'cycleTimes.*.integer' => 'Cycle Time harus berupa bilangan bulat.',
            'cycleTimes.*.min' => 'Cycle Time tidak boleh bernilai negatif.',
        ]);

        try {
            $service->saveCycleTimes($this->selectedNoSrp, $this->cycleTimes);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('cycleTimes', $exception->getMessage());
            return;
        }

        $this->loadCycleRows($service);
        session()->flash('success', 'Cycle Time untuk SRP '.$this->selectedNoSrp.' berhasil disimpan.');
    }

    public function clearSelection(): void
    {
        $this->selectedNoSrp = '';
        $this->selectedSrp = [];
        $this->cycleRows = [];
        $this->cycleTimes = [];
        $this->resetErrorBag();
    }

    public function resetFilters(): void
    {
        $this->filterBy = 'no_srp';
        $this->keyword = '';
        $this->resetPage();
    }

    public function render(CycleTimeService $service)
    {
        $rows = $service->srpQuery($this->filterBy, $this->keyword)
            ->paginate($this->perPage)
            ->through(fn ($row) => (array) $row);
        $completedSrpNos = array_fill_keys(
            $service->completedSrpNos($rows->getCollection()->pluck('no_srp')->all()),
            true,
        );
        $summary = $service->summary();

        return view('livewire.adjust-cycle-time', [
            'rows' => $rows,
            'filters' => CycleTimeService::FILTERS,
            'completedSrpNos' => $completedSrpNos,
            'summary' => $summary,
        ])->layout('layouts.app');
    }

    private function loadCycleRows(CycleTimeService $service): void
    {
        $this->cycleRows = $service->cycleRows($this->selectedNoSrp);
        $this->cycleTimes = [];
        foreach ($this->cycleRows as $row) {
            $idSequence = (string) ($row['id_sequence'] ?? '');
            $this->cycleTimes[$idSequence] = $row['cycle_time'] === null ? '' : (string) $row['cycle_time'];
        }
    }
}
