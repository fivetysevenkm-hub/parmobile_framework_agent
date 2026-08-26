<?php

namespace App\Livewire;

use App\Services\MenuAuthorizationService;
use Livewire\Component;

class Home extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $accessibleMenus = [];

    public string $username = 'Pengguna';

    public function mount(MenuAuthorizationService $authorization): void
    {
        $user = auth()->user();
        $this->username = (string) ($user?->username ?: session('username') ?: 'Pengguna');
        $idUser = $user ? (int) $user->getAuthIdentifier() : (int) (session('id_user') ?: 0);
        $accessMap = $authorization->accessMap($idUser, $this->username);
        $visibilityMap = $authorization->visibilityMap($idUser, $this->username);

        $this->accessibleMenus = collect($authorization->registry())
            ->filter(function (array $menu) use ($accessMap, $visibilityMap): bool {
                $key = (string) ($menu['key'] ?? '');
                return $key !== ''
                    && (bool) ($accessMap[$key] ?? false)
                    && (bool) ($visibilityMap[$key] ?? true);
            })
            ->map(function (array $menu): array {
                return [
                    'group' => (string) ($menu['group'] ?? 'LAINNYA'),
                    'name' => (string) ($menu['name'] ?? $menu['key'] ?? 'Menu'),
                    'description' => (string) ($menu['description'] ?? 'Menu aplikasi PAR Mobile.'),
                    'route' => (string) ($menu['route'] ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.home')->layout('layouts.app');
    }
}
