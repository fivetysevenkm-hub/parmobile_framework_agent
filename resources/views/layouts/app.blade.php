<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    @php($themeColors = app(\App\Services\ThemePreferenceService::class)->currentForUser(auth()->user())['colors'])
    <style>
        :root { color-scheme: dark; --bg: {{ $themeColors['bg'] }}; --panel: {{ $themeColors['panel'] }}; --panel-2: {{ $themeColors['panel_2'] }}; --line: {{ $themeColors['line'] }}; --muted: {{ $themeColors['muted'] }}; --cyan: {{ $themeColors['cyan'] }}; --orange: {{ $themeColors['orange'] }}; --red: {{ $themeColors['red'] }}; --lime: {{ $themeColors['lime'] }}; --text: {{ $themeColors['text'] }}; --navbar-bg: {{ $themeColors['navbar_bg'] }}; --sidebar-bg: {{ $themeColors['sidebar_bg'] }}; --input-bg: {{ $themeColors['input_bg'] }}; --table-header: {{ $themeColors['table_header'] }}; --table-alt: {{ $themeColors['table_alt'] }}; --success: {{ $themeColors['success'] }}; --warning: {{ $themeColors['warning'] }}; --danger: {{ $themeColors['danger'] }}; }
        * { box-sizing: border-box; }
        html { overflow-x:hidden; } body { margin: 0; background: var(--bg); color: var(--text); font-family: 'Space Grotesk', sans-serif; overflow-x:hidden; }
        button, input { font: inherit; }
        .app-shell { width:100%; min-width:0; min-height:calc(100vh - 64px); padding:24px; }
        .topbar { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:22px; }
        .eyebrow { color:var(--muted); font-family:'JetBrains Mono',monospace; font-size:.72rem; letter-spacing:.18em; text-transform:uppercase; }
        h1 { margin: 5px 0 6px; font-size: clamp(1.7rem, 3vw, 2.7rem); letter-spacing: -.05em; }
        .cyan { color:var(--cyan); } .orange { color:var(--orange); } .red { color:var(--red); } .lime { color:var(--lime); }
        .muted { color:var(--muted); }
        .toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
        .user-chip, .timer { border:1px solid var(--line); background:var(--panel); padding:9px 12px; border-radius:8px; font-family:'JetBrains Mono',monospace; font-size:.72rem; }
        .btn { border:0; border-radius:7px; padding:10px 14px; font-weight:700; cursor:pointer; transition:transform .16s ease, filter .16s ease; }
        .btn:active { transform:scale(.97); } .btn-cyan { background:var(--cyan); color:#041014; } .btn-ghost { background:transparent; color:#fff; border:1px solid var(--line); }
        .filter { background:#050708; border:1px solid var(--line); border-radius:10px; padding:14px; display:flex; gap:10px; align-items:end; flex-wrap:wrap; margin-bottom:18px; }
        .filter label { color:var(--muted); display:block; font-size:.7rem; letter-spacing:.08em; margin-bottom:6px; text-transform:uppercase; }
        .filter input { background:#10171b; border:1px solid var(--line); color:var(--cyan); border-radius:7px; padding:10px 12px; min-width:300px; }
        .error { color:#ffb4b8; font-size:.78rem; margin-top:6px; }
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
        th, td { border:1px solid #26333a; padding:11px 10px; text-align:center; white-space:nowrap; } th { background:#0b1013; color:var(--cyan); position:sticky; top:0; z-index:2; } td:first-child, th:first-child { position:sticky; left:0; background:#0b1013; text-align:left; z-index:1; } th:first-child { z-index:3; }
        .row-label { color:var(--muted); font-weight:700; text-transform:uppercase; } .zero { color:#45545b; } .bright { color:#f5f7f8; } .strong { font-weight:700; } .mobile-table-hint { display:none; color:var(--muted); font-size:.65rem; margin:-4px 0 10px; } .monthly-year-select { min-width:150px; background:#10171b; border:1px solid var(--line); color:var(--cyan); border-radius:7px; padding:10px 12px; } .monthly-filter .btn { white-space:nowrap; } .monthly-table-heading { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; } .month-label { color:#fff; text-align:left; } .month-label small { display:block; margin-top:4px; font-size:.58rem; } .month-qty-row td { background:#0d1519; } .month-end-row td { border-bottom:2px solid #333; } .chart-wrap-tall { height:280px; } .year-badge { font-family:'JetBrains Mono',monospace; font-size:.7rem; }
        .layout-shell { width:100%; min-height:100vh; } .menu-auth-modal[hidden] { display:none; } .menu-auth-modal { position:fixed; inset:0; z-index:100; display:grid; place-items:center; padding:18px; background:rgba(0,0,0,.72); } .menu-auth-modal-card { position:relative; width:min(100%,390px); padding:28px 24px 24px; border:1px solid #3b5360; border-radius:12px; background:#111a1f; box-shadow:0 20px 60px rgba(0,0,0,.55); color:#eef6f7; font-family:Tahoma,Arial,sans-serif; text-align:center; } .menu-auth-modal-card h2 { margin:12px 0 8px; color:#fff; font-size:18px; } .menu-auth-modal-card p { margin:0 0 20px; color:#c0cdd1; font-size:14px; line-height:1.5; } .menu-auth-modal-card .btn { min-width:100px; } .menu-auth-modal-icon { display:grid; place-items:center; width:42px; height:42px; margin:0 auto; border-radius:50%; background:#6d2631; color:#ffd6da; font-size:24px; font-weight:700; } .menu-auth-modal-close { position:absolute; top:8px; right:10px; border:0; background:transparent; color:#aab5bb; cursor:pointer; font-size:26px; line-height:1; } .menu-auth-modal-close:hover { color:#fff; }
        .site-navbar { width:100%; height:64px; position:fixed; inset:0 0 auto 0; z-index:30; display:flex; align-items:center; justify-content:space-between; gap:18px; padding:0 22px; background:var(--navbar-bg); border-bottom:1px solid var(--line); box-shadow:0 5px 18px rgba(0,0,0,.22); }
        .navbar-left, .navbar-right, .brand-link, .navbar-user, .navbar-logout { display:flex; align-items:center; }
        .navbar-left, .navbar-right { gap:12px; } .brand-link { gap:10px; text-decoration:none; } .brand-name { color:var(--text); font-size:.78rem; font-weight:700; letter-spacing:.14em; }
        .navbar-theme-link{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid var(--cyan);border-radius:7px;background:color-mix(in srgb,var(--cyan) 12%,transparent);color:var(--cyan);font-family:Tahoma,Arial,sans-serif;font-size:12px;font-weight:700;text-decoration:none;animation:themeAttention 1.45s ease-in-out infinite}.navbar-theme-link:hover{background:color-mix(in srgb,var(--cyan) 22%,transparent);color:var(--text)}@keyframes themeAttention{0%,100%{box-shadow:0 0 0 0 color-mix(in srgb,var(--cyan) 0%,transparent);transform:scale(1)}50%{box-shadow:0 0 0 4px color-mix(in srgb,var(--cyan) 20%,transparent);transform:scale(1.03)}}
        .brand-logo { display:block; width:82px; height:34px; padding:3px 7px; box-sizing:border-box; border-radius:7px; background:#fff; object-fit:contain; object-position:center; }

        .icon-button { display:grid; place-items:center; width:38px; height:38px; border:0; border-radius:7px; background:transparent; color:#cbd5e0; cursor:pointer; } .icon-button:hover { background:rgba(255,255,255,.08); color:#fff; }
        .hamburger, .hamburger span { display:block; } .hamburger { width:18px; } .hamburger span { height:2px; margin:4px 0; background:currentColor; border-radius:2px; }
        .navbar-user { color:#cbd5e0; font-size:.78rem; gap:7px; } .user-icon { color:#2dd4bf; font-size:.7rem; } .navbar-logout { border:0; background:transparent; color:#ff4d91; cursor:pointer; font-size:.78rem; gap:6px; }
        .site-sidebar { position:fixed; top:64px; bottom:0; left:0; z-index:20; width:242px; overflow-y:auto; background:var(--sidebar-bg); border-right:1px solid var(--line); transition:width .22s ease, transform .22s ease; }
        .sidebar-brand { display:flex; align-items:center; gap:10px; padding:18px 20px; border-bottom:1px solid #26333a; } .sidebar-brand .brand-logo { width:76px; height:29px; padding:2px 6px; } .brand-text { color:#fff; font-family:Tahoma, Arial, sans-serif; font-size:12px; font-weight:700; letter-spacing:.04em; }
        .sidebar-nav { padding:14px 10px; } .sidebar-link { display:flex; align-items:center; gap:11px; min-height:42px; padding:9px 12px; margin:3px 0; color:#aab5bb; border-radius:8px; text-decoration:none; font-family:Tahoma, Arial, sans-serif; font-size:12px; transition:background .16s ease,color .16s ease; } .sidebar-link:hover, .sidebar-link.active { background:#1e3038; color:#fff; } .sidebar-link.active { box-shadow:inset 3px 0 #00e5ff; } .sidebar-link.is-disabled { color:#68747a; cursor:not-allowed; opacity:.8; } .sidebar-link.is-disabled .nav-icon { color:#68747a; } .sidebar-link.is-disabled:hover { background:transparent; color:#68747a; } .sidebar-link.is-disabled:hover .nav-icon { color:#68747a; }
        .sidebar-group { margin-top:8px; } .sidebar-group.parent-hidden > .sidebar-group-toggle { display:none; } .sidebar-group.parent-hidden > .sidebar-group-items { padding-left:0; } .sidebar-group-toggle { display:flex; align-items:center; width:100%; min-height:34px; gap:8px; padding:8px 12px; border:0; background:transparent; color:#91a0a6; font-family:Tahoma, Arial, sans-serif; font-size:12px; font-weight:700; letter-spacing:.02em; text-align:left; cursor:pointer; } .sidebar-group-toggle:hover { color:#cbd5e0; } .group-chevron { display:grid; place-items:center; width:12px; font-size:1rem; line-height:1; transition:transform .18s ease; } .sidebar-group.is-open .group-chevron { transform:rotate(90deg); } .sidebar-group-items { padding-left:8px; } .sidebar-group-items[hidden] { display:none; }
        .nav-icon { display:grid; place-items:center; width:18px; color:#aab5bb; font-size:1rem; } .sidebar-link:hover .nav-icon, .sidebar-link.active .nav-icon { color:var(--cyan); }
        .layout-content { width:calc(100% - 242px); min-width:0; min-height:100vh; padding-top:64px; margin-left:242px; transition:width .22s ease,margin-left .22s ease; }         .sidebar-collapsed .site-sidebar { width:72px; } .sidebar-collapsed .layout-content { width:calc(100% - 72px); margin-left:72px; } .sidebar-collapsed .brand-text, .sidebar-collapsed .nav-label, .sidebar-collapsed .sidebar-divider { display:none; } .sidebar-collapsed .sidebar-brand { justify-content:center; padding-left:8px; padding-right:8px; } .sidebar-collapsed .sidebar-brand .brand-logo { width:54px; height:26px; padding:2px 4px; } .sidebar-collapsed .sidebar-link { justify-content:center; padding-left:8px; padding-right:8px; } .sidebar-collapsed .sidebar-group-toggle { justify-content:center; padding-left:8px; padding-right:8px; } .sidebar-collapsed .sidebar-group-items { display:none !important; padding-left:0; } .sidebar-collapsed .sidebar-group { margin-top:4px; }
        @media (max-width: 1100px) { .stats { grid-template-columns:repeat(3, minmax(0,1fr)); } }
        @media (max-width: 720px) { .app-shell { min-height:calc(100vh - 58px); } .layout-content { width:100%; } .monthly-table-heading { align-items:flex-start; } .monthly-year-select { width:100%; min-width:0; } .monthly-filter > div:first-child { flex:1 1 100%; } .monthly-filter .btn { width:100%; } .chart-wrap-tall { height:220px; } .year-badge { font-size:.62rem; } .site-navbar { height:58px; padding:0 12px; } .site-sidebar { top:58px; width:250px; transform:translateX(-100%); } .layout-content, .sidebar-collapsed .layout-content { width:100%; margin-left:0; padding-top:58px; } .app-shell { padding:14px 12px 24px; } .topbar { display:block; margin-bottom:16px; } .topbar h1 { font-size:1.65rem; } .toolbar { justify-content:flex-start; margin-top:12px; } .filter { gap:8px; padding:11px; } .filter input { min-width:0; width:100%; font-size:.78rem; } .filter > div:first-child { flex:1 1 100%; } .filter .btn, .filter #btnExport { flex:1 1 calc(50% - 4px); text-align:center; } .charts { grid-template-columns:1fr; gap:10px; } .panel { padding:13px; min-height:0; } .chart-wrap { height:220px; } .stats { grid-template-columns:repeat(2, minmax(0,1fr)); gap:8px; } .stat { min-height:96px; padding:11px; } .stat-label { font-size:.6rem; } .stat-value { font-size:1.08rem; margin-top:8px; } .stat-note { font-size:.58rem; } .table-scroll { margin:0 -13px; padding:0 13px 5px; -webkit-overflow-scrolling:touch; } .site-sidebar { top:58px; } .sidebar-collapsed .site-sidebar { width:250px; transform:translateX(-100%); } .sidebar-collapsed .brand-text, .sidebar-collapsed .nav-label, .sidebar-collapsed .sidebar-divider { display:block; } .sidebar-collapsed .sidebar-brand { justify-content:flex-start; padding-left:20px; padding-right:20px; } .sidebar-collapsed .sidebar-link { justify-content:flex-start; padding-left:12px; padding-right:12px; } .sidebar-collapsed .sidebar-group-toggle { justify-content:flex-start; padding-left:12px; padding-right:12px; } .sidebar-collapsed .sidebar-group-items { display:none !important; } .sidebar-collapsed .sidebar-group-items[hidden] { display:none !important; } .sidebar-open .site-sidebar { transform:translateX(0); } .brand-name { display:none; } .navbar-user { max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; } .navbar-theme-link{padding:7px 8px}.navbar-theme-link span:last-child{display:none}.navbar-logout { font-size:.72rem; } }
        @media (max-width: 430px) { .mobile-table-hint { display:block; } .stats { grid-template-columns:1fr 1fr; } .stat-value { font-size:.98rem; } .timer { font-size:.62rem; } .navbar-user { max-width:80px; } .navbar-theme-link{padding:6px 7px}.panel h2 { font-size:.68rem; } .chart-wrap { height:205px; } }
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
        })();
        window.addEventListener('theme-saved', () => window.location.reload());
    </script>
    @livewireScripts
</body>
</html>
