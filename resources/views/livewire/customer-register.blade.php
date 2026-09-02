<div class="login-box">
    <div class="brand">
        <img class="brand-logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
        <h1><strong>PAR</strong><span>Mobile</span></h1>
    </div>

    <div class="card">
        <h2>Daftar Akun Customer Portal</h2>

        <form wire:submit="register">
            <div class="field">
                <label for="name">Nama Lengkap</label>
                <input id="name" type="text" wire:model="name" class="control" placeholder="Nama contact person" autofocus>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" wire:model="email" class="control" placeholder="Email untuk login" autocomplete="email">
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="company">Nama Perusahaan</label>
                <input id="company" type="text" wire:model="company" class="control" placeholder="Nama perusahaan">
                @error('company') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="no_customer">Customer</label>
                <select id="no_customer" wire:model="no_customer" class="control">
                    <option value="">— Pilih Customer —</option>
                    @foreach($customers as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('no_customer') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="phone">No. Telepon <span style="font-weight:400;opacity:.6">(opsional)</span></label>
                <input id="phone" type="text" wire:model="phone" class="control" placeholder="08xxx">
                @error('phone') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <input id="password" type="password" wire:model="password" class="control" placeholder="Minimal 8 karakter" autocomplete="new-password">
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Konfirmasi Kata Sandi</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" class="control" placeholder="Ulangi kata sandi" autocomplete="new-password">
            </div>

            <button type="submit" class="submit" wire:loading.attr="disabled" wire:target="register">
                <span wire:loading.remove wire:target="register">DAFTAR</span>
                <span wire:loading wire:target="register">MEMPROSES...</span>
            </button>
        </form>

        <div class="links">
            Sudah punya akun? <a href="{{ route('customer.login') }}">Masuk</a>
        </div>

        <div class="footer">Customer Portal | {{ now()->year }} PT Dowa Thermotech Indonesia</div>
    </div>
</div>
