@php
    $menuCount = count($accessibleMenus);
    $groupCount = collect($accessibleMenus)->pluck('group')->filter()->unique()->count();

    $connHost = strtolower((string) request()->getHost());
    $connPort = request()->getPort();
    $connScheme = request()->isSecure() ? 'https' : 'http';
    $connNetwork = 'Lainnya';
    if (in_array($connHost, ['localhost', '127.0.0.1', '::1'], true)) {
        $connNetwork = 'Localhost / Server Lokal';
    } elseif ($connHost === '192.168.100.225') {
        $connNetwork = 'Jaringan Lokal (LAN)';
    } elseif ($connHost === '180.250.83.205') {
        $connNetwork = 'IP Public (Internet)';
    }
    $connAddress = $connHost . (in_array((int) $connPort, [80, 443], true) ? '' : ':' . $connPort);
@endphp

<div class="home-page">
    <style>
        .home-page {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: clamp(1rem, 2.2vw, 2rem) clamp(.75rem, 1.7vw, 1.5rem) 3rem;
            overflow: hidden;
        }

        .home-page *,
        .home-page *::before,
        .home-page *::after {
            box-sizing: border-box;
            min-width: 0;
        }

        .home-hero {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(260px, .8fr);
            gap: clamp(1.5rem, 4vw, 4rem);
            align-items: center;
            min-height: 300px;
            padding: clamp(1.5rem, 3.5vw, 3.5rem);
            overflow: hidden;
            border: 1px solid color-mix(in srgb, var(--cyan) 30%, var(--line));
            border-radius: 24px;
            background:
                radial-gradient(circle at 88% 8%, color-mix(in srgb, var(--cyan) 18%, transparent), transparent 34%),
                radial-gradient(circle at 10% 110%, color-mix(in srgb, var(--orange) 13%, transparent), transparent 32%),
                linear-gradient(135deg, color-mix(in srgb, var(--navbar-bg) 95%, #06131a), color-mix(in srgb, var(--panel) 80%, #09232d) 58%, #071117);
            box-shadow: 0 22px 55px color-mix(in srgb, var(--shadow-color) 35%, transparent);
        }

        .home-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .24;
            pointer-events: none;
            background-image: linear-gradient(color-mix(in srgb, var(--cyan) 10%, transparent) 1px, transparent 1px), linear-gradient(90deg, color-mix(in srgb, var(--cyan) 10%, transparent) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: linear-gradient(120deg, black, transparent 68%);
        }

        .home-hero::after {
            content: "";
            position: absolute;
            right: -96px;
            bottom: -132px;
            width: 300px;
            height: 300px;
            border: 1px solid color-mix(in srgb, var(--cyan) 28%, transparent);
            border-radius: 50%;
            box-shadow: 0 0 0 24px color-mix(in srgb, var(--cyan) 4%, transparent), 0 0 0 48px color-mix(in srgb, var(--cyan) 3%, transparent);
            pointer-events: none;
        }

        .home-hero-main,
        .home-hero-aside {
            position: relative;
            z-index: 1;
        }

        .home-kicker,
        .home-section-kicker,
        .home-aside-label {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: var(--text-header);
            font-family: 'JetBrains Mono', monospace;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .16em;
            line-height: 1.4;
            text-transform: uppercase;
        }

        .home-kicker {
            margin-bottom: 1.15rem;
            color: var(--cyan);
        }

        .home-kicker-dot {
            width: .55rem;
            height: .55rem;
            flex: 0 0 auto;
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 0 5px color-mix(in srgb, var(--cyan) 13%, transparent), 0 0 18px color-mix(in srgb, var(--cyan) 65%, transparent);
            animation: home-pulse 2.2s ease-in-out infinite;
        }

        .home-title {
            max-width: 15ch;
            margin: 0 0 1rem;
            color: var(--text-title);
            font-size: clamp(2.35rem, 5vw, 4.7rem);
            font-weight: 700;
            letter-spacing: -.075em;
            line-height: .98;
            overflow-wrap: anywhere;
            text-wrap: balance;
        }

        .home-title-name {
            display: block;
            max-width: 100%;
            color: var(--cyan);
            overflow-wrap: anywhere;
        }

        .home-subtitle {
            max-width: 58ch;
            margin: 0;
            color: color-mix(in srgb, var(--text-body) 92%, white);
            font-size: clamp(.88rem, 1.3vw, 1.02rem);
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .home-hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .85rem 1.1rem;
            margin-top: 1.65rem;
        }

        .home-primary,
        .home-open {
            transition: transform .16s cubic-bezier(.23, 1, .32, 1), background .16s ease, border-color .16s ease, color .16s ease;
        }

        .home-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            min-height: 42px;
            padding: .7rem 1rem;
            border: 1px solid color-mix(in srgb, var(--cyan) 72%, transparent);
            border-radius: 10px;
            background: var(--button-primary);
            color: var(--text-button);
            font-size: .8rem;
            font-weight: 700;
            text-decoration: none;
        }

        .home-primary:hover {
            transform: translateY(-2px);
            background: var(--button-primary-hover);
        }

        .home-primary:active {
            transform: scale(.97);
        }

        .home-primary span {
            font-size: 1rem;
            line-height: 1;
        }

        .home-status {
            color: var(--text-body);
            font-family: 'JetBrains Mono', monospace;
            font-size: .68rem;
            letter-spacing: .03em;
        }

        .home-status::first-letter {
            color: var(--success);
        }

        .home-hero-aside {
            justify-self: end;
            width: min(100%, 360px);
            padding: 1.15rem;
            border: 1px solid color-mix(in srgb, var(--line) 85%, var(--cyan));
            border-radius: 17px;
            background: color-mix(in srgb, var(--panel) 78%, transparent);
            box-shadow: inset 0 1px 0 color-mix(in srgb, white 7%, transparent), 0 16px 34px color-mix(in srgb, #000 24%, transparent);
            backdrop-filter: blur(12px);
        }

        .home-aside-label {
            margin-bottom: .95rem;
            color: var(--muted);
            font-size: .62rem;
            letter-spacing: .13em;
        }

        .home-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }

        .home-stat {
            padding: .9rem;
            border: 1px solid color-mix(in srgb, var(--line) 78%, transparent);
            border-radius: 12px;
            background: color-mix(in srgb, var(--bg) 46%, transparent);
        }

        .home-stat-value {
            display: block;
            margin-bottom: .25rem;
            color: var(--text-title);
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(1.35rem, 3vw, 1.8rem);
            font-weight: 700;
            letter-spacing: -.07em;
        }

        .home-stat-label {
            display: block;
            color: var(--muted);
            font-size: .68rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .home-aside-footnote {
            margin: .9rem 0 0;
            padding-top: .85rem;
            border-top: 1px solid color-mix(in srgb, var(--line) 74%, transparent);
            color: var(--text-body);
            font-size: .72rem;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .home-panel {
            margin-top: 1rem;
            padding: clamp(1rem, 2vw, 1.6rem);
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 16px 38px color-mix(in srgb, var(--shadow-color) 20%, transparent);
        }

        .home-panel-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .home-section-kicker {
            margin-bottom: .55rem;
            color: var(--orange);
            font-size: .61rem;
        }

        .home-panel-title {
            margin: 0 0 .45rem;
            color: var(--text-title);
            font-size: clamp(1.15rem, 2vw, 1.45rem);
            font-weight: 700;
            letter-spacing: -.035em;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .home-panel-intro {
            max-width: 75ch;
            margin: 0;
            color: var(--text-body);
            font-size: .82rem;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .home-count-badge {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            flex: 0 0 auto;
            padding: .55rem .7rem;
            border: 1px solid color-mix(in srgb, var(--cyan) 45%, var(--line));
            border-radius: 10px;
            background: color-mix(in srgb, var(--cyan) 9%, transparent);
            color: var(--text-body);
            font-size: .68rem;
            white-space: nowrap;
        }

        .home-count-badge strong {
            color: var(--cyan);
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
        }

        .conn-live-badge {
            gap: .5rem;
            border-color: color-mix(in srgb, var(--success) 45%, var(--line));
        }

        .conn-live-badge.is-warn {
            border-color: color-mix(in srgb, var(--warning) 55%, var(--line));
            background: color-mix(in srgb, var(--warning) 9%, transparent);
        }

        .conn-live-dot {
            width: .5rem;
            height: .5rem;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--success) 15%, transparent), 0 0 12px color-mix(in srgb, var(--success) 55%, transparent);
            animation: home-pulse 2.2s ease-in-out infinite;
        }

        .conn-live-badge.is-warn .conn-live-dot {
            background: var(--warning);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--warning) 15%, transparent), 0 0 12px color-mix(in srgb, var(--warning) 55%, transparent);
        }

        .home-conn-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
        }

        .home-conn-item {
            position: relative;
            min-width: 0;
            padding: .95rem 1rem;
            border: 1px solid color-mix(in srgb, var(--line) 82%, transparent);
            border-radius: 12px;
            background: color-mix(in srgb, var(--bg) 46%, transparent);
        }

        .home-conn-label {
            display: block;
            margin-bottom: .3rem;
            color: var(--muted);
            font-family: 'JetBrains Mono', monospace;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .1em;
            line-height: 1.4;
            text-transform: uppercase;
        }

        .home-conn-value {
            display: block;
            color: var(--text-title);
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(1rem, 2vw, 1.25rem);
            font-weight: 700;
            letter-spacing: -.03em;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .home-conn-sub {
            display: block;
            margin-top: .3rem;
            color: var(--muted);
            font-size: .68rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .home-conn-endpoints {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
            margin-top: .65rem;
        }

        .home-conn-endpoint {
            display: flex;
            align-items: center;
            gap: .8rem;
            min-width: 0;
            padding: .9rem 1rem;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: color-mix(in srgb, var(--panel-2) 72%, transparent);
        }

        .home-conn-endpoint-main {
            min-width: 0;
            flex: 1 1 auto;
        }

        .home-conn-endpoint-main strong {
            display: block;
            color: var(--text-title);
            font-family: 'JetBrains Mono', monospace;
            font-size: .84rem;
            overflow-wrap: anywhere;
        }

        .home-conn-endpoint-main span {
            display: block;
            margin-top: .22rem;
            color: var(--muted);
            font-size: .66rem;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .home-conn-endpoint-status {
            flex: 0 0 auto;
            color: var(--muted);
            font-family: 'JetBrains Mono', monospace;
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .home-conn-dot {
            width: .7rem;
            height: .7rem;
            flex: 0 0 auto;
            border-radius: 50%;
            background: color-mix(in srgb, var(--panel) 20%, var(--muted));
            transition: background .18s ease, box-shadow .18s ease;
        }

        .home-conn-dot.is-ok {
            background: var(--success);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--success) 16%, transparent), 0 0 14px color-mix(in srgb, var(--success) 50%, transparent);
        }

        .home-conn-dot.is-warn {
            background: var(--warning);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--warning) 16%, transparent), 0 0 14px color-mix(in srgb, var(--warning) 50%, transparent);
        }

        .home-conn-dot.is-bad {
            background: var(--danger);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--danger) 16%, transparent), 0 0 14px color-mix(in srgb, var(--danger) 50%, transparent);
        }

        .home-table-wrap {
            width: 100%;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: color-mix(in srgb, var(--bg) 42%, transparent);
        }

        .home-table {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: .8rem;
        }

        .home-table col.home-col-menu { width: 30%; }
        .home-table col.home-col-group { width: 20%; }
        .home-table col.home-col-function { width: 50%; }

        .home-table th {
            padding: .85rem 1rem;
            border-bottom: 1px solid color-mix(in srgb, var(--cyan) 38%, var(--line));
            background: color-mix(in srgb, var(--table-header) 88%, var(--cyan));
            color: var(--text-table);
            font-family: 'JetBrains Mono', monospace;
            font-size: .63rem;
            font-weight: 700;
            letter-spacing: .12em;
            line-height: 1.4;
            text-align: left;
            text-transform: uppercase;
            overflow-wrap: anywhere;
            white-space: normal;
            position: static;
        }

        .home-table td {
            padding: 1rem;
            border-bottom: 1px solid color-mix(in srgb, var(--line) 85%, transparent);
            color: var(--text-table);
            line-height: 1.55;
            text-align: left;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: normal;
            hyphens: auto;
            white-space: normal;
            position: static;
        }

        .home-table tr:last-child td {
            border-bottom: 0;
        }

        .home-table tbody tr {
            transition: background .16s ease, transform .16s cubic-bezier(.23, 1, .32, 1);
        }

        .home-table tbody tr:nth-child(even) td {
            background: color-mix(in srgb, var(--table-alt) 70%, transparent);
        }

        .home-table tbody tr:hover td {
            background: color-mix(in srgb, var(--table-hover) 82%, transparent);
        }

        .home-menu-name {
            margin-bottom: .35rem;
            color: var(--text-title);
            font-weight: 700;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .home-group {
            display: inline-flex;
            max-width: 100%;
            padding: .28rem .55rem;
            border: 1px solid color-mix(in srgb, var(--cyan) 45%, var(--line));
            border-radius: 999px;
            background: color-mix(in srgb, var(--cyan) 10%, transparent);
            color: var(--text-header);
            font-size: .68rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .home-function {
            color: var(--text-table);
            overflow-wrap: anywhere;
        }

        .home-open {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            max-width: 100%;
            color: var(--cyan);
            font-size: .7rem;
            font-weight: 700;
            line-height: 1.4;
            text-decoration: none;
            overflow-wrap: anywhere;
        }

        .home-open:hover {
            color: var(--text-header);
            transform: translateX(2px);
        }

        .home-open svg {
            flex: 0 0 auto;
        }

        .home-empty {
            padding: 1.5rem !important;
            color: var(--muted) !important;
            text-align: center !important;
        }

        .home-note {
            display: flex;
            align-items: flex-start;
            gap: .8rem;
            margin-top: 1rem;
            padding: .95rem 1rem;
            border: 1px solid color-mix(in srgb, var(--orange) 25%, var(--line));
            border-left: 3px solid var(--orange);
            border-radius: 12px;
            background: color-mix(in srgb, var(--orange) 7%, transparent);
        }

        .home-note-icon {
            display: grid;
            width: 1.55rem;
            height: 1.55rem;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid color-mix(in srgb, var(--orange) 58%, transparent);
            border-radius: 50%;
            color: var(--orange);
            font-family: 'JetBrains Mono', monospace;
            font-size: .72rem;
            font-weight: 700;
        }

        .home-note strong {
            display: block;
            margin-bottom: .25rem;
            color: var(--text-title);
            font-size: .76rem;
        }

        .home-note p {
            margin: 0;
            color: var(--text-footer);
            font-size: .74rem;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        @keyframes home-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .65; transform: scale(.82); }
        }

        @media (max-width: 820px) {
            .home-hero {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .home-hero-aside {
                justify-self: stretch;
                width: 100%;
            }
        }

        @media (max-width: 650px) {
            .home-page {
                padding: .85rem .1rem 2rem;
            }

            .home-hero {
                min-height: 0;
                padding: 1.35rem;
                border-radius: 18px;
            }

            .home-title {
                max-width: 100%;
                font-size: clamp(2.25rem, 12vw, 3.6rem);
            }

            .home-subtitle {
                font-size: .82rem;
                line-height: 1.65;
            }

            .home-hero-actions {
                align-items: stretch;
                flex-direction: column;
                margin-top: 1.3rem;
            }

            .home-primary {
                width: 100%;
            }

            .home-status {
                text-align: center;
            }

            .home-panel {
                padding: .85rem;
                border-radius: 15px;
            }

            .home-panel-heading {
                flex-direction: column;
                gap: .9rem;
                margin-bottom: 1rem;
            }

            .home-count-badge {
                align-self: flex-start;
            }

            .home-conn-grid,
            .home-conn-endpoints {
                grid-template-columns: 1fr;
            }

            .home-conn-endpoint-status {
                text-align: left;
            }

            .home-table-wrap {
                overflow: visible;
                border: 0;
                background: transparent;
            }

            .home-table,
            .home-table tbody,
            .home-table tr,
            .home-table td {
                display: block;
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .home-table thead {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0 0 0 0);
                clip-path: inset(50%);
                white-space: nowrap;
            }

            .home-table tr {
                margin-bottom: .8rem;
                padding: .9rem;
                border: 1px solid var(--line);
                border-radius: 13px;
                background: color-mix(in srgb, var(--table-row) 92%, transparent);
            }

            .home-table tr:nth-child(even) td,
            .home-table tr:hover td {
                background: transparent;
            }

            .home-table td,
            .home-table td.home-function {
                width: 100%;
                max-width: 100%;
                padding: 0;
                border: 0;
                line-height: 1.5;
                overflow-wrap: anywhere;
                white-space: normal;
                position: static;
            }

            .home-table td:not(:last-child) {
                margin-bottom: .78rem;
            }

            .home-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: .25rem;
                color: var(--muted);
                font-family: 'JetBrains Mono', monospace;
                font-size: .58rem;
                font-weight: 700;
                letter-spacing: .1em;
                line-height: 1.35;
                text-transform: uppercase;
            }

            .home-table td.home-empty {
                padding: .2rem !important;
                text-align: left !important;
            }

            .home-table td.home-empty::before {
                display: none;
            }

            .home-menu-name {
                margin-bottom: .35rem;
                font-size: .9rem;
            }

            .home-group {
                font-size: .64rem;
            }

            .home-note {
                padding: .85rem;
            }
        }

        @media (max-width: 430px) {
            .home-hero {
                padding: 1.15rem;
            }

            .home-kicker,
            .home-section-kicker {
                font-size: .58rem;
                letter-spacing: .11em;
            }

            .home-stats {
                gap: .5rem;
            }

            .home-stat {
                padding: .72rem;
            }

            .home-stat-label,
            .home-aside-footnote,
            .home-note p {
                font-size: .66rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .home-kicker-dot,
            .home-primary,
            .home-open,
            .home-table tbody tr {
                animation: none;
                transition: none;
            }
        }
    </style>

    <section class="home-hero" aria-labelledby="home-title">
        <div class="home-hero-main">
            <div class="home-kicker"><span class="home-kicker-dot" aria-hidden="true"></span><span>PAR Mobile / Control Center</span></div>
            <h1 id="home-title" class="home-title">Selamat datang,<span class="home-title-name">{{ $username }}</span></h1>
            <p class="home-subtitle">Industrial Process 4.0, Transformasi Digital</p>
            <div class="home-hero-actions">
                <a class="home-primary" href="#menu-access">Jelajahi menu <span aria-hidden="true">↓</span></a>
                <span class="home-status">● Akses akun aktif</span>
            </div>
        </div>

        <aside class="home-hero-aside" aria-label="Ringkasan akses akun">
            <div class="home-aside-label">Ringkasan akses / real-time</div>
            <div class="home-stats">
                <div class="home-stat">
                    <span class="home-stat-value">{{ $menuCount }}</span>
                    <span class="home-stat-label">Menu tersedia</span>
                </div>
                <div class="home-stat">
                    <span class="home-stat-value">{{ $groupCount }}</span>
                    <span class="home-stat-label">Kelompok akses</span>
                </div>
            </div>
            <p class="home-aside-footnote">Daftar halaman kontrol berdasarkan otorisasi akun dan pengaturan Anda.</p>
        </aside>
    </section>

    <section id="conn-access" class="home-panel" aria-labelledby="conn-access-title">
        <div class="home-panel-heading">
            <div>
                <div class="home-section-kicker">Network / 00</div>
                <h2 id="conn-access-title" class="home-panel-title">Status Koneksi Server</h2>
                <p class="home-panel-intro">Informasi jalur akses server saat ini:</p>
            </div>
            <!-- <div class="home-count-badge conn-live-badge{{ $connScheme !== 'https' ? ' is-warn' : '' }}" id="connLiveBadge"> -->
                <!-- <span class="conn-live-dot" aria-hidden="true"></span><span id="connLiveText">MEMERIKSA...</span> -->
            <!-- </div> -->
        </div>

        <div class="home-conn-grid">
            <div class="home-conn-item">
                <span class="home-conn-label">Alamat aktif</span>
                <strong class="home-conn-value">{{ $connScheme }}://{{ $connAddress }}</strong>
                <span class="home-conn-sub" id="connActiveHost">{{ $connHost }}</span>
            </div>
            <div class="home-conn-item">
                <span class="home-conn-label">Jaringan</span>
                <strong class="home-conn-value">{{ $connNetwork }}</strong>
                <span class="home-conn-sub">Deteksi otomatis dari alamat yang sedang digunakan.</span>
            </div>
            <div class="home-conn-item">
                <span class="home-conn-label">Protokol</span>
                <strong class="home-conn-value">{{ strtoupper($connScheme) }}</strong>
                <span class="home-conn-sub">{{ $connScheme === 'https' ? 'Koneksi terenkripsi (aman).' : 'Koneksi tidak terenkripsi.' }}</span>
            </div>
        </div>

        <div class="home-conn-endpoints">
            <div class="home-conn-endpoint">
                <span class="home-conn-dot" id="connLocalDot" aria-hidden="true"></span>
                <div class="home-conn-endpoint-main">
                    <strong>Local Area Netwok</strong>
                    <span>Alamat utama · prioritas saat di jaringan lokal (LAN) untuk kecepatan maksimal</span>
                </div>
                <span class="home-conn-endpoint-status" id="connLocalStatus">memeriksa...</span>
            </div>
            <div class="home-conn-endpoint">
                <span class="home-conn-dot" id="connPublicDot" aria-hidden="true"></span>
                <div class="home-conn-endpoint-main">
                    <strong>Internet Public network</strong>
                    <span>Alamat cadangan · IP Public (Internet / dari luar)</span>
                </div>
                <span class="home-conn-endpoint-status" id="connPublicStatus">memeriksa...</span>
            </div>
        </div>

        
    </section>

    <section id="menu-access" class="home-panel" aria-labelledby="menu-access-title">
        <div class="home-panel-heading">
            <div>
                <div class="home-section-kicker">Directory / 01</div>
                <h2 id="menu-access-title" class="home-panel-title">Menu yang dapat diakses</h2>
                <p class="home-panel-intro">Gunakan daftar ini sebagai panduan singkat fungsi setiap menu. Informasi akan mengikuti hak akses akun yang sedang digunakan.</p>
            </div>
            <div class="home-count-badge"><strong>{{ $menuCount }}</strong><span>menu tersedia</span></div>
        </div>

        <div class="home-table-wrap">
            <table class="home-table">
                <colgroup>
                    <col class="home-col-menu">
                    <col class="home-col-group">
                    <col class="home-col-function">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Menu</th>
                        <th scope="col">Kelompok</th>
                        <th scope="col">Fungsi menu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accessibleMenus as $menu)
                        <tr>
                            <td data-label="Menu">
                                <div class="home-menu-name">{{ $menu['name'] }}</div>
                                @if($menu['route'] !== '')
                                    <a class="home-open" href="{{ route($menu['route']) }}">
                                        <span>Buka menu</span>
                                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true">
                                            <path d="M2.5 10.5 10.5 2.5M5.5 2.5h5v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                @endif
                            </td>
                            <td data-label="Kelompok"><span class="home-group">{{ $menu['group'] ?: 'Umum' }}</span></td>
                            <td data-label="Fungsi menu" class="home-function">{{ $menu['description'] ?: 'Deskripsi belum tersedia.' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="home-empty" data-label="Informasi" colspan="3">Belum ada menu yang diotorisasi untuk akun ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="home-note">
            <div class="home-note-icon" aria-hidden="true">!</div>
            <div>
                <strong>Himbauan penggunaan informasi</strong>
                <p>Gunakan informasi pada PAR Mobile secara bijak, sesuai kewenangan dan kebutuhan pekerjaan. Data produksi, kualitas, user, dan laporan merupakan informasi operasional yang perlu dijaga kerahasiaannya. Jangan membagikan data kepada pihak yang tidak berwenang dan selalu lakukan logout setelah selesai menggunakan aplikasi.</p>
            </div>
        </div>
    </section>
</div>

<script>
    (() => {
        const LOCAL_ADDRESS = '192.168.100.225';
        const PUBLIC_ADDRESS = '180.250.83.205';
        const scheme = window.location.protocol;
        let host = (window.location.hostname || '').toLowerCase();

        const $ = (id) => document.getElementById(id);
        const setText = (id, value) => { const el = $(id); if (el) el.textContent = value; };
        const isLocalHost = () => host === 'localhost' || host === '127.0.0.1' || host === '::1';

        const probe = (address) => fetch(scheme + '//' + address + '/', { mode: 'no-cors', cache: 'no-store' })
            .then(() => true)
            .catch(() => false);

        const applyState = (localOk, publicOk) => {
            const setEndpointState = (dotId, statusId, ok, active) => {
                const dot = $(dotId);
                const statusEl = $(statusId);
                if (!dot || !statusEl) return;
                dot.classList.remove('is-ok', 'is-warn', 'is-bad');
                if (active) {
                    dot.classList.add('is-ok');
                    statusEl.textContent = 'AKTIF SEKARANG';
                    statusEl.style.color = 'var(--success)';
                } else if (ok) {
                    dot.classList.add('is-ok');
                    statusEl.textContent = 'TERJANGKAU';
                    statusEl.style.color = 'var(--success)';
                } else {
                    dot.classList.add('is-bad');
                    statusEl.textContent = 'TIDAK TERJANGKAU';
                    statusEl.style.color = 'var(--danger)';
                }
            };

            setEndpointState('connLocalDot', 'connLocalStatus', localOk, host === LOCAL_ADDRESS || isLocalHost());
            setEndpointState('connPublicDot', 'connPublicStatus', publicOk, host === PUBLIC_ADDRESS);

            const badge = $('connLiveBadge');
            const text = $('connLiveText');
            if (!badge || !text) return;
            if (scheme === 'http:') {
                badge.classList.add('is-warn');
                text.textContent = 'HTTP · TIDAK AMAN';
            } else {
                badge.classList.remove('is-warn');
                text.textContent = 'HTTPS · AMAN';
            }
        };

        const detect = () => {
            host = (window.location.hostname || '').toLowerCase();
            setText('connActiveHost', host || '-');
            Promise.all([probe(LOCAL_ADDRESS), probe(PUBLIC_ADDRESS)])
                .then(([localOk, publicOk]) => applyState(localOk, publicOk));
        };

        document.addEventListener('livewire:navigated', detect);
        document.addEventListener('livewire:updated', detect);
        document.addEventListener('DOMContentLoaded', detect);
        detect();
    })();
</script>
