<?php

namespace App\Livewire;

use App\Services\MenuAuthorizationService;
use Livewire\Component;

class HideMenu extends Component
{
    public array $users = [];
    public string $userSearch = '';
    public array $groups = [];
    public array $selectedKeys = [];
    public ?int $selectedUserId = null;
    public string $selectedUsername = '';
    public bool $tableReady = false;

    public function mount(MenuAuthorizationService $service): void
    {
        $this->users = $service->users();
        $firstActive = collect($this->users)->first(fn (array $user): bool => (int) ($user['is_active'] ?? 0) === 1);
        if ($firstActive) {
            $this->selectUser((int) $firstActive['id_user'], $service);
        }
    }

    public function selectUser(int $idUser, MenuAuthorizationService $service): void
    {
        try {
            $matrix = $service->visibilityMatrixForUser($idUser);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->selectedUserId = $idUser;
        $this->selectedUsername = (string) ($matrix['user']['username'] ?? '');
        $this->groups = (array) ($matrix['groups'] ?? []);
        $this->tableReady = (bool) ($matrix['table_ready'] ?? false);
        $this->selectedKeys = [];

        foreach ($this->groups as $group) {
            if (! empty($group['parent_visible'])) {
                $this->selectedKeys[] = (string) $group['parent_key'];
            }
            foreach ((array) ($group['items'] ?? []) as $item) {
                if (! empty($item['visible'])) {
                    $this->selectedKeys[] = (string) $item['key'];
                }
            }
        }

        $this->resetValidation();
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

    public function selectAll(): void
    {
        $this->selectedKeys = $this->allKeys();
    }

    public function clearAll(): void
    {
        $this->selectedKeys = [];
    }

    public function save(MenuAuthorizationService $service): void
    {
        if (! $this->selectedUserId) {
            $this->addError('form', 'Pilih user terlebih dahulu.');
            return;
        }

        try {
            $service->saveUserVisibility($this->selectedUserId, $this->selectedKeys);
            $this->selectUser($this->selectedUserId, $service);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        session()->flash('success', 'Pengaturan Hide Menu untuk '.$this->selectedUsername.' berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.hide-menu')->layout('layouts.app');
    }

    private function allKeys(): array
    {
        $keys = [];
        foreach ($this->groups as $group) {
            if (! empty($group['parent_key'])) {
                $keys[] = (string) $group['parent_key'];
            }
            foreach ((array) ($group['items'] ?? []) as $item) {
                if (! empty($item['key'])) {
                    $keys[] = (string) $item['key'];
                }
            }
        }

        return array_values(array_unique($keys));
    }
}
