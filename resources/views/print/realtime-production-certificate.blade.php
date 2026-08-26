@php
    $display = static fn (mixed $value, string $fallback = '-') => ($value === null || trim((string) $value) === '') ? $fallback : (string) $value;
    $statusClass = static fn (string $status): string => match ($status) {
        'Finish' => 'status-finish',
        'Hold' => 'status-hold',
        default => 'status-progress',
    };
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $certificateNo }} | Certificate of Conformance</title>
    <style>
        :root{--ink:#182228;--muted:#586a73;--line:#aebbc1;--soft:#edf2f4;--cyan:#007e98;--green:#147547;--orange:#a96100;--red:#a82736}
        *{box-sizing:border-box}html,body{margin:0;padding:0;background:#dfe5e8;color:var(--ink);font-family:Arial,Helvetica,sans-serif;font-size:9.5pt}.toolbar{position:sticky;top:0;z-index:10;display:flex;justify-content:center;gap:8px;padding:10px;background:#17232b;box-shadow:0 2px 10px #0003}.toolbar a,.toolbar button{padding:8px 13px;border:1px solid #607681;border-radius:5px;background:#22343e;color:#fff;font-size:9pt;font-weight:700;text-decoration:none;cursor:pointer}.toolbar .primary{background:#00b7d1;border-color:#00b7d1;color:#06171d}.paper{width:100%;max-width:1120px;margin:18px auto;padding:34px 38px 26px;background:#fff;box-shadow:0 2px 16px #0002}.header{display:grid;grid-template-columns:180px 1fr 250px;align-items:start;gap:16px;padding-bottom:14px;border-bottom:2px solid var(--ink)}.logo{width:145px;height:auto;max-height:60px;object-fit:contain;object-position:left center}.header-center{text-align:center}.company{font-size:9pt;font-weight:700;letter-spacing:.12em;color:var(--cyan);text-transform:uppercase}.title{margin:4px 0 2px;font-size:20pt;letter-spacing:.05em;font-weight:800}.subtitle{font-size:10pt;color:var(--muted);font-weight:700}.header-meta{text-align:right;color:var(--muted);font-size:8.5pt;line-height:1.55}.header-meta strong{color:var(--ink)}.document-title{margin:18px 0 8px;font-size:13pt;font-weight:800;letter-spacing:.04em;text-align:center}.notice{padding:10px 12px;border:1px solid #b5c7ce;background:#f2f8fa;color:#344850;line-height:1.45;text-align:justify}.section-title{margin:17px 0 8px;padding:7px 10px;border-left:4px solid var(--cyan);background:var(--soft);font-size:10pt;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.info-grid{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--line);border-left:1px solid var(--line)}.info-cell{min-height:42px;padding:7px 9px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}.info-label{display:block;margin-bottom:4px;color:var(--muted);font-size:7.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.04em}.info-value{display:block;font-weight:700;word-break:break-word}.process-card{display:grid;grid-template-columns:minmax(0,1fr) 142px;gap:16px;margin:0 0 11px;padding:12px;border:1px solid #778991;border-left:4px solid var(--cyan);border-radius:4px;page-break-inside:avoid}.process-main{min-width:0}.process-heading{display:flex;align-items:baseline;gap:8px;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid var(--line)}.seq{font-size:14pt;font-weight:800;color:var(--cyan)}.process-name{font-size:11pt;font-weight:800;text-transform:uppercase}.process-status{margin-left:auto;padding:3px 7px;border-radius:4px;background:var(--soft);font-size:8pt;font-weight:800}.status-finish{color:var(--green)}.status-progress{color:#1e63a4}.status-hold{color:var(--red)}.process-table{width:100%;border-collapse:collapse;table-layout:fixed}.process-table th,.process-table td{padding:5px 6px;border:1px solid var(--line);vertical-align:top;text-align:left;word-break:break-word}.process-table th{width:15%;background:var(--soft);color:var(--muted);font-size:7.5pt;text-transform:uppercase}.process-table td{width:18%;font-weight:600}.process-table th:nth-child(4){width:13%}.process-table td:nth-child(4){width:21%}.qr-panel{display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:8px;border:1px solid var(--line);background:#fff}.qr-panel svg{display:block;width:116px;height:116px}.qr-label{margin-top:7px;color:var(--muted);font-size:7.5pt;font-weight:700;text-align:center;text-transform:uppercase;letter-spacing:.04em}.qr-seq{margin-top:3px;color:var(--ink);font-size:9pt;font-weight:800}.qr-note{margin-top:8px;color:var(--muted);font-size:6.8pt;line-height:1.3;text-align:center}        .summary-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px}.summary-item{padding:8px;border:1px solid var(--line);background:var(--soft);text-align:center}.summary-item span{display:block;color:var(--muted);font-size:7.5pt;text-transform:uppercase}.summary-item strong{display:block;margin-top:3px;font-size:12pt}.pttn-continuation{break-before:page;page-break-before:always}.pttn-group{margin-bottom:14px;page-break-inside:avoid}.pttn-group-header{display:grid;grid-template-columns:minmax(0,1fr) 150px;gap:12px;align-items:center;margin-bottom:6px;padding:8px 10px;border:1px solid var(--line);border-left:4px solid var(--cyan);background:var(--soft)}.pttn-group-name{font-size:10pt;font-weight:800}.pttn-group-code{display:block;margin-bottom:3px;color:var(--cyan);font-size:8pt;font-weight:800;letter-spacing:.04em}.pttn-image{display:block;width:140px;max-height:85px;object-fit:contain;margin-left:auto;border:1px solid var(--line);background:#fff}.pttn-image-missing{display:flex;width:140px;height:62px;align-items:center;justify-content:center;margin-left:auto;border:1px solid var(--line);background:#fff;color:var(--muted);font-size:7.5pt;font-weight:700;text-align:center}.pttn-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:6.8pt}.pttn-table th,.pttn-table td{padding:4px 4px;border:1px solid var(--line);vertical-align:top;text-align:left;word-break:break-word}.pttn-table th{background:var(--ink);color:#fff;font-size:6.4pt;text-align:center;text-transform:uppercase}.pttn-table td.center{text-align:center}.pttn-table .status-ok{background:#d9f2df;color:var(--green);font-weight:800;text-align:center}.pttn-table .status-ng{background:#f8dadd;color:var(--red);font-weight:800;text-align:center}.pttn-table .status-empty{color:var(--muted);font-style:italic}.signatures{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-top:30px}.signature{min-height:70px;border-top:1px solid var(--ink);padding-top:6px;text-align:center;font-size:8.5pt}.footer{display:flex;justify-content:space-between;gap:10px;margin-top:18px;padding-top:8px;border-top:1px solid var(--line);color:var(--muted);font-size:7.5pt}.no-print{display:block}
        @media print{@page{size:A4 landscape;margin:10mm}.no-print{display:none!important}html,body{background:#fff;font-size:8.5pt}.paper{max-width:none;margin:0;padding:0;box-shadow:none}.header{grid-template-columns:145px 1fr 220px}.title{font-size:17pt}.section-title{margin-top:12px}.process-card{grid-template-columns:minmax(0,1fr) 125px;gap:10px;margin-bottom:7px;padding:8px}.process-table th,.process-table td{padding:4px 5px}.qr-panel svg{width:100px;height:100px}.pttn-group-header{grid-template-columns:minmax(0,1fr) 125px}.pttn-image,.pttn-image-missing{width:115px}.pttn-table{font-size:6.2pt}.pttn-table th{font-size:5.9pt}.pttn-table th,.pttn-table td{padding:3px 3px}.signatures{margin-top:18px}.footer{margin-top:10px}}
        @media(max-width:780px){.paper{margin:0;padding:18px 12px}.header{grid-template-columns:1fr;text-align:center}.logo{margin:0 auto}.header-meta{text-align:center}.info-grid{grid-template-columns:repeat(2,1fr)}.process-card{grid-template-columns:1fr}.qr-panel{order:-1}.pttn-group-header{grid-template-columns:1fr}.pttn-image,.pttn-image-missing{margin:0 auto}.pttn-table{font-size:7pt;display:block;overflow-x:auto}.pttn-table th,.pttn-table td{min-width:74px}.summary-strip,.signatures{grid-template-columns:1fr}.toolbar{position:static}}
    </style>
</head>
<body>
    <div class="toolbar no-print"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ url()->previous() }}">Kembali</a></div>
    <main class="paper">
        <header class="header">
            <img class="logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
            <div class="header-center"><div class="company">PT Dowa Thermotech Indonesia</div><div class="title">CERTIFICATE OF CONFORMANCE</div><div class="subtitle">SRP Process Traceability &amp; Production Position Record</div></div>
            <div class="header-meta"><div><strong>Certificate No.</strong><br>{{ $certificateNo }}</div><div style="margin-top:5px"><strong>Issued</strong><br>{{ $printedAt->format('d M Y H:i:s') }}</div><div><strong>Printed by</strong><br>{{ $printedBy }}</div></div>
        </header>

        <div class="document-title">SURAT SERTIFIKAT KETERTELUSURAN PROSES SRP</div>
        <div class="notice">Dokumen ini merupakan catatan elektronik ketertelusuran posisi proses produksi untuk No Lot yang tercantum. Informasi disusun berdasarkan data real-time pada PAR Mobile dan digunakan sebagai evidence monitoring proses. QR Code pada setiap sequence memuat identitas lot, SRP, sequence, proses, waktu mulai/selesai, dan status proses pada saat sertifikat diterbitkan.</div>

        <div class="section-title">A. General Information</div>
        <div class="info-grid">
            <div class="info-cell"><span class="info-label">No Lot DTI</span><span class="info-value">{{ $display($master['no_lot'] ?? null) }}</span></div>
            <div class="info-cell"><span class="info-label">No SRP</span><span class="info-value">{{ $display($master['no_srp'] ?? null) }}</span></div>
            <div class="info-cell"><span class="info-label">No Part</span><span class="info-value">{{ $display($master['no_part'] ?? null) }}</span></div>
            <div class="info-cell"><span class="info-label">Quantity</span><span class="info-value">{{ $display($master['qty'] ?? null) }}</span></div>
            <div class="info-cell"><span class="info-label">Nama Part</span><span class="info-value">{{ $display($master['nm_part'] ?? null) }}</span></div>
            <div class="info-cell"><span class="info-label">Customer</span><span class="info-value">{{ $display($master['customer'] ?? null) }}</span></div>
            <div class="info-cell"><span class="info-label">No Lot Customer</span><span class="info-value">{{ $display($master['no_lot_cust'] ?? null) }}</span></div>
            <div class="info-cell"><span class="info-label">No SJ Customer</span><span class="info-value">{{ $display($master['no_sj_cust'] ?? null) }}</span></div>
        </div>

        <div class="section-title">B. Record of Process</div>
        @foreach($processes as $process)
            <article class="process-card">
                <div class="process-main">
                    <div class="process-heading"><span class="seq">{{ $process['sequence_label'] }}</span><span class="process-name">{{ $display($process['nm_seq'] ?? null) }}</span><span class="process-status {{ $statusClass($process['status_text']) }}">{{ $process['status_text'] }}</span></div>
                    <table class="process-table"><tbody>
                        <tr><th>Start</th><td>{{ $display($process['start_time'] ?? null) }}</td><th>Finish</th><td>{{ $display($process['finish_time'] ?? null) }}</td></tr>
                        <tr><th>Duration</th><td>{{ $display($process['duration'] ?? null, '0') }} min</td><th>No Mesin</th><td>{{ $display($process['no_mesin'] ?? null) }}</td></tr>
                        <tr><th>No Tray</th><td>{{ $display($process['tray_no'] ?? null) }} / {{ $display($process['tray_nokecil'] ?? null) }}</td><th>OJT</th><td>{{ (($process['ojt'] ?? '') === 'yes') ? 'YES' : 'NO / -' }}</td></tr>
                        <tr><th>PIC 1</th><td>{{ $display($process['pic_01'] ?? null) }}</td><th>PIC 2</th><td>{{ $display($process['pic_02'] ?? null) }}</td></tr>
                        <tr><th>PIC 3</th><td>{{ $display($process['pic_03'] ?? null) }}</td><th>PIC 4</th><td>{{ $display($process['pic_04'] ?? null) }}</td></tr>
                        <tr><th>Akakami</th><td>{{ (($process['akakami'] ?? '') === 'yes') ? 'TERBIT / FLPP' : 'Tidak ada' }}</td><th>Updated</th><td>{{ $display($process['updated_at'] ?? null) }}</td></tr>
                    </tbody></table>
                </div>
                <div class="qr-panel">{!! $process['qr_svg'] !!}<div class="qr-label">QR Process</div><div class="qr-seq">{{ $process['sequence_label'] }}</div><div class="qr-note">Scan untuk memeriksa identitas sequence dan traceability proses.</div></div>
            </article>
        @endforeach

        <div class="summary-strip"><div class="summary-item"><span>Total Sequence</span><strong>{{ $processCount }}</strong></div><div class="summary-item"><span>No SRP</span><strong>{{ $display($master['no_srp'] ?? null) }}</strong></div><div class="summary-item"><span>Record Status</span><strong>REAL TIME</strong></div></div>

        @if($pttnGroups !== [])
            <section class="pttn-continuation">
                <div class="section-title">C. Actual PTTN Input &amp; Verification</div>
                <div class="notice">Halaman lanjutan ini menampilkan hasil pemeriksaan PTTN yang tersimpan untuk No Lot terkait. Nilai Standard, Actual, status pemeriksaan, PIC 1, PIC 2, serta tanggal dan jam update ditampilkan per parameter. Nilai kosong ditulis <strong>Belum diinput</strong>, sedangkan item dengan satuan Checkpoint menggunakan tanda <strong>-</strong> apabila belum memiliki data karena nilai checkpoint dikendalikan oleh checklist.</div>
                @foreach($pttnGroups as $group)
                    <article class="pttn-group">
                        <div class="pttn-group-header"><div><span class="pttn-group-code">{{ $group['sequence_label'] }} · PROCESS {{ $group['process_code'] }}</span><span class="pttn-group-name">{{ $group['process_name'] }} — PATTERN {{ $group['pattern_code'] }} · {{ $group['pattern_name'] }}</span></div>@if($group['image_data_uri'])<img class="pttn-image" src="{{ $group['image_data_uri'] }}" alt="Gambar pattern PTTN {{ $group['pattern_code'] }}">@else<div class="pttn-image-missing">Gambar PTTN<br>Belum diinput</div>@endif</div>
                        <table class="pttn-table"><thead><tr><th>Step</th><th>Point</th><th>Item Check</th><th>Satuan</th><th>Toleransi</th><th>Standard</th><th>Actual</th><th>Status</th><th>PIC 1</th><th>Update PIC 1</th><th>PIC 2</th><th>Update PIC 2</th></tr></thead><tbody>
                            @foreach($group['rows'] as $row)
                                @php $statusClass = match(strtoupper((string) ($row['status_check'] ?? ''))) { 'OK' => 'status-ok', 'NG' => 'status-ng', default => 'status-empty' }; @endphp
                                <tr><td class="center">{{ $display($row['step_no'] ?? null) }}</td><td>{{ $display($row['point'] ?? null) }}</td><td>{{ $display($row['item_name'] ?? null) }}</td><td class="center">{{ $display($row['satuan'] ?? null) }}</td><td class="center">{{ $display($row['toleransi'] ?? null) }}</td><td>{{ $row['standard_display'] }}</td><td>{{ $row['actual_display'] }}</td><td class="{{ $statusClass }}">{{ $row['status_display'] }}</td><td>{{ $row['pic1_display'] }}</td><td>{{ $row['datetime1_display'] }}</td><td>{{ $row['pic2_display'] }}</td><td>{{ $row['datetime2_display'] }}</td></tr>
                            @endforeach
                        </tbody></table>
                    </article>
                @endforeach
            </section>
        @endif

        <div class="section-title">D. Authorization and Record Control</div>
        <div class="notice">Sertifikat ini dibuat dari data aplikasi pada waktu penerbitan. Perubahan status proses setelah waktu penerbitan tidak mengubah salinan sertifikat ini. Setiap QR Code bersifat unik untuk satu No Lot dan satu sequence pada waktu sertifikat dibuat.</div>
        <div class="signatures"><div class="signature">Prepared / Printed by<br><strong>{{ $printedBy }}</strong></div><div class="signature">Production / Process Verification<br><br>________________________</div><div class="signature">Quality Assurance Review<br><br>________________________</div></div>
        <footer class="footer"><span>PAR Mobile · SRP Process Traceability</span><span>Certificate: {{ $certificateNo }}</span><span>Generated: {{ $printedAt->format('d/m/Y H:i:s') }}</span></footer>
    </main>
</body>
</html>
