<div class="login-box">
    <style>
        .auto-switch {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin: -6px 0 18px;
            color: var(--footer-text);
            font-family: 'Space Grotesk', sans-serif;
            font-size: .7rem;
            line-height: 1.5;
            text-align: center;
        }

        .auto-switch .pulse {
            width: .5rem;
            height: .5rem;
            flex: 0 0 auto;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--success) 15%, transparent);
            animation: autoSwitchPulse 1.8s ease-in-out infinite;
        }

        .auto-switch .skip {
            border: 0;
            background: transparent;
            padding: 0;
            color: var(--footer-text);
            font-size: .68rem;
            text-decoration: underline;
            cursor: pointer;
        }

        .auto-switch .skip:hover {
            color: var(--danger);
        }

        @keyframes autoSwitchPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .55; transform: scale(.85); }
        }
    </style>

    <div class="brand">
        <img class="brand-logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
        <h1><strong>PAR</strong><span>Mobile</span></h1>
    </div>

    <div class="auto-switch" id="autoSwitchBar" aria-live="polite">
        <span class="pulse" aria-hidden="true"></span>
        <span id="autoSwitchStatus">Auto-switch aktif: memeriksa ketersediaan alamat lokal...</span>
        <button type="button" class="skip" id="autoSwitchSkip">tetap di alamat ini</button>
    </div>

    <div class="card">
        <h2>Silakan masuk untuk akses sistem</h2>

        <form wire:submit="authenticate">
            <div class="field">
                <label for="pengguna">Nama Pengguna</label>
                <input id="pengguna" type="text" wire:model="pengguna" class="control" placeholder="Masukkan nama pengguna" autocomplete="username" autofocus>
                @error('pengguna') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <input id="password" type="password" wire:model="password" class="control" placeholder="Masukkan kata sandi" autocomplete="current-password">
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field captcha-field">
                <div
                    class="cf-turnstile"
                    data-sitekey="{{ config('services.turnstile.site_key') }}"
                    data-callback="parTurnstileCompleted"
                    data-expired-callback="parTurnstileExpired"
                    data-error-callback="parTurnstileError"
                ></div>
                <input id="turnstileToken" type="hidden" wire:model="turnstileToken">
                @error('turnstileToken') <div class="error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="submit" wire:loading.attr="disabled" wire:target="authenticate">
                <span wire:loading.remove wire:target="authenticate">MASUK</span>
                <span wire:loading wire:target="authenticate">MEMERIKSA...</span>
            </button>
        </form>

        <div class="footer">Version_02 | {{ now()->year }} PT Dowa Thermotech Indonesia</div>
    </div>
</div>

@script
<script>
    window.parTurnstileCompleted = token => {
        const input = document.getElementById('turnstileToken');
        if (!input) return;
        input.value = token;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    window.parTurnstileExpired = () => {
        const input = document.getElementById('turnstileToken');
        if (!input) return;
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    window.parTurnstileError = window.parTurnstileExpired;

    $wire.on('turnstile-reset', () => {
        window.turnstile?.reset();
    });
</script>
@endscript

<script>
    (() => {
        const LOCAL_ADDRESS = '192.168.100.225';
        const PUBLIC_ADDRESS = '180.250.83.205';
        const scheme = window.location.protocol;
        const host = (window.location.hostname || '').toLowerCase();

        const bar = document.getElementById('autoSwitchBar');
        const statusEl = document.getElementById('autoSwitchStatus');
        const skipBtn = document.getElementById('autoSwitchSkip');

        let cancelled = false;

        const setStatus = (text) => { if (statusEl) statusEl.textContent = text; };
        const hide = () => { if (bar) bar.style.display = 'none'; };

        if (skipBtn) {
            skipBtn.addEventListener('click', () => {
                cancelled = true;
                setStatus('Mode auto-switch dinonaktifkan untuk halaman ini.');
                skipBtn.style.display = 'none';
            });
        }

        const probe = (address, timeoutMs = 2000) =>
            new Promise((resolve) => {
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), timeoutMs);
                fetch(scheme + '//' + address + '/', { mode: 'no-cors', cache: 'no-store', signal: controller.signal })
                    .then(() => { clearTimeout(timer); resolve(true); })
                    .catch(() => { clearTimeout(timer); resolve(false); });
            });

        const go = (address) => {
            if (cancelled) return;
            setStatus('Mengalihkan ke ' + scheme + '//' + address + window.location.pathname);
            window.location.replace(scheme + '//' + address + window.location.pathname + window.location.search);
        };

        const run = () => {
            if (cancelled) return;

            if (host === LOCAL_ADDRESS) {
                setStatus('Anda sudah di alamat lokal (192.168.100.225).');
                setTimeout(hide, 2500);
                return;
            }

            if (host !== PUBLIC_ADDRESS) {
                setStatus('Auto-switch hanya berlaku saat akses via ' + PUBLIC_ADDRESS + '. Alamat ini tidak dialihkan.');
                setTimeout(hide, 3500);
                return;
            }

            probe(LOCAL_ADDRESS)
                .then((localOk) => {
                    if (cancelled) return;
                    if (localOk) { go(LOCAL_ADDRESS); return; }
                    setStatus('192.168.100.225 tidak terdeteksi aktif. Tetap di alamat public.');
                    setTimeout(hide, 3500);
                });
        };

        run();
    })();
</script>
