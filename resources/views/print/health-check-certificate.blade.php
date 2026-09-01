@php
    $fmt = static fn($n) => number_format((float)$n);
    $fmtBytes = static function($b) { $b = (float)$b; $u = ['B','KB','MB','GB','TB']; $i = 0; while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; } return number_format($b, $i ? 1 : 0).' '.$u[$i]; };
    $formatDate = static function ($value, bool $withTime = true): string {
        if (! $value) return '-';
        try { return \Carbon\Carbon::parse($value)->timezone('Asia/Jakarta')->format($withTime ? 'd-m-Y H:i:s' : 'd-m-Y'); }
        catch (\Throwable) { return (string) $value; }
    };
    $isPar = ($type ?? '') === 'PAR';
    $prev = $previous ?? [];
    $audit = $audit ?? $data['audit'] ?? [];
    $auditTotal = (int) ($audit_total ?? array_sum($audit));
    $db = $db ?? $data['db'] ?? [];
    $syntax = $syntax ?? $data['syntax'] ?? [];
    $dbOk = (bool) ($db['ok'] ?? false);
    $syntaxOk = (bool) ($syntax['ok'] ?? false);
    $eventLabels = ['login' => 'Login', 'login_failed' => 'Login failed', 'logout' => 'Logout', 'password_change' => 'Password change', 'access' => 'Menu access', 'access_denied' => 'Access denied', 'create' => 'Record created', 'update' => 'Record updated', 'delete' => 'Record deleted'];
    $bars = [
        ['label' => 'Records', 'cur' => (float)($record_total ?? 0), 'prev' => (float)($prev['record_total'] ?? 0)],
        ['label' => 'Size (MB)', 'cur' => (float)($bytes_total ?? 0) / 1048576, 'prev' => (float)($prev['bytes_total'] ?? 0) / 1048576],
        ['label' => 'Tables', 'cur' => (float)($tables_scanned ?? 0), 'prev' => (float)($prev['tables_scanned'] ?? 0)],
        ['label' => 'New users', 'cur' => (float)($new_users ?? 0), 'prev' => (float)($prev['new_users'] ?? 0)],
        ['label' => 'Audit logs', 'cur' => (float)$auditTotal, 'prev' => (float)($prev['audit_total'] ?? 0)],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Health Check' }} Certificate — {{ $certificate_no ?? '' }}</title>
    <style>
        :root{--ink:#15232b;--muted:#53656d;--line:#b7c3c8;--accent:#0b7285;--soft:#eef4f5;--success:#176b4d;--warning:#946200}
        *{box-sizing:border-box}body{margin:0;background:#e8edef;color:var(--ink);font-family:Arial,Helvetica,sans-serif;font-size:10px}.print-toolbar{display:flex;justify-content:space-between;gap:12px;max-width:1220px;margin:16px auto 0;padding:0 16px}.print-toolbar a,.print-toolbar button{border:1px solid #8ea0a8;border-radius:5px;background:#fff;color:var(--ink);padding:8px 12px;font-size:12px;text-decoration:none;cursor:pointer}.print-toolbar button.primary{background:#0b7285;border-color:#0b7285;color:#fff}.paper{width:min(1220px,calc(100% - 32px));margin:12px auto 24px;padding:22px;background:#fff;box-shadow:0 4px 18px rgba(21,35,43,.18)}.document-header{display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start;padding-bottom:12px;border-bottom:2px solid var(--ink)}.brand{color:var(--accent);font-size:11px;font-weight:700;letter-spacing:.18em}.document-header h1{margin:5px 0 3px;font-size:20px;letter-spacing:.02em}.subtitle{color:var(--muted);font-size:10px}.control-box{min-width:270px;border:1px solid var(--line)}.control-box div{display:grid;grid-template-columns:110px 1fr;border-bottom:1px solid var(--line)}.control-box div:last-child{border-bottom:0}.control-box span{padding:5px 7px}.control-box span:first-child{background:var(--soft);color:var(--muted);font-weight:700}.control-box span:last-child{font-weight:700}.notice{margin:12px 0;padding:8px 10px;border:1px solid #b7cbd0;background:#f5fafb;color:#29474f;line-height:1.45}.section{margin-top:16px;break-inside:avoid}.section-title{display:flex;align-items:center;gap:8px;margin:0 0 8px;padding:6px 8px;background:var(--ink);color:#fff;font-size:11px;letter-spacing:.04em;text-transform:uppercase}.section-title:before{content:"";width:4px;height:14px;background:#2dd4bf}.field-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border-top:1px solid var(--line);border-left:1px solid var(--line)}.field{min-height:42px;padding:6px 8px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}.field.wide{grid-column:span 2}.field.full{grid-column:1/-1}.field-label{display:block;margin-bottom:4px;color:var(--muted);font-size:8px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}.field-value{display:block;min-height:14px;overflow-wrap:anywhere;line-height:1.35}.field-value.emphasis{color:var(--accent);font-weight:700}.kpi-table,table{width:100%;border-collapse:collapse;table-layout:fixed}.kpi-table th,.kpi-table td,table th,table td{padding:6px;border:1px solid var(--line);text-align:left;vertical-align:top;overflow-wrap:anywhere;line-height:1.3}.kpi-table th,table th{background:var(--soft);color:var(--ink);font-size:8px;text-transform:uppercase}.kpi-table td{font-size:12px;font-weight:700}.kpi-table td small{display:block;margin-top:3px;color:var(--muted);font-size:8px;font-weight:400}.data-table td{font-size:8.5px}.data-table th{text-align:center}.data-table td.num{text-align:right;white-space:nowrap}.status-good{color:var(--success);font-weight:700}.status-review{color:var(--warning);font-weight:700}.status-empty{color:var(--muted)}.evaluation-table th{width:24%}.evaluation-table td{height:30px}.evaluation-table .blank{height:42px}.signature-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:18px;page-break-inside:avoid}.signature{min-height:105px;padding:8px;border:1px solid var(--line)}.signature-title{color:var(--muted);font-size:8px;font-weight:700;text-transform:uppercase}.signature-line{margin-top:62px;padding-top:5px;border-top:1px solid var(--ink);font-size:9px}.footer{display:flex;justify-content:space-between;gap:16px;margin-top:18px;padding-top:7px;border-top:1px solid var(--line);color:var(--muted);font-size:8px}.small-note{margin-top:6px;color:var(--muted);font-size:8px;line-height:1.4}.empty{padding:12px;border:1px dashed var(--line);color:var(--muted);text-align:center}@media(max-width:720px){body{background:#fff}.print-toolbar{padding:0 12px}.paper{width:100%;margin:0;padding:14px 12px;box-shadow:none}.document-header{grid-template-columns:1fr}.control-box{min-width:0}.field-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.field.wide{grid-column:span 2}.signature-grid{grid-template-columns:1fr}.section.table-section{overflow-x:auto}.data-table{min-width:1000px}}@media print{@page{size:A4 landscape;margin:10mm}body{background:#fff}.print-toolbar{display:none}.paper{width:100%;margin:0;padding:0;box-shadow:none}.section{break-inside:auto}.section-title,th{print-color-adjust:exact;-webkit-print-color-adjust:exact}a{color:inherit;text-decoration:none}.data-table{page-break-inside:auto}.data-table tr{page-break-inside:avoid}}
        .hc-kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:4px 0 8px}.hc-kpi>div{border:1px solid var(--line);border-top:3px solid var(--accent);background:#fafdfd;padding:8px}.hc-kpi .k{display:block;color:var(--muted);font-size:8px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}.hc-kpi .v{display:block;margin-top:3px;font-size:16px;font-weight:700;color:var(--ink)}.hc-kpi .s{display:block;margin-top:2px;color:var(--muted);font-size:8px}.hc-bars{display:flex;flex-direction:column;gap:7px}.hc-bars .row{display:grid;grid-template-columns:110px 1fr 90px;gap:8px;align-items:center;font-size:8.5px}.hc-bars .row .t{color:var(--muted);font-weight:700;text-align:right;text-transform:uppercase;font-size:7.5px}.hc-bar{background:var(--soft);border-radius:2px;height:11px}.hc-bar.cur{background:linear-gradient(90deg,#0b7285,#2dd4bf)}.hc-bar.prev{background:linear-gradient(90deg,#b78a1e,#e0b04a)}.hc-val{color:var(--ink);font-size:8.5px}.hc-legend{display:flex;gap:14px;margin-top:7px;color:var(--muted);font-size:8px}.hc-legend i{display:inline-block;width:12px;height:12px;border-radius:2px;margin-right:5px;vertical-align:-2px}.hc-health{display:grid;grid-template-columns:1fr 1fr;gap:8px}.hc-health-card{border:1px solid var(--line);background:#fafdfd;padding:8px}.hc-health-card h3{margin:0 0 6px;color:var(--ink);font-size:9px;text-transform:uppercase;letter-spacing:.04em}.hc-health-card ul{margin:0;padding-left:16px;font-size:8.5px;line-height:1.6}.hc-tag{display:inline-block;padding:2px 8px;border-radius:10px;font-weight:700;font-size:8px;letter-spacing:.04em}.hc-tag.ok{background:#e2f0e8;color:var(--success)}.hc-tag.warn{background:#fbf0dc;color:var(--warning)}.hc-cmp-td,tbody td{vertical-align:top}.hc-cmp td.num{text-align:right;white-space:nowrap}.hc-num{text-align:right}.hc-up{color:var(--success);font-weight:700}.hc-down{color:#a33;font-weight:700}@media(max-width:720px){.hc-kpi{grid-template-columns:repeat(2,minmax(0,1fr))}.hc-health{grid-template-columns:1fr}.hc-bars .row{grid-template-columns:1fr}.hc-bars .row .t{text-align:left}}
    </style>
</head>
<body>
    <div class="print-toolbar"><a href="{{ route('health-check') }}">Back to Health Check</a><button class="primary" type="button" onclick="window.print()">Print / Save PDF</button></div>
    <main class="paper">
        <header class="document-header">
            <div><div class="brand">CV. AMANAH MUDA BERKARYA</div><h1>{{ $isPar ? 'PAR HEALTH CHECK CERTIFICATE' : 'SMS HEALTH CHECK CERTIFICATE' }}</h1><div class="subtitle">{{ $isPar
                ? 'Certified periodic assessment of the PAR database and application program health across all production and support tables (excluding the SMS source tables).'
                : 'Certified periodic assessment of the SMS source tables (tblsrp and tbllotdaily) supporting the Shared Management System maintenance record.' }}</div></div>
            <div class="control-box"><div><span>Document ID</span><span>FOR-AMB-{{ $isPar ? 'PAR' : 'SMS' }}001</span></div><div><span>Revision</span><span>00</span></div><div><span>Certificate No.</span><span>{{ $certificate_no ?? '-' }}</span></div><div><span>Report Date</span><span>{{ $lampiran_date ? $formatDate($lampiran_date, false) : '-' }}</span></div></div>
        </header>
        <div class="notice"><strong>Controlled documented information.</strong> This certificate forms the official attachment to the monthly maintenance-fee minutes of meeting (Berita Acara) and certifies the operational health of the {{ $isPar ? 'PAR' : 'SMS' }} environment as of the report date. All figures are a point-in-time snapshot taken at certificate generation; the record is stored for traceability.</div>

        <section class="section"><h2 class="section-title">1. Scope &amp; reporting period</h2>
            <div class="field-grid">
                <div class="field"><span class="field-label">Reference period</span><span class="field-value">{{ $period_label ?? sprintf('%04d-%02d', $year ?? 0, $month ?? 0) }}</span></div>
                <div class="field"><span class="field-label">Comparison period</span><span class="field-value">{{ $previous_period_label ?? '-' }}</span></div>
                <div class="field"><span class="field-label">Prepared by</span><span class="field-value">{{ $creator ?: '-' }}</span></div>
                <div class="field"><span class="field-label">Checked at</span><span class="field-value">{{ $formatDate($checked_at ?? $reference_date ?? now()) }}</span></div>
                <div class="field"><span class="field-label">Tables scanned</span><span class="field-value emphasis">{{ $fmt($tables_scanned ?? 0) }} tables</span></div>
                <div class="field"><span class="field-label">Scope type</span><span class="field-value">{{ $isPar ? 'All DB tables (non-SMS)' : 'tblsrp + tbllotdaily' }}</span></div>
                <div class="field"><span class="field-label">Generated from snapshot</span><span class="field-value">{{ ($from_snapshot ?? false) ? 'Yes' : 'Live calculation' }}</span></div>
                <div class="field"><span class="field-label">Source</span><span class="field-value">information_schema / tbl_audit_trail / tbl_user</span></div>
            </div>
        </section>

        <section class="section"><h2 class="section-title">2. Key indicators</h2>
            <div class="hc-kpi">
                <div><span class="k">Tables scanned</span><span class="v">{{ $fmt($tables_scanned ?? 0) }}</span><span class="s">{{ $isPar ? 'all non-SMS' : 'SMS source' }} tables</span></div>
                <div><span class="k">Total records</span><span class="v">{{ $fmt($record_total ?? 0) }}</span><span class="s">rows in scope</span></div>
                <div><span class="k">Total size</span><span class="v">{{ $fmtBytes($bytes_total ?? 0) }}</span><span class="s">data + index</span></div>
                <div><span class="k">New users added</span><span class="v">{{ $fmt($new_users ?? 0) }}</span><span class="s">{{ $period_label ?? '' }}</span></div>
            </div>
        </section>

        <section class="section"><h2 class="section-title">3. Month-over-month comparison</h2>
            <table>
                <thead><tr><th>Metric</th><th class="hc-num">Reference month<br><small>{{ $period_label ?? '' }}</small></th><th class="hc-num">Previous month<br><small>{{ $previous_period_label ?? '' }}</small></th><th class="hc-num">Change</th></tr></thead>
                <tbody>
                    <tr><td>Tables scanned</td><td class="hc-num">{{ $fmt($tables_scanned ?? 0) }}</td><td class="hc-num">{{ $fmt($prev['tables_scanned'] ?? 0) }}</td><td class="hc-num">{{ $fmt((float)($tables_scanned??0) - (float)($prev['tables_scanned']??0)) }}</td></tr>
                    <tr><td>Total records</td><td class="hc-num">{{ $fmt($record_total ?? 0) }}</td><td class="hc-num">{{ $fmt($prev['record_total'] ?? 0) }}</td><td class="hc-num">{{ $fmt((float)($record_total??0) - (float)($prev['record_total']??0)) }}</td></tr>
                    <tr><td>Total size</td><td class="hc-num">{{ $fmtBytes($bytes_total ?? 0) }}</td><td class="hc-num">{{ $fmtBytes($prev['bytes_total'] ?? 0) }}</td><td class="hc-num">{{ $fmtBytes((float)($bytes_total??0) - (float)($prev['bytes_total']??0)) }}</td></tr>
                    <tr><td>New users added</td><td class="hc-num">{{ $fmt($new_users ?? 0) }}</td><td class="hc-num">{{ $fmt($prev['new_users'] ?? 0) }}</td><td class="hc-num">{{ $fmt((float)($new_users??0) - (float)($prev['new_users']??0)) }}</td></tr>
                    <tr><td>Audit log (rekap)</td><td class="hc-num">{{ $fmt($auditTotal) }}</td><td class="hc-num">{{ $fmt($prev['audit_total'] ?? 0) }}</td><td class="hc-num">{{ $fmt($auditTotal - (float)($prev['audit_total']??0)) }}</td></tr>
                    <tr><td>Database condition</td><td class="hc-num"><span class="hc-tag {{ $dbOk ? 'ok' : 'warn' }}">{{ $dbOk ? 'HEALTHY' : 'NOTICE' }}</span></td><td class="hc-num"><span class="hc-tag {{ (($prev['db']['ok']??false)) ? 'ok' : 'warn' }}">{{ (($prev['db']['ok']??false)) ? 'HEALTHY' : 'NOTICE' }}</span></td><td class="hc-num">—</td></tr>
                    <tr><td>Program syntax</td><td class="hc-num"><span class="hc-tag {{ $syntaxOk ? 'ok' : 'warn' }}">{{ $syntaxOk ? 'OK' : 'NEEDS ATTENTION' }}</span></td><td class="hc-num"><span class="hc-tag {{ (($prev['syntax']['ok']??false)) ? 'ok' : 'warn' }}">{{ (($prev['syntax']['ok']??false)) ? 'OK' : 'NEEDS ATTENTION' }}</span></td><td class="hc-num">—</td></tr>
                </tbody>
            </table>
            <div class="hc-legend"><span><i style="background:#2dd4bf"></i>Reference month</span><span><i style="background:#e0b04a"></i>Previous month</span></div>
        </section>

        <section class="section"><h2 class="section-title">4. Graphical comparison</h2>
            @php($max = max(1, ...array_map(fn($b) => max($b['cur'], $b['prev']), $bars)))
            <div class="hc-bars">
                @foreach($bars as $b)
                    <div class="row">
                        <div class="t">{{ $b['label'] }}</div>
                        <div>
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px"><span class="hc-bar cur" style="width:{{ ($b['cur'] / $max) * 100 }}%;max-width:100%"></span><span class="hc-val">{{ $b['label'] === 'Size (MB)' ? number_format($b['cur'],1) : $fmt($b['cur']) }}</span></div>
                            <div style="display:flex;align-items:center;gap:6px"><span class="hc-bar prev" style="width:{{ ($b['prev'] / $max) * 100 }}%;max-width:100%"></span><span class="hc-val">{{ $b['label'] === 'Size (MB)' ? number_format($b['prev'],1) : $fmt($b['prev']) }}</span></div>
                        </div>
                        <div class="hc-val">{{ $b['label'] === 'Size (MB)' ? 'MB' : '' }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="section"><h2 class="section-title">5. Reconciliation summary (as per audit trail)</h2>
            <table>
                <thead><tr><th>Event</th><th class="hc-num">Reference month</th><th class="hc-num">Previous month</th></tr></thead>
                <tbody>
                    @foreach(array_keys($eventLabels) as $ev)
                        <tr><td>{{ $eventLabels[$ev] }}</td><td class="hc-num">{{ $fmt($audit[$ev] ?? 0) }}</td><td class="hc-num">{{ $fmt($prev['audit'][$ev] ?? 0) }}</td></tr>
                    @endforeach
                    <tr><td><strong>Total</strong></td><td class="hc-num"><strong>{{ $fmt($auditTotal) }}</strong></td><td class="hc-num"><strong>{{ $fmt($prev['audit_total'] ?? 0) }}</strong></td></tr>
                </tbody>
            </table>
            <div class="small-note">Aggregated from tbl_audit_trail events within the reference and previous reporting periods.</div>
        </section>

        <section class="section"><h2 class="section-title">6. Database &amp; program health</h2>
            <div class="hc-health">
                <div class="hc-health-card">
                    <h3>Database condition</h3>
                    <span class="hc-tag {{ $dbOk ? 'ok' : 'warn' }}" style="margin-bottom:6px">{{ $dbOk ? 'HEALTHY' : 'NOTICE' }} ({{ $db['score'] ?? 0 }}/{{ $db['total'] ?? 0 }})</span>
                    <ul>
                        @foreach(($db['checks'] ?? []) as $c)<li>{{ $c['label'] }} — {{ $c['ok'] ? 'OK' : 'FAIL' }}</li>@endforeach
                        <li>Version: {{ $db['version'] ?? '-' }}</li>
                    </ul>
                </div>
                <div class="hc-health-card">
                    <h3>Program syntax condition</h3>
                    <span class="hc-tag {{ $syntaxOk ? 'ok' : 'warn' }}" style="margin-bottom:6px">{{ $syntaxOk ? 'OK' : 'NEEDS ATTENTION' }}</span>
                    <ul>
                        <li>{{ $syntax['valid'] ?? 0 }} of {{ $syntax['total'] ?? 0 }} PHP source files parsed without syntax error.</li>
                        <li>Scope: application source (app / routes).</li>
                    </ul>
                </div>
            </div>
        </section>

        @if(!empty($table_stats))
        <section class="section table-section"><h2 class="section-title">7. Per-table detail</h2>
            <table class="data-table">
                <thead><tr><th>Table</th><th class="hc-num">Records</th><th class="hc-num">Size</th></tr></thead>
                <tbody>
                    @foreach(array_slice($table_stats, 0, 30, true) as $tbl => $st)
                        <tr><td>{{ $tbl }}</td><td class="hc-num">{{ $fmt($st['records'] ?? 0) }}</td><td class="hc-num">{{ $fmtBytes($st['bytes'] ?? 0) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            @if(count($table_stats) > 30)<div class="small-note">Showing the first 30 of {{ count($table_stats) }} tables.</div>@endif
        </section>
        @endif

        <div class="signature-grid"><div class="signature"><div class="signature-title">Prepared by</div><div class="signature-line">{{ $creator ?: '________' }}<br>{{ $lampiran_date ? $formatDate($lampiran_date, false) : '________' }}</div></div><div class="signature"><div class="signature-title">Verified by</div><div class="signature-line">Name / NIK / date</div></div><div class="signature"><div class="signature-title">Reviewed by</div><div class="signature-line">Name / NIK / date</div></div><div class="signature"><div class="signature-title">Acknowledged by</div><div class="signature-line">Name / NIK / date</div></div></div>

        <footer class="footer"><span>FOR-AMB-{{ $isPar ? 'PAR' : 'SMS' }}001 | Rev. 00 | Controlled record </span><span>Certificate {{ $certificate_no ?? '' }} | Generated {{ $formatDate($checked_at ?? now()) }}</span></footer>
    </main>
</body>
</html>