<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B1013">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PAR Mobile">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-180.png') }}">
    <title>{{ $title ?? 'Production Dashboard | PAR Mobile' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @livewireStyles
    @php
        $themeColors = app(\App\Services\ThemePreferenceService::class)->currentForUser(auth()->user())['colors'];
        $themeBackground = ltrim((string) ($themeColors['bg'] ?? '#10171B'), '#');
        $themeRed = hexdec(substr($themeBackground, 0, 2));
        $themeGreen = hexdec(substr($themeBackground, 2, 2));
        $themeBlue = hexdec(substr($themeBackground, 4, 2));
        $themeColorScheme = (($themeRed * 0.299) + ($themeGreen * 0.587) + ($themeBlue * 0.114)) > 145 ? 'light' : 'dark';
    @endphp
    <style>
        :root {
            color-scheme: {{ $themeColorScheme }};
            @foreach($themeColors as $key => $value)
                --{{ $key }}: {{ $value }};
                --{{ str_replace('_', '-', $key) }}: {{ $value }};
            @endforeach
            --shadow: 0 12px 30px color-mix(in srgb, var(--shadow-color) 24%, transparent);
            --focus-ring: 0 0 0 3px color-mix(in srgb, var(--focus-ring) 24%, transparent);
            @if($themeColorScheme === 'light')
                /* Light-scheme guard: page sections/controls historically use fixed dark "legacy" fills and light text.
                   Rebind them to light surfaces + dark text so every custom light theme stays uniform and the
                   dark-fill-on-dark-text / light-fill-on-light-text mismatch is avoided across all pages. */
                --legacy_111a1f: #FFFFFF; --legacy-111a1f: #FFFFFF;
                --legacy_0d1418: #FFFFFF; --legacy-0d1418: #FFFFFF;
                --legacy_0b1013: #E3EDF2; --legacy-0b1013: #E3EDF2;
                --legacy_17252c: #E3EDF2; --legacy-17252c: #E3EDF2;
                --legacy_050708: #EEF3F6; --legacy-050708: #EEF3F6;
                --legacy_182228: #F6F9FB; --legacy-182228: #F6F9FB;
                --legacy_121e24: #EEF3F6; --legacy-121e24: #EEF3F6;
                --legacy_f5f7f8: #17242C; --legacy-f5f7f8: #17242C;
                --legacy_d9e0e3: #22313A; --legacy-d9e0e3: #22313A;
                --legacy_e6eef0: #17242C; --legacy-e6eef0: #17242C;
                --legacy_9aaeb4: #5B6B76; --legacy-9aaeb4: #5B6B76;
                --legacy_aeb5bb: #5B6B76; --legacy-aeb5bb: #5B6B76;
                --legacy_f2f5f6: #22313A; --legacy-f2f5f6: #22313A;
                --legacy_eef3f4: #22313A; --legacy-eef3f4: #22313A;
                --legacy_eef7f8: #22313A; --legacy-eef7f8: #22313A;
                --legacy_e8f5f7: #22313A; --legacy-e8f5f7: #22313A;
                --legacy_8ff5df: #0E7A63; --legacy-8ff5df: #0E7A63;
                --legacy_8fdcff: #00677F; --legacy-8fdcff: #00677F;
                --legacy_bfeef3: #075D6E; --legacy-bfeef3: #075D6E;
                --legacy_9ee8f1: #0B6A79; --legacy-9ee8f1: #0B6A79;
                --legacy_ff9eaa: #B22B3A; --legacy-ff9eaa: #B22B3A;
                --legacy_ffb4b8: #A82736; --legacy-ffb4b8: #A82736;
                --legacy_b9fff2: #0D745F; --legacy-b9fff2: #0D745F;
                --legacy_d6faff: #0B6A79; --legacy-d6faff: #0B6A79;
                --legacy_c9f8ff: #0B6A79; --legacy-c9f8ff: #0B6A79;
                --legacy_e7fbff: #0B6A79; --legacy-e7fbff: #0B6A79;
                --legacy_e9fcff: #0B6A79; --legacy-e9fcff: #0B6A79;
                --legacy_fecdd3: #B22B3A; --legacy-fecdd3: #B22B3A;
            @endif
        }
        * { box-sizing: border-box; }
        html { overflow-x:hidden; } body { margin: 0; background: var(--bg); color: var(--text-body); font-family: 'Space Grotesk', sans-serif; overflow-x:hidden; }
        button, input { font: inherit; }
        .app-shell { width:100%; max-width:100%; min-width:0; min-height:calc(100vh - 64px); padding:24px; }
        .topbar { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:22px; }
        .eyebrow { color:var(--muted); font-family:'JetBrains Mono',monospace; font-size:.72rem; letter-spacing:.18em; text-transform:uppercase; }
        h1 { margin: 5px 0 6px; font-size: clamp(1.7rem, 3vw, 2.7rem); letter-spacing: -.05em; color:var(--text-title); } h2, h3, h4 { color:var(--text-title); } p { color:var(--text-body); } footer, .footer { color:var(--text-footer); }
        .cyan { color:var(--cyan); } .orange { color:var(--orange); } .red { color:var(--red); } .lime { color:var(--lime); }
        .muted { color:var(--text-body); }
        .toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
        .user-chip, .timer { border:1px solid var(--line); background:var(--panel); padding:9px 12px; border-radius:8px; font-family:'JetBrains Mono',monospace; font-size:.72rem; }
        .btn { border:0; border-radius:7px; padding:10px 14px; font-weight:700; cursor:pointer; transition:transform .16s ease, filter .16s ease, background .16s ease; color:var(--text-button); } .btn:active { transform:scale(.97); } .btn:disabled,[disabled] { background:var(--button-disabled-bg); color:var(--button-disabled-text); cursor:not-allowed; } .btn-cyan { background:var(--button-primary); color:var(--text-button); } .btn-cyan:hover { background:var(--button-primary-hover); } .btn-ghost { background:var(--button-secondary-bg); color:var(--button-secondary-text); border:1px solid var(--line); }
        .filter { background:var(--filter-bg); border:1px solid var(--line); border-radius:10px; padding:14px; display:flex; gap:10px; align-items:end; flex-wrap:wrap; margin-bottom:18px; }
        .filter label { color:var(--text-body); display:block; font-size:.7rem; letter-spacing:.08em; margin-bottom:6px; text-transform:uppercase; }
        .filter input { background:var(--input-bg); border:1px solid var(--line); color:var(--input-text); border-radius:7px; padding:10px 12px; min-width:300px; } .filter input::placeholder { color:var(--input-placeholder); } .filter input:focus { background:var(--input-focus-bg); border-color:var(--focus-ring); outline:none; box-shadow:var(--focus-ring); }
        .error { color:var(--danger); font-size:.78rem; margin-top:6px; }
        .stats { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .stat, .panel { background:var(--panel); border:1px solid var(--line); border-radius:10px; }
        .stat { padding:16px; min-height:105px; border-left:4px solid var(--cyan); }
        .stat:nth-child(2), .stat:nth-child(3), .stat:nth-child(4) { border-left-color:var(--orange); }
        .stat:nth-child(5) { border-left-color:var(--lime); } .stat:nth-child(6) { border-left-color:var(--red); }
        .stat-label { color:var(--muted); font-size:.66rem; line-height:1.35; text-transform:uppercase; letter-spacing:.08em; }
        .stat-value { display:block; font-family:'JetBrains Mono',monospace; font-size:1.6rem; font-weight:700; margin-top:10px; }
        .stat-note { color:var(--muted); font-size:.65rem; margin-top:5px; }
        .charts { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; margin-bottom:18px; min-width:0; max-width:100%; }
        .charts[data-chart-ready="false"] { visibility:hidden; }
        .charts[data-chart-ready="true"] { visibility:visible; }
        .charts > .panel { min-width:0; width:100%; max-width:100%; overflow:hidden; }
        .panel { padding:18px; min-height:300px; } .panel h2 { font-size:.78rem; letter-spacing:.1em; margin:0 0 14px; text-transform:uppercase; }
        .chart-wrap { width:100%; min-width:0; max-width:100%; height:245px; position:relative; } .chart-wrap canvas { display:block !important; width:100% !important; max-width:100% !important; height:100% !important; }
        .table-panel { overflow:hidden; } .table-scroll { overflow:auto; }
        table { border-collapse:collapse; min-width:850px; width:100%; font-size:.78rem; }
        th, td { border:1px solid var(--table-border); padding:11px 10px; text-align:center; white-space:nowrap; color:var(--text-table); background:var(--table-row); } th { background:var(--table-header); color:var(--text-table); position:sticky; top:0; z-index:2; } td:first-child, th:first-child { position:sticky; left:0; background:var(--table-sticky); text-align:left; z-index:1; } th:first-child { z-index:3; } tbody tr:nth-child(even) td { background:var(--table-alt); } tbody tr:hover td { background:var(--table-hover); }
        .row-label { color:var(--text-body); font-weight:700; text-transform:uppercase; } .zero { color:var(--muted); } .bright { color:var(--text-title); } .strong { font-weight:700; } .mobile-table-hint { display:none; color:var(--muted); font-size:.65rem; margin:-4px 0 10px; } .monthly-year-select { min-width:150px; background:var(--input-bg); border:1px solid var(--line); color:var(--input-text); border-radius:7px; padding:10px 12px; } .monthly-filter .btn { white-space:nowrap; } .monthly-table-heading { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; } .month-label { color:var(--text-title); text-align:left; } .month-label small { display:block; margin-top:4px; font-size:.58rem; } .month-qty-row td { background:var(--table-alt); } .month-end-row td { border-bottom:2px solid var(--line); } .chart-wrap-tall { height:280px; } .year-badge { font-family:'JetBrains Mono',monospace; font-size:.7rem; }
        .layout-shell { width:100%; min-height:100vh; } .menu-auth-modal[hidden] { display:none; } .menu-auth-modal { position:fixed; inset:0; z-index:100; display:grid; place-items:center; padding:18px; background:color-mix(in srgb,var(--modal-backdrop) 72%,transparent); } .menu-auth-modal-card { position:relative; width:min(100%,390px); padding:28px 24px 24px; border:1px solid var(--modal-border); border-radius:12px; background:var(--modal-bg); box-shadow:0 20px 60px color-mix(in srgb,var(--shadow-color) 55%,transparent); color:var(--modal-text); font-family:Tahoma,Arial,sans-serif; text-align:center; } .menu-auth-modal-card h2 { margin:12px 0 8px; color:var(--modal-heading); font-size:18px; } .menu-auth-modal-card p { margin:0 0 20px; color:var(--modal-copy); font-size:14px; line-height:1.5; } .menu-auth-modal-card .btn { min-width:100px; } .menu-auth-modal-icon { display:grid; place-items:center; width:42px; height:42px; margin:0 auto; border-radius:50%; background:var(--modal-icon-bg); color:var(--modal-icon-text); font-size:24px; font-weight:700; } .menu-auth-modal-close { position:absolute; top:8px; right:10px; border:0; background:transparent; color:var(--modal-copy); cursor:pointer; font-size:26px; line-height:1; } .menu-auth-modal-close:hover { color:var(--modal-heading); }
        .site-navbar { width:100%; height:64px; position:fixed; inset:0 0 auto 0; z-index:30; display:flex; align-items:center; justify-content:space-between; gap:18px; padding:0 22px; background:var(--navbar-bg); border-bottom:1px solid var(--line); box-shadow:0 5px 18px color-mix(in srgb,var(--shadow-color) 22%,transparent); }
        .navbar-left, .navbar-right, .brand-link, .navbar-user, .navbar-logout { display:flex; align-items:center; }
        .navbar-left, .navbar-right { gap:12px; } .brand-link { gap:10px; text-decoration:none; } .brand-name { color:var(--text-header); font-size:.78rem; font-weight:700; letter-spacing:.14em; }
        .navbar-theme-link{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid var(--cyan);border-radius:7px;background:color-mix(in srgb,var(--cyan) 12%,transparent);color:var(--text-header);font-family:Tahoma,Arial,sans-serif;font-size:12px;font-weight:700;text-decoration:none;animation:themeAttention 1.45s ease-in-out infinite}.navbar-theme-link:hover{background:color-mix(in srgb,var(--cyan) 22%,transparent);color:var(--cyan)}@keyframes themeAttention{0%,100%{box-shadow:0 0 0 0 color-mix(in srgb,var(--cyan) 0%,transparent);transform:scale(1)}50%{box-shadow:0 0 0 4px color-mix(in srgb,var(--cyan) 20%,transparent);transform:scale(1.03)}}
        .brand-logo { display:block; width:82px; height:34px; padding:3px 7px; box-sizing:border-box; border-radius:7px; background:var(--logo-bg); object-fit:contain; object-position:center; }

        .icon-button { display:grid; place-items:center; width:38px; height:38px; border:0; border-radius:7px; background:transparent; color:var(--navbar-icon); cursor:pointer; } .icon-button:hover { background:color-mix(in srgb,var(--navbar-icon-hover-bg) 88%,transparent); color:var(--text-header); }
        .hamburger, .hamburger span { display:block; } .hamburger { width:18px; } .hamburger span { height:2px; margin:4px 0; background:currentColor; border-radius:2px; }
        .navbar-user { color:var(--text-header); font-size:.78rem; gap:7px; } .user-icon { color:var(--success); font-size:.7rem; } .navbar-logout { border:0; background:transparent; color:var(--logout); cursor:pointer; font-size:.78rem; gap:6px; } .navbar-logout:hover { color:var(--danger); } .conn-status{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border:1px solid var(--line);border-radius:7px;background:var(--panel);color:var(--text-title);font-size:.7rem;font-weight:700;white-space:nowrap} .conn-dot{width:8px;height:8px;border-radius:50%;background:#9AAEB4;transition:background .2s ease,box-shadow .2s ease} .conn-status.online .conn-dot{background:var(--success);box-shadow:0 0 0 4px color-mix(in srgb,var(--success) 18%,transparent)} .conn-status.offline .conn-dot{background:var(--danger);box-shadow:0 0 0 4px color-mix(in srgb,var(--danger) 18%,transparent)}
        @media (max-width:720px){.conn-label{display:none}.conn-status{padding:7px 8px}}
        .site-sidebar { position:fixed; top:64px; bottom:0; left:0; z-index:20; width:242px; overflow-y:auto; background:var(--sidebar-bg); border-right:1px solid var(--line); transition:width .22s ease, transform .22s ease; }
        .sidebar-brand { display:flex; align-items:center; gap:10px; padding:18px 20px; border-bottom:1px solid var(--line); } .sidebar-brand .brand-logo { width:76px; height:29px; padding:2px 6px; } .brand-text { color:var(--text-header); font-family:Tahoma, Arial, sans-serif; font-size:12px; font-weight:700; letter-spacing:.04em; }
        .sidebar-nav { padding:14px 10px; } .sidebar-link { display:flex; align-items:center; gap:11px; min-height:42px; padding:9px 12px; margin:3px 0; color:var(--text-sidebar); border-radius:8px; text-decoration:none; font-family:Tahoma, Arial, sans-serif; font-size:12px; transition:background .16s ease,color .16s ease; } .sidebar-link:hover, .sidebar-link.active { background:var(--sidebar-active-bg); color:var(--text-header); } .sidebar-link.active { box-shadow:inset 3px 0 var(--sidebar-active-indicator); } .sidebar-link.is-disabled { color:var(--sidebar-disabled); cursor:not-allowed; opacity:.8; } .sidebar-link.is-disabled .nav-icon { color:var(--sidebar-disabled); } .sidebar-link.is-disabled:hover { background:transparent; color:var(--sidebar-disabled); } .sidebar-link.is-disabled:hover .nav-icon { color:var(--sidebar-disabled); }
        .sidebar-group { margin-top:8px; } .sidebar-group.parent-hidden > .sidebar-group-toggle { display:none; } .sidebar-group.parent-hidden > .sidebar-group-items { padding-left:0; } .sidebar-group-toggle { display:flex; align-items:center; width:100%; min-height:34px; gap:8px; padding:8px 12px; border:0; background:transparent; color:var(--text-sidebar); font-family:Tahoma, Arial, sans-serif; font-size:12px; font-weight:700; letter-spacing:.02em; text-align:left; cursor:pointer; } .sidebar-group-toggle:hover { color:var(--text-header); } .group-chevron { display:grid; place-items:center; width:12px; font-size:1rem; line-height:1; transition:transform .18s ease; } .sidebar-group.is-open .group-chevron { transform:rotate(90deg); } .sidebar-group-items { padding-left:8px; } .sidebar-group-items[hidden] { display:none; }
        .nav-icon { display:grid; place-items:center; width:18px; color:var(--text-sidebar); font-size:1rem; } .sidebar-link:hover .nav-icon, .sidebar-link.active .nav-icon { color:var(--cyan); }
        .flatpickr-calendar,.flatpickr-months .flatpickr-month,.flatpickr-weekdays,.flatpickr-weekday,.flatpickr-current-month{background:var(--panel)!important;color:var(--text-title)!important;border-color:var(--line)!important}.flatpickr-calendar{box-shadow:var(--shadow)!important}.flatpickr-day{color:var(--text-body)!important;border-color:transparent!important}.flatpickr-day:hover{background:var(--table-hover)!important;border-color:var(--line)!important}.flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange{background:var(--button-primary)!important;color:var(--text-button)!important;border-color:var(--button-primary)!important}.flatpickr-day.today{border-color:var(--focus-ring)!important}.flatpickr-time input,.flatpickr-time .flatpickr-am-pm{background:var(--input-bg)!important;color:var(--input-text)!important}.flatpickr-time .flatpickr-time-separator{color:var(--text-body)!important}.flatpickr-months .flatpickr-prev-month svg,.flatpickr-months .flatpickr-next-month svg{fill:var(--text-title)!important}
        .layout-content { width:calc(100% - 242px); max-width:100%; min-width:0; min-height:100vh; padding-top:64px; margin-left:242px; overflow-x:clip; transition:width .22s ease,margin-left .22s ease; }         .sidebar-collapsed .site-sidebar { width:72px; } .sidebar-collapsed .layout-content { width:calc(100% - 72px); margin-left:72px; } .sidebar-collapsed .brand-text, .sidebar-collapsed .nav-label, .sidebar-collapsed .sidebar-divider { display:none; } .sidebar-collapsed .sidebar-brand { justify-content:center; padding-left:8px; padding-right:8px; } .sidebar-collapsed .sidebar-brand .brand-logo { width:54px; height:26px; padding:2px 4px; } .sidebar-collapsed .sidebar-link { justify-content:center; padding-left:8px; padding-right:8px; } .sidebar-collapsed .sidebar-group-toggle { justify-content:center; padding-left:8px; padding-right:8px; } .sidebar-collapsed .sidebar-group-items { display:none !important; padding-left:0; } .sidebar-collapsed .sidebar-group { margin-top:4px; }
        @media (max-width: 1100px) { .stats { grid-template-columns:repeat(3, minmax(0,1fr)); } }
        @media (max-width: 720px) { .app-shell { width:100%; max-width:100%; min-height:calc(100vh - 58px); } .layout-content { width:100%; max-width:100vw; overflow-x:clip; } .monthly-table-heading { align-items:flex-start; } .monthly-year-select { width:100%; min-width:0; } .monthly-filter > div:first-child { flex:1 1 100%; } .monthly-filter .btn { width:100%; } .chart-wrap-tall { height:220px; } .year-badge { font-size:.62rem; } .site-navbar { height:58px; padding:0 12px; } .site-sidebar { top:58px; width:250px; transform:translateX(-100%); } .layout-content, .sidebar-collapsed .layout-content { width:100%; margin-left:0; padding-top:58px; } .app-shell { padding:14px 12px 24px; } .topbar { display:block; margin-bottom:16px; } .topbar h1 { font-size:1.65rem; } .toolbar { justify-content:flex-start; margin-top:12px; } .filter { gap:8px; padding:11px; } .filter input { min-width:0; width:100%; font-size:.78rem; } .filter > div:first-child { flex:1 1 100%; } .filter .btn, .filter #btnExport { flex:1 1 calc(50% - 4px); text-align:center; } .charts { grid-template-columns:1fr; gap:10px; } .panel { padding:13px; min-height:0; } .chart-wrap { height:220px; } .stats { grid-template-columns:repeat(2, minmax(0,1fr)); gap:8px; } .stat { min-height:96px; padding:11px; } .stat-label { font-size:.6rem; } .stat-value { font-size:1.08rem; margin-top:8px; } .stat-note { font-size:.58rem; } .table-scroll { margin:0 -13px; padding:0 13px 5px; -webkit-overflow-scrolling:touch; } .site-sidebar { top:58px; } .sidebar-collapsed .site-sidebar { width:250px; transform:translateX(-100%); } .sidebar-collapsed .brand-text, .sidebar-collapsed .nav-label, .sidebar-collapsed .sidebar-divider { display:block; } .sidebar-collapsed .sidebar-brand { justify-content:flex-start; padding-left:20px; padding-right:20px; } .sidebar-collapsed .sidebar-link { justify-content:flex-start; padding-left:12px; padding-right:12px; } .sidebar-collapsed .sidebar-group-toggle { justify-content:flex-start; padding-left:12px; padding-right:12px; } .sidebar-collapsed .sidebar-group-items { display:none !important; } .sidebar-collapsed .sidebar-group-items[hidden] { display:none !important; } .sidebar-open .site-sidebar { transform:translateX(0); } .brand-name { display:none; } .navbar-user { max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; } .navbar-theme-link{padding:7px 8px}.navbar-theme-link span:last-child{display:none}.navbar-logout { font-size:.72rem; } }
        @media (max-width: 430px) { .mobile-table-hint { display:block; } .stats { grid-template-columns:1fr 1fr; } .stat-value { font-size:.98rem; } .timer { font-size:.62rem; } .navbar-user { max-width:80px; } .navbar-theme-link{padding:6px 7px}.panel h2 { font-size:.68rem; } .chart-wrap { height:205px; } }
        /* Theme guard: page-local styles must inherit the active surface/text pairing. */
        body .layout-shell .layout-content table:not(.preview-table):not(.home-table) :is(th, td) {
            border-color: var(--table-border) !important;
            color: var(--text-table) !important;
        }
        body .layout-shell .layout-content table:not(.preview-table):not(.home-table) th {
            background: var(--table-header) !important;
        }
        body .layout-shell .layout-content table:not(.preview-table):not(.home-table) td {
            background: var(--table-row) !important;
        }
        body .layout-shell .layout-content table:not(.preview-table):not(.home-table) tbody tr:nth-child(even) td {
            background: var(--table-alt) !important;
        }
        body .layout-shell .layout-content table:not(.preview-table):not(.home-table) tbody tr:hover td {
            background: var(--table-hover) !important;
        }
        body .layout-shell .layout-content table:not(.preview-table):not(.home-table) :is(th:first-child, td:first-child) {
            background: var(--table-sticky) !important;
        }
        body .layout-shell .layout-content :is(input, select, textarea):not([type='checkbox']):not([type='radio']):not([type='color']) {
            background: var(--input-bg) !important;
            border-color: var(--line) !important;
            color: var(--input-text) !important;
        }
        body .layout-shell .layout-content :is(input, select, textarea)::placeholder {
            color: var(--input-placeholder) !important;
        }
        body .layout-shell .layout-content :is(.page-link, .page-disabled) {
            background: var(--button-secondary-bg) !important;
            border-color: var(--line) !important;
            color: var(--button-secondary-text) !important;
        }
        body .layout-shell .layout-content .page-current {
            background: var(--button-primary) !important;
            border-color: var(--button-primary) !important;
            color: var(--text-button) !important;
        }
        body .layout-shell .layout-content .page-disabled {
            background: var(--button-disabled-bg) !important;
            color: var(--button-disabled-text) !important;
        }
    </style>
</head>
<body>
    <div class="layout-shell">
        @include('layouts.partials.navbar')
        @include('layouts.partials.sidebar')
        <main class="layout-content">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
    <script>
        (() => {
            const toggle = document.getElementById('sidebarToggle');
            const shell = document.querySelector('.layout-shell');
            if (!toggle || !shell) return;
            const sidebar = document.getElementById('appSidebar');
            toggle.addEventListener('click', event => {
                event.stopPropagation();
                const isMobile = window.matchMedia('(max-width: 720px)').matches;
                const stateClass = isMobile ? 'sidebar-open' : 'sidebar-collapsed';
                const active = shell.classList.toggle(stateClass);
                toggle.setAttribute('aria-expanded', String(active));
                window.dispatchEvent(new Event('layout-resized'));
            });
            document.addEventListener('click', event => {
                const target = event.target;
                if (!sidebar || !(target instanceof Element)) return;
                if (sidebar.contains(target) || toggle.contains(target)) return;

                const isMobile = window.matchMedia('(max-width: 720px)').matches;
                if (isMobile) {
                    shell.classList.remove('sidebar-open');
                } else {
                    shell.classList.add('sidebar-collapsed');
                }
                toggle.setAttribute('aria-expanded', 'false');
                window.dispatchEvent(new Event('layout-resized'));
            });
            document.querySelectorAll('.sidebar-group-toggle').forEach(button => {
                button.addEventListener('click', event => {
                    event.stopPropagation();
                    const group = button.closest('.sidebar-group');
                    const items = group?.querySelector('.sidebar-group-items');
                    if (!group || !items || shell.classList.contains('sidebar-collapsed')) return;

                    const isOpen = group.classList.toggle('is-open');
                    button.setAttribute('aria-expanded', String(isOpen));
                    if (isOpen) {
                        items.removeAttribute('hidden');
                    } else {
                        items.setAttribute('hidden', '');
                    }
                });
            });
            document.querySelectorAll('.is-placeholder').forEach(link => {
                link.addEventListener('click', event => {
                    event.preventDefault();
                    window.alert(`${link.dataset.placeholder} belum terhubung ke route Laravel.`);
                });
            });

            // Keep the shell state in sync when a mobile browser changes its visual viewport.
            const viewportQuery = window.matchMedia('(max-width: 720px)');
            const syncViewportLayout = () => {
                const isMobile = viewportQuery.matches;
                if (isMobile) {
                    shell.classList.remove('sidebar-collapsed');
                } else {
                    shell.classList.remove('sidebar-open');
                }
                window.dispatchEvent(new Event('layout-resized'));
            };

            syncViewportLayout();
            window.addEventListener('resize', syncViewportLayout, { passive: true });
            window.addEventListener('orientationchange', syncViewportLayout, { passive: true });
            window.addEventListener('pageshow', syncViewportLayout, { passive: true });
            window.visualViewport?.addEventListener('resize', syncViewportLayout, { passive: true });
            viewportQuery.addEventListener?.('change', syncViewportLayout);
            document.addEventListener('livewire:navigated', syncViewportLayout);
        })();
        window.addEventListener('theme-saved', () => window.location.reload());

        (() => {
            const el = document.getElementById('connStatus');
            if (!el) return;
            const label = el.querySelector('.conn-label');
            const set = () => {
                const online = navigator.onLine;
                el.classList.toggle('online', online);
                el.classList.toggle('offline', !online);
                if (label) label.textContent = online ? 'Online' : 'Offline';
            };
            set();
            window.addEventListener('online', set);
            window.addEventListener('offline', set);
        })();
    </script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {});
        }
    </script>
    @livewireScripts
</body>
</html>
