<div class="login-box">
    <div class="brand">
        <img class="brand-logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
        <h1><strong>PAR</strong><span>Mobile</span></h1>
    </div>

    <div class="card">
        <h2>Ganti kata sandi sebelum melanjutkan</h2>
        <p class="notice">Password saat ini masih menggunakan password default atau belum memenuhi standar keamanan. Buat password baru untuk melanjutkan ke aplikasi.</p>

        <form wire:submit="save">
            <div class="field">
                <label for="currentPassword">Password Saat Ini</label>
                <input id="currentPassword" type="password" wire:model="currentPassword" class="control" autocomplete="current-password" autofocus>
                @error('currentPassword') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="newPassword">Password Baru</label>
                <input id="newPassword" type="password" wire:model="newPassword" class="control" autocomplete="new-password">
                <div class="hint">8–15 karakter, terdiri dari huruf kecil, huruf besar, angka, dan karakter khusus.</div>
                @error('newPassword') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="newPasswordConfirmation">Konfirmasi Password Baru</label>
                <input id="newPasswordConfirmation" type="password" wire:model="newPasswordConfirmation" class="control" autocomplete="new-password">
                @error('newPasswordConfirmation') <div class="error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">SIMPAN PASSWORD</span>
                <span wire:loading wire:target="save">MENYIMPAN...</span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="logout-link">Keluar</button>
        </form>

        <div class="footer">Version_02 | {{ now()->year }} PT Dowa Thermotech Indonesia</div>
    </div>
</div>

<style>
    .notice { color:#cbd5e0; background:rgba(0,229,255,.07); border:1px solid rgba(0,229,255,.2); border-radius:8px; padding:11px 12px; font-size:.78rem; line-height:1.5; margin:0 0 20px; }
    .hint { color:#9aa9b0; font-size:.68rem; line-height:1.4; margin-top:6px; }
    .logout-form { text-align:center; margin-top:14px; }
    .logout-link { border:0; background:transparent; color:#ff9eaa; cursor:pointer; font-size:.74rem; }
    .logout-link:hover { color:#ffccd0; }
</style>
