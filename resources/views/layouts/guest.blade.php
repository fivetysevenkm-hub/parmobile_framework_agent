<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PAR Mobile | Log in' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @livewireStyles
    <style>
        :root { color-scheme:dark; --ink:#f5f7f8; --accent:#2dd4bf; --accent-strong:#00e5ff; --bg:#0b1013; --panel:#111820; --panel-deep:#0d1418; --line:#26333a; --muted:#9aa9b0; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:radial-gradient(circle at 18% 15%,rgba(0,229,255,.12),transparent 31%),radial-gradient(circle at 88% 84%,rgba(45,212,191,.08),transparent 34%),linear-gradient(135deg,#0b1013 0%,#111820 100%); color:var(--ink); font-family:'Space Grotesk',sans-serif; display:grid; place-items:center; padding:24px; }
        .login-box { width:min(100%,420px); }
        .brand { text-align:center; margin-bottom:18px; }
        .brand-logo { display:block; width:184px; height:auto; margin:0 auto 13px; object-fit:contain; filter:drop-shadow(0 10px 20px rgba(0,229,255,.12)); }
        .brand h1 { color:#fff; font-size:1.8rem; margin:0; letter-spacing:-.06em; }
        .brand h1 strong { color:#fff; }
        .brand h1 span { color:var(--accent); font-weight:400; }
        .card { background:rgba(17,24,32,.96); border:1px solid var(--line); border-radius:14px; padding:32px; box-shadow:0 18px 45px rgba(0,0,0,.32),0 0 0 1px rgba(255,255,255,.02); }
        .card h2 { color:#cbd5e0; font-size:1rem; text-align:center; margin:0 0 25px; font-weight:500; }
        .field { margin-bottom:17px; }
        label { display:block; color:#aab5bb; font-size:.78rem; font-weight:600; margin-bottom:7px; }
        .control { width:100%; height:46px; border:1px solid var(--line); border-radius:8px; padding:0 13px; background:var(--panel-deep); color:var(--ink); outline:none; transition:border-color .16s ease,box-shadow .16s ease,background .16s ease; }
        .control::placeholder { color:#65747b; }
        .control:focus { border-color:var(--accent-strong); background:#101b21; box-shadow:0 0 0 3px rgba(0,229,255,.12); }
        .submit { width:100%; height:46px; margin-top:3px; background:var(--accent); color:#071116; border:0; border-radius:8px; font-weight:800; letter-spacing:.04em; cursor:pointer; transition:transform .16s ease,background .16s ease,box-shadow .16s ease; box-shadow:0 8px 20px rgba(45,212,191,.14); }
        .submit:hover { background:#5eead4; box-shadow:0 10px 24px rgba(45,212,191,.2); }
        .submit:active { transform:scale(.98); }
        .submit[disabled] { opacity:.65; cursor:wait; }
        .error { color:#ff9eaa; font-size:.74rem; margin-top:6px; }
        .captcha-field { min-height:66px; }
        .captcha-field .cf-turnstile { min-height:65px; }
        .footer { border-top:1px solid var(--line); margin-top:25px; padding-top:18px; color:#65747b; text-align:center; font-size:.7rem; }
        @media (max-width:480px) { body { padding:16px; } .login-box { width:100%; } .brand-logo { width:min(184px,68vw); } .card { padding:25px 20px; } .card h2 { font-size:.92rem; margin-bottom:22px; } }
    </style>
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
