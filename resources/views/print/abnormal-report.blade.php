@php
    $formatDate = static function ($value, bool $withTime = true): string {
        if (! $value) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->timezone('Asia/Jakarta')->format($withTime ? 'd-m-Y H:i:s' : 'd-m-Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $value = static fn ($item, string $key, string $fallback = '-') => filled(data_get($item, $key)) ? data_get($item, $key) : $fallback;
    $escalatedTo = collect($approvalHistory)->map(fn ($row) => $row['approver'] ?? $row['nik'] ?? '-')->filter()->implode(', ');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abnormal Report - {{ $value($abnormal, 'noabn') }}</title>
    <style>
        :root { --ink:#15232b; --muted:#53656d; --line:#b7c3c8; --accent:#0b7285; --soft:#eef4f5; --success:#176b4d; --danger:#a12635; --warn:#9a6400; }
        * { box-sizing:border-box; }
        body { margin:0; background:#e8edef; color:var(--ink); font-family:Arial,Helvetica,sans-serif; font-size:10px; }
        .toolbar { display:flex; justify-content:space-between; gap:12px; max-width:1120px; margin:16px auto 0; padding:0 16px; }
        .toolbar a, .toolbar button { border:1px solid #8ea0a8; border-radius:5px; background:#fff; color:var(--ink); padding:8px 12px; font-size:12px; text-decoration:none; cursor:pointer; }
        .toolbar .primary { background:#0b7285; border-color:#0b7285; color:#fff; }
        .paper { width:min(1120px,calc(100% - 32px)); margin:12px auto 24px; padding:22px; background:#fff; box-shadow:0 4px 18px rgba(21,35,43,.18); }
        .header { display:grid; grid-template-columns:1fr auto; gap:20px; align-items:start; padding-bottom:12px; border-bottom:2px solid var(--ink); }
        .brand { color:var(--accent); font-size:11px; font-weight:700; letter-spacing:.18em; }
        h1 { margin:5px 0 3px; font-size:20px; letter-spacing:.02em; }
        .subtitle { color:var(--muted); font-size:10px; }
        .control-box { min-width:245px; border:1px solid var(--line); }
        .control-box div { display:grid; grid-template-columns:105px 1fr; border-bottom:1px solid var(--line); }
        .control-box div:last-child { border-bottom:0; }
        .control-box span { padding:5px 7px; }
        .control-box span:first-child { background:var(--soft); color:var(--muted); font-weight:700; }
        .control-box span:last-child { font-weight:700; }
        .notice { margin:12px 0; padding:8px 10px; border:1px solid #b7cbd0; background:#f5fafb; color:#29474f; line-height:1.45; }
        .section { margin-top:16px; break-inside:avoid; }
        .title { display:flex; align-items:center; gap:8px; margin:0 0 8px; padding:6px 8px; background:var(--ink); color:#fff; font-size:11px; letter-spacing:.04em; text-transform:uppercase; }
        .title::before { content:""; width:4px; height:14px; background:#2dd4bf; }
        .grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); border-top:1px solid var(--line); border-left:1px solid var(--line); }
        .field { min-height:42px; padding:6px 8px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); }
        .field.wide { grid-column:span 2; } .field.full { grid-column:1/-1; }
        .label { display:block; margin-bottom:4px; color:var(--muted); font-size:8px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .content { display:block; min-height:14px; overflow-wrap:anywhere; white-space:pre-wrap; line-height:1.35; }
        .emphasis { color:var(--accent); font-weight:700; } .status { color:var(--success); font-weight:700; text-transform:uppercase; } .warn { color:var(--warn); font-weight:700; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th, td { padding:6px; border:1px solid var(--line); text-align:left; vertical-align:top; overflow-wrap:anywhere; line-height:1.3; }
        th { background:var(--soft); color:var(--ink); font-size:8px; text-transform:uppercase; } td { font-size:9px; }
        .trace th:nth-child(1) { width:5%; } .trace th:nth-child(2) { width:18%; } .trace th:nth-child(3), .trace th:nth-child(4) { width:13%; } .trace th:nth-child(5) { width:9%; } .trace th:nth-child(6) { width:24%; } .trace th:nth-child(7), .trace th:nth-child(8), .trace th:nth-child(9) { width:6%; }
        .approval th:nth-child(1) { width:7%; } .approval th:nth-child(2) { width:14%; } .approval th:nth-child(3) { width:24%; } .approval th:nth-child(4) { width:13%; } .approval th:nth-child(5) { width:14%; } .approval th:nth-child(6) { width:18%; } .approval th:nth-child(7) { width:10%; }
        .approved { color:var(--success); font-weight:700; } .rejected { color:var(--danger); font-weight:700; } .waiting, .pending { color:var(--warn); font-weight:700; }
        .signatures { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:18px; page-break-inside:avoid; }
        .signature { min-height:88px; padding:8px; border:1px solid var(--line); }
        .signature-title { color:var(--muted); font-size:8px; font-weight:700; text-transform:uppercase; }
        .signature-line { margin-top:52px; padding-top:5px; border-top:1px solid var(--ink); font-size:9px; }
        .footer { display:flex; justify-content:space-between; gap:16px; margin-top:18px; padding-top:7px; border-top:1px solid var(--line); color:var(--muted); font-size:8px; }
        .empty { padding:12px; border:1px dashed var(--line); color:var(--muted); text-align:center; }
        @media (max-width:720px) { body { background:#fff; } .toolbar { padding:0 12px; } .paper { width:100%; margin:0; padding:14px 12px; box-shadow:none; } .header { grid-template-columns:1fr; } .control-box { min-width:0; } .grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .field.wide { grid-column:span 2; } .signatures { grid-template-columns:1fr; } .trace, .approval { min-width:900px; } .table-section { overflow-x:auto; } }
        @media print { body { background:#fff; } .toolbar { display:none; } .paper { width:100%; margin:0; padding:0; box-shadow:none; } .title, th { print-color-adjust:exact; -webkit-print-color-adjust:exact; } a { color:inherit; text-decoration:none; } }
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('abnormal.report') }}">Kembali ke Abnormal Report</a><button class="primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <main class="paper">
        <header class="header">
            <div><div class="brand">PAR MOBILE/ABNORMAL REPORT</div><h1>ABNORMAL REPORT</h1><div class="subtitle">Rekaman masalah, tindakan pengendalian, eskalasi, dan persetujuan</div></div>
            <div class="control-box"><div><span>Document ID</span><span>PAR-QA-ABN-001</span></div><div><span>Revision</span><span>00</span></div><div><span>Record ID</span><span>{{ $value($abnormal, 'noabn') }}</span></div><div><span>Status</span><span class="status">{{ strtoupper($value($abnormal, 'status')) }}</span></div></div>
        </header>
        <div class="notice"><strong>Controlled quality record.</strong> Dokumen ini memuat identitas laporan, traceability lot, tindakan koreksi, pihak yang menerima eskalasi, serta tanggal dan jam approval berdasarkan transaksi aplikasi PAR Mobile.</div>

        <section class="section"><h2 class="title">1. Identifikasi dan traceability</h2><div class="grid">
            <div class="field"><span class="label">No Abnormal Report</span><span class="content emphasis">{{ $value($abnormal, 'noabn') }}</span></div>
            <div class="field"><span class="label">Tanggal / Jam Kejadian</span><span class="content">{{ $formatDate($abnormal->tgl_problem ?? null) }}</span></div>
            <div class="field"><span class="label">Kategori</span><span class="content">{{ $value($abnormal, 'category') }}</span></div>
            <div class="field"><span class="label">Nama Object</span><span class="content">{{ $value($abnormal, 'nm_object') }}</span></div>
            <div class="field"><span class="label">Area Problem</span><span class="content">{{ $value($abnormal, 'nm_sequence', $value($abnormal, 'area')) }}</span></div>
            <div class="field"><span class="label">Ref Lot</span><span class="content emphasis">{{ $value($abnormal, 'ref_lot') }}</span></div>
            <div class="field"><span class="label">No SRP</span><span class="content">{{ $value($lot, 'no_srp') }}</span></div>
            <div class="field"><span class="label">No Part</span><span class="content">{{ $value($lot, 'no_part') }}</span></div>
            <div class="field"><span class="label">Nama Part</span><span class="content">{{ $value($lot, 'nm_part') }}</span></div>
            <div class="field"><span class="label">Customer</span><span class="content">{{ $value($lot, 'customer', 'INTERNAL') }}</span></div>
            <div class="field"><span class="label">No SJ Customer</span><span class="content">{{ $value($lot, 'no_sj_cust') }}</span></div>
            <div class="field"><span class="label">No Lot Customer</span><span class="content">{{ $value($lot, 'no_lot_cust') }}</span></div>
            <div class="field"><span class="label">Penemu Problem</span><span class="content">{{ $value($abnormal, 'nama_kary', $value($abnormal, 'found_by')) }}</span></div>
            <div class="field"><span class="label">PIC Handler</span><span class="content">{{ $handlerName }}</span></div>
            <div class="field"><span class="label">PIC Department</span><span class="content">{{ $value($abnormal, 'pic_dept_name', $value($abnormal, 'pic_dept')) }}</span></div>
            <div class="field"><span class="label">Status</span><span class="content status">{{ strtoupper($value($abnormal, 'status')) }}</span></div>
        </div></section>

        <section class="section"><h2 class="title">2. Problem dan tindakan pengendalian</h2><div class="grid">
            <div class="field full"><span class="label">Detail Problem</span><span class="content">{{ $value($abnormal, 'detail_problem') }}</span></div>
            <div class="field wide"><span class="label">Analisa Masalah / Penyebab</span><span class="content">{{ $value($abnormal, 'analisa') }}</span></div>
            <div class="field wide"><span class="label">Correction</span><span class="content">{{ $value($abnormal, 'correction') }}</span></div>
            <div class="field full"><span class="label">Corrective Action / Pencegahan Berulang</span><span class="content">{{ $value($abnormal, 'corrective') }}</span></div>
        </div></section>

        <section class="section table-section"><h2 class="title">3. Trace proses berdasarkan Ref Lot</h2>
            @if ($traceRows->isNotEmpty())<table class="trace"><thead><tr><th>Seq</th><th>Proses / Area</th><th>Start</th><th>Finish</th><th>Durasi</th><th>PIC / Operator</th><th>Hold</th><th>Finish</th><th>Akakami</th></tr></thead><tbody>@foreach ($traceRows as $trace)<tr><td>{{ $trace['seq'] ?? '-' }}</td><td>{{ $trace['process'] ?? '-' }}</td><td>{{ $formatDate($trace['start_time'] ?? null) }}</td><td>{{ $formatDate($trace['finish_time'] ?? null) }}</td><td>{{ $trace['duration'] ?? '-' }}</td><td>{{ $trace['pics'] ?: '-' }}</td><td>{{ $trace['hold'] ?? '-' }}</td><td>{{ $trace['finish'] ?? '-' }}</td><td>{{ $trace['akakami'] ?? '-' }}</td></tr>@endforeach</tbody></table>@else<div class="empty">Trace proses belum tersedia untuk Ref Lot ini.</div>@endif
        </section>

        <section class="section"><h2 class="title">4. Detail eskalasi dan pihak yang menerima</h2><div class="grid">
            <div class="field full"><span class="label">Eskalasi kepada</span><span class="content emphasis">{{ $escalatedTo ?: '-' }}</span></div>
            <div class="field"><span class="label">Jumlah Approver</span><span class="content">{{ count($approvalHistory) }}</span></div>
            <div class="field"><span class="label">PIC Pengajuan</span><span class="content">{{ $handlerName }}</span></div>
            <div class="field"><span class="label">Status Akhir</span><span class="content status">{{ strtoupper($value($abnormal, 'status')) }}</span></div>
            <div class="field"><span class="label">Printed By / At</span><span class="content">{{ $printedBy }} / {{ $formatDate($printedAt) }}</span></div>
        </div></section>

        <section class="section table-section"><h2 class="title">5. Riwayat approval lengkap</h2><table class="approval"><thead><tr><th>Seq</th><th>NIK</th><th>Approver</th><th>Role / Class</th><th>Status</th><th>Tanggal & Jam</th><th>Remark</th></tr></thead><tbody>
            <tr><td>Pengajuan</td><td>{{ $value($abnormal, 'pic_handler') }}</td><td>{{ $handlerName }}</td><td>PIC Handler</td><td class="approved">SUBMITTED</td><td>{{ $formatDate($abnormal->tgl_problem ?? null) }}</td><td>Laporan dibuat pada proses Abnormal Report</td></tr>
            @forelse ($approvalHistory as $approval)<tr><td>{{ $approval['seq_no'] ?? '-' }}</td><td>{{ $approval['nik'] ?? '-' }}</td><td>{{ $approval['approver'] ?? '-' }}</td><td>{{ $approval['role'] ?? '-' }}</td><td class="{{ strtolower((string) ($approval['status'] ?? '')) }}">{{ strtoupper($approval['status'] ?? '-') }}</td><td>{{ $formatDate($approval['approved_at'] ?? null) }}</td><td>{{ $approval['remark'] ?? '-' }}</td></tr>@empty<tr><td colspan="7">Tidak ada histori approval pada tabel `tbl_approval_abnreport`.</td></tr>@endforelse
        </tbody></table></section>

        <div class="signatures"><div class="signature"><div class="signature-title">Prepared / Submitted by</div><div class="signature-line">{{ $handlerName }}<br>{{ $formatDate($abnormal->tgl_problem ?? null) }}</div></div><div class="signature"><div class="signature-title">Verified by</div><div class="signature-line">Mengacu pada detail dan trace di atas</div></div><div class="signature"><div class="signature-title">Approved by</div><div class="signature-line">Mengacu pada histori approval di atas</div></div></div>
        <footer class="footer"><span>PAR-QA-ABN-001 | Rev. 00</span><span>Generated {{ $formatDate($printedAt) }} | Record {{ $value($abnormal, 'noabn') }}</span></footer>
    </main>
</body>
</html>
