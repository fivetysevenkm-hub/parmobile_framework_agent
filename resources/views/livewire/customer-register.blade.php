<div class="login-box">
    <style>
        @media (max-width:480px){
            .customer-picker-backdrop { align-items:flex-end !important; padding:0 !important; }
            .customer-picker { width:100% !important; max-height:86dvh !important; border-radius:18px 18px 0 0 !important; }
        }
    </style>
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
                <label for="customer">Customer</label>
                <button type="button" wire:click="openCustomerPicker" id="customer" class="control customer-picker-btn" style="display:flex;align-items:center;justify-content:space-between;width:100%;height:46px;text-align:left;cursor:pointer;font-family:inherit;">
                    <span style="{{ $this->no_customer ? '' : 'color:var(--input-placeholder);' }}">{{ $this->customerName ?: '— Pilih Customer —' }}</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.55;flex:0 0 auto;"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
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

        @if($showCustomerPicker)
            <div class="customer-picker-backdrop" wire:click="closeCustomerPicker" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999;display:flex;align-items:center;justify-content:center;padding:16px;">
                <div class="customer-picker" wire:click.stop style="width:min(100%,420px);max-height:80vh;display:flex;flex-direction:column;background:var(--panel);border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.5);">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 16px 12px;">
                        <h3 style="margin:0;font-size:.95rem;color:var(--ink);font-weight:700;">Pilih Customer</h3>
                        <button type="button" wire:click="closeCustomerPicker" style="background:none;border:none;font-size:1.4rem;cursor:pointer;line-height:1;color:var(--footer-text);padding:2px 8px;border-radius:6px;">&times;</button>
                    </div>
                    <div style="padding:0 16px 12px;">
                        <input type="text" wire:model.live.debounce.200ms="customerSearch" placeholder="Cari nama / kode customer..." class="control" style="width:100%;height:44px;border:1px solid var(--line);border-radius:8px;padding:0 13px;background:var(--panel-deep);color:var(--body-text);font-size:16px;box-sizing:border-box;">
                    </div>
                    <div style="overflow-y:auto;max-height:52vh;flex:1 1 auto;border-top:1px solid var(--line);">
                        @if(empty($this->filteredCustomers))
                            <div style="text-align:center;padding:32px 16px;color:var(--footer-text);font-size:.8rem;">Customer tidak ditemukan.</div>
                        @else
                            @foreach($this->filteredCustomers as $cust)
                                <button type="button" wire:click="selectCustomer('{{ $cust['no_customer'] }}')" style="display:block;width:100%;text-align:left;padding:13px 16px;border:none;border-bottom:1px solid var(--line);background:{{ $this->no_customer === $cust['no_customer'] ? 'color-mix(in srgb,var(--accent) 10%,transparent)' : 'transparent' }};color:var(--body-text);font-size:.84rem;cursor:pointer;font-family:inherit;" onmouseover="this.style.background='color-mix(in srgb,var(--accent) 6%,transparent)'" onmouseout="this.style.background='{{ $this->no_customer === $cust['no_customer'] ? 'color-mix(in srgb,var(--accent) 10%,transparent)' : 'transparent' }}'">
                                    <span style="font-weight:600;color:var(--ink);display:block;">{{ $cust['nm_customer'] }}</span>
                                    <span style="font-size:.7rem;color:var(--footer-text);">{{ $cust['no_customer'] }}</span>
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="links">
            Sudah punya akun? <a href="{{ route('customer.login') }}">Masuk</a>
        </div>

        <div class="footer">Customer Portal | {{ now()->year }} PT Dowa Thermotech Indonesia</div>
    </div>
</div>
