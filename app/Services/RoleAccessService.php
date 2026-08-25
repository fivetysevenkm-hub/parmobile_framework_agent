<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoleAccessService
{
    public function roles(): array
    {
        return DB::table('tbl_role as role')
            ->leftJoin('tbl_user as user', 'user.id_role', '=', 'role.id_role')
            ->leftJoin('tbl_role_menu as role_menu', 'role_menu.id_role', '=', 'role.id_role')
            ->select([
                'role.id_role',
                'role.role_name',
                'role.description',
                DB::raw('COUNT(DISTINCT user.id_user) as user_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN role_menu.can_access = 1 THEN role_menu.id_menu END) as menu_count'),
            ])
            ->groupBy('role.id_role', 'role.role_name', 'role.description')
            ->orderBy('role.id_role')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function role(int $id): ?array
    {
        $row = DB::table('tbl_role')
            ->select(['id_role', 'role_name', 'description'])
            ->where('id_role', $id)
            ->first();

        return $row ? (array) $row : null;
    }

    public function create(string $roleName, ?string $description): int
    {
        $this->assertRoleName($roleName);
        return (int) DB::table('tbl_role')->insertGetId([
            'role_name' => trim($roleName),
            'description' => $this->cleanDescription($description),
        ]);
    }

    public function update(int $id, string $roleName, ?string $description): void
    {
        $this->assertRoleName($roleName);
        if (! DB::table('tbl_role')->where('id_role', $id)->exists()) {
            throw new RuntimeException('Role yang dipilih tidak ditemukan.');
        }
        DB::table('tbl_role')
            ->where('id_role', $id)
            ->update([
                'role_name' => trim($roleName),
                'description' => $this->cleanDescription($description),
            ]);
    }

    public function delete(int $id): void
    {
        if (! DB::table('tbl_role')->where('id_role', $id)->exists()) {
            throw new RuntimeException('Role yang dipilih tidak ditemukan.');
        }
        if (DB::table('tbl_user')->where('id_role', $id)->exists()) {
            throw new RuntimeException('Role masih digunakan oleh user dan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($id): void {
            DB::table('tbl_role_menu')->where('id_role', $id)->delete();
            DB::table('tbl_role')->where('id_role', $id)->delete();
        });
    }

    private function assertRoleName(string $roleName): void
    {
        $roleName = trim($roleName);
        if ($roleName === '') {
            throw new RuntimeException('Nama Role harus diisi.');
        }
        if (mb_strlen($roleName) > 50) {
            throw new RuntimeException('Nama Role maksimal 50 karakter.');
        }
    }

    private function cleanDescription(?string $description): ?string
    {
        $description = trim((string) $description);
        if ($description === '') {
            return null;
        }
        if (mb_strlen($description) > 100) {
            throw new RuntimeException('Keterangan maksimal 100 karakter.');
        }
        return $description;
    }
}
