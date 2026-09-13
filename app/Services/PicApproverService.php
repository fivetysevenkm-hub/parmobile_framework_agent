<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PicApproverService
{
    public const ROWS_PER_PAGE = 10;

    public function jabatans(): array
    {
        if (! $this->tableExists('tbljabatan')) {
            return [];
        }

        return DB::table('tbljabatan')
            ->select(['id_jabatan', 'jabatan'])
            ->orderBy('id_jabatan')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    public function users(): array
    {
        if (! $this->tableExists('tbl_user')) {
            return [];
        }

        return DB::table('tbl_user')
            ->select(['id_user', 'username'])
            ->orderBy('username')
            ->get()
            ->map(fn ($row): array => [
                'id_user' => (int) $row->id_user,
                'username' => (string) ($row->username ?? ''),
            ])
            ->all();
    }

    public function approvers(int $page = 1, string $keyword = ''): array
    {
        if (! $this->tableExists('tbl_pic_approve')) {
            return ['rows' => [], 'total' => 0, 'totalPages' => 1, 'page' => 1];
        }

        $query = $this->baseQuery();
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->where('p.nik', 'like', '%'.$keyword.'%')
                    ->orWhere('u.username', 'like', '%'.$keyword.'%');
            });
        }

        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        $page = min(max(1, $page), $totalPages);

        $rows = $query
            ->select([
                'p.nik',
                'u.username',
                'p.jabatan',
                'j.jabatan as jabatan_name',
                'p.class',
                'p.email',
                'p.no_hp',
            ])
            ->orderBy('p.class')
            ->orderBy('p.nik')
            ->forPage($page, self::ROWS_PER_PAGE)
            ->get()
            ->map(function ($row): array {
                return [
                    'nik' => (string) $row->nik,
                    'username' => (string) ($row->username ?? ''),
                    'jabatan' => (string) ($row->jabatan ?? ''),
                    'jabatan_name' => (string) ($row->jabatan_name ?? ''),
                    'class' => (string) ($row->class ?? ''),
                    'email' => (string) ($row->email ?? ''),
                    'no_hp' => (string) ($row->no_hp ?? ''),
                ];
            })
            ->all();

        return compact('rows', 'total', 'totalPages', 'page');
    }

    public function approver(int $nik): ?array
    {
        if (! $this->tableExists('tbl_pic_approve') || $nik <= 0) {
            return null;
        }

        $row = $this->baseQuery()
            ->where('p.nik', (string) $nik)
            ->first([
                'p.nik',
                'u.id_user',
                'u.username',
                'p.jabatan',
                'j.jabatan as jabatan_name',
                'p.class',
                'p.email',
                'p.no_hp',
            ]);

        return $row ? (array) $row : null;
    }

    public function create(int $nik, string $jabatan, int $class, string $email, string $noHp, ?AuditLogger $audit = null): void
    {
        $this->assertFields($nik, $jabatan, $class, $email, $noHp);
        if (DB::table('tbl_pic_approve')->where('nik', (string) $nik)->exists()) {
            throw new RuntimeException('PIC Approver dengan NIK tersebut sudah terdaftar.');
        }

        DB::table('tbl_pic_approve')->insert([
            'nik' => (string) $nik,
            'jabatan' => trim($jabatan),
            'class' => (string) $class,
            'email' => trim($email),
            'no_hp' => trim($noHp),
        ]);

        ($audit ?? app(AuditLogger::class))->log(
            AuditLogger::EVENT_CREATE,
            'Menambahkan PIC Approver NIK '.$nik,
            'user-management',
            newData: ['nik' => (string) $nik, 'jabatan' => trim($jabatan), 'class' => $class, 'email' => trim($email), 'no_hp' => trim($noHp)],
        );
    }

    public function update(int $nik, string $jabatan, int $class, string $email, string $noHp, ?AuditLogger $audit = null): void
    {
        $this->assertFields($nik, $jabatan, $class, $email, $noHp);
        $existing = DB::table('tbl_pic_approve')->where('nik', (string) $nik)->first();
        if (! $existing) {
            throw new RuntimeException('PIC Approver yang dipilih tidak ditemukan.');
        }

        DB::table('tbl_pic_approve')->where('nik', (string) $nik)->update([
            'jabatan' => trim($jabatan),
            'class' => (string) $class,
            'email' => trim($email),
            'no_hp' => trim($noHp),
        ]);

        $old = ['jabatan' => $existing->jabatan, 'class' => (string) ($existing->class ?? ''), 'email' => $existing->email, 'no_hp' => $existing->no_hp];
        $new = ['jabatan' => trim($jabatan), 'class' => (string) $class, 'email' => trim($email), 'no_hp' => trim($noHp)];
        $logger = $audit ?? app(AuditLogger::class);
        $logger->log(
            AuditLogger::EVENT_UPDATE,
            'Memperbarui PIC Approver NIK '.$nik,
            'user-management',
            oldData: $old,
            newData: $new,
            metadata: ['changes' => $logger->diff($old, $new)],
        );
    }

    public function delete(int $nik, ?AuditLogger $audit = null): void
    {
        $existing = DB::table('tbl_pic_approve')->where('nik', (string) $nik)->first();
        if (! $existing) {
            throw new RuntimeException('PIC Approver yang dipilih tidak ditemukan.');
        }

        DB::table('tbl_pic_approve')->where('nik', (string) $nik)->delete();

        ($audit ?? app(AuditLogger::class))->log(
            AuditLogger::EVENT_DELETE,
            'Menghapus PIC Approver NIK '.$nik,
            'user-management',
            oldData: ['nik' => (string) $nik, 'jabatan' => $existing->jabatan, 'class' => (string) ($existing->class ?? ''), 'email' => $existing->email, 'no_hp' => $existing->no_hp],
        );
    }

    private function baseQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('tbl_pic_approve as p')
            ->leftJoin('tbl_user as u', 'u.id_user', '=', 'p.nik')
            ->leftJoin('tbljabatan as j', 'j.id_jabatan', '=', 'p.jabatan');
    }

    private function assertFields(int $nik, string $jabatan, int $class, string $email, string $noHp): void
    {
        if ($nik <= 0 || ! DB::table('tbl_user')->where('id_user', $nik)->exists()) {
            throw new RuntimeException('PIC Approver harus dipilih dari user yang terdaftar.');
        }
        if (trim($jabatan) === '' || ! DB::table('tbljabatan')->where('id_jabatan', trim($jabatan))->exists()) {
            throw new RuntimeException('Jabatan wajib dipilih.');
        }
        if ($class < 1 || $class > 99) {
            throw new RuntimeException('Urutan approval (class) wajib angka antara 1 sampai 99.');
        }
        if (trim($email) === '' || mb_strlen(trim($email)) > 30) {
            throw new RuntimeException('Email wajib diisi dan maksimal 30 karakter.');
        }
        if (trim($noHp) === '' || mb_strlen(trim($noHp)) > 30) {
            throw new RuntimeException('No HP wajib diisi dan maksimal 30 karakter.');
        }
    }

    private function tableExists(string $table): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable($table);
    }
}