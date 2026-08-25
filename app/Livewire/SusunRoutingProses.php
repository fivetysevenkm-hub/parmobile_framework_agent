<?php

namespace App\Livewire;

use App\Services\RoutingService;
use Livewire\Component;

class SusunRoutingProses extends Component
{
    public string $search = '';
    public string $selectedRoutingId = '';
    public string $routingName = '';
    public bool $isEditMode = false;
    public array $routings = [];
    public array $sequences = [];
    public array $sequenceSlots = [];

    public function mount(RoutingService $service): void
    {
        $this->routings = $service->routings();
        $this->sequences = $service->sequences();
        $this->resetSlots();
    }

    public function updatedSelectedRoutingId(RoutingService $service): void
    {
        $this->resetErrorBag();
        $id = trim($this->selectedRoutingId);
        if ($id === '') {
            $this->routingName = '';
            $this->isEditMode = false;
            $this->resetSlots();
            return;
        }

        $detail = $service->detail($id);
        if (! $detail) {
            $this->routingName = '';
            $this->isEditMode = false;
            $this->resetSlots();
            return;
        }

        $this->routingName = (string) ($detail['nm_routing'] ?? '');
        $this->sequenceSlots = $detail['slots'] ?? $this->emptySlots();
        $this->isEditMode = true;
    }

    public function fillAll(): void
    {
        foreach ($this->sequenceSlots as $index => &$slot) {
            $slot = $this->sequences[$index - 1]['id_seq'] ?? null;
        }
        unset($slot);
    }

    public function clearSlots(): void
    {
        $this->resetSlots();
    }

    public function save(RoutingService $service): void
    {
        $this->validate([
            'selectedRoutingId' => ['required', 'string', 'max:4'],
            'routingName' => ['required', 'string', 'max:100'],
            'sequenceSlots' => ['required', 'array', 'size:26'],
            'sequenceSlots.*' => ['nullable', 'string', 'max:3'],
        ], [
            'selectedRoutingId.required' => 'ID Routing wajib diisi.',
            'routingName.required' => 'Nama Routing wajib diisi.',
            'sequenceSlots.size' => 'Routing harus memiliki 26 slot sequence.',
        ]);

        $wasEdit = $this->isEditMode;
        try {
            $service->save($this->selectedRoutingId, $this->routingName, $this->sequenceSlots, $wasEdit);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('selectedRoutingId', $exception->getMessage());
            return;
        }

        $this->isEditMode = true;
        $this->routings = $service->routings();
        session()->flash('success', 'Routing berhasil '.($wasEdit ? 'diperbarui' : 'disimpan').'.');
    }

    public function resetForm(): void
    {
        $this->selectedRoutingId = '';
        $this->routingName = '';
        $this->isEditMode = false;
        $this->resetSlots();
        $this->resetErrorBag();
    }

    public function render()
    {
        $term = mb_strtolower(trim($this->search));
        $filteredRoutings = collect($this->routings)->filter(function (array $row) use ($term): bool {
            return $term === ''
                || str_contains(mb_strtolower((string) ($row['id_routing'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['nm_routing'] ?? '')), $term);
        })->values();

        return view('livewire.susun-routing-proses', [
            'filteredRoutings' => $filteredRoutings,
        ])->layout('layouts.app');
    }

    private function emptySlots(): array
    {
        return array_fill(1, 26, null);
    }

    private function resetSlots(): void
    {
        $this->sequenceSlots = $this->emptySlots();
    }
}
