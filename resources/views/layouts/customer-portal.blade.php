<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PAR Mobile | Customer Portal' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    @php($themeDefaults = config('theme.defaults', []))
    <style>
        :root { color-scheme:dark; @foreach($themeDefaults as $key => $value) --{{ $key }}:{{ $value }}; --{{ str_replace('_', '-', $key) }}:{{ $value }}; @endforeach --ink:var(--text-header); --body-text:var(--text-body); --button-text:var(--text-button); --footer-text:var(--text-footer); --accent:var(--button-primary); --accent-strong:var(--focus-ring); --panel-deep:var(--input-bg); }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:radial-gradient(circle at 18% 15%,color-mix(in srgb,var(--cyan) 12%,transparent),transparent 31%),radial-gradient(circle at 88% 84%,color-mix(in srgb,var(--success) 8%,transparent),transparent 34%),linear-gradient(135deg,var(--bg) 0%,var(--panel) 100%); color:var(--body-text); font-family:'Space Grotesk',sans-serif; display:grid; place-items:center; padding:24px; }
        .login-box { width:min(100%,420px); }
        .brand { text-align:center; margin-bottom:18px; }
        .brand-logo { display:block; width:184px; height:auto; margin:0 auto 13px; object-fit:contain; filter:drop-shadow(0 10px 20px color-mix(in srgb,var(--cyan) 12%,transparent)); }
        .brand h1 { color:var(--ink); font-size:1.8rem; margin:0; letter-spacing:-.06em; }
        .brand h1 strong { color:var(--ink); }
        .brand h1 span { color:var(--accent); font-weight:400; }
        .card { background:color-mix(in srgb,var(--panel) 96%,transparent); border:1px solid var(--line); border-radius:14px; padding:32px; box-shadow:0 18px 45px color-mix(in srgb,var(--shadow-color) 32%,transparent),0 0 0 1px color-mix(in srgb,var(--text-header) 2%,transparent); }
        .card h2 { color:var(--body-text); font-size:1rem; text-align:center; margin:0 0 25px; font-weight:500; }
        .field { margin-bottom:17px; }
        label { display:block; color:var(--body-text); font-size:.78rem; font-weight:600; margin-bottom:7px; }
        .control { width:100%; height:46px; border:1px solid var(--line); border-radius:8px; padding:0 13px; background:var(--panel-deep); color:var(--body-text); outline:none; transition:border-color .16s ease,box-shadow .16s ease,background .16s ease; }
        .control::placeholder { color:var(--input-placeholder); }
        .control:focus { border-color:var(--accent-strong); background:var(--input-focus-bg); box-shadow:0 0 0 3px color-mix(in srgb,var(--focus-ring) 24%,transparent); }
        select.control { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 13px center; padding-right:36px; }
        .submit { width:100%; height:46px; margin-top:3px; background:var(--accent); color:var(--button-text); border:0; border-radius:8px; font-weight:800; letter-spacing:.04em; cursor:pointer; transition:transform .16s ease,background .16s ease,box-shadow .16s ease; box-shadow:0 8px 20px color-mix(in srgb,var(--accent) 14%,transparent); }
        .submit:hover { background:var(--button-primary-hover); box-shadow:0 10px 24px color-mix(in srgb,var(--accent) 20%,transparent); }
        .submit:active { transform:scale(.98); }
        .submit[disabled] { opacity:.65; cursor:wait; }
        .error { color:var(--danger); font-size:.74rem; margin-top:6px; }
        .footer { border-top:1px solid var(--line); margin-top:25px; padding-top:18px; color:var(--footer-text); text-align:center; font-size:.7rem; }
        .links { text-align:center; margin-top:18px; font-size:.82rem; }
        .links a { color:var(--accent); text-decoration:none; font-weight:500; }
        .links a:hover { text-decoration:underline; }
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:18px; font-size:.82rem; }
        .alert-success { background:color-mix(in srgb,var(--success) 15%,transparent); border:1px solid color-mix(in srgb,var(--success) 30%,transparent); color:var(--success); }
        .alert-danger { background:color-mix(in srgb,var(--danger) 15%,transparent); border:1px solid color-mix(in srgb,var(--danger) 30%,transparent); color:var(--danger); }
        .alert-warning { background:color-mix(in srgb,var(--warning) 15%,transparent); border:1px solid color-mix(in srgb,var(--warning) 30%,transparent); color:var(--warning); }
        @media (max-width:480px) { body { padding:16px; } .login-box { width:100%; } .brand-logo { width:min(184px,68vw); } .card { padding:25px 20px; } .card h2 { font-size:.92rem; margin-bottom:22px; } }
    </style>
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
