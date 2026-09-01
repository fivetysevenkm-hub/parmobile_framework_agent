<div class="app-shell health-check-page">
    <style>
        .health-check-page { --hc-cyan: var(--cyan, #00e5ff); }
        .health-check-page .hc-header { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px; flex-wrap:wrap; }
        .health-check-page .hc-header h1 { margin:5px 0 6px; }
        .health-check-page .hc-headline { color:var(--muted); font-size:.74rem; }
        .health-check-page .hc-filter { display:grid; grid-template-columns:repeat(2,minmax(150px,.7fr)) repeat(2,minmax(180px,1fr)) auto; gap:10px; align-items:end; padding:14px; border:1px solid var(--line); border-radius:10px; background:var(--legacy_0b1013); margin-bottom:18px; }
        .health-check-page .field-label { display:block; margin-bottom:6px; color:var(--muted); font-size:.65rem; letter-spacing:.07em; text-transform:uppercase; }
        .health-check-page .dark-control { width:100%; background:var(--legacy_0d1418); border:1px solid var(--line); border-radius:7px; color:var(--legacy_f5f7f8); padding:10px 11px; outline:none; }
        .health-check-page .dark-control:focus { border-color:var(--hc-cyan); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .health-check-page select.dark-control { color-scheme:dark; }
        .health-check-page .hc-type-tabs { display:flex; gap:8px; }
        .health-check-page .hc-tab { border:1px solid var(--line); border-radius:8px; background:var(--legacy_182228); color:var(--legacy_d9e0e3); padding:10px 14px; font-size:.74rem; cursor:pointer; font-weight:600; white-space:nowrap; }
        .health-check-page .hc-tab.active { background:var(--hc-cyan); border-color:var(--hc-cyan); color:var(--legacy_041014); }
        .health-check-page .hc-tab.active small { color:var(--legacy_041014); opacity:.75; }
        .health-check-page .hc-tab small { display:block; font-weight:400; font-size:.62rem; margin-top:2px; }
        .health-check-page .btn { min-height:39px; white-space:nowrap; text-decoration:none; text-align:center; }
        .health-check-page .hc-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .health-check-page .hc-kpi { border:1px solid var(--line); border-radius:10px; padding:14px; background:var(--legacy_0b1013); }
        .health-check-page .hc-kpi .kpi-label { color:var(--muted); font-size:.62rem; letter-spacing:.08em; text-transform:uppercase; }
        .health-check-page .hc-kpi .kpi-value { margin-top:6px; font-size:1.35rem; font-weight:700; color:var(--legacy_f5f7f8); }
        .health-check-page .hc-kpi .kpi-value small { font-size:.62rem; color:var(--muted); font-weight:400; }
        .health-check-page .hc-kpi .kpi-sub { margin-top:4px; font-size:.66rem; color:var(--cyan); }
        .health-check-page .hc-panel { border:1px solid var(--line); border-radius:10px; margin-bottom:18px; overflow:hidden; background:var(--legacy_0b1013); }
        .health-check-page .hc-panel-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border-bottom:1px solid var(--line); }
        .health-check-page .hc-panel-head h2 { margin:0; font-size:.82rem; letter-spacing:.03em; text-transform:uppercase; color:var(--legacy_f5f7f8); }
        .health-check-page .hc-panel-body { padding:14px; }
        .health-check-page .compare-table { width:100%; border-collapse:collapse; font-size:.74rem; }
        .health-check-page .compare-table th, .health-check-page .compare-table td { padding:10px 10px; border:1px solid var(--legacy_26333a); text-align:center; }
        .health-check-page .compare-table th { background:var(--legacy_0d1418); color:var(--cyan); font-size:.66rem; text-transform:uppercase; }
        .health-check-page .compare-table td.label { text-align:left; color:var(--legacy_d9e0e3); }
        .health-check-page .compare-table td.delta { font-weight:700; }
        .health-check-page .compare-table td.up { color:#3dd68c; }
        .health-check-page .compare-table td.down { color:#ff7a6b; }
        .health-check-page .bar-row { display:grid; grid-template-columns:150px 1fr; gap:10px; align-items:center; margin-bottom:12px; }
        .health-check-page .bar-label { color:var(--legacy_d9e0e3); font-size:.7rem; text-align:right; }
        .health-check-page .bar-track { display:flex; flex-direction:column; gap:5px; }
        .health-check-page .bar-item { display:flex; align-items:center; gap:8px; }
        .health-check-page .bar-item .bar-legend { width:104px; font-size:.62rem; color:var(--muted); text-align:right; }
        .health-check-page .bar-fill { height:14px; border-radius:4px; }
        .health-check-page .bar-fill.cur { background:linear-gradient(90deg,#00b4d8,#00e5ff); }
        .health-check-page .bar-fill.prev { background:linear-gradient(90deg,#8a6f2f,#d6ad4a); }
        .health-check-page .bar-val { font-size:.66rem; color:var(--legacy_f5f7f8); min-width:110px; }
        .health-check-page .status-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; border-radius:20px; font-size:.68rem; font-weight:700; }
        .health-check-page .status-pill.ok { background:rgba(61,214,140,.16); color:#3dd68c; }
        .health-check-page .status-pill.notice { background:rgba(246,194,67,.16); color:#f6c243; }
        .health-check-page .status-pill.critical { background:rgba(255,122,107,.18); color:#ff7a6b; }
        .health-check-page .hc-legend { display:flex; gap:16px; font-size:.66rem; color:var(--muted); margin-top:8px; }
        .health-check-page .hc-legend span::before { content:""; display:inline-block; width:10px; height:10px; border-radius:3px; margin-right:5px; vertical-align:-1px; }
        .health-check-page .hc-legend span.cur::before { background:var(--cyan); }
        .health-check-page .hc-legend span.prev::before { background:#d6ad4a; }
        .health-check-page .db-checks { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .health-check-page .db-check { display:flex; align-items:center; gap:8px; padding:9px 11px; border:1px solid var(--line); border-radius:8px; background:var(--legacy_0d1418); font-size:.72rem; }
        .health-check-page .db-check .dot { width:9px; height:9px; border-radius:50%; }
        .health-check-page .db-check .dot.ok { background:#3dd68c; box-shadow:0 0 0 3px rgba(61,214,140,.15); }
        .health-check-page .db-check .dot.bad { background:#ff7a6b; box-shadow:0 0 0 3px rgba(255,122,107,.15); }
        .health-check-page .hc-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
        .health-check-page .btn-cert { display:inline-flex; align-items:center; gap:8px; border:1px solid var(--cyan); background:rgba(0,229,255,.1); color:var(--cyan); border-radius:8px; padding:10px 16px; font-size:.74rem; font-weight:700; text-decoration:none; }
        .health-check-page .btn-cert:hover { background:rgba(0,229,255,.22); }
        .health-check-page .btn-save { border:1px solid var(--line); background:var(--legacy_182228); color:var(--legacy_d9e0e3); border-radius:8px; padding:10px 16px; font-size:.74rem; font-weight:700; cursor:pointer; }
        .health-check-page .hc-note { margin-top:8px; color:var(--muted); font-size:.66rem; }
        @media (max-width:1100px) { .health-check-page .hc-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .health-check-page .hc-filter { grid-template-columns:repeat(2,minmax(0,1fr)); } .health-check-page .db-checks { grid-template-columns:1fr; } }
        @media (max-width:680px) { .health-check-page .hc-filter { display:block; } .health-check-page .hc-filter > div { margin-bottom:10px; } .health-check-page .hc-grid { grid-template-columns:1fr; } .health-check-page .hc-header { display:block; } .health-check-page .hc-actions { flex-direction:column; } .health-check-page .btn-cert, .health-check-page .btn-save { width:100%; justify-content:center; } .health-check-page .bar-row { grid-template-columns:1fr; } .health-check-page .bar-label { text-align:left; } }
    </style>

    <div class="hc-header">
        <div>
            <h1>Health Check</h1>
            <div class="hc-headline">Laporan pemantauan berkala database &amp; program — sertifikat lampiran berita acara biaya maintenance bulanan.</div>
        </div>
        <div class="hc-type-tabs">
            <button type="button" class="hc-tab @if($activeType === 'PAR') active @endif" wire:click="updatedActiveType('PAR')">PAR Health Check<small>Semua tabel non-SMS</small></button>
            <button type="button" class="hc-tab @if($activeType === 'SMS') active @endif" wire:click="updatedActiveType('SMS')">SMS Health Check<small>tblsrp &amp; tbllotdaily</small></button>
        </div>
    </div>

    <form class="hc-filter" wire:submit.prevent="recompute">
        <div>
            <span class="field-label">Bulan acuan</span>
            <select class="dark-control" wire:model="month">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}">{{ now()->setMonth($m)->format('F') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <span class="field-label">Tahun acuan</span>
            <select class="dark-control" wire:model="year">
                @foreach(range(now()->year - 5, now()->year) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <span class="field-label">Tanggal laporan (lampiran)</span>
            <input type="date" class="dark-control" wire:model="lampiranDate">
        </div>
        <div>
            <span class="field-label">Nama pembuat laporan</span>
            <input type="text" class="dark-control" wire:model="creatorName" placeholder="Nama pembuat laporan">
        </div>
        <div class="hc-actions" style="margin-top:0;">
            <a href="#" wire:click.prevent="openCertificate('{{ $activeType }}')" class="btn-cert">Cetak {{ $activeType }} Certificate</a>
        </div>
    </form>

    @php
        $data = $activeType === 'PAR' ? $par : $sms;
        $prev = $data['previous'] ?? [];
        $fmt = static function ($n) { return number_format((float) $n); };
        $fmtBytes = static function ($b) { $b = (float) $b; $u = ['B','KB','MB','GB','TB']; $i = 0; while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; } return number_format($b, $i ? 1 : 0).' '.$u[$i]; };
        $delta = function ($cur, $prevVal) use ($fmt) { $cv = (float) $cur; $pv = (float) $prevVal; if ($pv == 0) return $cv > 0 ? '▲ +'.$fmt($cv) : '—'; $d = (($cv - $pv) / $pv) * 100; $sign = $d >= 0 ? '▲' : '▼'; return $sign.' '.number_format(abs($d), 1).'%'; };
        $traceDelta = function ($cur, $prevVal) use ($fmt) { $cv = (float) $cur; $pv = (float) $prevVal; if ($pv == 0) return '+'.$fmt($cv); $d = $cv - $pv; return ($d >= 0 ? '▲ +' : '▼ ').$fmt(abs($d)); };
    @endphp

    @if($data)
        <div class="hc-grid">
            <div class="hc-kpi"><div class="kpi-label">Jumlah Tabel Dipindai</div><div class="kpi-value">{{ $data['tables_scanned'] }} <small>tabel</small></div><div class="kpi-sub">{{ $activeType }} scope</div></div>
            <div class="hc-kpi"><div class="kpi-label">Jumlah Record</div><div class="kpi-value">{{ $fmt($data['record_total']) }} <small>baris</small></div><div class="kpi-sub">{{ $activeType }}</div></div>
            <div class="hc-kpi"><div class="kpi-label">Jumlah Bytes</div><div class="kpi-value">{{ $fmtBytes($data['bytes_total']) }}</div><div class="kpi-sub">{{ collect($data['table_stats'] ?? [])->sum('bytes') >= 0 ? '' : '' }}</div></div>
            <div class="hc-kpi"><div class="kpi-label">User Baru Ditambahkan</div><div class="kpi-value">{{ $fmt($data['new_users']) }} <small>user</small></div><div class="kpi-sub">{{ $data['period_label'] }}</div></div>
        </div>

        <div class="hc-panel">
            <div class="hc-panel-head"><h2>Pembanding Bulan Berjalan vs Bulan Sebelumnya</h2><span class="hc-legend"><span class="cur">Bulan berjalan ({{ $data['period_label'] }})</span><span class="prev">Bulan sebelumnya ({{ $data['previous_period_label'] }})</span></span></div>
            <div class="hc-panel-body">
                <table class="compare-table">
                    <thead><tr><th style="text-align:left">Metric</th><th>{{ $data['period_label'] }}</th><th>{{ $data['previous_period_label'] }}</th><th>Selisih</th></tr></thead>
                    <tbody>
                        <tr><td class="label">Jumlah tabel</td><td>{{ $fmt($data['tables_scanned']) }}</td><td>{{ $fmt($prev['tables_scanned'] ?? 0) }}</td><td class="delta">{{ $delta($data['tables_scanned'], $prev['tables_scanned'] ?? 0) }}</td></tr>
                        <tr><td class="label">Jumlah record</td><td>{{ $fmt($data['record_total']) }}</td><td>{{ $fmt($prev['record_total'] ?? 0) }}</td><td class="delta">{{ $delta($data['record_total'], $prev['record_total'] ?? 0) }}</td></tr>
                        <tr><td class="label">Jumlah bytes</td><td>{{ $fmtBytes($data['bytes_total']) }}</td><td>{{ $fmtBytes($prev['bytes_total'] ?? 0) }}</td><td class="delta">{{ $delta($data['bytes_total'], $prev['bytes_total'] ?? 0) }}</td></tr>
                        <tr><td class="label">Penambahan user baru</td><td>{{ $fmt($data['new_users']) }}</td><td>{{ $fmt($prev['new_users'] ?? 0) }}</td><td class="delta">{{ $traceDelta($data['new_users'], $prev['new_users'] ?? 0) }}</td></tr>
                        <tr><td class="label">Rekap resume (log audit)</td><td>{{ $fmt($data['audit_total']) }}</td><td>{{ $fmt($prev['audit_total'] ?? 0) }}</td><td class="delta">{{ $delta($data['audit_total'], $prev['audit_total'] ?? 0) }}</td></tr>
                        <tr><td class="label">Kondisi database</td><td><span class="status-pill {{ ($data['db']['ok'] ?? false) ? 'ok' : 'notice' }}">{{ ($data['db']['ok'] ?? false) ? 'HEALTHY' : 'NOTICE' }}</span></td><td><span class="status-pill {{ ($prev['db']['ok'] ?? false) ? 'ok' : 'notice' }}">{{ ($prev['db']['ok'] ?? false) ? 'HEALTHY' : 'NOTICE' }}</span></td><td class="delta">—</td></tr>
                        <tr><td class="label">Kondisi syntax program</td><td><span class="status-pill {{ ($data['syntax']['ok'] ?? false) ? 'ok' : 'notice' }}">{{ ($data['syntax']['ok'] ?? false) ? 'OK' : 'NEEDS ATTENTION' }}</span></td><td><span class="status-pill {{ ($prev['syntax']['ok'] ?? false) ? 'ok' : 'notice' }}">{{ ($prev['syntax']['ok'] ?? false) ? 'OK' : 'NEEDS ATTENTION' }}</span></td><td class="delta">—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="hc-panel">
            <div class="hc-panel-head"><h2>Grafik Perbandingan</h2><span class="hc-legend"><span class="cur">Bulan berjalan</span><span class="prev">Bulan sebelumnya</span></span></div>
            <div class="hc-panel-body">
                @php
                    $bars = [
                        ['label' => 'Jumlah Record', 'cur' => (float)$data['record_total'], 'prev' => (float)($prev['record_total'] ?? 0)],
                        ['label' => 'Jumlah Bytes (MB)', 'cur' => (float)$data['bytes_total'] / 1048576, 'prev' => (float)($prev['bytes_total'] ?? 0) / 1048576],
                        ['label' => 'Jumlah Tabel', 'cur' => (float)$data['tables_scanned'], 'prev' => (float)($prev['tables_scanned'] ?? 0)],
                        ['label' => 'User Baru', 'cur' => (float)$data['new_users'], 'prev' => (float)($prev['new_users'] ?? 0)],
                        ['label' => 'Log Audit', 'cur' => (float)$data['audit_total'], 'prev' => (float)($prev['audit_total'] ?? 0)],
                    ];
                @endphp
                @foreach($bars as $b)
                    @php($max = max(1, max($b['cur'], $b['prev'])))
                    <div class="bar-row">
                        <div class="bar-label">{{ $b['label'] }}</div>
                        <div class="bar-track">
                            <div class="bar-item"><span class="bar-legend">Berjalan</span><div class="bar-fill cur" style="width:{{ ($b['cur'] / $max) * 100 }}%"></div><span class="bar-val">{{ $b['label'] === 'Jumlah Bytes (MB)' ? number_format($b['cur'], 1).' MB' : $fmt($b['cur']) }}</span></div>
                            <div class="bar-item"><span class="bar-legend">Sebelumnya</span><div class="bar-fill prev" style="width:{{ ($b['prev'] / $max) * 100 }}%"></div><span class="bar-val">{{ $b['label'] === 'Jumlah Bytes (MB)' ? number_format($b['prev'], 1).' MB' : $fmt($b['prev']) }}</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="hc-panel">
            <div class="hc-panel-head"><h2>Rekap Resume (Sebagaimana Audit Trail)</h2></div>
            <div class="hc-panel-body">
                <table class="compare-table">
                    <thead><tr><th style="text-align:left">Peristiwa</th><th>{{ $data['period_label'] }}</th><th>{{ $data['previous_period_label'] }}</th></tr></thead>
                    <tbody>
                        @foreach(['login' => 'Login', 'login_failed' => 'Login Gagal', 'logout' => 'Logout', 'password_change' => 'Ganti Password', 'access' => 'Akses Menu', 'access_denied' => 'Akses Ditolak', 'create' => 'Data Dibuat', 'update' => 'Data Diubah', 'delete' => 'Data Dihapus'] as $k => $label)
                            <tr><td class="label">{{ $label }}</td><td>{{ $fmt($data['audit'][$k] ?? 0) }}</td><td>{{ $fmt($prev['audit'][$k] ?? 0) }}</td></tr>
                        @endforeach
                        <tr><td class="label"><strong>Total</strong></td><td><strong>{{ $fmt($data['audit_total']) }}</strong></td><td><strong>{{ $fmt($prev['audit_total'] ?? 0) }}</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="hc-panel">
            <div class="hc-panel-head"><h2>Kondisi Database &amp; Syntax Program</h2></div>
            <div class="hc-panel-body">
                <div class="db-checks" style="margin-bottom:12px;">
                    @foreach(($data['db']['checks'] ?? []) as $c)
                        <div class="db-check"><span class="dot {{ $c['ok'] ? 'ok' : 'bad' }}"></span><span>{{ $c['label'] }}</span></div>
                    @endforeach
                    <div class="db-check"><span class="dot ok"></span><span>Versi DB: {{ $data['db']['version'] ?? '-' }}</span></div>
                    <div class="db-check"><span class="dot {{ (($data['syntax']['ok'] ?? false)) ? 'ok' : 'bad' }}"></span><span>Syntax PHP: {{ $data['syntax']['valid'] ?? 0 }}/{{ $data['syntax']['total'] ?? 0 }} file valid</span></div>
                </div>
                <div class="hc-note">
                    Nilai snapshot kondisi database &amp; syntax mencerminkan kondisi pada saat laporan dibuat (titik waktu). Skor kondisi DB: {{ $data['db']['score'] ?? 0 }}/{{ $data['db']['total'] ?? 0 }}.
                </div>
            </div>
        </div>

        <div class="hc-actions">
            <a href="{{ route('health-check.certificate', ['type' => 'PAR', 'year' => $year, 'month' => $month, 'lampiran_date' => $lampiranDate, 'creator' => $creatorName]) }}" target="_blank" class="btn-cert">Cetak PAR Health Check Certificate</a>
            <a href="{{ route('health-check.certificate', ['type' => 'SMS', 'year' => $year, 'month' => $month, 'lampiran_date' => $lampiranDate, 'creator' => $creatorName]) }}" target="_blank" class="btn-cert">Cetak SMS Health Check Certificate</a>
            <button type="button" class="btn-save" wire:click="saveSnapshot('PAR')">Simpan Snapshot PAR</button>
            <button type="button" class="btn-save" wire:click="saveSnapshot('SMS')">Simpan Snapshot SMS</button>
        </div>
    @else
        <div class="empty-state">Tidak ada data untuk periode ini.</div>
    @endif
</div>