<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManPowerControlService
{
    public function users(): array
    {
        return DB::table('tbl_user AS u')
            ->leftJoin('tblkaryawan AS k', 'k.nik', '=', 'u.id_user')
            ->leftJoin('tbldept AS d', 'd.Id_Dept', '=', 'u.id_dept')
            ->where('u.is_active', 1)
            ->select([
                'u.id_user AS nik',
                'u.username',
                'u.id_dept',
                'd.Dept AS dept_name',
                'k.jabatan AS current_jabatan',
                'k.ojt_a AS current_ojt',
            ])
            ->orderBy('u.username')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function departments(): array
    {
        return DB::table('tbldept')
            ->select(['Id_Dept AS id_dept', 'Dept AS dept'])
            ->orderBy('Dept')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function positions(): array
    {
        return DB::table('tbljabatan')
            ->select(['id_jabatan', 'jabatan'])
            ->orderBy('jabatan')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function sequences(): array
    {
        return DB::table('tblsequence')
            ->select(['id_seq', 'nm_sequence'])
            ->orderBy('id_seq')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function detail(string $nik): ?array
    {
        $employee = DB::table('tblkaryawan')->where('nik', $nik)->first();
        $user = DB::table('tbl_user AS u')
            ->leftJoin('tbldept AS d', 'd.Id_Dept', '=', 'u.id_dept')
            ->where('u.id_user', $nik)
            ->where('u.is_active', 1)
            ->first(['u.id_user AS nik', 'u.username', 'u.id_dept', 'd.Dept AS dept_name']);

        if (! $user) {
            return null;
        }

        $slots = [];
        for ($index = 1; $index <= 39; $index++) {
            $column = 'class_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $slots[$index] = [
                'id_seq' => $employee?->{$column},
                'status_ojt' => null,
            ];
        }

        DB::table('tblkaryawan_ojt_status')
            ->where('nik', $nik)
            ->get(['slot_index', 'status_ojt'])
            ->each(function ($row) use (&$slots): void {
                $index = (int) $row->slot_index;
                if ($index >= 1 && $index <= 39) {
                    $slots[$index]['status_ojt'] = $row->status_ojt;
                }
            });

        return [
            'nik' => (string) $user->nik,
            'username' => (string) $user->username,
            'id_dept' => (string) ($employee->id_dept_kar ?? $user->id_dept ?? ''),
            'dept_name' => (string) ($user->dept_name ?? '-'),
            'jabatan' => (string) ($employee->jabatan ?? ''),
            'ojt_a' => (string) ($employee->ojt_a ?? ''),
            'exists' => $employee !== null,
            'slots' => $slots,
        ];
    }

    public function save(string $nik, string $jabatan, string $ojt, array $slots): void
    {
        $user = DB::table('tbl_user')->where('id_user', $nik)->where('is_active', 1)->first(['id_user', 'username', 'id_dept']);
        if (! $user) {
            throw new RuntimeException('User aktif tidak ditemukan.');
        }

        $allowedSequences = DB::table('tblsequence')->pluck('id_seq')->map(fn ($id) => (string) $id)->all();
        $allowedLookup = array_fill_keys($allowedSequences, true);
        $mapping = [];
        for ($index = 1; $index <= 39; $index++) {
            $column = 'class_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $idSeq = (string) ($slots[$index]['id_seq'] ?? '');
            $mapping[$column] = $idSeq !== '' && isset($allowedLookup[$idSeq]) ? $idSeq : null;
        }

        DB::transaction(function () use ($nik, $jabatan, $ojt, $user, $mapping, $slots): void {
            $payload = array_merge([
                'jabatan' => $jabatan,
                'ojt_a' => $ojt,
            ], $mapping);

            $exists = DB::table('tblkaryawan')->where('nik', $nik)->exists();
            if ($exists) {
                DB::table('tblkaryawan')->where('nik', $nik)->update($payload);
            } else {
                DB::table('tblkaryawan')->insert(array_merge([
                    'nik' => $nik,
                    'nama_kary' => $user->username,
                    'id_dept_kar' => $user->id_dept,
                ], $payload));
            }

            DB::table('tblkaryawan_ojt_status')->where('nik', $nik)->delete();
            $ojtRows = [];
            for ($index = 1; $index <= 39; $index++) {
                $status = (string) ($slots[$index]['status_ojt'] ?? '');
                if (in_array($status, ['yes', 'no'], true)) {
                    $ojtRows[] = [
                        'nik' => $nik,
                        'slot_index' => $index,
                        'status_ojt' => $status,
                    ];
                }
            }
            if ($ojtRows !== []) {
                DB::table('tblkaryawan_ojt_status')->insert($ojtRows);
            }
        });
    }
}
