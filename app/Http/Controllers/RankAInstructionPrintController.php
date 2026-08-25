<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankAInstructionPrintController extends Controller
{
    public function __invoke(Request $request, string $noRank)
    {
        $firstIds = DB::table('tbl_seq_all')->selectRaw('MIN(id)')->groupBy('no_lot');
        $sequence = DB::table('tbl_seq_all AS ss')
            ->whereIn('ss.id', $firstIds)
            ->select(['ss.no_lot', 'ss.no_srp', 'ss.no_part', 'ss.nm_part', 'ss.customer', 'ss.no_sj_cust', 'ss.no_lot_cust']);

        $rank = DB::table('tblranka AS r')
            ->leftJoin('tblakakami AS a', 'a.noflpp', '=', 'r.noflpp')
            ->leftJoinSub($sequence, 's', 'a.no_lots', '=', 's.no_lot')
            ->where('r.no_rank', $noRank)
            ->where('r.status', 'approved')
            ->first([
                'r.no_rank', 'r.noflpp', 'r.tgl_pengajuan', 'r.instruksi_perbaikan', 'r.link_ilustrasi',
                's.no_part AS no_brg_asli', 's.nm_part', 's.customer AS nm_customer', 'a.qty_problem', 'a.deskripsi_problem', 'r.status',
                'a.no_lots', 'a.tgl_problem',
            ]);

        abort_if(! $rank, 404, 'Rank A approved tidak ditemukan.');

        $approvalHistory = DB::table('tbl_approval_ranka AS ar')
            ->leftJoin('tbl_user AS u', 'ar.nik_approver', '=', 'u.id_user')
            ->leftJoin('tblkaryawan AS k', 'ar.nik_approver', '=', 'k.nik')
            ->where('ar.id_ranka', $noRank)
            ->orderByRaw('CAST(ar.seq_no AS UNSIGNED)')
            ->orderBy('ar.id_approval')
            ->get([
                'ar.seq_no', 'ar.nik_approver', 'ar.role_approver', 'ar.approve_status',
                'ar.tgl_approve', 'ar.remark', 'u.username', 'k.nama_kary',
            ])
            ->map(fn ($row) => [
                'seq_no' => $row->seq_no,
                'nik' => $row->nik_approver,
                'approver' => $row->nama_kary ?: $row->username ?: $row->nik_approver,
                'role' => $row->role_approver,
                'status' => $row->approve_status,
                'approved_at' => $row->tgl_approve,
                'remark' => $row->remark,
            ])
            ->all();

        $lot = $rank->no_lots
            ? DB::table('tbl_seq_all')->where('no_lot', $rank->no_lots)->orderBy('id')->first([
                'no_srp', 'no_part', 'nm_part', 'customer', 'no_sj_cust', 'no_lot_cust',
            ])
            : null;

        $printedBy = $request->user()?->username
            ?? $request->user()?->name
            ?? session('username', 'System');

        return response()->view('print.rank-a-instruction-sheet', [
            'rank' => $rank,
            'lot' => $lot,
            'approvalHistory' => $approvalHistory,
            'printedBy' => $printedBy,
            'printedAt' => now('Asia/Jakarta'),
        ]);
    }
}
