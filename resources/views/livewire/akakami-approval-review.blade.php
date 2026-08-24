<div class="app-shell akakami-review-page">
    <style>
        .akakami-review-page .review-card { background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
        .akakami-review-page .review-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
        .akakami-review-page .review-header h1 { margin:5px 0 6px; }
        .akakami-review-page .review-toolbar { display:flex; align-items:end; justify-content:space-between; gap:12px; padding:14px; border-bottom:1px solid var(--line); background:rgba(255,255,255,.025); }
        .akakami-review-page .review-toolbar label { display:block; color:var(--muted); font-size:.68rem; letter-spacing:.07em; margin-bottom:6px; text-transform:uppercase; }
        .akakami-review-page .search-box { flex:1 1 380px; max-width:520px; }
        .akakami-review-page .dark-control { width:100%; background:#0d1418; border:1px solid var(--line); border-radius:7px; color:#f5f7f8; padding:10px 11px; outline:none; }
        .akakami-review-page .dark-control:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .akakami-review-page .queue-scroll { max-height:calc(100vh - 260px); min-height:300px; overflow:auto; -webkit-overflow-scrolling:touch; }
        .akakami-review-page table { width:100%; min-width:780px; border-collapse:collapse; font-size:.76rem; }
        .akakami-review-page th, .akakami-review-page td { padding:10px 9px; border:1px solid #26333a; text-align:center; white-space:nowrap; vertical-align:middle; }
        .akakami-review-page th { position:sticky; top:0; z-index:2; background:#0b1013; color:var(--cyan); }
        .akakami-review-page td:first-child, .akakami-review-page th:first-child { text-align:left; }
        .akakami-review-page .queue-row { cursor:pointer; transition:background .16s ease; }
        .akakami-review-page .queue-row:hover td { background:#1c3540; }
        .akakami-review-page .queue-row.selected td { background:#0754bd; color:#fff; }
        .akakami-review-page .flpp-button { display:inline-flex; align-items:center; justify-content:space-between; gap:8px; width:100%; border:0; background:transparent; color:inherit; font:inherit; text-align:left; cursor:pointer; }
        .akakami-review-page .review-body { padding:16px; }
        .akakami-review-page .empty-state { display:grid; min-height:300px; place-items:center; padding:24px; color:var(--muted); text-align:center; }
        .akakami-review-page .detail-card { margin-top:16px; border:1px solid var(--line); border-radius:10px; background:#111a1f; overflow:hidden; }
        .akakami-review-page .detail-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; border-bottom:1px solid var(--line); background:rgba(45,212,191,.06); }
        .akakami-review-page .detail-heading h2 { margin:0; color:var(--cyan); font-size:.95rem; }
        .akakami-review-page .detail-heading .date { color:var(--muted); font-size:.72rem; }
        .akakami-review-page .detail-content { padding:16px; }
        .akakami-review-page .detail-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
        .akakami-review-page .detail-item { min-width:0; }
        .akakami-review-page .detail-item.wide { grid-column:span 2; }
        .akakami-review-page .detail-label { display:block; margin-bottom:5px; color:var(--muted); font-size:.63rem; letter-spacing:.07em; text-transform:uppercase; }
        .akakami-review-page .detail-value { display:block; min-height:32px; padding:7px 9px; border-bottom:1px solid #34464e; color:#f5f7f8; font-size:.78rem; overflow-wrap:anywhere; }
        .akakami-review-page .detail-value.accent { color:var(--orange); font-weight:700; }
        .akakami-review-page .detail-value.problem { color:#ffb4b8; }
        .akakami-review-page textarea.dark-control { min-height:80px; resize:vertical; }
        .akakami-review-page .confirm-box { margin-top:18px; padding:14px; border:1px solid #5d4c1e; border-radius:8px; background:rgba(255,159,28,.07); }
        .akakami-review-page .confirm-label { display:flex; gap:9px; align-items:flex-start; color:#ffd98c; font-size:.78rem; cursor:pointer; }
        .akakami-review-page .confirm-label input { accent-color:var(--cyan); margin-top:3px; }
        .akakami-review-page .decision-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:14px; }
        .akakami-review-page .decision-actions .btn { width:100%; min-height:40px; }
        .akakami-review-page .btn-danger { background:#b42335; color:#fff; }
        .akakami-review-page .pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; color:var(--muted); font-size:.72rem; }
        .akakami-review-page .pagination { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:5px; }
        .akakami-review-page .page-link, .akakami-review-page .page-current, .akakami-review-page .page-disabled { display:grid; min-width:31px; min-height:30px; place-items:center; padding:4px 8px; border:1px solid var(--line); border-radius:6px; font-size:.7rem; line-height:1; }
        .akakami-review-page .page-link { background:#182228; color:#d9e0e3; cursor:pointer; }
        .akakami-review-page .page-link:hover { border-color:var(--cyan); color:var(--cyan); }
        .akakami-review-page .page-current { background:var(--cyan); border-color:var(--cyan); color:#041014; font-weight:700; }
        .akakami-review-page .page-disabled { color:#d9e0e3; opacity:.4; }
        .akakami-review-page .flash { margin-bottom:14px; padding:11px 13px; border:1px solid #1e8f6d; border-radius:7px; background:rgba(45,212,191,.12); color:#8ff5df; font-size:.78rem; }
        @media (max-width:900px) { .akakami-review-page .detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:720px) { .akakami-review-page { padding:14px 12px 24px; } .akakami-review-page .review-header { display:block; } .akakami-review-page .review-toolbar { display:block; } .akakami-review-page .search-box { max-width:none; margin-bottom:10px; } .akakami-review-page .review-toolbar .btn { width:100%; } .akakami-review-page .queue-scroll { max-height:52vh; min-height:240px; } .akakami-review-page .pagination-wrap { display:block; } .akakami-review-page .pagination-wrap > span { display:block; margin-bottom:8px; } .akakami-review-page .pagination { justify-content:flex-start; } .akakami-review-page .detail-content { padding:12px; } .akakami-review-page .detail-grid { grid-template-columns:1fr 1fr; gap:9px; } .akakami-review-page .detail-item.wide { grid-column:span 2; } }
        @media (max-width:430px) { .akakami-review-page .detail-grid { grid-template-columns:1fr; } .akakami-review-page .detail-item.wide { grid-column:span 1; } .akakami-review-page .decision-actions { grid-template-columns:1fr; } .akakami-review-page th, .akakami-review-page td { padding:8px 7px; font-size:.7rem; } }
    </style>

    <div class="review-header">
        <div>
            <div class="eyebrow">PAR Mobile / Report Approval</div>
            <h1>Prv. <span class="cyan">Akakami</span></h1>
            <div class="muted">Antrean approval Akakami sesuai NIK approver yang sedang login.</div>
        </div>
    </div>

    @if (session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif
    @error('selectedFlpp') <div class="error">{{ $message }}</div> @enderror
    @error('confirmed') <div class="error">{{ $message }}</div> @enderror

    <section class="review-card">
        <div class="review-toolbar">
            <div class="search-box">
                <label for="approvalReviewSearch">Cari FLPP, lot, atau problem</label>
                <input id="approvalReviewSearch" type="search" class="dark-control" wire:model.live.debounce.400ms="search" placeholder="Ketik kata pencarian...">
            </div>
            <button type="button" class="btn btn-ghost" wire:click="refreshQueue" wire:loading.attr="disabled" wire:target="refreshQueue">Refresh Antrean</button>
        </div>

        <div class="review-body">
            @if ($rows->count() > 0)
                <div class="queue-scroll">
                    <table>
                        <thead>
                            <tr><th>No. FLPP</th><th>Tanggal Problem</th><th>Lot</th><th>Qty</th><th>Rank</th><th>Urutan</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="queue-row {{ $selectedFlpp === (string) $row['noflpp'] ? 'selected' : '' }}" wire:key="approval-review-{{ $row['noflpp'] }}">
                                    <td><button type="button" class="flpp-button" wire:click="selectFlpp('{{ $row['noflpp'] }}')"><strong>{{ $row['noflpp'] }}</strong><span>›</span></button></td>
                                    <td>{{ $row['tgl_problem'] ?? '-' }}</td>
                                    <td>{{ $row['no_lots'] ?? '-' }}</td>
                                    <td>{{ $row['qty_problem'] ?? '-' }}</td>
                                    <td>{{ $row['rank'] ?? '-' }}</td>
                                    <td>{{ $row['seq_no'] ?? '-' }}</td>
                                    <td><span class="warning-text">Waiting</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap">
                    <span>Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }} data</span>
                    @if ($rows->hasPages())
                        <nav class="pagination" aria-label="Pagination Prv. Akakami">
                            @if ($rows->onFirstPage())
                                <span class="page-disabled">‹ Sebelumnya</span>
                            @else
                                <button type="button" class="page-link" wire:click="previousPage" wire:loading.attr="disabled">‹ Sebelumnya</button>
                            @endif
                            @php
                                $firstPage = max(1, $rows->currentPage() - 2);
                                $lastPage = min($rows->lastPage(), $rows->currentPage() + 2);
                            @endphp
                            @for ($page = $firstPage; $page <= $lastPage; $page++)
                                @if ($page === $rows->currentPage())
                                    <span class="page-current" aria-current="page">{{ $page }}</span>
                                @else
                                    <button type="button" class="page-link" wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled">{{ $page }}</button>
                                @endif
                            @endfor
                            @if ($rows->currentPage() === $rows->lastPage())
                                <span class="page-disabled">Berikutnya ›</span>
                            @else
                                <button type="button" class="page-link" wire:click="nextPage" wire:loading.attr="disabled">Berikutnya ›</button>
                            @endif
                        </nav>
                    @endif
                </div>
            @else
                <div class="empty-state">
                    <div><strong>Tidak ada antrean approval.</strong><br><span>Data waiting untuk NIK login akan tampil di sini.</span></div>
                </div>
            @endif

            @if ($selectedFlpp !== '')
                <section class="detail-card" aria-labelledby="approvalReviewDetailTitle">
                    <div class="detail-heading">
                        <h2 id="approvalReviewDetailTitle">Review FLPP: {{ $activeDetail['no_flpp'] ?? $selectedFlpp }}</h2>
                        <span class="date">{{ $activeDetail['tgl_problem'] ?? '-' }}</span>
                    </div>
                    <div class="detail-content">
                        <div class="detail-grid">
                            <div class="detail-item"><span class="detail-label">No. Lot</span><span class="detail-value">{{ $activeDetail['no_lots'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Cust Lot</span><span class="detail-value">{{ $activeDetail['no_lot_cust'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Part No</span><span class="detail-value">{{ $activeDetail['no_brg_asli'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Part Name</span><span class="detail-value">{{ $activeDetail['nm_part'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Customer</span><span class="detail-value">{{ $activeDetail['nm_customer'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Quantity</span><span class="detail-value accent">{{ $activeDetail['qty_problem'] ?? '-' }} PCS</span></div>
                            <div class="detail-item"><span class="detail-label">Area / Sequence</span><span class="detail-value">{{ $activeDetail['nm_sequence'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Found By</span><span class="detail-value">{{ $activeDetail['nama_kary'] ?? '-' }}</span></div>
                            <div class="detail-item wide"><span class="detail-label">Problem</span><span class="detail-value problem">{{ $activeDetail['nmproblem'] ?? $activeDetail['deskripsi_problem'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Rank</span><span class="detail-value">{{ $activeDetail['rank'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">PIC Dept</span><span class="detail-value">{{ $activeDetail['pic_dept'] ?? '-' }}</span></div>
                            <div class="detail-item"><span class="detail-label">4M-1E</span><span class="detail-value accent">{{ $activeDetail['fish_bone'] ?? '-' }}</span></div>
                            <div class="detail-item wide"><label class="detail-label" for="reviewAnalisa">Analyze Problem</label><textarea id="reviewAnalisa" class="dark-control" readonly>{{ $activeDetail['analisa'] ?? '' }}</textarea></div>
                            <div class="detail-item"><label class="detail-label" for="reviewTemp">Correction (Temp)</label><textarea id="reviewTemp" class="dark-control" readonly>{{ $activeDetail['temp_action'] ?? '' }}</textarea></div>
                            <div class="detail-item"><label class="detail-label" for="reviewCorrective">Corrective Action</label><textarea id="reviewCorrective" class="dark-control" readonly>{{ $activeDetail['corrective_act'] ?? '' }}</textarea></div>
                        </div>

                        <div class="confirm-box">
                            <label class="confirm-label"><input type="checkbox" wire:model="confirmed"> <span>Saya menyatakan telah memeriksa data ini dengan benar.</span></label>
                            <div class="decision-actions">
                                <button type="button" class="btn btn-cyan" wire:click="process('approve')" wire:confirm="Konfirmasi APPROVE untuk FLPP {{ $selectedFlpp }}? Pastikan data sudah diperiksa." wire:loading.attr="disabled" wire:target="process">✓ APPROVE</button>
                                <button type="button" class="btn btn-danger" wire:click="process('reject')" wire:confirm="Konfirmasi REJECT untuk FLPP {{ $selectedFlpp }}?" wire:loading.attr="disabled" wire:target="process">✕ REJECT</button>
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </section>
</div>
