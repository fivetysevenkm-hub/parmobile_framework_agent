<div class="mm-page">
    <div class="topbar mm-topbar">
        <div>
            <div class="eyebrow">PAR Mobile / Routing</div>
            <h1>Machine <span class="cyan">Mapping</span></h1>
            <div class="muted">Atur mesin mana saja yang boleh dipilih operator pada tiap sequence saat input proses Gemba.</div>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="mm-alert mm-alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="mm-alert mm-alert-error" role="alert">{{ session('error') }}</div>
    @endif

    <section class="mm-card">
        <div class="mm-card-head">
            <div>
                <h2>Atur Mapping Mesin</h2>
                <p>Sequence yang belum dimapping akan tetap menampilkan <strong>semua mesin</strong> pada combo Gemba.</p>
            </div>
            <span class="mm-head-badge">{{ count($sequences) }} Sequence</span>
        </div>

        <div class="mm-steps">
            <div class="mm-step mm-step-active"><span class="mm-step-no">1</span><span>Pilih Sequence</span></div>
            <span class="mm-step-arrow">→</span>
            <div class="mm-step"><span class="mm-step-no">2</span><span>Centang Mesin yang Diizinkan</span></div>
            <span class="mm-step-arrow">→</span>
            <div class="mm-step"><span class="mm-step-no">3</span><span>Simpan Mapping</span></div>
        </div>

        <div class="mm-grid">
            <section class="mm-panel mm-panel-sequences">
                <div class="mm-panel-head">
                    <h3>Daftar Sequence</h3>
                    <input type="search" class="mm-search" wire:model.live.debounce.250ms="sequenceSearch"
                        placeholder="Cari sequence..." aria-label="Cari sequence">
                </div>

                <div class="mm-seq-list">
                    @forelse($filteredSequences as $sequence)
                        @php
                            $mappedCount = $mappedCounts[$sequence['id_seq']] ?? 0;
                            $isActive = (string) $selectedSequence === (string) $sequence['id_seq'];
                        @endphp
                        <button type="button"
                            class="mm-seq-item {{ $isActive ? 'is-active' : '' }}"
                            wire:click="selectSequence('{{ $sequence['id_seq'] }}')">
                            <span class="mm-seq-id">{{ $sequence['id_seq'] }}</span>
                            <span class="mm-seq-name">{{ $sequence['nm_sequence'] ?: 'Tanpa nama' }}</span>
                            @if($mappedCount > 0)
                                <span class="mm-seq-badge is-mapped">{{ $mappedCount }} mesin</span>
                            @else
                                <span class="mm-seq-badge is-empty">Belum mapping</span>
                            @endif
                        </button>
                    @empty
                        <div class="mm-empty">Sequence tidak ditemukan.</div>
                    @endforelse
                </div>
            </section>

            <section class="mm-panel mm-panel-machines">
                <div class="mm-select-head">
                    <div class="mm-select-info">
                        <span class="mm-select-id">{{ $selectedSequence }}</span>
                        <div>
                            <h3>{{ $selectedSequenceName ?: 'Pilih sequence di sebelah kiri' }}</h3>
                            @if($selectedSequenceCount > 0)
                                <p class="mm-select-status is-mapped">{{ $selectedSequenceCount }} mesin terdaftar</p>
                            @else
                                <p class="mm-select-status is-empty">Belum ada mapping — combo Gemba menampilkan semua mesin</p>
                            @endif
                        </div>
                    </div>
                </div>

                @if($selectedSequence !== '')
                    <div class="mm-toolbar">
                        <input type="search" class="mm-search" wire:model.live.debounce.250ms="machineSearch"
                            placeholder="Cari mesin..." aria-label="Cari mesin">
                        <div class="mm-toolbar-actions">
                            <button type="button" class="mm-btn mm-btn-ghost" wire:click="selectAllVisible"
                                @disabled($filteredMachineOptions === [])>Centang Semua</button>
                            <button type="button" class="mm-btn mm-btn-ghost" wire:click="clearSelection"
                                @disabled($selectedMachines === [])>Kosongkan</button>
                        </div>
                    </div>

                    <div class="mm-machine-list">
                        @forelse($filteredMachineOptions as $machine)
                            <label class="mm-machine {{ $machine['checked'] ? 'is-checked' : '' }}">
                                <input type="checkbox" class="mm-check"
                                    wire:click="toggleMachine('{{ $machine['no_mesin'] }}')"
                                    @checked($machine['checked'])>
                                <span class="mm-check-icon"></span>
                                <span class="mm-machine-name">{{ $machine['mesin'] }}</span>
                                <span class="mm-machine-no">{{ $machine['no_mesin'] }}</span>
                            </label>
                        @empty
                            <div class="mm-empty">Tidak ada mesin yang cocok.<br><small>Coba kata kunci lain atau reset pencarian.</small></div>
                        @endforelse
                    </div>

                    <div class="mm-footer">
                        <div class="mm-footer-summary">
                            <span class="mm-footer-count">{{ count($selectedMachines) }} mesin dipilih</span>
                            @if($selectedMachines !== [])
                                <div class="mm-chips">
                                    @foreach($machineOptions as $machine)
                                        @if(in_array($machine['no_mesin'], $selectedMachines, true))
                                            <span class="mm-chip">{{ $machine['mesin'] }} <small>{{ $machine['no_mesin'] }}</small></span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="mm-footer-actions">
                            <button type="button" class="mm-btn mm-btn-danger" wire:click="removeMapping"
                                wire:confirm="Hapus seluruh mapping mesin untuk sequence {{ $selectedSequence }}?"
                                @disabled($selectedSequenceCount === 0)>Hapus Mapping</button>
                            <button type="button" class="mm-btn mm-btn-primary" wire:click="save"
                                @disabled($selectedMachines === [])>Simpan Mapping</button>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </section>

    <style>
        .mm-page { padding:18px; }
        .mm-topbar { margin-bottom:12px; }

        .mm-alert { margin-bottom:14px; padding:12px 14px; border-radius:8px; font-size:.78rem; font-weight:600; }
        .mm-alert-success { border:1px solid color-mix(in srgb,var(--success) 35%,var(--line)); background:color-mix(in srgb,var(--success) 12%,var(--panel)); color:var(--success); }
        .mm-alert-error { border:1px solid color-mix(in srgb,var(--danger) 35%,var(--line)); background:color-mix(in srgb,var(--danger) 10%,var(--panel)); color:var(--danger); }

        .mm-card { background:var(--panel); border:1px solid var(--line); border-radius:12px; overflow:hidden; }
        .mm-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid var(--line); background:var(--panel_2); }
        .mm-card-head h2 { margin:0; color:var(--text-title); font-size:1rem; }
        .mm-card-head p { margin:5px 0 0; color:var(--muted); font-size:.74rem; }
        .mm-card-head strong { color:var(--cyan); }
        .mm-head-badge { white-space:nowrap; padding:6px 12px; border:1px solid var(--line); border-radius:20px; background:var(--panel); color:var(--muted); font-size:.7rem; font-weight:700; }

        .mm-steps { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:14px 20px; border-bottom:1px solid var(--line); }
        .mm-step { display:inline-flex; align-items:center; gap:8px; color:var(--muted); font-size:.74rem; font-weight:600; }
        .mm-step-no { display:grid; place-items:center; width:22px; height:22px; border-radius:50%; background:var(--panel_2); border:1px solid var(--line); color:var(--muted); font-size:.68rem; font-weight:700; }
        .mm-step-active .mm-step-no { background:var(--button-primary); border-color:var(--button-primary); color:var(--text-button); }
        .mm-step-active { color:var(--text-title); }
        .mm-step-arrow { color:var(--cyan); font-size:1rem; opacity:.6; }

        .mm-grid { display:grid; grid-template-columns:minmax(280px,380px) 1fr; gap:0; }
        .mm-panel { padding:18px 20px; }
        .mm-panel-sequences { border-right:1px solid var(--line); background:color-mix(in srgb,var(--panel_2) 45%,transparent); }
        .mm-panel-head { display:flex; flex-direction:column; gap:10px; margin-bottom:14px; }
        .mm-panel-head h3 { margin:0; color:var(--text-title); font-size:.82rem; letter-spacing:.04em; text-transform:uppercase; }
        .mm-panel-machines { min-width:0; }

        .mm-search { width:100%; box-sizing:border-box; padding:9px 12px; border:1px solid var(--line); border-radius:7px; background:var(--input_bg); color:var(--input_text); outline:none; font-size:.76rem; }
        .mm-search::placeholder { color:var(--input_placeholder); }
        .mm-search:focus { border-color:var(--focus_ring); box-shadow:0 0 0 3px color-mix(in srgb,var(--focus_ring) 16%,transparent); }

        .mm-seq-list { display:flex; flex-direction:column; gap:6px; max-height:560px; overflow:auto; padding-right:2px; }
        .mm-seq-item { display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:10px; width:100%; text-align:left; padding:10px 12px; border:1px solid var(--line); border-radius:8px; background:var(--panel); color:var(--text-body); cursor:pointer; transition:border-color .15s ease, background .15s ease; }
        .mm-seq-item:hover { border-color:var(--cyan); }
        .mm-seq-item.is-active { border-color:var(--cyan); background:color-mix(in srgb,var(--cyan) 10%,var(--panel)); }
        .mm-seq-id { font-family:'JetBrains Mono',monospace; font-size:.74rem; font-weight:700; color:var(--cyan); }
        .mm-seq-name { font-size:.76rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .mm-seq-badge { font-size:.62rem; font-weight:700; padding:3px 8px; border-radius:20px; white-space:nowrap; }
        .mm-seq-badge.is-mapped { background:var(--success_bg); color:var(--success); border:1px solid color-mix(in srgb,var(--success) 35%,transparent); }
        .mm-seq-badge.is-empty { background:var(--panel_2); color:var(--muted); border:1px solid var(--line); }

        .mm-select-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; margin-bottom:14px; border:1px solid var(--line); border-radius:9px; background:var(--panel_2); }
        .mm-select-info { display:flex; align-items:center; gap:12px; min-width:0; }
        .mm-select-id { flex:none; display:inline-flex; align-items:center; padding:6px 11px; border-radius:7px; background:color-mix(in srgb,var(--cyan) 16%,var(--panel)); border:1px solid color-mix(in srgb,var(--cyan) 45%,transparent); color:var(--cyan); font-family:'JetBrains Mono',monospace; font-size:.78rem; font-weight:700; }
        .mm-select-info h3 { margin:0; color:var(--text-title); font-size:.9rem; }
        .mm-select-status { margin:3px 0 0; font-size:.7rem; font-weight:600; }
        .mm-select-status.is-mapped { color:var(--success); }
        .mm-select-status.is-empty { color:var(--warning); }

        .mm-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
        .mm-toolbar .mm-search { flex:1; min-width:180px; }
        .mm-toolbar-actions { display:flex; gap:8px; }

        .mm-btn { border:0; border-radius:7px; padding:9px 13px; font-weight:700; font-size:.72rem; cursor:pointer; transition:filter .15s ease, transform .15s ease; }
        .mm-btn:active { transform:scale(.97); }
        .mm-btn:disabled { opacity:.45; cursor:not-allowed; }
        .mm-btn-primary { background:var(--button-primary); color:var(--text-button); }
        .mm-btn-primary:hover:not(:disabled) { background:var(--button-primary-hover); }
        .mm-btn-ghost { background:var(--button-secondary-bg); color:var(--button-secondary-text); border:1px solid var(--line); }
        .mm-btn-ghost:hover:not(:disabled) { border-color:var(--cyan); }
        .mm-btn-danger { background:var(--danger_bg); color:var(--danger); border:1px solid color-mix(in srgb,var(--danger) 35%,transparent); }
        .mm-btn-danger:hover:not(:disabled) { background:color-mix(in srgb,var(--danger) 18%,transparent); }

        .mm-machine-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:8px; max-height:420px; overflow:auto; align-content:start; }
        .mm-machine { position:relative; display:flex; align-items:center; gap:9px; padding:10px 12px; border:1px solid var(--line); border-radius:8px; background:var(--panel); cursor:pointer; transition:border-color .15s ease, background .15s ease; }
        .mm-machine:hover { border-color:var(--cyan); }
        .mm-machine.is-checked { border-color:var(--success); background:color-mix(in srgb,var(--success) 8%,var(--panel)); }
        .mm-check { position:absolute; opacity:0; pointer-events:none; }
        .mm-check-icon { flex:none; display:grid; place-items:center; width:17px; height:17px; border:1.5px solid var(--line); border-radius:4px; background:var(--input_bg); transition:all .15s ease; }
        .mm-machine.is-checked .mm-check-icon { background:var(--success); border-color:var(--success); }
        .mm-machine.is-checked .mm-check-icon::after { content:'✓'; font-size:.68rem; font-weight:700; color:#041014; line-height:1; }
        .mm-machine-name { font-size:.76rem; color:var(--text-body); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .mm-machine.is-checked .mm-machine-name { color:var(--text-title); }
        .mm-machine-no { margin-left:auto; flex:none; font-family:'JetBrains Mono',monospace; font-size:.64rem; color:var(--muted); }

        .mm-empty { grid-column:1/-1; padding:26px 14px; border:1px dashed var(--line); border-radius:8px; color:var(--muted); text-align:center; font-size:.78rem; line-height:1.5; }

        .mm-footer { display:flex; align-items:flex-end; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-top:16px; padding-top:16px; border-top:1px solid var(--line); }
        .mm-footer-summary { flex:1; min-width:220px; }
        .mm-footer-count { color:var(--text-title); font-size:.76rem; font-weight:700; }
        .mm-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .mm-chip { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border:1px solid color-mix(in srgb,var(--cyan) 35%,transparent); border-radius:20px; background:color-mix(in srgb,var(--cyan) 10%,var(--panel)); color:var(--text-title); font-size:.68rem; }
        .mm-chip small { color:var(--muted); font-family:'JetBrains Mono',monospace; }
        .mm-footer-actions { display:flex; gap:9px; }

        @media(max-width:1100px) {
            .mm-grid { grid-template-columns:1fr; }
            .mm-panel-sequences { border-right:0; border-bottom:1px solid var(--line); }
            .mm-seq-list { max-height:280px; }
        }
        @media(max-width:560px) {
            .mm-page { padding:10px; }
            .mm-card-head { flex-direction:column; }
            .mm-machine-list { grid-template-columns:1fr; }
            .mm-footer { flex-direction:column; align-items:stretch; }
            .mm-footer-actions { flex-direction:column; }
            .mm-footer-actions .mm-btn { width:100%; }
        }
    </style>
</div>