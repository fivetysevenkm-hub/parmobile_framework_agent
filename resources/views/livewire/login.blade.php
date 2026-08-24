<div class="login-box">
    <div class="brand">
        <img class="brand-logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
        <h1><strong>PAR</strong><span>Mobile</span></h1>
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


            <button type="submit" class="submit" wire:loading.attr="disabled" wire:target="authenticate">
                <span wire:loading.remove wire:target="authenticate">MASUK</span>
                <span wire:loading wire:target="authenticate">MEMERIKSA...</span>
            </button>
        </form>

        <div class="footer">Version_02 | {{ now()->year }} PT Dowa Thermotech Indonesia</div>
    </div>
</div>
