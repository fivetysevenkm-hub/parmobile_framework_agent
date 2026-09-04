<div style="width:min(100%,720px);margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="color:var(--ink);font-size:1.4rem;margin:0;font-weight:700;">Customer Portal</h1>
            <p style="color:var(--footer-text);font-size:.78rem;margin:4px 0 0;">Selamat datang, {{ auth()->guard('customer')->user()->name }}</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ route('customer.logout') }}" onclick="event.preventDefault();document.getElementById('customer-logout-form').submit();" style="color:var(--danger);font-size:.78rem;text-decoration:none;font-weight:500;">Keluar</a>
            <form id="customer-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">@csrf</form>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
        <span style="display:inline-block;padding:3px 12px;border-radius:12px;font-size:.72rem;font-weight:600;background:color-mix(in srgb,var(--accent) 12%,transparent);color:var(--accent);">
            Customer Portal Summary H-30
        </span>
        <span style="color:var(--footer-text);font-size:.72rem;">{{ now()->subDays(30)->format('d M Y') }} – {{ now()->format('d M Y') }}</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px;">
        <div style="background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:12px;padding:20px;text-align:center;">
            <div style="font-size:1.8rem;font-weight:700;color:var(--ink);">{{ $totalLots }}</div>
            <div style="font-size:.78rem;color:var(--footer-text);margin-top:4px;">Total Lot</div>
        </div>
        <div style="background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:12px;padding:20px;text-align:center;">
            <div style="font-size:1.8rem;font-weight:700;color:var(--cyan);">{{ $activeLots }}</div>
            <div style="font-size:.78rem;color:var(--footer-text);margin-top:4px;">Diproses</div>
        </div>
        <div style="background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:12px;padding:20px;text-align:center;">
            <div style="font-size:1.8rem;font-weight:700;color:var(--success);">{{ $completedLots }}</div>
            <div style="font-size:.78rem;color:var(--footer-text);margin-top:4px;">Selesai</div>
        </div>
    </div>

    <a href="{{ route('customer.lot-tracking') }}" wire:navigate style="display:block;background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:14px;padding:24px;text-decoration:none;transition:all .2s;box-shadow:0 8px 20px color-mix(in srgb,var(--shadow-color) 16%,transparent);" onmouseover="this.style.borderColor='var(--accent)';this.style.boxShadow='0 12px 28px color-mix(in srgb,var(--accent) 12%,transparent)'" onmouseout="this.style.borderColor='var(--line)';this.style.boxShadow='0 8px 20px color-mix(in srgb,var(--shadow-color) 16%,transparent)'">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:1rem;font-weight:700;color:var(--ink);">Lot Tracking</div>
                <div style="font-size:.78rem;color:var(--footer-text);margin-top:4px;">Lihat status real-time semua lot produksi Anda</div>
            </div>
            <span style="font-size:1.4rem;color:var(--accent);">&rarr;</span>
        </div>
    </a>

    <div style="text-align:center;margin-top:28px;color:var(--footer-text);font-size:.7rem;">
        Customer Portal | {{ now()->year }} PT Dowa Thermotech Indonesia
    </div>
</div>
