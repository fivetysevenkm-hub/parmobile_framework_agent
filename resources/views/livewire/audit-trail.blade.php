<div>
    <style>
        .audit-page{padding:2px}
        .audit-card{background:var(--panel);border:1px solid var(--line);border-radius:11px;overflow:hidden}
        .audit-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
        .badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:.65rem;font-weight:700;letter-spacing:.03em;background:color-mix(in srgb,var(--cyan) 14%,transparent);color:var(--cyan);white-space:nowrap}
        .badge.login,.badge.logout,.badge.password_change{background:color-mix(in srgb,var(--lime) 16%,transparent);color:var(--lime)}
        .badge.login_failed,.badge.access_denied,.badge.delete{background:color-mix(in srgb,var(--red) 18%,transparent);color:var(--red)}
        .badge.create{background:color-mix(in srgb,var(--cyan) 16%,transparent);color:var(--cyan)}
        .badge.update{background:color-mix(in srgb,var(--orange) 18%,transparent);color:var(--orange)}
        .badge.access{background:color-mix(in srgb,var(--lime) 16%,transparent);color:var(--lime)}
        .audit-table th,.audit-table td{min-width:0}
        .audit-table td{text-align:left}
        .audit-table .time{font-family:'JetBrains Mono',monospace;font-size:.7rem;white-space:nowrap}
        .audit-table .cell-user{white-space:nowrap}
        .audit-table .cell-action{max-width:340px;white-space:normal;line-height:1.4}
        .btn-detail{background:var(--button-secondary-bg);color:var(--button-secondary-text);border:1px solid var(--line);border-radius:6px;padding:5px 10px;font-size:.7rem;font-weight:700;cursor:pointer}
        .btn-detail:hover{background:var(--button-primary);color:var(--text-button)}
        .pager{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 17px;border-top:1px solid var(--line);flex-wrap:wrap}
        .pager-info{color:var(--muted);font-size:.72rem;font-family:'JetBrains Mono',monospace}
        .pager-btns{display:flex;gap:8px}
        .page-link,.page-disabled{padding:8px 13px;border:1px solid var(--line);border-radius:7px;background:var(--button-secondary-bg);color:var(--button-secondary-text);font-size:.72rem;font-weight:700;cursor:pointer}
        .page-link:hover{background:var(--button-primary);color:var(--text-button)}
        .page-disabled{opacity:.45;cursor:not-allowed}
        .audit-modal[hidden]{display:none}
        .audit-modal{position:fixed;inset:0;z-index:100;display:grid;place-items:center;padding:18px;background:color-mix(in srgb,var(--modal-backdrop) 72%,transparent)}
        .audit-modal-card{width:min(100%,720px);max-height:86vh;overflow:auto;border:1px solid var(--modal-border);border-radius:12px;background:var(--modal-bg);box-shadow:0 20px 60px color-mix(in srgb,var(--shadow-color) 55%,transparent);color:var(--modal-text);font-family:Tahoma,Arial,sans-serif}
        .audit-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid var(--line)}
        .audit-modal-head h2{margin:0;font-size:15px;color:var(--modal-heading)}
        .audit-modal-close{border:0;background:transparent;color:var(--modal-text);cursor:pointer;font-size:24px;line-height:1}
        .audit-modal-body{padding:16px 18px}
        .kv{display:flex;gap:14px;margin-bottom:10px}
        .kv .k{flex:0 0 130px;color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;padding-top:2px}
        .kv .v{flex:1;min-width:0;word-break:break-word;font-size:.82rem;color:var(--modal-copy);line-height:1.5}
        .diff-block{margin-top:14px;border:1px solid var(--line);border-radius:8px;overflow:hidden}
        .diff-block .diff-head{padding:9px 12px;background:color-mix(in srgb,var(--panel) 40%,transparent);color:var(--muted);font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;border-bottom:1px solid var(--line)}
        .diff-table{min-width:100%;font-size:.74rem}
        .diff-table td{border:1px solid var(--line);padding:8px 10px;text-align:left;white-space:normal;vertical-align:top}
        .diff-table td.k{font-weight:700;color:var(--modal-copy);width:130px}
        .diff-table td.from{color:var(--red)}
        .diff-table td.to{color:var(--lime)}
        .json-pre{background:rgba(0,0,0,.22);border:1px solid var(--line);border-radius:7px;padding:10px;margin:6px 0;font-family:'JetBrains Mono',monospace;font-size:.7rem;white-space:pre-wrap;word-break:break-word;color:var(--modal-copy)}
        @media(max-width:720px){.audit-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.kv{flex-direction:column;gap:3px}.kv .k{flex:0 0 auto}}
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / User Management</div>
            <h1>Audit <span class="cyan">Trail</span></h1>
            <div class="muted">Jejak audit seluruh aktivitas: login, akses menu, dan perubahan data per user (IATF 16949:2016).</div>
        </div>
        <div class="toolbar">
            @php($hasFilter = $search !== '' || $event !== '' || $module !== '' || $usernameFilter !== '' || $dateFrom !== '' || $dateTo !== '')
            @if($hasFilter)
                <a class="btn btn-cyan" target="_blank" rel="noopener"
                   href="{{ route('user-management.audit-trail.certificate', [
                       'search' => $search, 'event' => $event, 'module' => $module,
                       'username' => $usernameFilter, 'date_from' => $dateFrom, 'date_to' => $dateTo,
                   ]) }}">Sertifikat Audit</a>
            @else
                <button type="button" class="btn btn-ghost" title="Terapkan minimal satu filter untuk mencetak sertifikat" disabled>Sertifikat Audit</button>
            @endif
        </div>
    </div>

    <form class="filter" wire:submit.prevent="resetFilters">
        <div>
            <label>Kata Kunci</label>
            <input type="search" wire:model.live="search" placeholder="Cari aksi, URL, atau module..." autocomplete="off">
        </div>
        <div>
            <label>Event</label>
            <select wire:model.live="event" style="background:var(--input-bg);border:1px solid var(--line);color:var(--input-text);border-radius:7px;padding:10px 12px;min-width:200px">
                <option value="">Semua Event</option>
                @foreach($events as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Module</label>
            <select wire:model.live="module" style="background:var(--input-bg);border:1px solid var(--line);color:var(--input-text);border-radius:7px;padding:10px 12px;min-width:200px">
                <option value="">Semua Module</option>
                @foreach($modules as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>User / NIK</label>
            <input type="search" wire:model.live="usernameFilter" placeholder="Nama user atau NIK..." autocomplete="off">
        </div>
        <div>
            <label>Dari Tanggal</label>
            <input type="date" wire:model.live="dateFrom">
        </div>
        <div>
            <label>Sampai Tanggal</label>
            <input type="date" wire:model.live="dateTo">
        </div>
        <button type="button" class="btn btn-ghost" wire:click="resetFilters">RESET</button>
    </form>

    <div class="audit-stats">
        <div class="stat"><span class="stat-label">Total Log</span><span class="stat-value">{{ $totalLogs }}</span></div>
        <div class="stat"><span class="stat-label">Akses Menu</span><span class="stat-value" style="color:var(--lime)">{{ collect($logs)->where('event','access')->count() }}</span></div>
        <div class="stat"><span class="stat-label">Login Gagal</span><span class="stat-value" style="color:var(--red)">{{ collect($logs)->where('event','login_failed')->count() }}</span></div>
        <div class="stat"><span class="stat-label">Perubahan Data</span><span class="stat-value" style="color:var(--orange)">{{ collect($logs)->whereIn('event',['create','update','delete'])->count() }}</span></div>
    </div>

    <section class="audit-card table-panel">
        <div class="table-scroll">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Module</th>
                        <th>Aksi</th>
                        <th>IP</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="time">{{ \Illuminate\Support\Carbon::parse($log['created_at'])->format('d/m/Y H:i:s') }}</td>
                            <td class="cell-user">
                                <strong>{{ $log['username'] ?? '-' }}</strong>
                                @if($log['user_id']) <br><small class="muted">NIK {{ $log['user_id'] }}</small> @endif
                            </td>
                            <td><span class="badge {{ $log['event'] }}">{{ $events[$log['event']] ?? $log['event'] }}</span></td>
                            <td><small class="muted">{{ $log['module'] ?? '-' }}</small></td>
                            <td class="cell-action">{{ $log['action'] }}</td>
                            <td><small class="muted">{{ $log['ip_address'] ?? '-' }}</small></td>
                            <td>
                                <button type="button" class="btn-detail" wire:click="showDetail({{ $log['id'] }})">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Tidak ada data audit yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">
            <span class="pager-info">{{ $totalLogs }} log &nbsp;|&nbsp; Halaman {{ $currentPage }} / {{ $totalPages }}</span>
            <div class="pager-btns">
                <button type="button" class="page-{{ $currentPage > 1 ? 'link' : 'disabled' }}" wire:click="previousPage" @if($currentPage<=1) disabled @endif>‹ Sebelumnya</button>
                <button type="button" class="page-{{ $currentPage < $totalPages ? 'link' : 'disabled' }}" wire:click="nextPage" @if($currentPage>=$totalPages) disabled @endif>Berikutnya ›</button>
            </div>
        </div>
    </section>

    @if($this->selectedLog)
        @php($log = $this->selectedLog)
        <div class="audit-modal" wire:click.self="closeDetail">
            <div class="audit-modal-card">
                <div class="audit-modal-head">
                    <h2>Detail Audit — #{{ $log->id }}</h2>
                    <button type="button" class="audit-modal-close" wire:click="closeDetail">×</button>
                </div>
                <div class="audit-modal-body">
                    <div class="kv"><span class="k">Waktu</span><span class="v">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</span></div>
                    <div class="kv"><span class="k">User</span><span class="v">{{ $log->username ?? '-' }} @if($log->user_id)&nbsp;(NIK {{ $log->user_id }})@endif</span></div>
                    <div class="kv"><span class="k">Event</span><span class="v"><span class="badge {{ $log->event }}">{{ $events[$log->event] ?? $log->event }}</span></span></div>
                    <div class="kv"><span class="k">Module</span><span class="v">{{ $log->module ?? '-' }}</span></div>
                    <div class="kv"><span class="k">Aksi</span><span class="v">{{ $log->action }}</span></div>
                    <div class="kv"><span class="k">URL</span><span class="v">{{ $log->url ?? '-' }}</span></div>
                    <div class="kv"><span class="k">Method</span><span class="v">{{ $log->method ?? '-' }}</span></div>
                    <div class="kv"><span class="k">IP Address</span><span class="v">{{ $log->ip_address ?? '-' }}</span></div>
                    <div class="kv"><span class="k">User Agent</span><span class="v">{{ $log->user_agent ?? '-' }}</span></div>

                    @if($log->metadata && isset($log->metadata['changes']) && is_array($log->metadata['changes']))
                        <div class="diff-block">
                            <div class="diff-head">Perubahan Data</div>
                            <table class="diff-table">
                                <thead><tr><td class="k">Field</td><td>Sebelum</td><td>Sesudah</td></tr></thead>
                                <tbody>
                                    @foreach($log->metadata['changes'] as $field => $change)
                                        <tr>
                                            <td class="k">{{ $field }}</td>
                                            <td class="from">{{ is_scalar($change['from'] ?? null) ? $change['from'] : json_encode($change['from'] ?? null) }}</td>
                                            <td class="to">{{ is_scalar($change['to'] ?? null) ? $change['to'] : json_encode($change['to'] ?? null) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($log->old_data)
                        <div class="diff-block">
                            <div class="diff-head">Data Lama (old_data)</div>
                            <pre class="json-pre">{{ json_encode($log->old_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                    @if($log->new_data)
                        <div class="diff-block">
                            <div class="diff-head">Data Baru (new_data)</div>
                            <pre class="json-pre">{{ json_encode($log->new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                    @if($log->metadata && ! isset($log->metadata['changes']))
                        <div class="diff-block">
                            <div class="diff-head">Metadata</div>
                            <pre class="json-pre">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
