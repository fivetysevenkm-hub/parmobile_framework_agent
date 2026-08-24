<?php

namespace App\Http\Controllers;

use App\Services\ManpowerProductivityReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManpowerCertificateController extends Controller
{
    public function __invoke(Request $request, ManpowerProductivityReport $report)
    {
        $validated = $request->validate([
            'operator' => ['nullable', 'string', 'max:100'],
            'proses' => ['nullable', 'string', 'max:100'],
            'tgl_awal' => ['nullable', 'date_format:Y-m-d'],
            'tgl_akhir' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $operator = trim((string) ($validated['operator'] ?? ''));
        $process = trim((string) ($validated['proses'] ?? ''));
        $dateFrom = $validated['tgl_awal'] ?? null;
        $dateTo = $validated['tgl_akhir'] ?? null;

        abort_if($operator === '' && $process === '' && ! $dateFrom && ! $dateTo, 422, 'Pilih filter Man Power sebelum mencetak sertifikat.');
        abort_if(($dateFrom && ! $dateTo) || (! $dateFrom && $dateTo), 422, 'Tanggal awal dan akhir harus dipilih bersama.');

        $summary = $report->summary($report->summaryQuery($operator, $process, $dateFrom, $dateTo));
        $performanceRows = $report->rowsQuery($operator, $process, $dateFrom, $dateTo)->get();
        $detailQuery = $report->certificateDetailQuery($operator, $process, $dateFrom, $dateTo);
        $detailTotal = (clone $detailQuery)->count();
        $detailRows = $detailQuery->limit(500)->get();

        $operatorIds = $performanceRows->pluck('pic_01')->filter()->unique()->values();
        $operatorNames = DB::table('tblkaryawan')->whereIn('nik', $operatorIds)->pluck('nama_kary', 'nik');
        $loggedInUser = auth()->user();

        return response()->view('print.manpower-certificate', [
            'certificateNo' => 'MP-'.now('Asia/Jakarta')->format('Ymd-His'),
            'operator' => $operator,
            'process' => $process,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'summary' => $summary,
            'performanceRows' => $performanceRows,
            'detailRows' => $detailRows,
            'detailTotal' => $detailTotal,
            'operatorNames' => $operatorNames,
            'printedBy' => $loggedInUser?->username ?? $loggedInUser?->name ?? session('username') ?? 'System',
            'printedAt' => now('Asia/Jakarta'),
        ]);
    }
}
