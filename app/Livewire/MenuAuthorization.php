<?php

namespace App\Livewire;

use App\Services\MenuAuthorizationService;
use Livewire\Component;

class MenuAuthorization extends Component
{
    public array $users = [];
    public string $userSearch = '';
    public array $groups = [];
    public array $selectedKeys = [];
    public ?int $selectedUserId = null;
    public string $selectedUsername = '';
    public bool $isFullAccess = false;

    public function mount(MenuAuthorizationService $service): void
    {
        $this->loadUsers($service);
        if ($this->users !== []) {
            $this->selectUser((int) $this->users[0]['id_user'], $service);
        }
    }

    public function selectUser(int $idUser, MenuAuthorizationService $service): void
    {
        try {
            $matrix = $service->matrixForUser($idUser);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->selectedUserId = $idUser;
        $this->selectedUsername = (string) ($matrix['user']['username'] ?? '');
        $this->groups = (array) ($matrix['groups'] ?? []);
        $this->isFullAccess = (bool) ($matrix['is_full_access'] ?? false);
        $this->selectedKeys = [];
        foreach ($this->groups as $menus) {
            foreach ($menus as $menu) {
                if (! empty($menu['can_access']) && empty($menu['locked'])) {
                    $this->selectedKeys[] = (string) $menu['key'];
                }
            }
        }
        $this->resetValidation();
    }

    public function updatedUserSearch(): void
    {
        // Filtering is computed locally from the already loaded user catalog.
    }

    public function getFilteredUsersProperty(): array
    {
        $search = mb_strtolower(trim($this->userSearch));
        if ($search === '') {
            return $this->users;
        }

        return array_values(array_filter($this->users, function (array $user) use ($search): bool {
            return str_contains(mb_strtolower((string) ($user['username'] ?? '')), $search)
                || str_contains(mb_strtolower((string) ($user['id_user'] ?? '')), $search);
        }));
    }

    public function save(MenuAuthorizationService $service): void
    {
        if (! $this->selectedUserId) {
            $this->addError('form', 'Pilih user terlebih dahulu.');
            return;
        }

        try {
            $service->saveUserAccess($this->selectedUserId, $this->selectedKeys);
            $this->selectUser($this->selectedUserId, $service);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        session()->flash('success', 'Otorisasi menu untuk '.$this->selectedUsername.' berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.menu-authorization')->layout('layouts.app');
    }

    private function loadUsers(MenuAuthorizationService $service): void
    {
        $this->users = $service->users();
    }
}
