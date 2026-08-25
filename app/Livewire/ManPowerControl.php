<?php

namespace App\Livewire;

use App\Services\ManPowerControlService;
use Livewire\Component;

class ManPowerControl extends Component
{
    public string $search = '';
    public string $selectedNik = '';
    public string $selectedUsername = '';
    public string $selectedDept = '';
    public string $selectedDeptId = '';
    public string $jabatan = '';
    public string $ojt = '';
    public bool $isEditMode = false;
    public array $users = [];
    public array $departments = [];
    public array $positions = [];
    public array $sequences = [];
    public array $sequenceSlots = [];

    public function mount(ManPowerControlService $service): void
    {
        $this->users = $service->users();
        $this->departments = $service->departments();
        $this->positions = $service->positions();
        $this->sequences = $service->sequences();
        $this->resetSlots();
    }

    public function updatedSelectedNik(ManPowerControlService $service): void
    {
        if ($this->selectedNik === '') {
            $this->resetEmployeeFields();
            return;
        }

        $user = collect($this->users)->first(fn (array $row): bool => (string) $row['nik'] === (string) $this->selectedNik);
        $detail = $service->detail($this->selectedNik);
        if (! $user || ! $detail) {
            $this->addError('selectedNik', 'User aktif tidak ditemukan.');
            return;
        }

        $this->selectedUsername = (string) ($detail['username'] ?? $user['username'] ?? '');
        $this->selectedDeptId = (string) ($detail['id_dept'] ?? $user['id_dept'] ?? '');
        $this->selectedDept = (string) ($detail['dept_name'] ?? $user['dept_name'] ?? '-');
        $this->jabatan = (string) ($detail['jabatan'] ?? '');
        $this->ojt = (string) ($detail['ojt_a'] ?? '');
        $this->isEditMode = (bool) ($detail['exists'] ?? false);
        $this->sequenceSlots = $detail['slots'] ?? $this->emptySlots();
        $this->resetErrorBag();
    }

    public function fillAll(): void
    {
        foreach ($this->sequenceSlots as $index => &$slot) {
            $sequence = $this->sequences[$index - 1] ?? null;
            $slot['id_seq'] = $sequence['id_seq'] ?? null;
        }
        unset($slot);
    }

    public function clearMappings(): void
    {
        foreach ($this->sequenceSlots as &$slot) {
            $slot['id_seq'] = null;
            $slot['status_ojt'] = null;
        }
        unset($slot);
        $this->ojt = '';
    }

    public function save(ManPowerControlService $service): void
    {
        $this->validate([
            'selectedNik' => ['required', 'string', 'max:12'],
            'jabatan' => ['required', 'string', 'max:30'],
            'ojt' => ['required', 'in:yes,no'],
            'sequenceSlots' => ['required', 'array', 'size:39'],
            'sequenceSlots.*.id_seq' => ['nullable', 'string', 'max:3'],
            'sequenceSlots.*.status_ojt' => ['nullable', 'in:yes,no'],
        ], [
            'selectedNik.required' => 'Pilih karyawan terlebih dahulu.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'ojt.required' => 'Status OJT wajib dipilih.',
            'sequenceSlots.size' => 'Mapping sequence harus terdiri dari 39 slot.',
        ]);

        try {
            $service->save($this->selectedNik, $this->jabatan, $this->ojt, $this->sequenceSlots);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('selectedNik', $exception->getMessage());
            return;
        }

        $this->isEditMode = true;
        session()->flash('success', 'Mapping Man Power dan status OJT berhasil disimpan.');
    }

    public function resetForm(): void
    {
        $this->selectedNik = '';
        $this->resetEmployeeFields();
        $this->resetErrorBag();
    }

    public function render()
    {
        $term = mb_strtolower(trim($this->search));
        $filteredUsers = collect($this->users)->filter(function (array $row) use ($term): bool {
            return $term === ''
                || str_contains(mb_strtolower((string) ($row['nik'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['username'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['dept_name'] ?? '')), $term);
        })->values();

        return view('livewire.man-power-control', [
            'filteredUsers' => $filteredUsers,
        ])->layout('layouts.app');
    }

    private function emptySlots(): array
    {
        $sequenceSlots = [];
        for ($index = 1; $index <= 39; $index++) {
            $sequenceSlots[$index] = ['id_seq' => null, 'status_ojt' => null];
        }
        return $sequenceSlots;
    }

    private function resetSlots(): void
    {
        $this->sequenceSlots = $this->emptySlots();
    }

    private function resetEmployeeFields(): void
    {
        $this->selectedUsername = '';
        $this->selectedDept = '';
        $this->selectedDeptId = '';
        $this->jabatan = '';
        $this->ojt = '';
        $this->isEditMode = false;
        $this->resetSlots();
    }
}
