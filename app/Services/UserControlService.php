<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserControlService
{
    public const ROWS_PER_PAGE = 10;

    public function departments(): array
    {
        return DB::table('tbldept')
            ->select(['Id_Dept as id_dept', 'Dept as dept'])
            ->orderBy('Dept')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function roles(): array
    {
        return DB::table('tbl_role')
            ->select(['id_role', 'role_name'])
            ->orderBy('role_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function users(int $page = 1, string $keyword = ''): array
    {
        $query = DB::table('tbl_user as u')
            ->join('tbldept as d', 'u.id_dept', '=', 'd.Id_Dept')
            ->join('tbl_role as r', 'u.id_role', '=', 'r.id_role');
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->where('u.id_user', 'like', '%'.$keyword.'%')
                    ->orWhere('u.username', 'like', '%'.$keyword.'%');
            });
        }

        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        $page = min(max(1, $page), $totalPages);

        $rows = $query
            ->select([
                'u.id_user',
                'u.username',
                'd.Dept as dept',
                'u.id_dept',
                'r.role_name',
                'u.id_role',
                'u.is_active',
            ])
            ->orderBy('u.id_user')
            ->forPage($page, self::ROWS_PER_PAGE)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return compact('rows', 'total', 'totalPages', 'page');
    }

    public function user(int $id): ?array
    {
        $row = DB::table('tbl_user')
            ->select(['id_user', 'username', 'id_dept', 'id_role', 'is_active'])
            ->where('id_user', $id)
            ->first();

        return $row ? (array) $row : null;
    }

    public function create(int $idUser, string $username, string $password, string $idDept, int $idRole, bool $isActive): void
    {
        $this->assertFields($idUser, $username, $password, $idDept, $idRole);
        if (DB::table('tbl_user')->where('id_user', $idUser)->exists()) {
            throw new RuntimeException('NIK sudah terdaftar.');
        }

        DB::table('tbl_user')->insert([
            'id_user' => $idUser,
            'username' => trim($username),
            'id_dept' => trim($idDept),
            'id_role' => $idRole,
            'is_active' => $isActive ? 1 : 0,
            'pwd' => $password,
        ]);
    }

    public function update(int $idUser, string $username, string $password, string $idDept, int $idRole, bool $isActive): void
    {
        $this->assertFields($idUser, $username, $password, $idDept, $idRole);
        if (! DB::table('tbl_user')->where('id_user', $idUser)->exists()) {
            throw new RuntimeException('User yang dipilih tidak ditemukan.');
        }

        DB::table('tbl_user')
            ->where('id_user', $idUser)
            ->update([
                'username' => trim($username),
                'id_dept' => trim($idDept),
                'pwd' => $password,
                'id_role' => $idRole,
                'is_active' => $isActive ? 1 : 0,
            ]);
    }

    public function delete(int $idUser): void
    {
        if (! DB::table('tbl_user')->where('id_user', $idUser)->exists()) {
            throw new RuntimeException('User yang dipilih tidak ditemukan.');
        }

        DB::transaction(function () use ($idUser): void {
            DB::table('tbl_user')->where('id_user', $idUser)->delete();
        });
    }

    private function assertFields(int $idUser, string $username, string $password, string $idDept, int $idRole): void
    {
        if ($idUser <= 0) {
            throw new RuntimeException('NIK wajib berupa angka positif.');
        }
        if (trim($username) === '' || mb_strlen(trim($username)) > 50) {
            throw new RuntimeException('Username wajib diisi dan maksimal 50 karakter.');
        }
        if ($password === '' || mb_strlen($password) < 8 || mb_strlen($password) > 15) {
            throw new RuntimeException('Password wajib 8–15 karakter sesuai batas tabel legacy.');
        }
        if (! preg_match('/[a-z]/', $password) || ! preg_match('/[A-Z]/', $password) || ! preg_match('/\d/', $password) || ! preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new RuntimeException('Password harus memiliki huruf kecil, huruf besar, angka, dan karakter khusus.');
        }
        if (trim($idDept) === '') {
            throw new RuntimeException('Departemen wajib dipilih.');
        }
        if (! DB::table('tbldept')->where('Id_Dept', trim($idDept))->exists()) {
            throw new RuntimeException('Departemen tidak ditemukan.');
        }
        if ($idRole <= 0 || ! DB::table('tbl_role')->where('id_role', $idRole)->exists()) {
            throw new RuntimeException('Role tidak ditemukan.');
        }
    }
}
