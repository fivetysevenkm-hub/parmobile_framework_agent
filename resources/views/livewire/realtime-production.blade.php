<div class="app-shell realtime-production-page">
    <style>
        .realtime-production-page .rt-header { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:18px; }
        .realtime-production-page .rt-header h1 { margin:5px 0 6px; }
        .realtime-production-page .rt-card { background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
        .realtime-production-page .filter-panel { display:grid; grid-template-columns:minmax(220px,1fr) minmax(400px,2fr) auto; align-items:end; gap:12px; padding:14px; border-bottom:1px solid var(--line); background:rgba(255,255,255,.025); }
        .realtime-production-page .field-label { display:block; margin-bottom:6px; color:var(--muted); font-size:.65rem; letter-spacing:.07em; text-transform:uppercase; }
        .realtime-production-page .dark-control { width:100%; background:var(--legacy_0d1418); border:1px solid var(--line); border-radius:7px; color:var(--legacy_f5f7f8); padding:10px 11px; outline:none; }
        .realtime-production-page .dark-control:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .realtime-production-page .filter-options { display:flex; flex-wrap:wrap; align-items:center; gap:8px 13px; min-height:39px; }
        .realtime-production-page .filter-option { display:flex; align-items:center; gap:5px; color:var(--legacy_d9e0e3); font-size:.7rem; white-space:nowrap; cursor:pointer; }
        .realtime-production-page .filter-option input { accent-color:var(--cyan); }
        .realtime-production-page .filter-actions { display:flex; gap:8px; }
        .realtime-production-page .filter-actions .btn { min-height:39px; white-space:nowrap; text-decoration:none; text-align:center; }
        .realtime-production-page .tab-bar { display:flex; gap:4px; padding:10px 14px 0; border-bottom:1px solid var(--line); background:var(--legacy_111a1f); }
        .realtime-production-page .tab-button { padding:10px 14px; border:0; border-bottom:2px solid transparent; background:transparent; color:var(--muted); font-size:.73rem; cursor:pointer; }
        .realtime-production-page .tab-button.active { border-bottom-color:var(--cyan); color:var(--cyan); }
        .realtime-production-page .tab-body { padding:14px; }
        .realtime-production-page .table-wrap { max-height:calc(100vh - 330px); min-height:280px; overflow:auto; -webkit-overflow-scrolling:touch; }
        .realtime-production-page table { width:100%; min-width:900px; border-collapse:collapse; font-size:.73rem; }
        .realtime-production-page th, .realtime-production-page td { padding:9px 8px; border:1px solid var(--legacy_26333a); text-align:center; white-space:nowrap; vertical-align:middle; }
        .realtime-production-page th { position:sticky; top:0; z-index:2; background:var(--legacy_0b1013); color:var(--cyan); font-size:.67rem; letter-spacing:.04em; text-transform:uppercase; }
        .realtime-production-page th:first-child, .realtime-production-page td:first-child { position:sticky; left:0; z-index:1; background:var(--legacy_0b1013); text-align:left; }
        .realtime-production-page th:first-child { z-index:3; }
        .realtime-production-page tbody tr { cursor:pointer; }
        .realtime-production-page tbody tr:hover td { background:var(--legacy_1c3540); }
        .realtime-production-page .lot-cell { color:var(--legacy_bfeef3); font-weight:700; }
        .realtime-production-page .qty-cell { color:var(--orange); font-weight:700; }
        .realtime-production-page .pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:12px; padding-top:12px; color:var(--muted); font-size:.72rem; }
        .realtime-production-page .pagination { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:5px; }
        .realtime-production-page .page-link, .realtime-production-page .page-current, .realtime-production-page .page-disabled { display:grid; min-width:31px; min-height:30px; place-items:center; padding:4px 8px; border:1px solid var(--line); border-radius:6px; font-size:.7rem; line-height:1; }
        .realtime-production-page .page-link { background:var(--legacy_182228); color:var(--legacy_d9e0e3); cursor:pointer; }
        .realtime-production-page .page-link:hover { border-color:var(--cyan); color:var(--cyan); }
        .realtime-production-page .page-current { background:var(--cyan); border-color:var(--cyan); color:var(--legacy_041014); font-weight:700; }
        .realtime-production-page .page-disabled { color:var(--legacy_d9e0e3); opacity:.4; }
        .realtime-production-page .master-card { margin-bottom:14px; padding:14px; border:1px solid var(--line); border-left:4px solid var(--cyan); border-radius:8px; background:var(--legacy_111a1f); }
        .realtime-production-page .master-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
        .realtime-production-page .master-label { display:block; margin-bottom:4px; color:var(--muted); font-size:.62rem; text-transform:uppercase; }
        .realtime-production-page .master-value { display:block; color:var(--orange); font-size:.84rem; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .realtime-production-page .flow-container { display:flex; flex-wrap:wrap; justify-content:center; gap:12px; }
        .realtime-production-page .process-box { position:relative; width:100%; max-width:235px; padding:12px; border:1px solid var(--line); border-radius:7px; background:var(--legacy_050708); box-shadow:0 4px 10px rgba(0,0,0,.4); font-size:.73rem; }
        .realtime-production-page .process-box .flpp-badge { position:absolute; top:8px; right:8px; padding:2px 6px; border-radius:4px; background:var(--legacy_ef233c); color:#fff; font-size:.6rem; font-weight:700; }
        .realtime-production-page .process-head { display:flex; align-items:center; gap:8px; margin-bottom:9px; padding-bottom:6px; border-bottom:1px solid var(--legacy_26333a); }
        .realtime-production-page .seq-number { color:var(--legacy_fff700); font-size:1rem; font-weight:700; }
        .realtime-production-page .seq-name { color:var(--legacy_2dd4bf); font-weight:700; text-transform:uppercase; }
        .realtime-production-page .process-line { margin:4px 0; color:var(--legacy_f5f7f8); line-height:1.35; }
        .realtime-production-page .process-label { display:inline-block; min-width:64px; color:var(--orange); font-weight:600; }
        .realtime-production-page .process-divider { margin:7px 0; border-top:1px solid var(--legacy_26333a); }
        .realtime-production-page .tray-unit { display:inline-block; min-width:30px; margin-right:4px; padding:1px 5px; border-radius:3px; background:#fff; color:#000; text-align:center; font-weight:700; }
        .realtime-production-page .process-status { margin-top:8px; padding-top:6px; border-top:1px solid var(--legacy_26333a); font-weight:700; }
        .realtime-production-page .status-finish { color:var(--legacy_2dd4bf); } .realtime-production-page .status-progress { color:var(--legacy_3b9cff); } .realtime-production-page .status-hold { color:var(--legacy_ff5964); }
        .realtime-production-page .ojt-badge { margin-left:5px; padding:1px 5px; border-radius:3px; background:var(--legacy_fff700); color:#000; font-size:.6rem; }
        .realtime-production-page .empty-state { display:grid; min-height:280px; place-items:center; padding:24px; color:var(--muted); text-align:center; }
        .realtime-production-page .status-bar { display:flex; justify-content:space-between; gap:12px; margin-top:12px; color:var(--muted); font-size:.68rem; }
        .realtime-production-page .certificate-actions{display:flex;justify-content:flex-end;gap:8px;margin:-3px 0 12px}.realtime-production-page .certificate-actions .btn{text-decoration:none;white-space:nowrap}
        @media (max-width:1100px) { .realtime-production-page .filter-panel { grid-template-columns:1fr 1fr; } .realtime-production-page .filter-panel > div:nth-child(3) { grid-column:span 2; } .realtime-production-page .master-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:720px) { .realtime-production-page { padding:14px 12px 24px; } .realtime-production-page .rt-header { display:block; } .realtime-production-page .filter-panel { display:block; } .realtime-production-page .filter-panel > div { margin-bottom:10px; } .realtime-production-page .filter-panel > div:last-child { margin-bottom:0; } .realtime-production-page .filter-actions { display:grid; grid-template-columns:1fr 1fr; } .realtime-production-page .filter-actions .btn { width:100%; } .realtime-production-page .tab-bar { overflow-x:auto; } .realtime-production-page .tab-button { flex:0 0 auto; } .realtime-production-page .table-wrap { max-height:52vh; min-height:240px; } .realtime-production-page .pagination-wrap { display:block; } .realtime-production-page .pagination-wrap > span { display:block; margin-bottom:8px; } .realtime-production-page .pagination { justify-content:flex-start; } .realtime-production-page .tab-body { padding:10px; } .realtime-production-page .master-grid { grid-template-columns:1fr 1fr; gap:8px; } .realtime-production-page .flow-container { justify-content:stretch; } .realtime-production-page .process-box { max-width:none; } .realtime-production-page .status-bar { display:block; } .realtime-production-page .status-bar span { display:block; margin-top:5px; } .realtime-production-page .certificate-actions{justify-content:stretch}.realtime-production-page .certificate-actions .btn{width:100%;text-align:center} }
        @media (max-width:430px) { .realtime-production-page .master-grid { grid-template-columns:1fr 1fr; } .realtime-production-page th, .realtime-production-page td { padding:8px 7px; font-size:.69rem; } }
    </style>

    <div class="rt-header">
        <div>
            <div class="eyebrow">PAR Mobile / Monitoring</div>
            <h1>Real Time <span class="cyan">Production</span></h1>
            <div class="muted">Monitoring posisi proses produksi secara real-time.</div>
        </div>
    </div>

    @error('selectedLot') <div class="error">{{ $message }}</div> @enderror

    <section class="rt-card">
        <div class="filter-panel">
            <div>
                <label class="field-label" for="realtimeSearch">Keyword</label>
                <input id="realtimeSearch" type="search" class="dark-control" wire:model.live.debounce.400ms="search" wire:keydown.enter="applySearch" placeholder="Cari lot, part, customer...">
            </div>
            <div>
                <span class="field-label">Filter berdasarkan</span>
                <div class="filter-options">
                    @foreach ($filters as $filterValue => $filterLabel)
                        <label class="filter-option"><input type="radio" wire:model.live="filterBy" value="{{ $filterValue }}"> {{ $filterLabel }}</label>
                    @endforeach
                </div>
            </div>
            <div class="filter-actions">
                <button type="button" class="btn btn-cyan" wire:click="applySearch" wire:loading.attr="disabled" wire:target="applySearch">Cari</button>
                <button type="button" class="btn btn-ghost" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters">Reset</button>
            </div>
        </div>

        <div class="tab-bar" role="tablist">
            <button type="button" class="tab-button {{ $activeTab === 'list' ? 'active' : '' }}" wire:click="showList">List Pencarian Lot</button>
            <button type="button" class="tab-button {{ $activeTab === 'position' ? 'active' : '' }}" wire:click="selectLot('{{ $selectedLot }}')" @disabled($selectedLot === '')>Data Posisi Real Time</button>
        </div>

        @if ($activeTab === 'list')
            <div class="tab-body">
                <div class="muted" style="margin-bottom:9px;font-size:.72rem">Klik baris lot untuk melihat detail posisi proses.</div>
                @if ($rows->count() > 0)
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>No Lot</th><th>SRP</th><th>No Part</th><th>Nama Part</th><th>Lot Cust</th><th>SJ Cust</th><th>Qty</th></tr></thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="realtime-production-{{ $row['no_lot'] }}" wire:click="selectLot('{{ $row['no_lot'] }}')">
                                        <td class="lot-cell">{{ $row['no_lot'] ?? '-' }}</td><td>{{ $row['no_srp'] ?? '-' }}</td><td>{{ $row['no_part'] ?? '-' }}</td><td>{{ $row['nm_part'] ?? '-' }}</td><td>{{ $row['no_lot_cust'] ?? '-' }}</td><td>{{ $row['no_sj_cust'] ?? '-' }}</td><td class="qty-cell">{{ $row['qty'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-wrap">
                        <span>Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }} data</span>
                        @if ($rows->hasPages())
                            <nav class="pagination" aria-label="Pagination Real Time Production">
                                @if ($rows->onFirstPage())<span class="page-disabled">‹ Sebelumnya</span>@else<button type="button" class="page-link" wire:click="previousPage" wire:loading.attr="disabled">‹ Sebelumnya</button>@endif
                                @php $firstPage = max(1, $rows->currentPage() - 2); $lastPage = min($rows->lastPage(), $rows->currentPage() + 2); @endphp
                                @for ($page = $firstPage; $page <= $lastPage; $page++)
                                    @if ($page === $rows->currentPage())<span class="page-current" aria-current="page">{{ $page }}</span>@else<button type="button" class="page-link" wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled">{{ $page }}</button>@endif
                                @endfor
                                @if ($rows->currentPage() === $rows->lastPage())<span class="page-disabled">Berikutnya ›</span>@else<button type="button" class="page-link" wire:click="nextPage" wire:loading.attr="disabled">Berikutnya ›</button>@endif
                            </nav>
                        @endif
                    </div>
                @else
                    <div class="empty-state">Data lot tidak ditemukan untuk pencarian yang dipilih.</div>
                @endif
            </div>
        @else
            <div class="tab-body" wire:poll.60s="refreshFlow">
                @if ($selectedMaster !== [])
                    <div class="certificate-actions"><a class="btn btn-cyan" href="{{ route('realtime.production.certificate', ['noLot' => $selectedLot]) }}" target="_blank" rel="noopener">Cetak Sertifikat SRP</a></div>
                    <div class="master-card">
                        <div class="master-grid">
                            <div><span class="master-label">No Lot</span><span class="master-value">{{ $selectedMaster['no_lot'] ?? '-' }}</span></div>
                            <div><span class="master-label">No Part</span><span class="master-value">{{ $selectedMaster['no_part'] ?? '-' }}</span></div>
                            <div><span class="master-label">Nama Part</span><span class="master-value">{{ $selectedMaster['nm_part'] ?? '-' }}</span></div>
                            <div><span class="master-label">No SRP</span><span class="master-value">{{ $selectedMaster['no_srp'] ?? '-' }}</span></div>
                            <div><span class="master-label">SJ Customer</span><span class="master-value">{{ $selectedMaster['no_sj_cust'] ?? '-' }}</span></div>
                            <div><span class="master-label">Lot Customer</span><span class="master-value">{{ $selectedMaster['no_lot_cust'] ?? '-' }}</span></div>
                            <div><span class="master-label">Qty</span><span class="master-value">{{ $selectedMaster['qty'] ?? '-' }}</span></div>
                            <div><span class="master-label">Customer</span><span class="master-value">{{ $selectedMaster['customer'] ?? '-' }}</span></div>
                        </div>
                    </div>
                @endif

                @if ($processRows !== [])
                    <div class="flow-container">
                        @foreach ($processRows as $process)
                            @php
                                $statusClass = 'status-progress';
                                $statusText = 'On Progress';
                                if (($process['finish'] ?? '') === 'yes') { $statusClass = 'status-finish'; $statusText = 'Finish'; }
                                elseif (($process['hold'] ?? '') === 'yes') { $statusClass = 'status-hold'; $statusText = 'Hold'; }
                            @endphp
                            <article class="process-box">
                                @if (($process['akakami'] ?? '') === 'yes')<span class="flpp-badge">FLPP</span>@endif
                                <div class="process-head"><span class="seq-number">{{ str_pad((string) ($process['seq'] ?? 0), 2, '0', STR_PAD_LEFT) }}</span><span class="seq-name">{{ $process['nm_seq'] ?? '-' }}</span></div>
                                <div class="process-line"><span class="process-label">PIC 1:</span>{{ $process['pic_01'] ?? '-' }}</div>
                                <div class="process-line"><span class="process-label">PIC 2:</span>{{ $process['pic_02'] ?? '-' }}</div>
                                <div class="process-line"><span class="process-label">PIC 3:</span>{{ $process['pic_03'] ?? '-' }}</div>
                                <div class="process-line"><span class="process-label">PIC 4:</span>{{ $process['pic_04'] ?? '-' }}</div>
                                <div class="process-divider"></div>
                                <div class="process-line"><span class="process-label">Start:</span>{{ $process['start_time'] ?? '-' }}</div>
                                <div class="process-line"><span class="process-label">Finish:</span>{{ $process['finish_time'] ?? '-' }}</div>
                                <div class="process-divider"></div>
                                <div class="process-line"><span class="process-label">Duration:</span>{{ $process['duration'] ?? '0' }}</div>
                                <div class="process-line"><span class="process-label">No Tray:</span><span class="tray-unit">{{ $process['tray_no'] ?? '-' }}</span><span class="tray-unit">{{ $process['tray_nokecil'] ?? '-' }}</span></div>
                                <div class="process-line"><span class="process-label">No Mesin:</span>{{ $process['no_mesin'] ?? '-' }}</div>
                                <div class="process-status"><span class="process-label">Status:</span><span class="{{ $statusClass }}">{{ $statusText }}</span>@if (($process['ojt'] ?? '') === 'yes')<span class="ojt-badge">OJT</span>@endif</div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Pilih data pada tab List Pencarian Lot terlebih dahulu.</div>
                @endif
            </div>
        @endif
    </section>

    <div class="status-bar"><span>{{ $totalRows }} lot tersedia dari query real-time.</span><span>Refresh posisi otomatis setiap 60 detik.</span></div>
</div>
