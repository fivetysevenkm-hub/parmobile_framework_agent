<?php

namespace App\Livewire;

use App\Services\UserControlService;
use Livewire\Component;

class UserControl extends Component
{
    public ?int $selectedUserId = null;
    public string $nik = '';
    public string $username = '';
    public string $password = '';
    public string $idDept = '';
    public string $idRole = '';
    public bool $isActive = true;
    public array $departments = [];
    public array $roles = [];
    public array $users = [];
    public int $currentPage = 1;
    public int $totalPages = 1;
    public int $totalUsers = 0;
    public string $userSearch = '';

    public function mount(UserControlService $service): void
    {
        $this->departments = $service->departments();
        $this->roles = $service->roles();
        $this->loadUsers($service);
    }

    public function updatedUserSearch(UserControlService $service): void
    {
        $this->currentPage = 1;
        $this->loadUsers($service);
    }

    public function nextPage(UserControlService $service): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->loadUsers($service);
        }
    }

    public function previousPage(UserControlService $service): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadUsers($service);
        }
    }

    public function selectUser(int $id, UserControlService $service): void
    {
        $user = $service->user($id);
        if (! $user) {
            $this->addError('form', 'User tidak ditemukan.');
            return;
        }

        $this->selectedUserId = (int) $user['id_user'];
        $this->nik = (string) $user['id_user'];
        $this->username = (string) $user['username'];
        $this->password = '';
        $this->idDept = (string) ($user['id_dept'] ?? '');
        $this->idRole = (string) ($user['id_role'] ?? '');
        $this->isActive = (int) ($user['is_active'] ?? 0) === 1;
        $this->resetValidation();
    }

    public function saveUser(UserControlService $service): void
    {
        $this->validate([
            'nik' => ['required', 'regex:/^[0-9]+$/', 'max:10'],
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:15'],
            'idDept' => ['required', 'string', 'max:10'],
            'idRole' => ['required', 'integer', 'min:1'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.regex' => 'NIK hanya boleh berisi angka.',
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password maksimal 15 karakter.',
            'idDept.required' => 'Departemen wajib dipilih.',
            'idRole.required' => 'Role wajib dipilih.',
        ]);

        try {
            $idUser = (int) $this->nik;
            $idRole = (int) $this->idRole;
            if ($this->selectedUserId) {
                $service->update($this->selectedUserId, $this->username, $this->password, $this->idDept, $idRole, $this->isActive);
                $message = 'User berhasil diupdate.';
            } else {
                $service->create($idUser, $this->username, $this->password, $this->idDept, $idRole, $this->isActive);
                $message = 'User berhasil disimpan.';
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadUsers($service);
        $this->clearForm(false);
        session()->flash('success', $message);
    }

    public function deleteUser(UserControlService $service): void
    {
        if (! $this->selectedUserId) {
            $this->addError('form', 'Pilih user yang akan dihapus.');
            return;
        }

        try {
            $service->delete($this->selectedUserId);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->currentPage = min($this->currentPage, $this->totalPages);
        $this->loadUsers($service);
        $this->clearForm(false);
        session()->flash('success', 'User berhasil dihapus.');
    }

    public function clearForm(bool $resetMessages = true): void
    {
        $this->selectedUserId = null;
        $this->nik = '';
        $this->username = '';
        $this->password = '';
        $this->idDept = '';
        $this->idRole = '';
        $this->isActive = true;
        if ($resetMessages) {
            $this->resetValidation();
            session()->forget('success');
        }
    }

    public function render()
    {
        return view('livewire.user-control')->layout('layouts.app');
    }

    private function loadUsers(UserControlService $service): void
    {
        $result = $service->users($this->currentPage, $this->userSearch);
        $this->users = $result['rows'];
        $this->totalUsers = $result['total'];
        $this->totalPages = $result['totalPages'];
        $this->currentPage = $result['page'];
    }
}
