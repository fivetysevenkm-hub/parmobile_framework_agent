<div class="login-box">
    <div class="brand">
        <img class="brand-logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
        <h1><strong>PAR</strong><span>Mobile</span></h1>
    </div>

    <div class="card">
        <h2>Customer Portal — Masuk</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form wire:submit="authenticate">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" wire:model="email" class="control" placeholder="Masukkan email" autocomplete="email" autofocus>
                @error('email') <div class="error">{{ $message }}</div> @enderror
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

        <div class="links">
            <a href="{{ route('customer.register') }}">Daftar Akun Baru</a>
            &nbsp;|&nbsp;
            <a href="{{ route('customer.forgot-password') }}">Lupa Kata Sandi</a>
        </div>

        <div class="footer">Customer Portal | {{ now()->year }} PT Dowa Thermotech Indonesia</div>
    </div>
</div>
