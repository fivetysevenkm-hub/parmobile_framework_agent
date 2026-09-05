<div class="custom-theme-page">
    <style>
        .custom-theme-page{padding:24px;color:var(--text-body)}
        .custom-theme-page .theme-card{background:var(--panel);border:1px solid var(--line);border-radius:12px;overflow:hidden}
        .custom-theme-page .theme-header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid var(--line);background:color-mix(in srgb,var(--panel) 92%,var(--text-title))}
        .custom-theme-page h1{margin:0;font-size:1.1rem;color:var(--text-title)}.custom-theme-page h2{color:var(--text-title)}
        .custom-theme-page .subtitle{margin-top:6px;color:var(--text-body);font-size:.76rem;line-height:1.5}.custom-theme-page .theme-body{padding:20px}
        .custom-theme-page .theme-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:18px}
        .custom-theme-page .section{padding:16px;border:1px solid var(--line);border-radius:9px;background:var(--panel-2)}
        .custom-theme-page .section h2{margin:0 0 12px;color:var(--cyan);font-size:.8rem;letter-spacing:.08em;text-transform:uppercase}
        .custom-theme-page .preset-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        .custom-theme-page .preset{display:flex;align-items:center;gap:10px;width:100%;padding:11px;border:1px solid var(--line);border-radius:8px;background:var(--panel);color:var(--text-title);text-align:left;cursor:pointer;transition:border-color .16s ease,transform .16s ease,box-shadow .16s ease}
        .custom-theme-page .preset:hover,.custom-theme-page .preset.active{border-color:var(--cyan);transform:translateY(-1px)}.custom-theme-page .preset.active{box-shadow:0 0 0 2px color-mix(in srgb,var(--cyan) 25%,transparent)}
        .custom-theme-page .swatches{display:flex;gap:3px;flex:0 0 auto}.custom-theme-page .swatch{width:18px;height:18px;border:1px solid color-mix(in srgb,var(--text-header) 35%,transparent);border-radius:50%}
        .custom-theme-page .preset-name{font-family:Tahoma,Arial,sans-serif;font-size:14px}.custom-theme-page .color-section{margin-top:14px}
        .custom-theme-page .color-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        .custom-theme-page .color-item{display:grid;grid-template-columns:42px minmax(0,1fr);gap:9px;align-items:center;padding:9px;border:1px solid var(--line);border-radius:8px;background:var(--panel)}
        .custom-theme-page .color-item input[type=color]{width:38px;height:34px;padding:2px;border:1px solid var(--line);border-radius:6px;background:var(--input-bg);cursor:pointer}
        .custom-theme-page .color-item label{display:block;margin:0;color:var(--text-title);font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.25}.custom-theme-page .hex{display:block;margin-top:3px;color:var(--text-footer);font-family:'JetBrains Mono',monospace;font-size:.65rem;text-transform:uppercase}
        .custom-theme-page .preview{border:1px solid var(--line);border-radius:9px;background:var(--bg);overflow:hidden}.custom-theme-page .preview-nav{padding:11px 13px;background:var(--navbar-bg);color:var(--text-header);font-family:Tahoma,Arial,sans-serif;font-size:14px;font-weight:700}
        .custom-theme-page .preview-sidebar{padding:9px 11px;background:var(--sidebar-bg);color:var(--text-sidebar);font-family:Tahoma,Arial,sans-serif;font-size:12px}.custom-theme-page .preview-content{padding:13px}
        .custom-theme-page .preview-panel{padding:13px;border:1px solid var(--line);border-radius:8px;background:var(--panel)}.custom-theme-page .preview-panel h3{margin:0 0 8px;color:var(--text-title);font-size:.8rem}
        .custom-theme-page .preview-copy{margin:0 0 9px;color:var(--text-body);font-family:Tahoma,Arial,sans-serif;font-size:12px;line-height:1.45}.custom-theme-page .preview-table{width:100%;min-width:0;border-collapse:collapse;font-size:.7rem}
        .custom-theme-page .preview-table th,.custom-theme-page .preview-table td{padding:8px;border:1px solid var(--table-border);color:var(--text-table);white-space:normal;text-align:left;background:var(--table-row)}.custom-theme-page .preview-table th{background:var(--table-header)}.custom-theme-page .preview-table tr:nth-child(even) td{background:var(--table-alt)}
        .custom-theme-page .preview-status{display:inline-block;padding:2px 6px;border-radius:999px;font-weight:700}.custom-theme-page .preview-status.success{background:var(--success-bg);color:var(--success)}.custom-theme-page .preview-status.warning{background:var(--warning-bg);color:var(--warning)}
        .custom-theme-page .preview-button{display:inline-flex;margin-top:10px;padding:8px 12px;border:0;border-radius:6px;background:var(--button-primary);color:var(--text-button);font-size:.72rem;font-weight:700}.custom-theme-page .preview-footer{margin-top:10px;color:var(--text-footer);font-size:11px}
        .custom-theme-page .note{margin-top:14px;padding:11px 12px;border-left:3px solid var(--orange);background:var(--panel);color:var(--text-footer);font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.5}.custom-theme-page .note strong{color:var(--text-title)}
        .custom-theme-page .contrast-alert{margin-bottom:14px;padding:12px 14px;border:1px solid var(--danger);border-radius:8px;background:var(--danger-bg);color:var(--danger);font-size:.78rem;line-height:1.5}.custom-theme-page .contrast-alert strong{color:var(--text-title)}.custom-theme-page .contrast-alert ul{margin:6px 0 0 18px;padding:0}
        .custom-theme-page .actions{display:flex;justify-content:flex-end;gap:9px;margin-top:18px;padding-top:16px;border-top:1px solid var(--line)}.custom-theme-page .flash{margin-bottom:14px;padding:11px 13px;border:1px solid var(--success);border-radius:7px;background:var(--success-bg);color:var(--success);font-family:Tahoma,Arial,sans-serif;font-size:14px}.custom-theme-page .error{margin-top:4px;color:var(--danger);font-size:.68rem}
        @media(max-width:900px){.custom-theme-page{padding:14px}.custom-theme-page .theme-layout{grid-template-columns:1fr}.custom-theme-page .theme-body{padding:14px}}
        @media(max-width:560px){.custom-theme-page{padding:10px}.custom-theme-page .theme-header{display:block;padding:14px}.custom-theme-page .preset-grid,.custom-theme-page .color-grid{grid-template-columns:1fr}.custom-theme-page .section{padding:12px}.custom-theme-page .actions{display:grid;grid-template-columns:1fr 1fr}.custom-theme-page .actions .btn{width:100%}}
        @media(prefers-reduced-motion:reduce){.custom-theme-page .preset{transition:none}}
    </style>

    <div class="topbar"><div><div class="eyebrow">PAR Mobile / User Preference</div><h1>Custom <span class="cyan">Tema</span></h1><div class="subtitle"></div></div></div>
    @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
    @if($contrastWarnings)<div class="contrast-alert"><strong>Kontras perlu diperbaiki sebelum disimpan.</strong><ul>@foreach($contrastWarnings as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>@endif
    @error('contrast')<div class="contrast-alert">{{ $message }}</div>@enderror

    <section class="theme-card">
        <div class="theme-header"><div><h2>PERSONALISASI TEMA</h2><div class="subtitle"></div></div><span class="tag">Preset: {{ $presetNames[$presetKey] ?? ($presetKey === 'custom' ? 'Custom' : $presetKey) }}</span></div>
        <div class="theme-body"><div class="theme-layout">
            <div>
                <section class="section"><h2>Preset Warna</h2><div class="preset-grid">@foreach($presetNames as $key => $name)<button type="button" class="preset {{ $presetKey === $key ? 'active' : '' }}" wire:click="applyPreset('{{ $key }}')"><span class="swatches">@foreach(array_slice(($presets[$key]['colors'] ?? []) ?: [], 0, 5) as $swatch)<span class="swatch" style="background:{{ $swatch }}"></span>@endforeach</span><span class="preset-name">{{ $name }}</span></button>@endforeach</div></section>
                @foreach($colorGroups as $group)
                    <section class="section color-section"><h2>{{ $group['name'] }}</h2><div class="color-grid">@foreach($group['keys'] as $key) @if(isset($colorLabels[$key]))<div class="color-item"><input type="color" wire:model.live="colors.{{ $key }}" aria-label="{{ $colorLabels[$key] }}"><div><label>{{ $colorLabels[$key] }}</label><span class="hex">{{ $colors[$key] ?? '-' }}</span>@error('colors.'.$key)<div class="error">{{ $message }}</div>@enderror</div></div>@endif @endforeach</div></section>
                @endforeach
            </div>
            <section class="section"><h2>Preview Kontras</h2><div class="preview"><div class="preview-nav">PAR MOBILE · {{ auth()->user()->username ?? 'User' }}</div><div class="preview-sidebar">Home &nbsp; | &nbsp; Daily Report &nbsp; | &nbsp; PTTN Omes</div><div class="preview-content"><div class="preview-panel"><h3>Contoh Judul Panel</h3><p class="preview-copy">Contoh paragraf body untuk memastikan warna teks tetap terbaca di atas area form dan permukaan panel.</p><table class="preview-table"><thead><tr><th>Menu</th><th>Status</th></tr></thead><tbody><tr><td>Daily Report</td><td><span class="preview-status success">Aktif</span></td></tr><tr><td>PTTN Omes</td><td><span class="preview-status warning">Monitoring</span></td></tr></tbody></table><span class="preview-button">Text Tombol</span><div class="preview-footer">Contoh text footer / informasi tambahan</div></div></div></div><div class="note"><strong>Catatan penggunaan:</strong> Tema yang Anda pilih akan disimpan hanya untuk akun Anda saja.</div></section>
        </div><div class="actions"><button type="button" class="btn btn-ghost" wire:click="resetToDefault">KEMBALI DEFAULT</button><button type="button" class="btn btn-cyan" wire:click="save" wire:loading.attr="disabled" wire:target="save">SIMPAN TEMA</button></div></div>
    </section>
</div>
