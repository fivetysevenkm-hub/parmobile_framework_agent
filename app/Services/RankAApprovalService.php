<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RankAApprovalService
{
    public function pendingForApprover(string $nik): array
    {
        if ($nik === '') {
            return [];
        }

        return DB::table('tbl_approval_ranka AS ar')
            ->join('tblranka AS r', 'r.no_rank', '=', 'ar.id_ranka')
            ->leftJoin('tblakakami AS a', 'a.noflpp', '=', 'r.noflpp')
            ->where('ar.nik_approver', $nik)
            ->where('ar.approve_status', 'waiting')
            ->select([
                'r.no_rank',
                'r.noflpp',
                'r.tgl_pengajuan',
                'r.status',
                'ar.seq_no',
                'a.no_lots',
                'a.qty_problem',
                'a.deskripsi_problem',
            ])
            ->distinct()
            ->orderByDesc('r.no_rank')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function detailForApprover(string $noRank, string $nik): ?array
    {
        if ($noRank === '' || $nik === '') {
            return null;
        }

        $rank = DB::table('tblranka AS r')
            ->join('tbl_approval_ranka AS ar', function ($join) use ($nik): void {
                $join->on('ar.id_ranka', '=', 'r.no_rank')
                    ->where('ar.nik_approver', '=', $nik)
                    ->where('ar.approve_status', '=', 'waiting');
            })
            ->leftJoin('tblakakami AS a', 'a.noflpp', '=', 'r.noflpp')
            ->where('r.no_rank', $noRank)
            ->first([
                'r.no_rank',
                'r.noflpp',
                'r.tgl_pengajuan',
                'r.instruksi_perbaikan',
                'r.link_ilustrasi',
                'r.status',
                'ar.seq_no',
                'a.no_lots',
                'a.qty_problem',
                'a.deskripsi_problem',
            ]);

        if (! $rank) {
            return null;
        }

        $sequence = $rank->no_lots
            ? DB::table('tbl_seq_all')
                ->where('no_lot', $rank->no_lots)
                ->orderBy('id')
                ->first(['no_part', 'nm_part', 'customer'])
            : null;

        return [
            'no_rank' => $rank->no_rank,
            'noflpp' => $rank->noflpp,
            'tgl_pengajuan' => $rank->tgl_pengajuan,
            'instruksi_perbaikan' => $rank->instruksi_perbaikan,
            'link_ilustrasi' => $rank->link_ilustrasi,
            'status' => $rank->status,
            'seq_no' => $rank->seq_no,
            'no_lots' => $rank->no_lots,
            'no_brg_asli' => $sequence->no_part ?? null,
            'nm_part' => $sequence->nm_part ?? null,
            'nm_customer' => $sequence->customer ?? 'INTERNAL',
            'qty_problem' => $rank->qty_problem,
            'deskripsi_problem' => $rank->deskripsi_problem,
        ];
    }

    public function process(string $noRank, string $nik, string $type): array
    {
        if (! in_array($type, ['approve', 'reject'], true)) {
            throw new RuntimeException('Jenis proses Rank A tidak valid.');
        }

        return DB::transaction(function () use ($noRank, $nik, $type): array {
            $rank = DB::table('tblranka')
                ->where('no_rank', $noRank)
                ->lockForUpdate()
                ->first();

            if (! $rank) {
                throw new RuntimeException('Data Rank A tidak ditemukan.');
            }

            $current = DB::table('tbl_approval_ranka')
                ->where('id_ranka', $noRank)
                ->where('nik_approver', $nik)
                ->where('approve_status', 'waiting')
                ->lockForUpdate()
                ->first(['id_approval', 'seq_no']);

            if (! $current) {
                throw new RuntimeException('PIC approver sebelum Anda belum tanda tangan atau antrean telah berubah.');
            }

            $currentSeq = (int) $current->seq_no;

            if ($type === 'reject') {
                DB::table('tbl_approval_ranka')
                    ->where('id_approval', $current->id_approval)
                    ->update([
                        'approve_status' => 'rejected',
                        'tgl_approve' => now('Asia/Jakarta'),
                    ]);
                DB::table('tblranka')->where('no_rank', $noRank)->update(['status' => 'rejected']);

                return [
                    'rank' => $rank,
                    'type' => $type,
                    'seq' => $currentSeq,
                    'status_label' => 'REJECTED',
                    'message' => 'Rank A berhasil direject secara permanen.',
                ];
            }

            $maxSeq = (int) DB::table('tbl_approval_ranka')
                ->where('id_ranka', $noRank)
                ->max('seq_no');

            DB::table('tbl_approval_ranka')
                ->where('id_approval', $current->id_approval)
                ->update([
                    'approve_status' => 'approved',
                    'tgl_approve' => now('Asia/Jakarta'),
                ]);

            if ($currentSeq < $maxSeq) {
                $nextSeq = $currentSeq + 1;
                DB::table('tbl_approval_ranka')
                    ->where('id_ranka', $noRank)
                    ->where('seq_no', $nextSeq)
                    ->where('approve_status', 'pending')
                    ->update(['approve_status' => 'waiting']);

                return [
                    'rank' => $rank,
                    'type' => $type,
                    'seq' => $currentSeq,
                    'next_seq' => $nextSeq,
                    'status_label' => 'APPROVED (PARTIAL)',
                    'message' => "Approve berhasil. Menunggu approver urutan {$nextSeq}.",
                ];
            }

            DB::table('tblranka')->where('no_rank', $noRank)->update(['status' => 'approved']);

            return [
                'rank' => $rank,
                'type' => $type,
                'seq' => $currentSeq,
                'status_label' => 'FULLY APPROVED',
                'message' => "Anda PIC tanda tangan terakhir, pastikan konfirmasi ke Team jika sudah 'approved'.",
            ];
        });
    }
}
