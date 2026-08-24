<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AbnormalMtcApprovalService
{
    public function pendingForApprover(string $nik): array
    {
        if ($nik === '') {
            return [];
        }

        return DB::table('tbl_approval_abnreport AS ar')
            ->join('tblabnreport AS a', 'a.noabn', '=', 'ar.id_abn')
            ->where('ar.nik_approver', $nik)
            ->where('ar.approve_status', 'waiting')
            ->select([
                'a.noabn',
                'a.tgl_problem',
                'a.category',
                'a.nm_object',
                'a.ref_lot',
                'a.status',
                'ar.seq_no',
            ])
            ->distinct()
            ->orderByDesc('a.tgl_problem')
            ->orderByDesc('a.noabn')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function detailForApprover(string $noAbn, string $nik): ?array
    {
        if ($noAbn === '' || $nik === '') {
            return null;
        }

        $abnormal = DB::table('tblabnreport AS a')
            ->join('tbl_approval_abnreport AS ar', function ($join) use ($nik): void {
                $join->on('ar.id_abn', '=', 'a.noabn')
                    ->where('ar.nik_approver', '=', $nik)
                    ->where('ar.approve_status', '=', 'waiting');
            })
            ->leftJoin('tblsequence AS s', 'a.area', '=', 's.id_seq')
            ->leftJoin('tblkaryawan AS k', 'a.found_by', '=', 'k.nik')
            ->where('a.noabn', $noAbn)
            ->first([
                'a.noabn',
                'a.tgl_problem',
                'a.category',
                'a.nm_object',
                's.nm_sequence',
                'k.nama_kary',
                'a.ref_lot',
                'a.detail_problem',
                'a.analisa',
                'a.correction',
                'a.corrective',
                'a.pic_handler',
                'a.pic_dept',
                'a.status',
                'ar.seq_no',
            ]);

        if (! $abnormal) {
            return null;
        }

        $lotData = $abnormal->ref_lot
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
            'nm_sequence' => $abnormal->nm_sequence,
            'nama_kary' => $abnormal->nama_kary,
            'ref_lot' => $abnormal->ref_lot,
            'no_part' => $lotData->no_part ?? null,
            'nm_part' => $lotData->nm_part ?? null,
            'nm_customer' => $lotData->customer ?? null,
            'detail_problem' => $abnormal->detail_problem,
            'analisa' => $abnormal->analisa,
            'correction' => $abnormal->correction,
            'corrective' => $abnormal->corrective,
            'pic_handler' => $abnormal->pic_handler,
            'pic_dept' => $abnormal->pic_dept,
            'status' => $abnormal->status,
            'seq_no' => $abnormal->seq_no,
        ];
    }

    public function process(string $noAbn, string $nik, string $type): array
    {
        if (! in_array($type, ['approve', 'reject'], true)) {
            throw new RuntimeException('Jenis proses Abnormal Mtc tidak valid.');
        }

        return DB::transaction(function () use ($noAbn, $nik, $type): array {
            $abnormal = DB::table('tblabnreport')
                ->where('noabn', $noAbn)
                ->lockForUpdate()
                ->first();

            if (! $abnormal) {
                throw new RuntimeException('Data Abnormal Report tidak ditemukan.');
            }

            $current = DB::table('tbl_approval_abnreport')
                ->where('id_abn', $noAbn)
                ->where('nik_approver', $nik)
                ->where('approve_status', 'waiting')
                ->lockForUpdate()
                ->first(['id_approval', 'seq_no']);

            if (! $current) {
                throw new RuntimeException('PIC approver sebelum Anda belum tanda tangan atau antrean telah berubah.');
            }

            $now = now('Asia/Jakarta');
            $currentSeq = (int) $current->seq_no;

            if ($type === 'reject') {
                DB::table('tbl_approval_abnreport')
                    ->where('id_approval', $current->id_approval)
                    ->update([
                        'approve_status' => 'rejected',
                        'tgl_approve' => $now,
                    ]);
                DB::table('tblabnreport')->where('noabn', $noAbn)->update(['status' => 'rejected']);

                return [
                    'abnormal' => $abnormal,
                    'type' => $type,
                    'seq' => $currentSeq,
                    'status_label' => 'REJECTED',
                    'message' => 'Abnormal Report berhasil di-Reject.',
                ];
            }

            $maxSeq = (int) DB::table('tbl_approval_abnreport')
                ->where('id_abn', $noAbn)
                ->max('seq_no');

            DB::table('tbl_approval_abnreport')
                ->where('id_approval', $current->id_approval)
                ->update([
                    'approve_status' => 'approved',
                    'tgl_approve' => $now,
                ]);

            if ($currentSeq < $maxSeq) {
                $nextSeq = $currentSeq + 1;
                DB::table('tbl_approval_abnreport')
                    ->where('id_abn', $noAbn)
                    ->where('seq_no', $nextSeq)
                    ->where('approve_status', 'pending')
                    ->update(['approve_status' => 'waiting']);

                return [
                    'abnormal' => $abnormal,
                    'type' => $type,
                    'seq' => $currentSeq,
                    'next_seq' => $nextSeq,
                    'status_label' => 'APPROVED',
                    'message' => 'Abnormal Report Approved. Lanjut ke urutan berikutnya.',
                ];
            }

            DB::table('tblabnreport')->where('noabn', $noAbn)->update(['status' => 'approved']);

            return [
                'abnormal' => $abnormal,
                'type' => $type,
                'seq' => $currentSeq,
                'status_label' => 'APPROVED',
                'message' => 'Abnormal Report Selesai di-Approve Final.',
            ];
        });
    }
}
