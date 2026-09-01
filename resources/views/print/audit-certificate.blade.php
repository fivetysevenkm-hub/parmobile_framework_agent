@php
    $formatDate = static function ($value, bool $withTime = true): string {
        if (! $value) return '-';
        try { return \Carbon\Carbon::parse($value)->timezone('Asia/Jakarta')->format($withTime ? 'd-m-Y H:i:s' : 'd-m-Y'); }
        catch (\Throwable) { return (string) $value; }
    };
    $filterLabel = static function (array $f): string {
        $parts = [];
        if ($f['search'] !== '') $parts[] = 'Kata kunci: "'.$f['search'].'"';
        if ($f['username'] !== '') $parts[] = 'User/NIK: '.$f['username'];
        if ($f['module'] !== '') $parts[] = 'Module: '.$f['module'];
        if ($f['event'] !== '') $parts[] = 'Event: '.$f['event'];
        if (empty($parts)) $parts[] = 'Semua data pada periode filter';
        return implode(' · ', $parts);
    };
    $period = static function (?string $from, ?string $to) use ($formatDate): string {
        if ($from && $to) return $formatDate($from, false).' s/d '.$formatDate($to, false);
        return $from ? 'dari '.$formatDate($from, false) : ($to ? 'sampai '.$formatDate($to, false) : 'Semua periode');
    };
    $eventGroupName = static function (string $group): string {
        return match ($group) {
            'authentication' => 'Authentication',
            'access' => 'Akses Menu',
            'data_change' => 'Perubahan Data',
            default => $group,
        };
    };
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sertifikat Audit Trail - {{ $certificateNo }}</title>
    <style>
        :root{--ink:#15232b;--muted:#53656d;--line:#b7c3c8;--accent:#0b7285;--soft:#eef4f5;--success:#176b4d;--warning:#946200}
        *{box-sizing:border-box}body{margin:0;background:#e8edef;color:var(--ink);font-family:Arial,Helvetica,sans-serif;font-size:10px}.print-toolbar{display:flex;justify-content:space-between;gap:12px;max-width:1220px;margin:16px auto 0;padding:0 16px}.print-toolbar a,.print-toolbar button{border:1px solid #8ea0a8;border-radius:5px;background:#fff;color:var(--ink);padding:8px 12px;font-size:12px;text-decoration:none;cursor:pointer}.print-toolbar button.primary{background:#0b7285;border-color:#0b7285;color:#fff}.paper{width:min(1220px,calc(100% - 32px));margin:12px auto 24px;padding:22px;background:#fff;box-shadow:0 4px 18px rgba(21,35,43,.18)}.document-header{display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start;padding-bottom:12px;border-bottom:2px solid var(--ink)}.brand{color:var(--accent);font-size:11px;font-weight:700;letter-spacing:.18em}.document-header h1{margin:5px 0 3px;font-size:20px;letter-spacing:.02em}.subtitle{color:var(--muted);font-size:10px}.control-box{min-width:270px;border:1px solid var(--line)}.control-box div{display:grid;grid-template-columns:110px 1fr;border-bottom:1px solid var(--line)}.control-box div:last-child{border-bottom:0}.control-box span{padding:5px 7px}.control-box span:first-child{background:var(--soft);color:var(--muted);font-weight:700}.control-box span:last-child{font-weight:700}.notice{margin:12px 0;padding:8px 10px;border:1px solid #b7cbd0;background:#f5fafb;color:#29474f;line-height:1.45}.section{margin-top:16px;break-inside:avoid}.section-title{display:flex;align-items:center;gap:8px;margin:0 0 8px;padding:6px 8px;background:var(--ink);color:#fff;font-size:11px;letter-spacing:.04em;text-transform:uppercase}.section-title:before{content:"";width:4px;height:14px;background:#2dd4bf}.field-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border-top:1px solid var(--line);border-left:1px solid var(--line)}.field{min-height:42px;padding:6px 8px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}.field.wide{grid-column:span 2}.field.full{grid-column:1/-1}.field-label{display:block;margin-bottom:4px;color:var(--muted);font-size:8px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}.field-value{display:block;min-height:14px;overflow-wrap:anywhere;line-height:1.35}.field-value.emphasis{color:var(--accent);font-weight:700}.kpi-table,table{width:100%;border-collapse:collapse;table-layout:fixed}.kpi-table th,.kpi-table td,table th,table td{padding:6px;border:1px solid var(--line);text-align:left;vertical-align:top;overflow-wrap:anywhere;line-height:1.3}.kpi-table th,table th{background:var(--soft);color:var(--ink);font-size:8px;text-transform:uppercase}.kpi-table td{font-size:12px;font-weight:700}.kpi-table td small{display:block;margin-top:3px;color:var(--muted);font-size:8px;font-weight:400}.data-table td{font-size:8.5px}.data-table th{text-align:center}.data-table td.num{text-align:right;white-space:nowrap}.status-good{color:var(--success);font-weight:700}.status-review{color:var(--warning);font-weight:700}.status-empty{color:var(--muted)}.evaluation-table th{width:24%}.evaluation-table td{height:30px}.evaluation-table .blank{height:42px}.signature-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:18px;page-break-inside:avoid}.signature{min-height:105px;padding:8px;border:1px solid var(--line)}.signature-title{color:var(--muted);font-size:8px;font-weight:700;text-transform:uppercase}.signature-line{margin-top:62px;padding-top:5px;border-top:1px solid var(--ink);font-size:9px}.footer{display:flex;justify-content:space-between;gap:16px;margin-top:18px;padding-top:7px;border-top:1px solid var(--line);color:var(--muted);font-size:8px}.small-note{margin-top:6px;color:var(--muted);font-size:8px;line-height:1.4}.empty{padding:12px;border:1px dashed var(--line);color:var(--muted);text-align:center}@media(max-width:720px){body{background:#fff}.print-toolbar{padding:0 12px}.paper{width:100%;margin:0;padding:14px 12px;box-shadow:none}.document-header{grid-template-columns:1fr}.control-box{min-width:0}.field-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.field.wide{grid-column:span 2}.signature-grid{grid-template-columns:1fr}.section.table-section{overflow-x:auto}.data-table{min-width:1000px}}@media print{@page{size:A4 landscape;margin:10mm}body{background:#fff}.print-toolbar{display:none}.paper{width:100%;margin:0;padding:0;box-shadow:none}.section{break-inside:auto}.section-title,th{print-color-adjust:exact;-webkit-print-color-adjust:exact}a{color:inherit;text-decoration:none}.data-table{page-break-inside:auto}.data-table tr{page-break-inside:avoid}}
    </style>
</head>
<body>
    <div class="print-toolbar"><a href="{{ route('user-management.audit-trail') }}">Kembali ke Audit Trail</a><button class="primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <main class="paper">
        <header class="document-header">
            <div><div class="brand">PAR MOBILE / IATF 16949 : 2016</div><h1>AUDIT TRAIL CERTIFICATE</h1><div class="subtitle">Rekaman jejak aktivitas aplikasi untuk ketertelusuran: autentikasi, akses menu, dan perubahan data</div></div>
            <div class="control-box"><div><span>Document ID</span><span>PAR-QMS-AUD-001</span></div><div><span>Revision</span><span>00</span></div><div><span>Certificate No.</span><span>{{ $certificateNo }}</span></div><div><span>Generated</span><span>{{ $formatDate($printedAt) }}</span></div></div>
        </header>
        <div class="notice"><strong>Controlled documented information.</strong> Dokumen ini merupakan rekaman elektronik ketertelusuran aktivitas pengguna pada PAR Mobile sesuai persyaratan IATF 16949:2016 (ketertelusuran dan bukti pelaksanaan). Sertifikat dibuat berdasarkan filter yang dipilih pada saat penerbitan; data setelah waktu penerbitan tidak mengubah salinan dokumen ini.</div>

        <section class="section"><h2 class="section-title">1. Identifikasi ruang lingkup dan filter</h2><div class="field-grid">
            <div class="field full"><span class="field-label">Filter yang diterapkan</span><span class="field-value emphasis">{{ $filterLabel($filters) }}</span></div>
            <div class="field"><span class="field-label">Periode Mulai</span><span class="field-value">{{ $filters['dateFrom'] ? $formatDate($filters['dateFrom'], false) : '-' }}</span></div>
            <div class="field"><span class="field-label">Periode Akhir</span><span class="field-value">{{ $filters['dateTo'] ? $formatDate($filters['dateTo'], false) : '-' }}</span></div>
            <div class="field"><span class="field-label">Disiapkan / Dicetak oleh</span><span class="field-value">{{ $printedBy }}</span></div>
            <div class="field"><span class="field-label">Sumber Evidence</span><span class="field-value">tbl_audit_trail</span></div>
            <div class="field"><span class="field-label">Total Log Terinklusi</span><span class="field-value">{{ number_format($total) }} log</span></div>
            <div class="field wide"><span class="field-label">Metode Pengambilan</span><span class="field-value">Filter (kata kunci / event / module / user / periode) diterapkan pada seluruh rekaman sebelum agregasi sertifikat.</span></div>
        </div></section>

        <section class="section"><h2 class="section-title">2. Ringkasan aktivitas</h2><div class="kpi-table"><table>
            <thead><tr><th>Total Log</th><th>Authentication</th><th>Akses Menu</th><th>Perubahan Data</th></tr></thead>
            <tbody><tr>
                <td>{{ number_format($summary['total']) }}<small>seluruh rekaman dalam cakupan</small></td>
                <td>{{ number_format($summary['auth']) }}<small>login / logout / password</small></td>
                <td>{{ number_format($summary['menu']) }}<small>akses dan penolakan menu</small></td>
                <td>{{ number_format($summary['data']) }}<small>create / update / delete</small></td>
            </tr></tbody></table></div>
            <div class="small-note">Agregasi dihitung dari seluruh rekaman yang cocok dengan filter (bukan hanya yang tampil). Detail rekaman tetap dilampirkan sebagai bukti pada bagian berikutnya.</div>
        </section>

        <section class="section"><h2 class="section-title">3. Rekap aktivitas per peristiwa</h2><table class="data-table"><thead><tr><th>Kategori</th><th>Peristiwa</th><th>Jumlah</th></tr></thead><tbody>
            @php($grouped = ['authentication' => ['login','login_failed','logout','password_change'], 'access' => ['access','access_denied'], 'data_change' => ['create','update','delete']])
            @foreach($grouped as $group => $events)
                @foreach($events as $ev)
                    <tr><td>{{ $eventGroupName($group) }}</td><td>{{ $eventLabels[$ev] ?? $ev }}</td><td class="num">{{ number_format($summary[$ev]) }}</td></tr>
                @endforeach
            @endforeach
            <tr><td colspan="2"><strong>Total</strong></td><td class="num"><strong>{{ number_format($summary['total']) }}</strong></td></tr>
        </tbody></table></section>

        <section class="section table-section"><h2 class="section-title">4. Bukti detail rekaman aktivitas</h2>@if($rows->isNotEmpty())<table class="data-table"><thead><tr><th>Waktu</th><th>User</th><th>NIK</th><th>Event</th><th>Module</th><th>Pernyataan</th><th>IP</th></tr></thead><tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $formatDate($row->created_at) }}</td>
                    <td>{{ $row->username ?? '-' }}</td>
                    <td>{{ $row->user_id ?? '-' }}</td>
                    <td>{{ $eventLabels[$row->event] ?? $row->event }}</td>
                    <td>{{ $row->module ?? '-' }}</td>
                    <td>{{ $row->action ?? '-' }}</td>
                    <td>{{ $row->ip_address ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody></table>@if($total > $rows->count())<div class="small-note">Menampilkan {{ number_format($rows->count()) }} dari {{ number_format($total) }} rekaman untuk menjaga ukuran dokumen. Ringkasan KPI tetap dihitung dari seluruh hasil filter.</div>@endif @else<div class="empty">Tidak ada rekaman audit pada filter yang dipilih.</div>@endif</section>

        <section class="section"><h2 class="section-title">5. Evaluasi dan pembuktian ketertelusuran</h2><table class="evaluation-table"><tbody>
            <tr><th>Rekaman autentikasi tersedia (login/logout/password)</th><td class="blank">☐ Cukup &nbsp;&nbsp; ☐ Perlu perbaikan</td><th>Rekaman akses menu tersedia</th><td class="blank">☐ Cukup &nbsp;&nbsp; ☐ Perlu perbaikan</td></tr>
            <tr><th>Rekaman perubahan data tersedia</th><td class="blank">☐ Cukup &nbsp;&nbsp; ☐ Perlu perbaikan</td><th>Metode verifikasi</th><td class="blank">☐ Sampling &nbsp;&nbsp; ☐ Review data &nbsp;&nbsp; ☐ Observasi</td></tr>
            <tr><th>Temuan / gap serta tindak lanjut</th><td colspan="3" class="blank"></td></tr>
            <tr><th>Tindakan perbaikan dan PIC</th><td colspan="3" class="blank"></td></tr>
            <tr><th>Target tanggal review ulang</th><td></td><th>Referensi catatan mutu</th><td></td></tr>
        </tbody></table><div class="small-note">Evaluasi diisi dan ditandatangani oleh personel yang ditunjuk (Quality Management Representative / Internal Auditor). Bukti tinjauan manajemen atau audit internal dapat dilampirkan dengan mencantumkan nomor referensi.</div></section>

        <div class="signature-grid"><div class="signature"><div class="signature-title">Prepared / Printed by</div><div class="signature-line">{{ $printedBy }}<br>{{ $formatDate($printedAt) }}</div></div><div class="signature"><div class="signature-title">Evaluated by</div><div class="signature-line">Nama / NIK / tanggal</div></div><div class="signature"><div class="signature-title">Verified by QMR</div><div class="signature-line">Nama / NIK / tanggal</div></div><div class="signature"><div class="signature-title">Internal Auditor</div><div class="signature-line">Nama / NIK / tanggal</div></div></div>
        <footer class="footer"><span>PAR-QMS-AUD-001 | Rev. 00 | Controlled record — IATF 16949:2016</span><span>Generated {{ $formatDate($printedAt) }} | Certificate {{ $certificateNo }}</span></footer>
    </main>
</body>
</html>