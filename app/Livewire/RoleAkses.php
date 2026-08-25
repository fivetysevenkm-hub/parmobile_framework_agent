<?php

namespace App\Livewire;

use App\Services\RoleAccessService;
use Livewire\Component;

class RoleAkses extends Component
{
    public ?int $selectedRoleId = null;
    public string $roleName = '';
    public string $description = '';
    public array $roles = [];

    public function mount(RoleAccessService $service): void
    {
        $this->loadRoles($service);
    }

    public function selectRole(int $id, RoleAccessService $service): void
    {
        $role = $service->role($id);
        if (! $role) {
            $this->addError('role', 'Role tidak ditemukan.');
            return;
        }

        $this->selectedRoleId = (int) $role['id_role'];
        $this->roleName = (string) $role['role_name'];
        $this->description = (string) ($role['description'] ?? '');
        $this->resetValidation();
    }

    public function saveRole(RoleAccessService $service): void
    {
        $this->validate([
            'roleName' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:100'],
        ], [
            'roleName.required' => 'Nama Role harus diisi.',
            'roleName.max' => 'Nama Role maksimal 50 karakter.',
            'description.max' => 'Keterangan maksimal 100 karakter.',
        ]);

        try {
            if ($this->selectedRoleId) {
                $service->update($this->selectedRoleId, $this->roleName, $this->description);
                $message = 'Role berhasil diupdate.';
            } else {
                $service->create($this->roleName, $this->description);
                $message = 'Role berhasil ditambah.';
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('role', $exception->getMessage());
            return;
        }

        $this->loadRoles($service);
        $this->clearForm(false);
        session()->flash('success', $message);
    }

    public function deleteRole(RoleAccessService $service): void
    {
        if (! $this->selectedRoleId) {
            $this->addError('role', 'Pilih role yang akan dihapus.');
            return;
        }

        try {
            $service->delete($this->selectedRoleId);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('role', $exception->getMessage());
            return;
        }

        $this->loadRoles($service);
        $this->clearForm(false);
        session()->flash('success', 'Role berhasil dihapus.');
    }

    public function clearForm(bool $resetMessages = true): void
    {
        $this->selectedRoleId = null;
        $this->roleName = '';
        $this->description = '';
        if ($resetMessages) {
            $this->resetValidation();
            session()->forget('success');
        }
    }

    public function render()
    {
        return view('livewire.role-akses')->layout('layouts.app');
    }

    private function loadRoles(RoleAccessService $service): void
    {
        $this->roles = $service->roles();
    }
}
