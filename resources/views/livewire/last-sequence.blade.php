<div class="app-shell last-sequence-page">
    <style>
        .last-sequence-page .sequence-card { background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
        .last-sequence-page .sequence-header { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:18px; }
        .last-sequence-page .sequence-header h1 { margin:5px 0 6px; }
        .last-sequence-page .info-banner { margin-bottom:14px; padding:11px 14px; border-left:4px solid var(--cyan); border-radius:7px; background:#0b1013; color:#bfeef3; font-size:.78rem; line-height:1.5; }
        .last-sequence-page .filter-panel { display:grid; grid-template-columns:minmax(180px,1.25fr) repeat(2,minmax(145px,.8fr)) minmax(260px,1.5fr) auto; align-items:end; gap:10px; padding:14px; border-bottom:1px solid var(--line); background:rgba(255,255,255,.025); }
        .last-sequence-page .field-label { display:block; margin-bottom:6px; color:var(--muted); font-size:.65rem; letter-spacing:.07em; text-transform:uppercase; }
        .last-sequence-page .dark-control { width:100%; background:#0d1418; border:1px solid var(--line); border-radius:7px; color:#f5f7f8; padding:10px 11px; outline:none; }
        .last-sequence-page .dark-control:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .last-sequence-page select.dark-control { color-scheme:dark; }
        .last-sequence-page .filter-options { display:flex; flex-wrap:wrap; gap:8px 12px; min-height:38px; align-items:center; }
        .last-sequence-page .filter-option { display:flex; align-items:center; gap:5px; color:#d9e0e3; font-size:.69rem; white-space:nowrap; cursor:pointer; }
        .last-sequence-page .filter-option input { accent-color:var(--cyan); }
        .last-sequence-page .filter-actions { display:flex; gap:8px; }
        .last-sequence-page .filter-actions .btn { min-height:39px; white-space:nowrap; text-decoration:none; text-align:center; }
        .last-sequence-page .table-wrap { overflow:auto; max-height:calc(100vh - 310px); min-height:280px; -webkit-overflow-scrolling:touch; }
        .last-sequence-page table { width:100%; min-width:1260px; border-collapse:collapse; font-size:.73rem; }
        .last-sequence-page th, .last-sequence-page td { padding:9px 8px; border:1px solid #26333a; text-align:center; white-space:nowrap; vertical-align:middle; }
        .last-sequence-page th { position:sticky; top:0; z-index:2; background:#0b1013; color:var(--cyan); font-size:.67rem; text-transform:uppercase; }
        .last-sequence-page th:first-child, .last-sequence-page td:first-child { position:sticky; left:0; z-index:1; background:#0b1013; text-align:left; }
        .last-sequence-page th:first-child { z-index:3; }
        .last-sequence-page tbody tr:hover td { background:#1c3540; }
        .last-sequence-page .sort-button { display:inline-flex; align-items:center; gap:5px; border:0; background:transparent; color:inherit; font:inherit; cursor:pointer; }
        .last-sequence-page .sort-button:hover { color:#fff; }
        .last-sequence-page .sort-icon { color:var(--orange); font-size:.72rem; }
        .last-sequence-page .lot-link { color:#bfeef3; font-weight:700; }
        .last-sequence-page .process-cell { color:var(--orange); font-weight:700; }
        .last-sequence-page .pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; color:var(--muted); font-size:.72rem; }
        .last-sequence-page .pagination { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:5px; }
        .last-sequence-page .page-link, .last-sequence-page .page-current, .last-sequence-page .page-disabled { display:grid; min-width:31px; min-height:30px; place-items:center; padding:4px 8px; border:1px solid var(--line); border-radius:6px; font-size:.7rem; line-height:1; }
        .last-sequence-page .page-link { background:#182228; color:#d9e0e3; cursor:pointer; }
        .last-sequence-page .page-link:hover { border-color:var(--cyan); color:var(--cyan); }
        .last-sequence-page .page-current { background:var(--cyan); border-color:var(--cyan); color:#041014; font-weight:700; }
        .last-sequence-page .page-disabled { color:#d9e0e3; opacity:.4; }
        .last-sequence-page .empty-state { display:grid; min-height:280px; place-items:center; padding:24px; color:var(--muted); text-align:center; }
        .last-sequence-page .status-bar { display:flex; justify-content:space-between; gap:12px; margin-top:12px; color:var(--muted); font-size:.68rem; }
        @media (max-width:1100px) { .last-sequence-page .filter-panel { grid-template-columns:repeat(3,minmax(0,1fr)); } .last-sequence-page .filter-options { grid-column:span 2; } }
        @media (max-width:720px) { .last-sequence-page { padding:14px 12px 24px; } .last-sequence-page .sequence-header { display:block; } .last-sequence-page .filter-panel { display:block; } .last-sequence-page .filter-panel > div { margin-bottom:10px; } .last-sequence-page .filter-panel > div:last-child { margin-bottom:0; } .last-sequence-page .filter-options { min-height:0; } .last-sequence-page .filter-actions { display:grid; grid-template-columns:1fr 1fr; } .last-sequence-page .filter-actions .btn { width:100%; } .last-sequence-page .table-wrap { max-height:58vh; min-height:240px; } .last-sequence-page .pagination-wrap { display:block; } .last-sequence-page .pagination-wrap > span { display:block; margin-bottom:8px; } .last-sequence-page .pagination { justify-content:flex-start; } .last-sequence-page .status-bar { display:block; } .last-sequence-page .status-bar span { display:block; margin-top:5px; } }
    </style>

    <div class="sequence-header">
        <div>
            <div class="eyebrow">PAR Mobile / Monitoring</div>
            <h1>List <span class="cyan">Proses Terakhir</span></h1>
            <div class="muted">Menampilkan posisi terakhir sequence dari setiap lot produksi.</div>
        </div>
    </div>

    <div class="info-banner"><strong>Monitoring Last Sequence:</strong> Gunakan pencarian dan rentang tanggal untuk melihat posisi proses terakhir. Klik judul kolom yang memiliki ikon sort untuk mengubah urutan data.</div>

    <section class="sequence-card">
        <div class="filter-panel">
            <div>
                <label class="field-label" for="lastSequenceSearch">Pencarian</label>
                <input id="lastSequenceSearch" type="search" class="dark-control" wire:model.live.debounce.400ms="search" placeholder="Keyword...">
            </div>
            <div>
                <label class="field-label" for="lastSequenceFrom">Dari tanggal</label>
                <input id="lastSequenceFrom" type="date" class="dark-control" wire:model.live="dateFrom">
            </div>
            <div>
                <label class="field-label" for="lastSequenceTo">Sampai tanggal</label>
                <input id="lastSequenceTo" type="date" class="dark-control" wire:model.live="dateTo">
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
                <button type="button" class="btn btn-cyan" wire:click="applyFilter" wire:loading.attr="disabled" wire:target="applyFilter">Cari</button>
                <button type="button" class="btn btn-ghost" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters">Reset</button>
                <a class="btn btn-ghost" href="{{ route('last.sequence.export', ['keyword' => $search, 'filter' => $filterBy, 'tgl_awal' => $dateFrom, 'tgl_akhir' => $dateTo, 'sort_col' => $sortColumn, 'sort_dir' => $sortDirection]) }}" target="_blank" rel="noopener">Excel</a>
            </div>
        </div>

        @if ($rows->count() > 0)
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><button type="button" class="sort-button" wire:click="sortBy('no_lot')">No Lot <span class="sort-icon">{{ $sortColumn === 'no_lot' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th>No Part</th>
                            <th><button type="button" class="sort-button" wire:click="sortBy('nm_part')">Nama Part <span class="sort-icon">{{ $sortColumn === 'nm_part' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th><button type="button" class="sort-button" wire:click="sortBy('customer')">Customer <span class="sort-icon">{{ $sortColumn === 'customer' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th><button type="button" class="sort-button" wire:click="sortBy('no_sj_cust')">SJ Cust <span class="sort-icon">{{ $sortColumn === 'no_sj_cust' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th><button type="button" class="sort-button" wire:click="sortBy('no_lot_cust')">Lot Cust <span class="sort-icon">{{ $sortColumn === 'no_lot_cust' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th>Qty</th>
                            <th><button type="button" class="sort-button" wire:click="sortBy('nm_seq')">Proses Terakhir <span class="sort-icon">{{ $sortColumn === 'nm_seq' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th>Start</th><th>Finish</th><th>Durasi</th><th>Tray 1</th><th>Tray 2</th><th>PIC</th><th>Mesin</th><th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="last-sequence-{{ $row['no_lot'] }}">
                                <td class="lot-link">{{ $row['no_lot'] ?? '-' }}</td>
                                <td>{{ $row['no_part'] ?? '-' }}</td>
                                <td>{{ $row['nm_part'] ?? '-' }}</td>
                                <td>{{ $row['customer'] ?? '-' }}</td>
                                <td>{{ $row['no_sj_cust'] ?? '-' }}</td>
                                <td>{{ $row['no_lot_cust'] ?? '-' }}</td>
                                <td>{{ $row['qty'] ?? '-' }}</td>
                                <td class="process-cell">{{ $row['nm_seq'] ?? '-' }}</td>
                                <td>{{ $row['start_time'] ?? '-' }}</td>
                                <td>{{ $row['finish_time'] ?? '-' }}</td>
                                <td>{{ $row['duration'] ?? '-' }}</td>
                                <td>{{ $row['tray_no'] ?? '-' }}</td>
                                <td>{{ $row['tray_nokecil'] ?? '-' }}</td>
                                <td>{{ $row['pic_01'] ?? '-' }}</td>
                                <td>{{ $row['no_mesin'] ?? '-' }}</td>
                                <td>{{ $row['updated_at'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">
                <span>Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }} data</span>
                @if ($rows->hasPages())
                    <nav class="pagination" aria-label="Pagination List Proses Terakhir">
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
            <div class="empty-state">Tidak ada data untuk filter yang dipilih.</div>
        @endif
    </section>

    <div class="status-bar"><span>Menampilkan {{ $totalRows }} data hasil query.</span><span>Last Sequence Tracking</span></div>
</div>
