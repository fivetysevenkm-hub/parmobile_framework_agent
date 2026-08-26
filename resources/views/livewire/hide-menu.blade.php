<div class="hide-menu-page">
    <style>
        .hide-menu-page{padding:12px;font-family:Tahoma,Arial,sans-serif}.hide-menu-page .hide-card{background:var(--panel);border:1px solid var(--line);border-radius:11px;overflow:hidden}.hide-menu-page .hide-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 17px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.04)}.hide-menu-page h1{margin:0;color:#f5f7f8;font-size:1rem}.hide-menu-page h2{margin:0;color:var(--cyan);font-size:.78rem;letter-spacing:.05em;text-transform:uppercase}.hide-menu-page .subtitle{margin-top:5px;color:var(--muted);font-size:.72rem;line-height:1.45}.hide-menu-page .body{padding:12px}.hide-menu-page .grid{display:grid;grid-template-columns:minmax(230px,.55fr) minmax(0,1.45fr);gap:12px}.hide-menu-page .panel{min-width:0;padding:12px;border:1px solid var(--line);border-radius:8px;background:#111a1f}.hide-menu-page .panel-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}.hide-menu-page .search{width:100%;padding:10px 11px;border:1px solid var(--line);border-radius:7px;background:#0d1418;color:#fff;font:14px Tahoma,Arial,sans-serif;outline:none}.hide-menu-page .search:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,229,255,.12)}.hide-menu-page .user-list{max-height:650px;overflow:auto}.hide-menu-page .user-row{display:block;width:100%;padding:10px;border:1px solid transparent;border-bottom-color:#23323a;background:transparent;color:#e6eef0;text-align:left;cursor:pointer;font:14px Tahoma,Arial,sans-serif}.hide-menu-page .user-row:hover,.hide-menu-page .user-row.active{background:#1d3540;border-color:#2b6478}.hide-menu-page .user-row.inactive{color:#77878e;cursor:not-allowed}.hide-menu-page .user-row strong{display:block;font-size:14px}.hide-menu-page .user-row small{display:block;margin-top:4px;color:var(--muted);font-size:12px}.hide-menu-page .user-row.active small{color:#9ee8f1}.hide-menu-page .badge{display:inline-flex;padding:3px 6px;border-radius:4px;background:#123a59;color:#8fdcff;font-size:11px;font-weight:700}.hide-menu-page .badge.off{background:#3c2528;color:#ffb8bf}.hide-menu-page .selected{margin-bottom:12px;padding:10px 12px;border-left:3px solid var(--cyan);background:#0d181d;color:#eef7f8;font-size:14px}.hide-menu-page .selected strong{color:#fff}.hide-menu-page .notice{margin-bottom:11px;padding:10px 12px;border:1px solid #725d00;border-radius:7px;background:#2a2300;color:#ffe783;font-size:13px;line-height:1.45}.hide-menu-page .flash{margin-bottom:11px;padding:10px 12px;border:1px solid #1e8f6d;border-radius:7px;background:rgba(45,212,191,.12);color:#8ff5df;font-size:13px}.hide-menu-page .error{margin-top:7px;color:#ff9eaa;font-size:13px}.hide-menu-page .matrix{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.hide-menu-page .group{border:1px solid #2a414a;border-radius:8px;background:#0e171b;overflow:hidden}.hide-menu-page .group-head{display:flex;align-items:flex-start;gap:9px;padding:10px;border-bottom:1px solid #2a414a;background:#15262d;color:#c9f8ff;font-size:14px}.hide-menu-page .group-head input,.hide-menu-page .item input{width:21px;height:21px;flex:0 0 auto;margin:0;accent-color:#00d9ee}.hide-menu-page .group-head strong{display:block;color:#fff}.hide-menu-page .group-head small{display:block;margin-top:3px;color:#9ee8f1;font-size:11px;font-weight:400;line-height:1.3}.hide-menu-page .item{display:flex;align-items:flex-start;gap:9px;padding:10px;border-bottom:1px solid #203139;color:#eef3f4;font-size:14px}.hide-menu-page .item:last-child{border-bottom:0}.hide-menu-page .item small{display:block;margin-top:3px;color:var(--muted);font-size:11px;line-height:1.3}.hide-menu-page .toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:11px}.hide-menu-page .toolbar-actions{display:flex;gap:7px;flex-wrap:wrap}.hide-menu-page .note{color:var(--muted);font-size:12px;line-height:1.45}.hide-menu-page .form-actions{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px}.hide-menu-page .empty{padding:30px 12px;color:var(--muted);font-size:14px;text-align:center}
        @media(max-width:900px){.hide-menu-page .grid{grid-template-columns:1fr}.hide-menu-page .user-list{max-height:290px}.hide-menu-page .matrix{grid-template-columns:1fr}}
        @media(max-width:560px){.hide-menu-page{padding:8px}.hide-menu-page .hide-head{align-items:flex-start;flex-direction:column}.hide-menu-page .body{padding:9px}.hide-menu-page .panel{padding:9px}.hide-menu-page .toolbar,.hide-menu-page .form-actions{align-items:stretch;flex-direction:column}.hide-menu-page .toolbar-actions,.hide-menu-page .toolbar-actions .btn,.hide-menu-page .form-actions .btn{width:100%}}
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / User Management</div>
            <h1>Hide <span class="cyan">Menu</span></h1>
            <div class="muted">Sembunyikan menu parent atau submenu per user agar sidebar dan tabel Home lebih sederhana.</div>
        </div>
    </div>

    @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
    @error('form')<div class="error">{{ $message }}</div>@enderror

    <section class="hide-card">
        <div class="hide-head"><div><h1>PENGATURAN VISIBILITAS MENU</h1><div class="subtitle">Checkbox tercentang berarti menu terlihat. Jika parent disembunyikan, submenu yang tetap tercentang akan muncul langsung pada sidebar sebagai link mandiri.</div></div><span class="badge">{{ count($users) }} user</span></div>
        <div class="body">
            @if(! $tableReady)<div class="notice">Tabel preferensi Hide Menu belum tersedia. Tampilan masih menggunakan default semua terlihat. Jalankan migration fitur ini sebelum menyimpan pengaturan.</div>@endif
            <div class="grid">
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
                        <div class="selected">User terpilih: <strong>{{ $selectedUsername }}</strong> <span class="muted">(NIK {{ $selectedUserId }})</span></div>
                        <div class="toolbar"><span class="note">Default awal semua parent dan submenu terlihat. Hilangkan centang pada item yang ingin disembunyikan.</span><div class="toolbar-actions"><button type="button" class="btn btn-ghost" wire:click="selectAll">CENTANG SEMUA</button><button type="button" class="btn btn-ghost" wire:click="clearAll">KOSONGKAN SEMUA</button></div></div>
                        <div class="matrix">
                            @foreach($groups as $groupName => $group)
                                <div class="group" wire:key="hide-group-{{ md5($groupName) }}">
                                    @if($group['parent_key'] !== '')
                                        <label class="group-head" wire:key="hide-parent-{{ md5($groupName) }}"><input type="checkbox" value="{{ $group['parent_key'] }}" wire:model.live="selectedKeys"><span><strong>{{ $group['parent_name'] }}</strong><small>Parent group — menyembunyikan header parent; submenu tetap independen.</small></span></label>
                                    @else
                                        <div class="group-head" wire:key="hide-parent-label-{{ md5($groupName) }}"><span><strong>{{ $group['parent_name'] }}</strong><small>Menu level utama — setiap item diatur secara mandiri.</small></span></div>
                                    @endif
                                    @foreach($group['items'] as $item)
                                        <label class="item" wire:key="hide-item-{{ $item['key'] }}"><input type="checkbox" value="{{ $item['key'] }}" wire:model.live="selectedKeys"><span><strong>{{ $item['name'] }}</strong><small>{{ $item['description'] }}</small></span></label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <div class="form-actions"><span class="note">Pengaturan ini hanya mengatur tampilan. Hak akses buka halaman tetap mengikuti menu authorization.</span><button type="button" class="btn btn-cyan" wire:click="save" wire:loading.attr="disabled" wire:target="save">SIMPAN HIDE MENU</button></div>
                    @else
                        <div class="empty">Pilih user aktif untuk menampilkan checklist visibilitas menu.</div>
                    @endif
                </section>
            </div>
        </div>
    </section>
</div>
