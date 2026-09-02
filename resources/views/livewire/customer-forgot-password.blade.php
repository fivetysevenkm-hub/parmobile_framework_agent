<div class="login-box">
    <div class="brand">
        <img class="brand-logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
        <h1><strong>PAR</strong><span>Mobile</span></h1>
    </div>

    <div class="card">
        <h2>{{ $showResetForm ? 'Atur Ulang Kata Sandi' : 'Lupa Kata Sandi' }}</h2>

        @if (! $showResetForm)
            <form wire:submit="sendResetLink">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" wire:model="email" class="control" placeholder="Masukkan email terdaftar" autofocus>
                    @error('email') <div class="error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="submit" wire:loading.attr="disabled" wire:target="sendResetLink">
                    <span wire:loading.remove wire:target="sendResetLink">KIRIM LINK RESET</span>
                    <span wire:loading wire:target="sendResetLink">MEMPROSES...</span>
                </button>
            </form>
        @else
            <div class="alert alert-warning">Masukkan kata sandi baru Anda.</div>

            <form wire:submit="resetPassword">
                <input type="hidden" wire:model="token">

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" wire:model="email" class="control" disabled>
                </div>

                <div class="field">
                    <label for="password">Kata Sandi Baru</label>
                    <input id="password" type="password" wire:model="password" class="control" placeholder="Minimal 8 karakter" autofocus autocomplete="new-password">
                    @error('password') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input id="password_confirmation" type="password" wire:model="password_confirmation" class="control" placeholder="Ulangi kata sandi baru" autocomplete="new-password">
                </div>

                <button type="submit" class="submit" wire:loading.attr="disabled" wire:target="resetPassword">
                    <span wire:loading.remove wire:target="resetPassword">SIMPAN KATA SANDI BARU</span>
                    <span wire:loading wire:target="resetPassword">MEMPROSES...</span>
                </button>
            </form>
        @endif

        <div class="links">
            <a href="{{ route('customer.login') }}">Kembali ke Login</a>
        </div>

        <div class="footer">Customer Portal | {{ now()->year }} PT Dowa Thermotech Indonesia</div>
    </div>
</div>
