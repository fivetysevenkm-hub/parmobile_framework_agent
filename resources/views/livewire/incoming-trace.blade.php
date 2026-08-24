<div class="app-shell incoming-trace-page" wire:poll.30s>
    <style>
        .incoming-trace-page .trace-header { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:18px; }
        .incoming-trace-page .trace-header h1 { margin:5px 0 6px; }
        .incoming-trace-page .info-banner { margin-bottom:14px; padding:11px 14px; border-left:4px solid var(--orange); border-radius:7px; background:#0b1013; color:#ffe0a3; font-size:.78rem; line-height:1.5; }
        .incoming-trace-page .trace-card { background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
        .incoming-trace-page .filter-panel { display:grid; grid-template-columns:minmax(180px,1.1fr) repeat(2,minmax(145px,.8fr)) minmax(280px,1.6fr) auto; align-items:end; gap:10px; padding:14px; border-bottom:1px solid var(--line); background:rgba(255,255,255,.025); }
        .incoming-trace-page .field-label { display:block; margin-bottom:6px; color:var(--muted); font-size:.65rem; letter-spacing:.07em; text-transform:uppercase; }
        .incoming-trace-page .dark-control { width:100%; background:#0d1418; border:1px solid var(--line); border-radius:7px; color:#f5f7f8; padding:10px 11px; outline:none; }
        .incoming-trace-page .dark-control:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .incoming-trace-page .filter-options { display:flex; flex-wrap:wrap; align-items:center; gap:8px 12px; min-height:38px; }
        .incoming-trace-page .filter-option { display:flex; align-items:center; gap:5px; color:#d9e0e3; font-size:.69rem; white-space:nowrap; cursor:pointer; }
        .incoming-trace-page .filter-option input { accent-color:var(--cyan); }
        .incoming-trace-page .filter-actions { display:flex; gap:8px; }
        .incoming-trace-page .filter-actions .btn { min-height:39px; white-space:nowrap; text-decoration:none; text-align:center; }
        .incoming-trace-page .table-wrap { max-height:calc(100vh - 320px); min-height:280px; overflow:auto; -webkit-overflow-scrolling:touch; }
        .incoming-trace-page table { width:100%; min-width:1120px; border-collapse:collapse; font-size:.73rem; }
        .incoming-trace-page th, .incoming-trace-page td { padding:9px 8px; border:1px solid #26333a; text-align:center; white-space:nowrap; vertical-align:middle; }
        .incoming-trace-page th { position:sticky; top:0; z-index:2; background:#0b1013; color:var(--cyan); font-size:.67rem; letter-spacing:.04em; text-transform:uppercase; }
        .incoming-trace-page th:nth-child(2), .incoming-trace-page td:nth-child(2) { position:sticky; left:0; z-index:1; background:#0b1013; }
        .incoming-trace-page th:nth-child(2) { z-index:3; }
        .incoming-trace-page tbody tr:hover td { background:#1c3540; }
        .incoming-trace-page .lot-cell { color:#bfeef3; font-weight:700; }
        .incoming-trace-page .qty-cell { color:var(--orange); font-weight:700; }
        .incoming-trace-page .pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; color:var(--muted); font-size:.72rem; }
        .incoming-trace-page .pagination { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:5px; }
        .incoming-trace-page .page-link, .incoming-trace-page .page-current, .incoming-trace-page .page-disabled { display:grid; min-width:31px; min-height:30px; place-items:center; padding:4px 8px; border:1px solid var(--line); border-radius:6px; font-size:.7rem; line-height:1; }
        .incoming-trace-page .page-link { background:#182228; color:#d9e0e3; cursor:pointer; }
        .incoming-trace-page .page-link:hover { border-color:var(--cyan); color:var(--cyan); }
        .incoming-trace-page .page-current { background:var(--cyan); border-color:var(--cyan); color:#041014; font-weight:700; }
        .incoming-trace-page .page-disabled { color:#d9e0e3; opacity:.4; }
        .incoming-trace-page .empty-state { display:grid; min-height:280px; place-items:center; padding:24px; color:var(--muted); text-align:center; }
        .incoming-trace-page .status-bar { display:flex; justify-content:space-between; gap:12px; margin-top:12px; color:var(--muted); font-size:.68rem; }
        @media (max-width:1100px) { .incoming-trace-page .filter-panel { grid-template-columns:repeat(3,minmax(0,1fr)); } .incoming-trace-page .filter-options { grid-column:span 2; } }
        @media (max-width:720px) { .incoming-trace-page { padding:14px 12px 24px; } .incoming-trace-page .trace-header { display:block; } .incoming-trace-page .filter-panel { display:block; } .incoming-trace-page .filter-panel > div { margin-bottom:10px; } .incoming-trace-page .filter-panel > div:last-child { margin-bottom:0; } .incoming-trace-page .filter-options { min-height:0; } .incoming-trace-page .filter-actions { display:grid; grid-template-columns:1fr 1fr 1fr; } .incoming-trace-page .filter-actions .btn { width:100%; padding-left:7px; padding-right:7px; } .incoming-trace-page .table-wrap { max-height:58vh; min-height:240px; } .incoming-trace-page .pagination-wrap { display:block; } .incoming-trace-page .pagination-wrap > span { display:block; margin-bottom:8px; } .incoming-trace-page .pagination { justify-content:flex-start; } .incoming-trace-page .status-bar { display:block; } .incoming-trace-page .status-bar span { display:block; margin-top:5px; } }
    </style>

    <div class="trace-header">
        <div>
            <div class="eyebrow">PAR Mobile / Monitoring</div>
            <h1>Incoming <span class="cyan">Trace</span></h1>
            <div class="muted">Monitoring incoming sebelum data masuk ke proses produksi utama.</div>
        </div>
    </div>

    <div class="info-banner"><strong>Informasi:</strong> Incoming Trace digunakan ketika data real-time produksi belum muncul di sistem utama atau belum melalui proses Production Setting.</div>

    <section class="trace-card">
        <div class="filter-panel">
            <div>
                <label class="field-label" for="incomingSearch">Keyword</label>
                <input id="incomingSearch" type="search" class="dark-control" wire:model.live.debounce.400ms="search" placeholder="Keyword...">
            </div>
            <div>
                <label class="field-label" for="incomingFrom">Dari tgl input</label>
                <input id="incomingFrom" type="date" class="dark-control" wire:model.live="dateFrom">
            </div>
            <div>
                <label class="field-label" for="incomingTo">Sampai tgl input</label>
                <input id="incomingTo" type="date" class="dark-control" wire:model.live="dateTo">
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
                <a class="btn btn-ghost" href="{{ route('incoming.trace.export', ['keyword' => $search, 'filter' => $filterBy, 'tgl_awal' => $dateFrom, 'tgl_akhir' => $dateTo]) }}" target="_blank" rel="noopener">Excel</a>
            </div>
        </div>

        @if ($rows->count() > 0)
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tgl Input</th><th>No Lot</th><th>SRP</th><th>No Part</th><th>Nama Part</th><th>Lot Cust</th><th>SJ Cust</th><th>Qty</th><th>Customer</th><th>Tgl Delivery</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="incoming-trace-{{ $row['no_lot'] }}-{{ $loop->index }}">
                                <td>{{ $row['tgl_input'] ?? '-' }}</td>
                                <td class="lot-cell">{{ $row['no_lot'] ?? '-' }}</td>
                                <td>{{ $row['no_srp'] ?? '-' }}</td>
                                <td>{{ $row['no_part'] ?? '-' }}</td>
                                <td>{{ $row['nm_part'] ?? '-' }}</td>
                                <td>{{ $row['no_lot_cust'] ?? '-' }}</td>
                                <td>{{ $row['no_sj_cust'] ?? '-' }}</td>
                                <td class="qty-cell">{{ $row['qty'] ?? '-' }}</td>
                                <td>{{ $row['customer'] ?? '-' }}</td>
                                <td>{{ $row['tgl_delivery'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">
                <span>Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }} data</span>
                @if ($rows->hasPages())
                    <nav class="pagination" aria-label="Pagination Incoming Trace">
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
            <div class="empty-state">Tidak ada data incoming untuk filter yang dipilih.</div>
        @endif
    </section>

    <div class="status-bar"><span>Menampilkan {{ $totalRows }} data hasil query.</span><span>Refresh otomatis setiap 30 detik · Terakhir diperbarui {{ $lastUpdated->format('d-m-Y H:i:s') }}</span></div>
</div>
