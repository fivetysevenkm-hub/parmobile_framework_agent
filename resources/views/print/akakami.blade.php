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
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akakami QAQC - {{ $value($akakami, 'noflpp') }}</title>
    <style>
        :root { --ink:#15232b; --muted:#53656d; --line:#b7c3c8; --accent:#0b7285; --soft:#eef4f5; --success:#176b4d; --danger:#a12635; }
        * { box-sizing:border-box; }
        body { margin:0; background:#e8edef; color:var(--ink); font-family:Arial,Helvetica,sans-serif; font-size:10px; }
        .print-toolbar { display:flex; justify-content:space-between; gap:12px; max-width:1120px; margin:16px auto 0; padding:0 16px; }
        .print-toolbar button, .print-toolbar a { border:1px solid #8ea0a8; border-radius:5px; background:#fff; color:var(--ink); padding:8px 12px; font-size:12px; text-decoration:none; cursor:pointer; }
        .print-toolbar button.primary { background:#0b7285; border-color:#0b7285; color:#fff; }
        .paper { width:min(1120px,calc(100% - 32px)); margin:12px auto 24px; padding:22px; background:#fff; box-shadow:0 4px 18px rgba(21,35,43,.18); }
        .document-header { display:grid; grid-template-columns:1fr auto; gap:20px; align-items:start; padding-bottom:12px; border-bottom:2px solid var(--ink); }
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
        .section-title { display:flex; align-items:center; gap:8px; margin:0 0 8px; padding:6px 8px; background:var(--ink); color:#fff; font-size:11px; letter-spacing:.04em; text-transform:uppercase; }
        .section-title::before { content:""; width:4px; height:14px; background:#2dd4bf; }
        .field-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); border-top:1px solid var(--line); border-left:1px solid var(--line); }
        .field { min-height:42px; padding:6px 8px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); }
        .field.wide { grid-column:span 2; }
        .field.full { grid-column:1/-1; }
        .field-label { display:block; margin-bottom:4px; color:var(--muted); font-size:8px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .field-value { display:block; min-height:14px; overflow-wrap:anywhere; white-space:pre-wrap; line-height:1.35; }
        .field-value.emphasis { color:var(--accent); font-weight:700; }
        .field-value.status { color:var(--success); font-weight:700; text-transform:uppercase; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th, td { padding:6px 6px; border:1px solid var(--line); text-align:left; vertical-align:top; overflow-wrap:anywhere; line-height:1.3; }
        th { background:var(--soft); color:var(--ink); font-size:8px; text-transform:uppercase; }
        td { font-size:9px; }
        .trace-table th:nth-child(1) { width:5%; } .trace-table th:nth-child(2) { width:18%; } .trace-table th:nth-child(3), .trace-table th:nth-child(4) { width:13%; } .trace-table th:nth-child(5) { width:8%; } .trace-table th:nth-child(6) { width:10%; } .trace-table th:nth-child(7) { width:20%; } .trace-table th:nth-child(8), .trace-table th:nth-child(9), .trace-table th:nth-child(10) { width:5%; }
        .approval-table th:nth-child(1) { width:7%; } .approval-table th:nth-child(2) { width:13%; } .approval-table th:nth-child(3) { width:25%; } .approval-table th:nth-child(4) { width:14%; } .approval-table th:nth-child(5) { width:14%; } .approval-table th:nth-child(6) { width:17%; } .approval-table th:nth-child(7) { width:10%; }
        .status-approved { color:var(--success); font-weight:700; } .status-rejected { color:var(--danger); font-weight:700; } .status-waiting, .status-pending { color:#9a6400; font-weight:700; }
        .signature-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:18px; page-break-inside:avoid; }
        .signature { min-height:88px; padding:8px; border:1px solid var(--line); }
        .signature-title { color:var(--muted); font-size:8px; font-weight:700; text-transform:uppercase; }
        .signature-line { margin-top:52px; padding-top:5px; border-top:1px solid var(--ink); font-size:9px; }
        .footer { display:flex; justify-content:space-between; gap:16px; margin-top:18px; padding-top:7px; border-top:1px solid var(--line); color:var(--muted); font-size:8px; }
        .empty { padding:12px; border:1px dashed var(--line); color:var(--muted); text-align:center; }
        @media (max-width:720px) { body { background:#fff; } .print-toolbar { padding:0 12px; } .paper { width:100%; margin:0; padding:14px 12px; box-shadow:none; } .document-header { grid-template-columns:1fr; } .control-box { min-width:0; } .field-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .field.wide { grid-column:span 2; } .signature-grid { grid-template-columns:1fr; } .trace-table, .approval-table { min-width:850px; } .section.table-section { overflow-x:auto; } }
        @media print { body { background:#fff; } .print-toolbar { display:none; } .paper { width:100%; margin:0; padding:0; box-shadow:none; } .section { break-inside:auto; } .section-title, th { print-color-adjust:exact; -webkit-print-color-adjust:exact; } a { color:inherit; text-decoration:none; } }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <a href="{{ route('akakami.qaqc') }}">Kembali ke QAQC</a>
        <button class="primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="paper">
        <header class="document-header">
            <div>
                <div class="brand">PAR MOBILE / QUALITY CONTROL</div>
                <h1>AKAKAMI QUALITY ASSURANCE RECORD</h1>
                <div class="subtitle">Rekaman problem, trace proses, tindakan, dan approval FLPP</div>
            </div>
            <div class="control-box">
                <div><span>Document ID</span><span>PAR-QA-AKAKAMI-001</span></div>
                <div><span>Revision</span><span>00</span></div>
                <div><span>Record ID</span><span>{{ $value($akakami, 'noflpp') }}</span></div>
                <div><span>Status</span><span>{{ strtoupper($value($akakami, 'status_flpp')) }}</span></div>
            </div>
        </header>

        <div class="notice"><strong>Controlled quality record.</strong> Dokumen ini dibuat dari data transaksi aplikasi dan digunakan sebagai bukti telusur. Perubahan terhadap data sumber harus dilakukan melalui workflow berwenang agar histori approval tetap terjaga.</div>

        <section class="section">
            <h2 class="section-title">1. Identifikasi dan traceability</h2>
            <div class="field-grid">
                <div class="field"><span class="field-label">No. FLPP</span><span class="field-value emphasis">{{ $value($akakami, 'noflpp') }}</span></div>
                <div class="field"><span class="field-label">Tanggal Problem</span><span class="field-value">{{ $formatDate($akakami->tgl_problem ?? null) }}</span></div>
                <div class="field"><span class="field-label">No. Lot</span><span class="field-value emphasis">{{ $value($akakami, 'no_lots') }}</span></div>
                <div class="field"><span class="field-label">No. SRP</span><span class="field-value">{{ $value($masterSequence, 'no_srp') }}</span></div>
                <div class="field"><span class="field-label">No. Part</span><span class="field-value">{{ $value($masterSequence, 'no_part') }}</span></div>
                <div class="field"><span class="field-label">Nama Part</span><span class="field-value">{{ $value($masterSequence, 'nm_part') }}</span></div>
                <div class="field"><span class="field-label">Customer</span><span class="field-value">{{ $value($masterSequence, 'customer', 'INTERNAL') }}</span></div>
                <div class="field"><span class="field-label">No. SJ Customer</span><span class="field-value">{{ $value($masterSequence, 'no_sj_cust') }}</span></div>
                <div class="field"><span class="field-label">No. Lot Customer</span><span class="field-value">{{ $value($masterSequence, 'no_lot_cust') }}</span></div>
                <div class="field"><span class="field-label">Quantity Problem</span><span class="field-value emphasis">{{ $value($akakami, 'qty_problem', '0') }} PCS</span></div>
                <div class="field"><span class="field-label">Sequence / Area</span><span class="field-value">{{ $value(['value' => $sequenceName], 'value') }}</span></div>
                <div class="field"><span class="field-label">Penemu</span><span class="field-value">{{ $value(['value' => $foundBy], 'value', 'INTERNAL') }}</span></div>
                <div class="field"><span class="field-label">PIC Handler</span><span class="field-value">{{ $value(['value' => $handlerName], 'value', $value($akakami, 'pic_handler')) }}</span></div>
                <div class="field"><span class="field-label">Dept Bertanggung Jawab</span><span class="field-value">{{ $value(['value' => $picDepartment], 'value', $value($akakami, 'pic_dept')) }}</span></div>
                <div class="field"><span class="field-label">Rank</span><span class="field-value">{{ $value($akakami, 'rank') }}</span></div>
                <div class="field"><span class="field-label">Rank A</span><span class="field-value">{{ $value($akakami, 'rank_a') }}</span></div>
                <div class="field"><span class="field-label">Judgment Level</span><span class="field-value">{{ $value($akakami, 'judgment_level') }}</span></div>
                <div class="field"><span class="field-label">Tanggal Selesai / Approved</span><span class="field-value">{{ $formatDate($akakami->tgl_approved ?? null) }}</span></div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">2. Problem dan tindakan pengendalian</h2>
            <div class="field-grid">
                <div class="field wide"><span class="field-label">Jenis Problem</span><span class="field-value problem">{{ $problemName ?: $value($akakami, 'deskripsi_problem') }}</span></div>
                <div class="field wide"><span class="field-label">Deskripsi Problem</span><span class="field-value">{{ $value($akakami, 'deskripsi_problem') }}</span></div>
                <div class="field wide"><span class="field-label">Fishbone Category / 4M-1E</span><span class="field-value">{{ $value($akakami, 'fish_bone') }}</span></div>
                <div class="field wide"><span class="field-label">Analisa Penyebab</span><span class="field-value">{{ $value($akakami, 'analisa') }}</span></div>
                <div class="field wide"><span class="field-label">Tindakan Sementara</span><span class="field-value">{{ $value($akakami, 'temp_action') }}</span></div>
                <div class="field wide"><span class="field-label">Corrective Action</span><span class="field-value">{{ $value($akakami, 'corrective_act') }}</span></div>
            </div>
        </section>

        <section class="section table-section">
            <h2 class="section-title">3. Trace proses berdasarkan lot</h2>
            @if ($traceRows->isNotEmpty())
                <table class="trace-table">
                    <thead><tr><th>Seq</th><th>Process / Area</th><th>Start</th><th>Finish</th><th>Duration</th><th>Tray</th><th>PIC / Operator</th><th>Hold</th><th>Finish</th><th>Akakami</th></tr></thead>
                    <tbody>
                        @foreach ($traceRows as $trace)
                            <tr><td>{{ $trace['seq'] ?? '-' }}</td><td>{{ $trace['process'] ?? '-' }}</td><td>{{ $formatDate($trace['start_time'] ?? null) }}</td><td>{{ $formatDate($trace['finish_time'] ?? null) }}</td><td>{{ $trace['duration'] ?? '-' }}</td><td>{{ $trace['tray_no'] ?? '-' }}{{ filled($trace['tray_nokecil'] ?? null) ? ' / '.$trace['tray_nokecil'] : '' }}</td><td>{{ $trace['pics'] ?: '-' }}</td><td>{{ $trace['hold'] ?? '-' }}</td><td>{{ $trace['finish'] ?? '-' }}</td><td>{{ $trace['akakami'] ?? '-' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty">Trace proses belum tersedia untuk nomor lot ini.</div>
            @endif
        </section>

        <section class="section table-section">
            <h2 class="section-title">4. Histori approval dan otorisasi</h2>
            <table class="approval-table">
                <thead><tr><th>Seq</th><th>NIK</th><th>Approver</th><th>Role</th><th>Status</th><th>Tanggal / Jam</th><th>Remark</th></tr></thead>
                <tbody>
                    <tr><td>QAQC</td><td>{{ $value($akakami, 'pic_handler') }}</td><td>{{ $value(['value' => $handlerName], 'value', $value($akakami, 'pic_handler')) }}</td><td>Handler</td><td class="status-approved">{{ strtoupper($value($akakami, 'judgment_level')) }}</td><td>{{ $formatDate($akakami->tgl_approved ?? null) }}</td><td>Input / judgment pada proses Akakami</td></tr>
                    @forelse ($approvalHistory as $approval)
                        <tr><td>{{ $approval['seq_no'] ?? '-' }}</td><td>{{ $approval['nik'] ?? '-' }}</td><td>{{ $approval['approver'] ?? '-' }}</td><td>{{ $approval['role'] ?? '-' }}</td><td class="{{ 'status-'.strtolower((string) ($approval['status'] ?? '')) }}">{{ strtoupper($approval['status'] ?? '-') }}</td><td>{{ $formatDate($approval['approved_at'] ?? null) }}</td><td>{{ $approval['remark'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="7">Tidak ada baris approval lanjutan. Record menunjukkan judgment langsung pada proses Akakami atau histori approval lama belum tercatat pada tabel approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="signature-grid">
            <div class="signature"><div class="signature-title">Prepared / Printed by</div><div class="signature-line">{{ $printedBy }}<br>{{ $formatDate($printedAt) }}</div></div>
            <div class="signature"><div class="signature-title">Verified by</div><div class="signature-line">Nama / NIK / tanggal verifikasi</div></div>
            <div class="signature"><div class="signature-title">Approved by</div><div class="signature-line">Mengacu pada histori approval di atas</div></div>
        </div>

        <footer class="footer"><span>PAR-QA-AKAKAMI-001 | Rev. 00</span><span>Generated {{ $formatDate($printedAt) }} | Page record: {{ $value($akakami, 'noflpp') }}</span></footer>
    </main>
</body>
</html>
