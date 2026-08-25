<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AbnormalReportService
{
    public const STATUSES = ['open', 'eskalasi', 'approved', 'rejected'];

    public function listQuery(string $status, string $search = '', ?string $from = null, ?string $to = null)
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Status Abnormal Report tidak valid.');
        }

        $term = '%'.trim($search).'%';

        return DB::table('tblabnreport AS a')
            ->leftJoin('tblsequence AS s', 'a.area', '=', 's.id_seq')
            ->leftJoin('tblkaryawan AS k', 'a.found_by', '=', 'k.nik')
            ->where('a.status', $status)
            ->when(trim($search) !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('a.noabn', 'like', $term)
                        ->orWhere('a.ref_lot', 'like', $term)
                        ->orWhere('a.detail_problem', 'like', $term)
                        ->orWhere('a.nm_object', 'like', $term)
                        ->orWhere('a.category', 'like', $term);
                });
            })
            ->when($from !== null && $from !== '', fn ($query) => $query->whereDate('a.tgl_problem', '>=', $from))
            ->when($to !== null && $to !== '', fn ($query) => $query->whereDate('a.tgl_problem', '<=', $to))
            ->select([
                'a.noabn',
                'a.tgl_problem',
                'a.category',
                'a.nm_object',
                's.nm_sequence',
                'k.nama_kary',
                'a.ref_lot',
                'a.detail_problem',
                'a.status',
            ])
            ->orderByDesc('a.tgl_problem')
            ->orderByDesc('a.noabn');
    }

    public function detail(string $noAbn): ?array
    {
        $abnormal = DB::table('tblabnreport AS a')
            ->leftJoin('tblsequence AS s', 'a.area', '=', 's.id_seq')
            ->leftJoin('tblkaryawan AS k', 'a.found_by', '=', 'k.nik')
            ->leftJoin('tbldept AS d', 'a.pic_dept', '=', 'd.Id_Dept')
            ->where('a.noabn', $noAbn)
            ->first([
                'a.noabn',
                'a.tgl_problem',
                'a.category',
                'a.nm_object',
                'a.area',
                's.nm_sequence',
                'a.found_by',
                'k.nama_kary',
                'a.ref_lot',
                'a.detail_problem',
                'a.analisa',
                'a.correction',
                'a.corrective',
                'a.pic_handler',
                'a.pic_dept',
                'd.Dept AS pic_dept_name',
                'a.status',
            ]);

        if (! $abnormal) {
            return null;
        }

        $lot = $abnormal->ref_lot
            ? DB::table('tbl_seq_all')
                ->where('no_lot', $abnormal->ref_lot)
                ->orderBy('id')
                ->first(['no_part', 'nm_part', 'customer'])
            : null;

        return [
            'noabn' => $abnormal->noabn,
            'tgl_problem' => $abnormal->tgl_problem,
            'category' => $abnormal->category,
            'nm_object' => $abnormal->nm_object,
            'area' => $abnormal->area,
            'nm_sequence' => $abnormal->nm_sequence,
            'found_by' => $abnormal->found_by,
            'nama_kary' => $abnormal->nama_kary,
            'ref_lot' => $abnormal->ref_lot,
            'no_part' => $lot->no_part ?? null,
            'nm_part' => $lot->nm_part ?? null,
            'nm_customer' => $lot->customer ?? 'INTERNAL',
            'detail_problem' => $abnormal->detail_problem,
            'analisa' => $abnormal->analisa,
            'correction' => $abnormal->correction,
            'corrective' => $abnormal->corrective,
            'pic_handler' => $abnormal->pic_handler,
            'pic_dept' => $abnormal->pic_dept,
            'pic_dept_name' => $abnormal->pic_dept_name,
            'status' => $abnormal->status,
        ];
    }

    public function departments(): array
    {
        return DB::table('tbldept')
            ->select(['Id_Dept', 'Dept'])
            ->orderBy('Dept')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function approvers(): array
    {
        return DB::table('vw_pic_approve')
            ->select(['nik', 'user', 'jabatan', 'class'])
            ->orderBy('user')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function approvalProgress(string $noAbn): array
    {
        return DB::table('tbl_approval_abnreport AS a')
            ->leftJoin('tbl_user AS u', 'a.nik_approver', '=', 'u.id_user')
            ->where('a.id_abn', $noAbn)
            ->orderBy('a.seq_no')
            ->get([
                'a.seq_no',
                'a.nik_approver',
                'a.approve_status',
                'a.tgl_approve',
                'a.remark',
                'u.username AS nama',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function commit(array $data, array $approverNiks, string $handlerId): array
    {
        if ($approverNiks === []) {
            throw new RuntimeException('Minimal satu PIC Judgment harus dipilih.');
        }

        return DB::transaction(function () use ($data, $approverNiks, $handlerId): array {
            $current = DB::table('tblabnreport')
                ->where('noabn', $data['noabn'])
                ->lockForUpdate()
                ->first();

            if (! $current) {
                throw new RuntimeException('Data Abnormal Report tidak ditemukan.');
            }

            if (! in_array((string) $current->status, ['open', 'rejected'], true)) {
                throw new RuntimeException('Data hanya dapat diproses jika statusnya OPEN atau REJECTED.');
            }

            $now = now('Asia/Jakarta');
            DB::table('tblabnreport')
                ->where('noabn', $data['noabn'])
                ->update([
                    'detail_problem' => $data['detail_problem'],
                    'analisa' => $data['analisa'],
                    'correction' => $data['correction'],
                    'corrective' => $data['corrective'],
                    'pic_handler' => $handlerId,
                    'pic_dept' => $data['pic_dept'],
                    'status' => 'eskalasi',
                ]);

            DB::table('tbl_approval_abnreport')
                ->where('id_abn', $data['noabn'])
                ->delete();

            $approverRows = DB::table('vw_pic_approve')
                ->whereIn('nik', array_values(array_unique(array_map('strval', $approverNiks))))
                ->orderByDesc('class')
                ->orderBy('user')
                ->get(['nik', 'user', 'class']);

            if ($approverRows->isEmpty()) {
                throw new RuntimeException('PIC approver yang dipilih tidak ditemukan.');
            }

            $names = [];
            $sequence = 1;
            foreach ($approverRows as $approver) {
                $names[] = (string) $approver->user;
                DB::table('tbl_approval_abnreport')->insert([
                    'id_abn' => $data['noabn'],
                    'seq_no' => (string) $sequence,
                    'nik_approver' => (string) $approver->nik,
                    'role_approver' => (string) ($approver->class ?? ''),
                    'approve_status' => $sequence === 1 ? 'waiting' : 'pending',
                    // Legacy schema declares tgl_approve NOT NULL; use submission time
                    // as the row timestamp, then overwrite it on actual approval.
                    'tgl_approve' => $now,
                ]);
                $sequence++;
            }

            return [
                'detail' => $this->detail($data['noabn']),
                'approver_names' => $names,
                'status' => 'eskalasi',
                'submitted_at' => $now,
            ];
        });
    }
}
