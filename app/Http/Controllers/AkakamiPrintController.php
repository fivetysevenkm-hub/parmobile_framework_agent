<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AkakamiPrintController extends Controller
{
    public function __invoke(Request $request, string $noflpp)
    {
        $akakami = DB::table('tblakakami')->where('noflpp', $noflpp)->first();

        abort_if(! $akakami, 404, 'Data Akakami tidak ditemukan.');

        $sequenceRows = DB::table('tbl_seq_all')
            ->where('no_lot', $akakami->no_lots)
            ->orderBy('seq')
            ->orderBy('id')
            ->get([
                'id',
                'no_lot',
                'no_srp',
                'seq',
                'id_seq',
                'nm_seq',
                'start_time',
                'finish_time',
                'duration',
                'tray_no',
                'tray_nokecil',
                'pic_01',
                'pic_02',
                'pic_03',
                'pic_04',
                'hold',
                'finish',
                'akakami',
            ]);

        $sequenceNames = DB::table('tblsequence')
            ->whereIn('id_seq', $sequenceRows->pluck('id_seq')->filter()->unique()->values())
            ->pluck('nm_sequence', 'id_seq');
        $tracePics = $sequenceRows->flatMap(function ($row) {
            return collect([$row->pic_01, $row->pic_02, $row->pic_03, $row->pic_04])->filter();
        })->unique()->values();
        $employeeNames = DB::table('tblkaryawan')
            ->whereIn('nik', $tracePics)
            ->pluck('nama_kary', 'nik');

        $traceRows = $sequenceRows->map(function ($row) use ($sequenceNames, $employeeNames) {
            $pics = collect([$row->pic_01, $row->pic_02, $row->pic_03, $row->pic_04])
                ->filter()
                ->map(fn ($nik) => $employeeNames[$nik] ?? $nik)
                ->implode(', ');

            return [
                'seq' => $row->seq,
                'process' => $sequenceNames[$row->id_seq] ?? $row->nm_seq ?? $row->id_seq ?? '-',
                'start_time' => $row->start_time,
                'finish_time' => $row->finish_time,
                'duration' => $row->duration,
                'tray_no' => $row->tray_no,
                'tray_nokecil' => $row->tray_nokecil,
                'pics' => $pics,
                'hold' => $row->hold,
                'finish' => $row->finish,
                'akakami' => $row->akakami,
            ];
        });

        $approvalRows = DB::table('tbl_approval_akakami')
            ->where('id_flpp', $noflpp)
            ->orderByRaw('CAST(seq_no AS UNSIGNED)')
            ->orderBy('id_approval')
            ->get([
                'id_approval',
                'seq_no',
                'nik_approver',
                'role_approver',
                'approve_status',
                'tgl_approve',
                'remark',
            ]);

        $approverIds = $approvalRows->pluck('nik_approver')->filter()->unique()->values();
        $approverNames = DB::table('tbl_user')
            ->whereIn('id_user', $approverIds)
            ->pluck('username', 'id_user');
        $approverEmployeeNames = DB::table('tblkaryawan')
            ->whereIn('nik', $approverIds)
            ->pluck('nama_kary', 'nik');
        $approvalHistory = $approvalRows->map(function ($row) use ($approverNames, $approverEmployeeNames) {
            $nik = (string) $row->nik_approver;

            return [
                'seq_no' => $row->seq_no,
                'nik' => $nik,
                'approver' => $approverEmployeeNames[$nik] ?? $approverNames[(int) $nik] ?? $approverNames[$nik] ?? '-',
                'role' => $row->role_approver,
                'status' => $row->approve_status,
                'approved_at' => $row->tgl_approve,
                'remark' => $row->remark,
            ];
        });

        $masterSequence = $sequenceRows->first();
        $problemName = $akakami->idproblems
            ? DB::table('tblproblem')->where('idproblem', $akakami->idproblems)->value('nmproblem')
            : null;
        $foundBy = $akakami->pic
            ? DB::table('tblkaryawan')->where('nik', $akakami->pic)->value('nama_kary')
            : null;
        $picDepartment = $akakami->pic_dept
            ? DB::table('tbldept')->where('Id_Dept', $akakami->pic_dept)->value('Dept')
            : null;
        $sequenceName = $akakami->id_depts
            ? DB::table('tblsequence')->where('id_seq', $akakami->id_depts)->value('nm_sequence')
            : null;
        $handlerName = $akakami->pic_handler
            ? (DB::table('tblkaryawan')->where('nik', $akakami->pic_handler)->value('nama_kary')
                ?? DB::table('tbl_user')->where('id_user', $akakami->pic_handler)->value('username')
                ?? (string) $akakami->pic_handler)
            : null;
        $loggedInUser = auth()->user();

        return response()->view('print.akakami', [
            'akakami' => $akakami,
            'masterSequence' => $masterSequence,
            'traceRows' => $traceRows,
            'approvalHistory' => $approvalHistory,
            'problemName' => $problemName,
            'foundBy' => $foundBy,
            'picDepartment' => $picDepartment,
            'sequenceName' => $sequenceName,
            'handlerName' => $handlerName,
            'printedBy' => $loggedInUser?->username ?? $loggedInUser?->name ?? session('username') ?? 'System',
            'printedAt' => now('Asia/Jakarta'),
        ]);
    }
}
