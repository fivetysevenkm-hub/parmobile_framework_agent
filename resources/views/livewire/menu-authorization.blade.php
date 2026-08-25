<div class="menu-auth-page">
    <style>
        .menu-auth-page{padding:12px;font-family:Tahoma,Arial,sans-serif}.menu-auth-page .auth-card{background:var(--panel);border:1px solid var(--line);border-radius:11px;overflow:hidden}.menu-auth-page .auth-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 17px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.04)}.menu-auth-page h1{margin:0;color:#f5f7f8;font-size:1rem}.menu-auth-page h2{margin:0;color:var(--cyan);font-size:.78rem;letter-spacing:.05em;text-transform:uppercase}.menu-auth-page .auth-subtitle{margin-top:5px;color:var(--muted);font-size:.72rem}.menu-auth-page .auth-body{padding:12px}.menu-auth-page .auth-grid{display:grid;grid-template-columns:minmax(230px,.55fr) minmax(0,1.45fr);gap:12px}.menu-auth-page .panel{min-width:0;padding:12px;border:1px solid var(--line);border-radius:8px;background:#111a1f}.menu-auth-page .panel-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}.menu-auth-page .search{width:100%;padding:10px 11px;border:1px solid var(--line);border-radius:7px;background:#0d1418;color:#fff;font:14px Tahoma,Arial,sans-serif;outline:none}.menu-auth-page .search:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,229,255,.12)}.menu-auth-page .user-list{max-height:650px;overflow:auto}.menu-auth-page .user-row{display:block;width:100%;padding:10px;border:1px solid transparent;border-bottom-color:#23323a;background:transparent;color:#e6eef0;text-align:left;cursor:pointer;font:14px Tahoma,Arial,sans-serif}.menu-auth-page .user-row:hover,.menu-auth-page .user-row.active{background:#1d3540;border-color:#2b6478}.menu-auth-page .user-row.inactive{color:#77878e;cursor:not-allowed}.menu-auth-page .user-row strong{display:block;font-size:14px}.menu-auth-page .user-row small{display:block;margin-top:4px;color:var(--muted);font-size:12px}.menu-auth-page .user-row.active small{color:#9ee8f1}.menu-auth-page .badge{display:inline-flex;padding:3px 6px;border-radius:4px;background:#123a59;color:#8fdcff;font-size:11px;font-weight:700}.menu-auth-page .badge.off{background:#3c2528;color:#ffb8bf}.menu-auth-page .selected-user{margin-bottom:12px;padding:10px 12px;border-left:3px solid var(--cyan);background:#0d181d;color:#eef7f8;font-size:14px}.menu-auth-page .selected-user strong{color:#fff}.menu-auth-page .matrix{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.menu-auth-page .group{border:1px solid #2a414a;border-radius:8px;background:#0e171b;overflow:hidden}.menu-auth-page .group h3{margin:0;padding:9px 10px;border-bottom:1px solid #2a414a;background:#15262d;color:#9ee8f1;font-size:14px}.menu-auth-page .menu-item{display:flex;align-items:flex-start;gap:9px;padding:10px;border-bottom:1px solid #203139;color:#eef3f4;font-size:14px}.menu-auth-page .menu-item:last-child{border-bottom:0}.menu-auth-page .menu-item input{width:21px;height:21px;flex:0 0 auto;margin:0;accent-color:#00d9ee}.menu-auth-page .menu-item.locked{color:#c6d0d3;background:#142126}.menu-auth-page .menu-item.locked input{accent-color:#2dd4bf}.menu-auth-page .menu-item small{display:block;margin-top:3px;color:var(--muted);font-size:11px;line-height:1.3}.menu-auth-page .form-actions{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px}.menu-auth-page .note{color:var(--muted);font-size:12px;line-height:1.45}.menu-auth-page .flash{margin-bottom:11px;padding:10px 12px;border:1px solid #1e8f6d;border-radius:7px;background:rgba(45,212,191,.12);color:#8ff5df;font-size:13px}.menu-auth-page .error{margin-top:7px;color:#ff9eaa;font-size:13px}.menu-auth-page .empty{padding:30px 12px;color:var(--muted);font-size:14px;text-align:center}
        @media(max-width:900px){.menu-auth-page .auth-grid{grid-template-columns:1fr}.menu-auth-page .user-list{max-height:290px}.menu-auth-page .matrix{grid-template-columns:1fr}}
        @media(max-width:560px){.menu-auth-page{padding:8px}.menu-auth-page .auth-head{align-items:flex-start;flex-direction:column}.menu-auth-page .auth-body{padding:9px}.menu-auth-page .panel{padding:9px}.menu-auth-page .form-actions{align-items:stretch;flex-direction:column}.menu-auth-page .form-actions .btn{width:100%}}
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / User Management</div>
            <h1>Otorisasi <span class="cyan">Menu</span></h1>
            <div class="muted">Atur menu dan submenu yang dapat dibuka oleh setiap user.</div>
        </div>
    </div>

    @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
    @error('form')<div class="error">{{ $message }}</div>@enderror

    <section class="auth-card">
        <div class="auth-head"><div><h1>OTORISASI USER MENU</h1><div class="auth-subtitle">Hak akses disimpan per user. Tidak ada pemilihan role sebagai pengganti akses menu.</div></div><span class="badge">{{ count($users) }} user</span></div>
        <div class="auth-body">
            <div class="auth-grid">
                <section class="panel">
                    <div class="panel-head"><h2>Daftar User</h2><span class="badge">Pilih</span></div>
                    <input type="search" class="search" wire:model.live="userSearch" placeholder="Cari NIK atau nama user..." autocomplete="off">
                    <div class="user-list">
                        @forelse($this->filteredUsers as $user)
                            @php($active = (int) ($user['is_active'] ?? 0) === 1)
                            <button type="button" class="user-row {{ $selectedUserId === (int) $user['id_user'] ? 'active' : '' }} {{ ! $active ? 'inactive' : '' }}" wire:click="selectUser({{ (int) $user['id_user'] }})" @disabled(! $active)>
                                <strong>{{ $user['username'] }}</strong>
                                <small>NIK: {{ $user['id_user'] }} &nbsp;|&nbsp; Role: {{ $user['id_role'] }} &nbsp; <span class="badge {{ $active ? '' : 'off' }}">{{ $active ? 'AKTIF' : 'NONAKTIF' }}</span></small>
                            </button>
                        @empty
                            <div class="empty">User tidak ditemukan.</div>
                        @endforelse
                    </div>
                </section>

                <section class="panel">
                    @if($selectedUserId)
                        <div class="selected-user">User terpilih: <strong>{{ $selectedUsername }}</strong> <span class="muted">(NIK {{ $selectedUserId }})</span></div>
                        @if($isFullAccess)<div class="flash">User <strong>{{ $selectedUsername }}</strong> memiliki akses penuh khusus dan seluruh checkbox dikunci aktif.</div>@endif
                        <div class="matrix">
                            @foreach($groups as $groupName => $menus)
                                <div class="group" wire:key="auth-group-{{ md5($groupName) }}">
                                    <h3>{{ $groupName }}</h3>
                                    @foreach($menus as $menu)
                                        <label class="menu-item {{ $menu['locked'] ? 'locked' : '' }}" wire:key="auth-menu-{{ $menu['key'] }}">
                                            <input type="checkbox" value="{{ $menu['key'] }}" wire:model.live="selectedKeys" @checked($menu['locked'] && $menu['can_access']) @disabled($menu['locked'])>
                                            <span>{{ $menu['name'] }}@if($menu['locked'])<small>{{ $menu['locked_reason'] }}</small>@endif</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <div class="form-actions"><span class="note">Daily Report, Monthly Report, dan Akakami Dashboard selalu aktif sebagai akses default. Akses Didik selalu penuh.</span><button type="button" class="btn btn-cyan" wire:click="save" wire:loading.attr="disabled" wire:target="save">SIMPAN OTORISASI</button></div>
                    @else
                        <div class="empty">Pilih user untuk menampilkan daftar menu dan submenu.</div>
                    @endif
                </section>
            </div>
        </div>
    </section>
</div>
