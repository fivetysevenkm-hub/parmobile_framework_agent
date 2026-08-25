<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
