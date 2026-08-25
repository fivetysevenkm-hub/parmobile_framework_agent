<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbnormalReportPrintController extends Controller
{
    public function __invoke(Request $request, string $noAbn)
    {
        $abnormal = DB::table('tblabnreport AS a')
            ->leftJoin('tblsequence AS s', 'a.area', '=', 's.id_seq')
            ->leftJoin('tblkaryawan AS k', 'a.found_by', '=', 'k.nik')
            ->leftJoin('tbldept AS d', 'a.pic_dept', '=', 'd.Id_Dept')
            ->where('a.noabn', $noAbn)
            ->where('a.status', 'approved')
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

        abort_if(! $abnormal, 404, 'Abnormal Report approved tidak ditemukan.');

        $lot = $abnormal->ref_lot
            ? DB::table('tbl_seq_all')
                ->where('no_lot', $abnormal->ref_lot)
                ->orderBy('id')
                ->first(['no_srp', 'no_part', 'nm_part', 'customer', 'no_sj_cust', 'no_lot_cust'])
            : null;

        $traceRows = $abnormal->ref_lot
            ? DB::table('tbl_seq_all')
                ->where('no_lot', $abnormal->ref_lot)
                ->orderBy('seq')
                ->orderBy('id')
                ->get(['seq', 'id_seq', 'nm_seq', 'start_time', 'finish_time', 'duration', 'hold', 'finish', 'akakami', 'pic_01', 'pic_02', 'pic_03', 'pic_04'])
            : collect();

        $traceProcessIds = $traceRows->pluck('id_seq')->filter()->unique()->values();
        $traceProcessNames = $traceProcessIds->isNotEmpty()
            ? DB::table('tblsequence')->whereIn('id_seq', $traceProcessIds)->pluck('nm_sequence', 'id_seq')
            : collect();
        $tracePics = $traceRows->flatMap(fn ($row) => collect([$row->pic_01, $row->pic_02, $row->pic_03, $row->pic_04])->filter())->unique()->values();
        $traceEmployeeNames = $tracePics->isNotEmpty()
            ? DB::table('tblkaryawan')->whereIn('nik', $tracePics)->pluck('nama_kary', 'nik')
            : collect();
        $traceRows = $traceRows->map(function ($row) use ($traceProcessNames, $traceEmployeeNames): array {
            $pics = collect([$row->pic_01, $row->pic_02, $row->pic_03, $row->pic_04])
                ->filter()
                ->map(fn ($nik) => $traceEmployeeNames[$nik] ?? $nik)
                ->implode(', ');

            return [
                'seq' => $row->seq,
                'process' => $traceProcessNames[$row->id_seq] ?? $row->nm_seq ?? $row->id_seq ?? '-',
                'start_time' => $row->start_time,
                'finish_time' => $row->finish_time,
                'duration' => $row->duration,
                'hold' => $row->hold,
                'finish' => $row->finish,
                'akakami' => $row->akakami,
                'pics' => $pics,
            ];
        });

        $approvalRows = DB::table('tbl_approval_abnreport AS a')
            ->leftJoin('tbl_user AS u', 'a.nik_approver', '=', 'u.id_user')
            ->leftJoin('tblkaryawan AS k', 'a.nik_approver', '=', 'k.nik')
            ->where('a.id_abn', $noAbn)
            ->orderByRaw('CAST(a.seq_no AS UNSIGNED)')
            ->orderBy('a.id_approval')
            ->get([
                'a.id_approval',
                'a.seq_no',
                'a.nik_approver',
                'a.role_approver',
                'a.approve_status',
                'a.tgl_approve',
                'a.remark',
                'u.username',
                'k.nama_kary',
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

        $handlerName = $abnormal->pic_handler
            ? (DB::table('tblkaryawan')->where('nik', $abnormal->pic_handler)->value('nama_kary')
                ?? DB::table('tbl_user')->where('id_user', $abnormal->pic_handler)->value('username')
                ?? $abnormal->pic_handler)
            : '-';

        $printedBy = $request->user()?->username
            ?? $request->user()?->name
            ?? session('username', 'System');

        return response()->view('print.abnormal-report', [
            'abnormal' => $abnormal,
            'lot' => $lot,
            'approvalHistory' => $approvalRows,
            'traceRows' => $traceRows,
            'handlerName' => $handlerName,
            'printedBy' => $printedBy,
            'printedAt' => now('Asia/Jakarta'),
        ]);
    }
}
