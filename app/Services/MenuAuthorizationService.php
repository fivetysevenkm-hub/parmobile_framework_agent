<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class MenuAuthorizationService
{
    public function registry(): array
    {
        return (array) config('menu_authorization.menus', []);
    }

    public function users(): array
    {
        return DB::table('tbl_user')
            ->select(['id_user', 'username', 'id_role', 'is_active'])
            ->orderBy('username')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    public function user(int $idUser): ?array
    {
        $row = DB::table('tbl_user')
            ->select(['id_user', 'username', 'id_role', 'is_active'])
            ->where('id_user', $idUser)
            ->first();

        return $row ? (array) $row : null;
    }

    public function isFullAccessUser(?string $username): bool
    {
        $username = trim((string) $username);
        foreach ((array) config('menu_authorization.full_access_usernames', []) as $fullAccessUsername) {
            if ($username !== '' && mb_strtolower($username) === mb_strtolower(trim((string) $fullAccessUsername))) {
                return true;
            }
        }
        return false;
    }

    public function defaultAccessKeys(): array
    {
        return (array) config('menu_authorization.default_access_keys', []);
    }

    public function canAccess(?int $idUser, ?string $username, string $menuKey): bool
    {
        if ($menuKey === 'user-management-authorization') {
            return $this->isFullAccessUser($username);
        }
        if ($this->isFullAccessUser($username)) {
            return true;
        }
        if (in_array($menuKey, $this->defaultAccessKeys(), true)) {
            return true;
        }
        if (! $idUser || ! Schema::hasTable('tbl_user_menu_access')) {
            return false;
        }

        return DB::table('tbl_user_menu_access')
            ->where('id_user', $idUser)
            ->where('menu_key', $menuKey)
            ->where('can_access', 1)
            ->exists();
    }

    public function accessMap(?int $idUser, ?string $username): array
    {
        $fullAccess = $this->isFullAccessUser($username);
        $defaultKeys = $this->defaultAccessKeys();
        $stored = [];

        if ($idUser && Schema::hasTable('tbl_user_menu_access')) {
            $stored = DB::table('tbl_user_menu_access')
                ->where('id_user', $idUser)
                ->where('can_access', 1)
                ->pluck('menu_key')
                ->map(fn ($key): string => (string) $key)
                ->all();
        }

        $access = [];
        foreach ($this->registry() as $menu) {
            $key = (string) ($menu['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $access[$key] = $fullAccess || in_array($key, $defaultKeys, true) || in_array($key, $stored, true);
        }
        return $access;
    }

    public function visibilityTableReady(): bool
    {
        return Schema::hasTable('tbl_user_menu_visibility');
    }

    public function groupKey(string $groupName): string
    {
        return 'group:'.Str::slug($groupName);
    }

    public function sidebarGroupNames(): array
    {
        return array_values(array_map(
            fn ($group): string => (string) $group,
            (array) config('menu_authorization.sidebar_group_names', []),
        ));
    }

    public function groupHasVisibleChild(string $groupName, array $visibility): bool
    {
        foreach ($this->registry() as $menu) {
            if ((string) ($menu['group'] ?? 'LAINNYA') !== $groupName) {
                continue;
            }
            $key = (string) ($menu['key'] ?? '');
            if ($key !== '' && (bool) ($visibility[$key] ?? true)) {
                return true;
            }
        }

        return false;
    }

    public function visibilityMap(?int $idUser, ?string $username): array
    {
        $visibility = [];
        $groupKeys = [];
        $sidebarGroups = $this->sidebarGroupNames();
        foreach ($this->registry() as $menu) {
            $key = (string) ($menu['key'] ?? '');
            $group = (string) ($menu['group'] ?? 'LAINNYA');
            if ($key !== '') {
                $visibility[$key] = true;
            }
            if (in_array($group, $sidebarGroups, true)) {
                $groupKeys[$this->groupKey($group)] = true;
            }
        }
        $visibility = array_merge($groupKeys, $visibility);

        if ($idUser && $this->visibilityTableReady()) {
            $hiddenKeys = DB::table('tbl_user_menu_visibility')
                ->where('id_user', $idUser)
                ->where('is_visible', 0)
                ->pluck('menu_key')
                ->map(fn ($key): string => (string) $key)
                ->all();
            foreach ($hiddenKeys as $key) {
                if (array_key_exists($key, $visibility)) {
                    $visibility[$key] = false;
                }
            }
        }

        return $visibility;
    }

    public function visibilityMatrixForUser(int $idUser): array
    {
        $user = $this->user($idUser);
        if (! $user || (int) ($user['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('User aktif tidak ditemukan.');
        }

        $visibility = $this->visibilityMap($idUser, (string) $user['username']);
        $groups = [];
        foreach ($this->registry() as $menu) {
            $key = (string) ($menu['key'] ?? '');
            $groupName = (string) ($menu['group'] ?? 'LAINNYA');
            if ($key === '') {
                continue;
            }
            $groupKey = in_array($groupName, $this->sidebarGroupNames(), true) ? $this->groupKey($groupName) : '';
            $groups[$groupName]['parent_key'] = $groupKey;
            $groups[$groupName]['parent_name'] = $groupName;
            $groups[$groupName]['parent_visible'] = $groupKey === '' ? true : (bool) ($visibility[$groupKey] ?? true);
            $groups[$groupName]['items'][] = [
                'key' => $key,
                'name' => (string) ($menu['name'] ?? $key),
                'description' => (string) ($menu['description'] ?? ''),
                'visible' => (bool) ($visibility[$key] ?? true),
            ];
        }

        return [
            'user' => $user,
            'groups' => $groups,
            'table_ready' => $this->visibilityTableReady(),
        ];
    }

    public function saveUserVisibility(int $idUser, array $selectedKeys): void
    {
        $user = $this->user($idUser);
        if (! $user || (int) ($user['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('User aktif tidak ditemukan.');
        }
        if (! $this->visibilityTableReady()) {
            throw new RuntimeException('Tabel visibilitas belum tersedia. Jalankan migration Hide Menu terlebih dahulu.');
        }

        $validKeys = [];
        $groups = [];
        $sidebarGroups = $this->sidebarGroupNames();
        foreach ($this->registry() as $menu) {
            $key = (string) ($menu['key'] ?? '');
            $group = (string) ($menu['group'] ?? 'LAINNYA');
            if ($key !== '') {
                $validKeys[] = $key;
            }
            if (in_array($group, $sidebarGroups, true)) {
                $groups[$this->groupKey($group)] = true;
            }
        }
        $validKeys = array_values(array_unique(array_merge(array_keys($groups), $validKeys)));
        $selectedKeys = collect($selectedKeys)
            ->map(fn ($key): string => (string) $key)
            ->filter(fn (string $key): bool => in_array($key, $validKeys, true))
            ->unique()
            ->values()
            ->all();
        $hiddenKeys = array_values(array_diff($validKeys, $selectedKeys));

        DB::transaction(function () use ($idUser, $validKeys, $hiddenKeys): void {
            DB::table('tbl_user_menu_visibility')
                ->where('id_user', $idUser)
                ->whereIn('menu_key', $validKeys)
                ->delete();

            if ($hiddenKeys !== []) {
                DB::table('tbl_user_menu_visibility')->insert(array_map(
                    fn (string $menuKey): array => [
                        'id_user' => $idUser,
                        'menu_key' => $menuKey,
                        'is_visible' => 0,
                    ],
                    $hiddenKeys,
                ));
            }
        });
    }

    public function matrixForUser(int $idUser): array
    {
        $user = $this->user($idUser);
        if (! $user || (int) ($user['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('User aktif tidak ditemukan.');
        }

        $access = $this->accessMap($idUser, (string) $user['username']);
        $fullAccess = $this->isFullAccessUser((string) $user['username']);
        $defaultKeys = $this->defaultAccessKeys();
        $groups = [];

        foreach ($this->registry() as $menu) {
            $key = (string) ($menu['key'] ?? '');
            $group = (string) ($menu['group'] ?? 'LAINNYA');
            if ($key === '') {
                continue;
            }
            $groups[$group][] = [
                'key' => $key,
                'name' => (string) ($menu['name'] ?? $key),
                'can_access' => (bool) ($access[$key] ?? false),
                'locked' => $fullAccess || in_array($key, $defaultKeys, true),
                'locked_reason' => $fullAccess ? 'Akses penuh Didik' : (in_array($key, $defaultKeys, true) ? 'Akses dashboard default' : ''),
            ];
        }

        return [
            'user' => $user,
            'groups' => $groups,
            'is_full_access' => $fullAccess,
        ];
    }

    public function saveUserAccess(int $idUser, array $selectedKeys): void
    {
        $user = $this->user($idUser);
        if (! $user || (int) ($user['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('User aktif tidak ditemukan.');
        }
        if ($this->isFullAccessUser((string) $user['username'])) {
            throw new RuntimeException('Akses user Didik bersifat penuh dan tidak perlu diubah.');
        }
        if (! Schema::hasTable('tbl_user_menu_access')) {
            throw new RuntimeException('Tabel otorisasi belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $validKeys = collect($this->registry())
            ->pluck('key')
            ->map(fn ($key): string => (string) $key)
            ->all();
        $defaultKeys = $this->defaultAccessKeys();
        $selectedKeys = collect($selectedKeys)
            ->map(fn ($key): string => (string) $key)
            ->filter(fn (string $key): bool => in_array($key, $validKeys, true) && ! in_array($key, $defaultKeys, true))
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($idUser, $selectedKeys, $validKeys, $defaultKeys): void {
            $managedKeys = array_values(array_diff($validKeys, $defaultKeys));
            if ($managedKeys !== []) {
                DB::table('tbl_user_menu_access')
                    ->where('id_user', $idUser)
                    ->whereIn('menu_key', $managedKeys)
                    ->delete();
            }

            if ($selectedKeys !== []) {
                DB::table('tbl_user_menu_access')->insert(array_map(
                    fn (string $menuKey): array => [
                        'id_user' => $idUser,
                        'menu_key' => $menuKey,
                        'can_access' => 1,
                    ],
                    $selectedKeys,
                ));
            }
        });
    }

    public function keyForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }
        foreach ($this->registry() as $menu) {
            if (($menu['route'] ?? null) === $routeName) {
                return (string) $menu['key'];
            }
            if (in_array($routeName, (array) ($menu['aliases'] ?? []), true)) {
                return (string) $menu['key'];
            }
        }
        return null;
    }
}
