<?php

namespace App\Livewire;

use App\Services\UserQrCodeService;
use Livewire\Component;

class UserQrCode extends Component
{
    public bool $showUserModal = false;
    public string $keyword = '';
    public int $currentPage = 1;
    public int $totalPages = 1;
    public int $totalUsers = 0;
    public array $users = [];
    public array $selectedUsers = [];

    public function openUserSearch(): void
    {
        $this->showUserModal = true;
        $this->currentPage = 1;
    }

    public function closeUserSearch(): void
    {
        $this->showUserModal = false;
    }

    public function updatedKeyword(UserQrCodeService $service): void
    {
        $this->currentPage = 1;
        $this->loadUsers($service);
    }

    public function nextPage(UserQrCodeService $service): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->loadUsers($service);
        }
    }

    public function previousPage(UserQrCodeService $service): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadUsers($service);
        }
    }

    public function toggleUser(int $id, UserQrCodeService $service): void
    {
        $user = $service->activeUsersByIds([$id])[0] ?? null;
        if (! $user) {
            $this->addError('users', 'User aktif tidak ditemukan.');
            return;
        }

        $existingIndex = collect($this->selectedUsers)->search(fn (array $selected) => (int) $selected['id_user'] === $id);
        if ($existingIndex !== false) {
            unset($this->selectedUsers[$existingIndex]);
            $this->selectedUsers = array_values($this->selectedUsers);
        } else {
            $this->selectedUsers[] = $user;
        }
        $this->resetErrorBag('users');
    }

    public function removeUser(int $id): void
    {
        $this->selectedUsers = array_values(array_filter(
            $this->selectedUsers,
            fn (array $selected) => (int) $selected['id_user'] !== $id,
        ));
    }

    public function clearUsers(): void
    {
        $this->selectedUsers = [];
    }

    public function printQrCodes()
    {
        if ($this->selectedUsers === []) {
            $this->addError('users', 'Pilih minimal satu user aktif terlebih dahulu.');
            return null;
        }

        $ids = collect($this->selectedUsers)->pluck('id_user')->map(fn ($id) => (int) $id)->all();
        return redirect()->route('user-management.qr-code.print', ['ids' => implode(',', $ids)]);
    }

    public function render(UserQrCodeService $service)
    {
        $this->loadUsers($service);
        return view('livewire.user-qr-code')->layout('layouts.app');
    }

    private function loadUsers(UserQrCodeService $service): void
    {
        $result = $service->activeUsers($this->keyword, $this->currentPage);
        $this->users = $result['rows'];
        $this->totalUsers = $result['total'];
        $this->totalPages = $result['totalPages'];
        $this->currentPage = $result['page'];
    }
}
