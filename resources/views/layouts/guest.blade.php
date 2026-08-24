<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PAR Mobile | Log in' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        :root { color-scheme: light; --ink:#1f2b42; --accent:#405080; --bg:#eef1f6; }
        * { box-sizing:border-box; } body { margin:0; min-height:100vh; background:linear-gradient(135deg,#e0e4ed 0%,#fcfcfc 100%); color:var(--ink); font-family:'Space Grotesk',sans-serif; display:grid; place-items:center; padding:20px; }
        .login-box { width:min(100%,390px); } .brand { text-align:center; margin-bottom:16px; } .brand-mark { width:82px; height:82px; margin:0 auto 9px; border-radius:20px; background:var(--accent); color:white; display:grid; place-items:center; font-size:1.7rem; font-weight:700; letter-spacing:-.1em; box-shadow:0 12px 26px rgba(64,80,128,.22); } .brand h1 { font-size:1.8rem; margin:0; letter-spacing:-.06em; } .brand h1 span { font-weight:400; }
        .card { background:#fff; border-radius:16px; padding:32px; box-shadow:0 12px 30px rgba(0,0,0,.08); } .card h2 { font-size:1rem; text-align:center; margin:0 0 24px; font-weight:500; color:#657087; }
        .field { margin-bottom:16px; } label { display:block; color:#58647c; font-size:.8rem; font-weight:600; margin-bottom:7px; } .control { width:100%; height:46px; border:1px solid #d9dee8; border-radius:8px; padding:0 13px; color:var(--ink); outline:none; transition:border-color .16s ease, box-shadow .16s ease; } .control:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(64,80,128,.12); }
        .remember { display:flex; align-items:center; gap:8px; color:#6c7689; font-size:.78rem; margin:4px 0 22px; } .remember input { accent-color:var(--accent); } .submit { width:100%; height:46px; background:var(--accent); color:#fff; border:0; border-radius:8px; font-weight:700; cursor:pointer; transition:transform .16s ease,background .16s ease; } .submit:hover { background:#2d3a5e; } .submit:active { transform:scale(.98); } .submit[disabled] { opacity:.65; cursor:wait; }
        .error { color:#b4232d; font-size:.76rem; margin-top:6px; } .footer { border-top:1px solid #edf0f4; margin-top:25px; padding-top:18px; color:#9aa2b1; text-align:center; font-size:.72rem; }
    </style>
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
