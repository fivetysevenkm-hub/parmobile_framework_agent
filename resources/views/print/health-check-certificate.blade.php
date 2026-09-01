@php
    $fmt = static fn($n) => number_format((float)$n);
    $fmtBytes = static function($b) { $b = (float)$b; $u = ['B','KB','MB','GB','TB']; $i = 0; while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; } return number_format($b, $i ? 1 : 0).' '.$u[$i]; };
    $formatDate = static function ($value, bool $withTime = true): string {
        if (! $value) return '-';
        try { return \Carbon\Carbon::parse($value)->timezone('Asia/Jakarta')->format($withTime ? 'd F Y H:i:s' : 'd F Y'); }
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
        :root{--ink:#123;--muted:#4a5b63;--line:#c9d3d6;--accent:#0b7285;--gold:#b78a1e;--soft:#f2f7f8;--ok:#176b4d;--warn:#946200}
        *{box-sizing:border-box}body{margin:0;background:#e7ecee;color:var(--ink);font-family:'Georgia','Times New Roman',serif;font-size:10px}
        .print-toolbar{display:flex;justify-content:space-between;gap:12px;max-width:1180px;margin:14px auto 0;padding:0 14px}
        .print-toolbar a,.print-toolbar button{border:1px solid #8a9ba3;border-radius:5px;background:#fff;color:#123;padding:8px 13px;font-size:12px;text-decoration:none;cursor:pointer;font-family:Arial,sans-serif}
        .print-toolbar button.primary{background:#0b7285;border-color:#0b7285;color:#fff}
        .paper{width:min(1180px,calc(100% - 28px));margin:12px auto 24px;padding:0;background:#fff;box-shadow:0 4px 20px rgba(20,40,50,.18);border:1px solid #d6dfe2}
        .frame{border:6px double #b78a1e;margin:10px;padding:18px;position:relative}
        .frame:after{content:"";position:absolute;inset:6px;border:1px solid #0b7285;pointer-events:none}
        .doc-header{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:start;padding-bottom:14px;border-bottom:2px solid var(--gold)}
        .brand{color:var(--accent);font-size:10px;font-weight:700;letter-spacing:.22em;text-transform:uppercase}
        .doc-header h1{margin:8px 0 4px;font-size:24px;letter-spacing:.02em;color:#0b4f5c}
        .subtitle{color:var(--muted);font-size:10px;font-style:italic;max-width:620px;line-height:1.5}
        .doc-id{border:1px solid var(--line);border-radius:6px;min-width:250px;font-family:Arial,sans-serif;overflow:hidden}
        .doc-id div{display:grid;grid-template-columns:98px 1fr;border-bottom:1px solid var(--line)}
        .doc-id div:last-child{border-bottom:0}
        .doc-id span{padding:5px 8px}
        .doc-id span:first-child{background:var(--soft);color:var(--muted);font-weight:700;font-size:8.5px}
        .doc-id span:last-child{font-weight:700;font-size:9.5px}
        .seal{display:grid;place-items:center;width:52px;height:52px;border:3px solid var(--gold);border-radius:50%;color:var(--gold);font-size:7.5px;font-weight:700;text-align:center;letter-spacing:.06em;line-height:1.25}
        .notice{margin:14px 0;padding:10px 12px;border:1px solid #c3d5da;background:#f4fafb;color:#2a4750;line-height:1.5;font-family:Arial,sans-serif;font-size:9px}
        .section{margin-top:16px;break-inside:avoid}
        .section-title{display:flex;align-items:center;gap:9px;margin:0 0 9px;padding:7px 9px;background:#0b4f5c;color:#fff;font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;font-family:Arial,sans-serif}
        .section-title .num{display:grid;place-items:center;width:18px;height:18px;border:1px solid #fff;border-radius:50%;font-size:9px}
        .field-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border-top:1px solid var(--line);border-left:1px solid var(--line)}
        .field{position:relative;padding:7px 9px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);font-family:Arial,sans-serif}
        .field .field-label{display:block;color:var(--muted);font-size:8px;letter-spacing:.05em;text-transform:uppercase;margin-bottom:3px}
        .field .field-value{font-weight:700;font-size:11px}
        .field.emphasis .field-value{color:var(--accent);font-size:11.5px}
        table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:9px}
        table.cmp th,table.cmp td{padding:7px 9px;border:1px solid var(--line);text-align:center}
        table.cmp th{background:#eef4f5;color:#0b4f5c;text-transform:uppercase;font-size:8.4px;letter-spacing:.04em}
        table.cmp td.lab{text-align:left;font-weight:700}
        table.cmp td.up{color:var(--ok);font-weight:700}
        table.cmp td.down{color:#a33;font-weight:700}
        table.detail th,table.detail td{padding:6px 8px;border:1px solid var(--line);text-align:left;vertical-align:top}
        table.detail th{background:#eef4f5;color:#0b4f5c;text-transform:uppercase;font-size:8.2px}
        table.detail td.num{text-align:right}
        .kpi-band{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:12px 0}
        .kpi{border:1px solid var(--line);background:#fafdfd;padding:10px;text-align:center;border-top:3px solid var(--accent)}
        .kpi .k{color:var(--muted);font-size:8px;text-transform:uppercase;letter-spacing:.05em}
        .kpi .v{font-size:18px;font-weight:700;color:#0b4f5c;margin-top:3px;font-family:Georgia,serif}
        .kpi .s{color:var(--muted);font-size:8px}
        .bars{display:flex;flex-direction:column;gap:8px;margin-top:4px}
        .bars .row{display:grid;grid-template-columns:120px 1fr 96px;gap:8px;align-items:center;font-family:Arial,sans-serif;font-size:8.5px}
        .bars .row .t{color:var(--muted);text-align:right}
        .bars .fill-cur,.bars .fill-prev{height:12px;border-radius:3px}
        .bars .fill-cur{background:linear-gradient(90deg,#0b9db5,#19c7dd)}
        .bars .fill-prev{background:linear-gradient(90deg,#b78a1e,#e0b04a)}
        .bars .val{font-size:8.5px}
        .legend{display:flex;gap:14px;font-family:Arial,sans-serif;font-size:8.5px;color:var(--muted);margin-top:8px}
        .legend i{display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:5px;vertical-align:-2px}
        .health-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .health-card{border:1px solid var(--line);border-radius:6px;padding:10px}
        .health-card h3{margin:0 0 8px;font-family:Arial,sans-serif;font-size:9.5px;text-transform:uppercase;color:#0b4f5c;letter-spacing:.04em}
        .health-card ul{margin:0;padding-left:16px;line-height:1.7;font-family:Arial,sans-serif;font-size:9px}
        .tag{display:inline-block;padding:3px 10px;border-radius:12px;font-weight:700;font-size:9px;letter-spacing:.04em}
        .tag.ok{background:#e3f3ea;color:var(--ok)}
        .tag.warn{background:#fdf3dd;color:var(--warn)}
        .signature-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:26px}
        .signature{text-align:center;font-family:Arial,sans-serif}
        .signature .sig-title{color:var(--muted);font-size:8.5px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
        .signature .sig-name{min-height:34px;font-weight:700;border-top:1px solid #0b4f5c;padding-top:7px;font-size:9px;line-height:1.5}
        .footer{display:flex;justify-content:space-between;gap:10px;margin-top:18px;padding-top:10px;border-top:1px solid var(--line);color:var(--muted);font-family:Arial,sans-serif;font-size:8px}
        .summary-table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:9px;margin-top:8px}
        .summary-table th,.summary-table td{padding:6px 8px;border:1px solid var(--line);text-align:left}
        .summary-table th{background:#eef4f5;color:#0b4f5c}
        .summary-table td.n{text-align:right}
        @media print{body{background:#fff}.print-toolbar{display:none}.paper{margin:0;width:100%;box-shadow:none}}
        @media (max-width:820px){.doc-header{grid-template-columns:1fr}.field-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.health-row{grid-template-columns:1fr}.kpi-band{grid-template-columns:repeat(2,minmax(0,1fr))}.signature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.bars .row{grid-template-columns:1fr}.bars .row .t{text-align:left}}
    </style>
</head>
<body>
    <div class="print-toolbar"><a href="{{ route('health-check') }}">Back to Health Check</a><button class="primary" type="button" onclick="window.print()">Print / Save PDF</button></div>
    <main class="paper">
        <div class="frame">
            <header class="doc-header">
                <div>
                    <div class="brand">PAR Mobile / {{ $isPar ? 'Maintenance &amp; Database' : 'Support &amp; Monitoring System' }}</div>
                    <h1>{{ $isPar ? 'PAR Health Check Certificate' : 'SMS Health Check Certificate' }}</h1>
                    <div class="subtitle">{{ $isPar
                        ? 'Certified periodic assessment of the PAR database and application program health across all production and support tables (excluding the SMS source tables).'
                        : 'Certified periodic assessment of the SMS source tables (tblsrp and tbllotdaily) supporting the Shared Management System maintenance record.' }}</div>
                </div>
                <div class="doc-id">
                    <div><span>Document ID</span><span>QMS-HC-{{ $isPar ? 'PAR' : 'SMS' }}-01</span></div>
                    <div><span>Revision</span><span>00</span></div>
                    <div><span>Certificate No.</span><span>{{ $certificate_no ?? '-' }}</span></div>
                    <div><span>Report Date</span><span>{{ $lampiran_date ? $formatDate($lampiran_date, false) : '-' }}</span></div>
                </div>
            </header>

            <div class="notice">
                <strong>Controlled documented information.</strong>
                This certificate forms the official attachment to the monthly maintenance-fee minutes of meeting (Berita Acara) and certifies the operational health of the {{ $isPar ? 'PAR' : 'SMS' }} environment as of the report date.
                All figures are a point-in-time snapshot taken at certificate generation; the record is stored for traceability (IATF 16949:2016).
            </div>

            <section class="section">
                <h2 class="section-title"><span class="num">1</span>Scope &amp; reporting period</h2>
                <div class="field-grid">
                    <div class="field"><span class="field-label">Reference period</span><span class="field-value">{{ $period_label ?? sprintf('%04d-%02d', $year ?? 0, $month ?? 0) }}</span></div>
                    <div class="field"><span class="field-label">Comparison period</span><span class="field-value">{{ $previous_period_label ?? '-' }}</span></div>
                    <div class="field"><span class="field-label">Prepared by</span><span class="field-value">{{ $creator ?: '-' }}</span></div>
                    <div class="field"><span class="field-label">Checked at</span><span class="field-value">{{ $formatDate($checked_at ?? $reference_date ?? now()) }}</span></div>
                    <div class="field emphasis"><span class="field-label">Tables scanned</span><span class="field-value">{{ $fmt($tables_scanned ?? 0) }} tables</span></div>
                    <div class="field"><span class="field-label">Scope type</span><span class="field-value">{{ $isPar ? 'All DB tables (non-SMS)' : 'tblsrp + tbllotdaily' }}</span></div>
                    <div class="field"><span class="field-label">Generated from snapshot</span><span class="field-value">{{ ($from_snapshot ?? false) ? 'Yes' : 'Live calculation' }}</span></div>
                    <div class="field"><span class="field-label">Source</span><span class="field-value">information_schema / tbl_audit_trail / tbl_user</span></div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title"><span class="num">2</span>Key indicators</h2>
                <div class="kpi-band">
                    <div class="kpi"><div class="k">Tables scanned</div><div class="v">{{ $fmt($tables_scanned ?? 0) }}</div><div class="s">{{ $isPar ? 'all non-SMS' : 'SMS source' }} tables</div></div>
                    <div class="kpi"><div class="k">Total records</div><div class="v">{{ $fmt($record_total ?? 0) }}</div><div class="s">rows in scope</div></div>
                    <div class="kpi"><div class="k">Total size</div><div class="v">{{ $fmtBytes($bytes_total ?? 0) }}</div><div class="s">data + index</div></div>
                    <div class="kpi"><div class="k">New users added</div><div class="v">{{ $fmt($new_users ?? 0) }}</div><div class="s">{{ $period_label ?? '' }}</div></div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title"><span class="num">3</span>Month-over-month comparison</h2>
                <table class="cmp">
                    <thead><tr><th style="text-align:left">Metric</th><th>Reference month<br><small>{{ $period_label ?? '' }}</small></th><th>Previous month<br><small>{{ $previous_period_label ?? '' }}</small></th><th>Change</th></tr></thead>
                    <tbody>
                        <tr><td class="lab">Tables scanned</td><td>{{ $fmt($tables_scanned ?? 0) }}</td><td>{{ $fmt($prev['tables_scanned'] ?? 0) }}</td><td class="{{ (float)($tables_scanned??0) >= (float)($prev['tables_scanned']??0) ? 'up' : 'down' }}">{{ $fmt((float)($tables_scanned??0) - (float)($prev['tables_scanned']??0)) }}</td></tr>
                        <tr><td class="lab">Total records</td><td>{{ $fmt($record_total ?? 0) }}</td><td>{{ $fmt($prev['record_total'] ?? 0) }}</td><td class="{{ (float)($record_total??0) >= (float)($prev['record_total']??0) ? 'up' : 'down' }}">{{ $fmt((float)($record_total??0) - (float)($prev['record_total']??0)) }}</td></tr>
                        <tr><td class="lab">Total size</td><td>{{ $fmtBytes($bytes_total ?? 0) }}</td><td>{{ $fmtBytes($prev['bytes_total'] ?? 0) }}</td><td class="{{ (float)($bytes_total??0) >= (float)($prev['bytes_total']??0) ? 'up' : 'down' }}">{{ $fmtBytes((float)($bytes_total??0) - (float)($prev['bytes_total']??0)) }}</td></tr>
                        <tr><td class="lab">New users added</td><td>{{ $fmt($new_users ?? 0) }}</td><td>{{ $fmt($prev['new_users'] ?? 0) }}</td><td class="{{ (float)($new_users??0) >= (float)($prev['new_users']??0) ? 'up' : 'down' }}">{{ $fmt((float)($new_users??0) - (float)($prev['new_users']??0)) }}</td></tr>
                        <tr><td class="lab">Audit log (rekap)</td><td>{{ $fmt($auditTotal) }}</td><td>{{ $fmt($prev['audit_total'] ?? 0) }}</td><td class="{{ $auditTotal >= (float)($prev['audit_total']??0) ? 'up' : 'down' }}">{{ $fmt($auditTotal - (float)($prev['audit_total']??0)) }}</td></tr>
                        <tr><td class="lab">Database condition</td><td><span class="tag {{ $dbOk ? 'ok' : 'warn' }}">{{ $dbOk ? 'HEALTHY' : 'NOTICE' }}</span></td><td><span class="tag {{ (($prev['db']['ok']??false)) ? 'ok' : 'warn' }}">{{ (($prev['db']['ok']??false)) ? 'HEALTHY' : 'NOTICE' }}</span></td><td>—</td></tr>
                        <tr><td class="lab">Program syntax</td><td><span class="tag {{ $syntaxOk ? 'ok' : 'warn' }}">{{ $syntaxOk ? 'OK' : 'NEEDS ATTENTION' }}</span></td><td><span class="tag {{ (($prev['syntax']['ok']??false)) ? 'ok' : 'warn' }}">{{ (($prev['syntax']['ok']??false)) ? 'OK' : 'NEEDS ATTENTION' }}</span></td><td>—</td></tr>
                    </tbody>
                </table>
                <div class="legend"><span><i style="background:#19c7dd"></i>Reference month</span><span><i style="background:#e0b04a"></i>Previous month</span></div>
            </section>

            <section class="section">
                <h2 class="section-title"><span class="num">4</span>Graphical comparison</h2>
                @php($max = max(1, ...array_map(fn($b) => max($b['cur'], $b['prev']), $bars)))
                <div class="bars">
                    @foreach($bars as $b)
                        <div class="row">
                            <div class="t">{{ $b['label'] }}</div>
                            <div>
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px"><span class="fill-cur" style="width:{{ ($b['cur'] / $max) * 100 }}%;max-width:100%"></span><span class="val">{{ $b['label'] === 'Size (MB)' ? number_format($b['cur'],1) : $fmt($b['cur']) }}</span></div>
                                <div style="display:flex;align-items:center;gap:6px"><span class="fill-prev" style="width:{{ ($b['prev'] / $max) * 100 }}%;max-width:100%"></span><span class="val">{{ $b['label'] === 'Size (MB)' ? number_format($b['prev'],1) : $fmt($b['prev']) }}</span></div>
                            </div>
                            <div class="val">{{ $b['label'] === 'Size (MB)' ? 'MB' : '' }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="section">
                <h2 class="section-title"><span class="num">5</span>Reconciliation summary (as per audit trail)</h2>
                <table class="summary-table">
                    <thead><tr><th>Event</th><th>Reference month</th><th>Previous month</th></tr></thead>
                    <tbody>
                        @foreach(array_keys($eventLabels) as $ev)
                            <tr><td>{{ $eventLabels[$ev] }}</td><td class="n">{{ $fmt($audit[$ev] ?? 0) }}</td><td class="n">{{ $fmt($prev['audit'][$ev] ?? 0) }}</td></tr>
                        @endforeach
                        <tr><td><strong>Total</strong></td><td class="n"><strong>{{ $fmt($auditTotal) }}</strong></td><td class="n"><strong>{{ $fmt($prev['audit_total'] ?? 0) }}</strong></td></tr>
                    </tbody>
                </table>
                <div style="font-family:Arial,sans-serif;font-size:8.5px;color:var(--muted);margin-top:6px">Aggregated from tbl_audit_trail events within the reference and previous reporting periods.</div>
            </section>

            <section class="section">
                <h2 class="section-title"><span class="num">6</span>Database &amp; program health</h2>
                <div class="health-row">
                    <div class="health-card">
                        <h3>Database condition</h3>
                        <span class="tag {{ $dbOk ? 'ok' : 'warn' }}" style="margin-bottom:8px">{{ $dbOk ? 'HEALTHY' : 'NOTICE' }} ({{ $db['score'] ?? 0 }}/{{ $db['total'] ?? 0 }})</span>
                        <ul>
                            @foreach(($db['checks'] ?? []) as $c)<li>{{ $c['label'] }} — {{ $c['ok'] ? 'OK' : 'FAIL' }}</li>@endforeach
                            <li>Version: {{ $db['version'] ?? '-' }}</li>
                        </ul>
                    </div>
                    <div class="health-card">
                        <h3>Program syntax condition</h3>
                        <span class="tag {{ $syntaxOk ? 'ok' : 'warn' }}" style="margin-bottom:8px">{{ $syntaxOk ? 'OK' : 'NEEDS ATTENTION' }}</span>
                        <ul>
                            <li>{{ $syntax['valid'] ?? 0 }} of {{ $syntax['total'] ?? 0 }} PHP source files parsed without syntax error.</li>
                            <li>Scope: application source (app / routes).</li>
                        </ul>
                    </div>
                </div>
            </section>

            @if(!empty($table_stats))
            <section class="section">
                <h2 class="section-title"><span class="num">7</span>Per-table detail</h2>
                <table class="detail">
                    <thead><tr><th>Table</th><th>Records</th><th>Size</th></tr></thead>
                    <tbody>
                        @foreach(array_slice($table_stats, 0, 30, true) as $tbl => $st)
                            <tr><td>{{ $tbl }}</td><td class="num">{{ $fmt($st['records'] ?? 0) }}</td><td class="num">{{ $fmtBytes($st['bytes'] ?? 0) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($table_stats) > 30)<div style="font-family:Arial,sans-serif;font-size:8.5px;color:var(--muted);margin-top:6px">Showing the first 30 of {{ count($table_stats) }} tables.</div>@endif
            </section>
            @endif

            <div class="signature-grid">
                <div class="signature"><div class="sig-title">Prepared by</div><div class="sig-name">{{ $creator ?: '________' }}<br>{{ $lampiran_date ? $formatDate($lampiran_date, false) : '________' }}</div></div>
                <div class="signature"><div class="sig-title">Verified by</div><div class="sig-name">Name / NIK / date</div></div>
                <div class="signature"><div class="sig-title">Reviewed by QMR</div><div class="sig-name">Name / NIK / date</div></div>
                <div class="signature"><div class="sig-title">Internal Auditor</div><div class="sig-name">Name / NIK / date</div></div>
            </div>

            <footer class="footer"><span>QMS-HC-{{ $isPar ? 'PAR' : 'SMS' }}-01 | Rev. 00 | Controlled record — IATF 16949:2016</span><span>Certificate {{ $certificate_no ?? '' }} | Generated {{ $formatDate($checked_at ?? now()) }}</span></footer>
        </div>
    </main>
</body>
</html>