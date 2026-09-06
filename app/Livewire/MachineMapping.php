<?php

namespace App\Livewire;

use App\Services\MachineMappingService;
use Livewire\Component;

class MachineMapping extends Component
{
    public array $sequences = [];

    public array $machineOptions = [];

    public array $mappedCounts = [];

    public string $selectedSequence = '';

    public array $selectedMachines = [];

    public string $machineSearch = '';

    public string $sequenceSearch = '';

    public function mount(MachineMappingService $service): void
    {
        $this->sequences = $service->sequences();
        $this->machineOptions = $service->machines();
        $this->mappedCounts = $service->mappedCounts();
        if ($this->sequences !== []) {
            $this->selectSequence($this->sequences[0]['id_seq']);
        }
    }

    public function selectSequence(string $idSeq): void
    {
        $this->selectedSequence = $idSeq;
        $this->selectedMachines = app(MachineMappingService::class)->mappedMachines($idSeq);
        $this->machineSearch = '';
    }

    public function toggleMachine(string $noMesin): void
    {
        if (in_array($noMesin, $this->selectedMachines, true)) {
            $this->selectedMachines = array_values(array_diff($this->selectedMachines, [$noMesin]));
        } else {
            $this->selectedMachines[] = $noMesin;
        }
    }

    public function selectAllVisible(): void
    {
        $visible = collect($this->filteredMachineOptions())
            ->pluck('no_mesin')
            ->all();

        $this->selectedMachines = array_values(array_unique(array_merge($this->selectedMachines, $visible)));
    }

    public function clearSelection(): void
    {
        $this->selectedMachines = [];
    }

    public function save(MachineMappingService $service): void
    {
        if ($this->selectedSequence === '') {
            session()->flash('error', 'Pilih sequence terlebih dahulu.');

            return;
        }

        try {
            $service->save($this->selectedSequence, $this->selectedMachines);
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        $this->mappedCounts = $service->mappedCounts();
        $this->selectedMachines = $service->mappedMachines($this->selectedSequence);
        session()->flash('success', 'Mapping mesin untuk sequence '.$this->selectedSequence.' berhasil disimpan.');
    }

    public function removeMapping(MachineMappingService $service): void
    {
        if ($this->selectedSequence === '') {
            return;
        }

        try {
            $service->save($this->selectedSequence, []);
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        $this->mappedCounts = $service->mappedCounts();
        $this->selectedMachines = [];
        session()->flash('success', 'Mapping untuk sequence '.$this->selectedSequence.' dihapus.');
    }

    public function filteredSequences(): array
    {
        $term = mb_strtolower(trim($this->sequenceSearch));

        return collect($this->sequences)
            ->filter(fn (array $sequence): bool => $term === ''
                || str_contains(mb_strtolower((string) ($sequence['id_seq'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($sequence['nm_sequence'] ?? '')), $term))
            ->values()
            ->all();
    }

    public function selectedSequenceName(): string
    {
        return collect($this->sequences)
            ->first(fn (array $sequence): bool => (string) ($sequence['id_seq'] ?? '') === $this->selectedSequence)['nm_sequence'] ?? '';
    }

    public function filteredMachineOptions(): array
    {
        $term = mb_strtolower(trim($this->machineSearch));

        return collect($this->machineOptions)
            ->filter(fn (array $machine): bool => $term === ''
                || str_contains(mb_strtolower((string) ($machine['mesin'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($machine['no_mesin'] ?? '')), $term))
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.machine-mapping', [
            'filteredSequences' => $this->filteredSequences(),
            'selectedSequenceName' => $this->selectedSequenceName(),
            'selectedSequenceCount' => $this->mappedCounts[$this->selectedSequence] ?? 0,
            'filteredMachineOptions' => collect($this->filteredMachineOptions())
                ->map(fn (array $machine): array => $machine + [
                    'checked' => in_array($machine['no_mesin'], $this->selectedMachines, true),
                ])
                ->all(),
        ])->layout('layouts.app');
    }
}
