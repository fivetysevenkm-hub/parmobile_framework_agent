<div class="app-shell abnormal-mtc-page">
    <style>
        .abnormal-mtc-page .abn-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.abnormal-mtc-page .abn-header h1{margin:5px 0 6px}.abnormal-mtc-page .abn-layout{display:grid;grid-template-columns:minmax(260px,320px) minmax(0,1fr);gap:14px}.abnormal-mtc-page .abn-card{background:var(--panel);border:1px solid var(--line);border-radius:10px;overflow:hidden}.abnormal-mtc-page .abn-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 15px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.025)}.abnormal-mtc-page .abn-card-head h2{margin:0;color:var(--cyan);font-size:.9rem}.abnormal-mtc-page .abn-toolbar{padding:12px;border-bottom:1px solid var(--line)}.abnormal-mtc-page .abn-toolbar label{display:block;margin-bottom:6px;color:var(--muted);font-size:.65rem;letter-spacing:.07em;text-transform:uppercase}.abnormal-mtc-page .abn-control{width:100%;padding:9px 10px;border:1px solid var(--line);border-radius:7px;background:#0d1418;color:#f5f7f8;outline:none}.abnormal-mtc-page .abn-control:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,229,255,.1)}.abnormal-mtc-page .abn-queue-scroll{max-height:calc(100vh - 300px);min-height:260px;overflow:auto}.abnormal-mtc-page .abn-table{width:100%;border-collapse:collapse;font-size:.74rem}.abnormal-mtc-page .abn-table th,.abnormal-mtc-page .abn-table td{padding:9px 10px;border-bottom:1px solid #26333a;white-space:nowrap;text-align:left;vertical-align:middle}.abnormal-mtc-page .abn-table th{position:sticky;top:0;z-index:2;background:#0b1013;color:var(--cyan);font-size:.63rem;text-transform:uppercase}.abnormal-mtc-page .abn-row{cursor:pointer}.abnormal-mtc-page .abn-row:hover td{background:#3e251c}.abnormal-mtc-page .abn-row.selected td{background:#a33f10;color:#fff}.abnormal-mtc-page .abn-link{display:block;width:100%;padding:0;border:0;background:transparent;color:inherit;font:inherit;text-align:left;cursor:pointer}.abnormal-mtc-page .abn-link strong{color:var(--orange)}.abnormal-mtc-page .abn-queue-foot{padding:10px 12px;color:var(--muted);font-size:.68rem;text-align:center}.abnormal-mtc-page .abn-queue-empty{padding:45px 16px;color:var(--muted);font-size:.75rem;text-align:center}.abnormal-mtc-page .abn-detail-body{padding:16px}.abnormal-mtc-page .abn-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px}.abnormal-mtc-page .abn-item{min-width:0}.abnormal-mtc-page .abn-item.wide{grid-column:span 2}.abnormal-mtc-page .abn-label{display:block;margin-bottom:5px;color:var(--muted);font-size:.63rem;letter-spacing:.07em;text-transform:uppercase}.abnormal-mtc-page .abn-value{display:block;min-height:32px;padding:7px 9px;border-bottom:1px solid #34464e;color:#f5f7f8;font-size:.78rem;overflow-wrap:anywhere}.abnormal-mtc-page .abn-value.accent{color:var(--orange);font-weight:700}.abnormal-mtc-page .abn-value.problem{color:#ffb4b8}.abnormal-mtc-page .abn-text{min-height:78px;padding:10px;border:1px solid var(--line);border-radius:7px;background:#0d1418;color:#bfeef3;font-size:.78rem;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere}.abnormal-mtc-page .abn-section{margin:16px 0 8px;padding:7px 10px;border-left:4px solid #ff4500;background:#111;color:#ff8c00;font-size:.72rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.abnormal-mtc-page .abn-confirm{margin-top:17px;padding:14px;border:1px solid #5d4c1e;border-radius:8px;background:rgba(255,159,28,.07)}.abnormal-mtc-page .abn-confirm-label{display:flex;align-items:flex-start;gap:9px;color:#ffd98c;font-size:.78rem;cursor:pointer}.abnormal-mtc-page .abn-confirm-label input{margin-top:3px;accent-color:var(--cyan)}.abnormal-mtc-page .abn-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:13px}.abnormal-mtc-page .abn-actions .btn{width:100%;min-height:40px}.abnormal-mtc-page .btn-danger{background:#b42335;color:#fff}.abnormal-mtc-page .flash{margin-bottom:14px;padding:11px 13px;border:1px solid #1e8f6d;border-radius:7px;background:rgba(45,212,191,.12);color:#8ff5df;font-size:.78rem}.abnormal-mtc-page .error-box{margin-bottom:14px;padding:10px 12px;border:1px solid #8b2e3d;border-radius:7px;background:rgba(180,35,53,.13);color:#ffb4b8;font-size:.76rem}.abnormal-mtc-page .pagination{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;color:var(--muted);font-size:.7rem}.abnormal-mtc-page .page-btn{min-width:30px;min-height:29px;padding:4px 8px;border:1px solid var(--line);border-radius:6px;background:#182228;color:#fff;cursor:pointer}.abnormal-mtc-page .page-btn:disabled{cursor:not-allowed;opacity:.35}.abnormal-mtc-page .placeholder{display:grid;min-height:540px;place-items:center;padding:30px;color:var(--muted);text-align:center}.abnormal-mtc-page .placeholder strong{display:block;margin-bottom:7px;color:#d9e0e3;font-size:1rem}.abnormal-mtc-page .note{margin-top:12px;color:var(--muted);font-size:.7rem;line-height:1.45}@media(max-width:900px){.abnormal-mtc-page .abn-layout{grid-template-columns:1fr}.abnormal-mtc-page .abn-queue-scroll{max-height:38vh}.abnormal-mtc-page .placeholder{min-height:240px}.abnormal-mtc-page .abn-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.abnormal-mtc-page{padding:14px 12px 24px}.abnormal-mtc-page .abn-header{display:block}.abnormal-mtc-page .abn-detail-body{padding:12px}.abnormal-mtc-page .abn-grid{grid-template-columns:1fr 1fr;gap:9px}.abnormal-mtc-page .abn-item.wide{grid-column:span 2}.abnormal-mtc-page .abn-actions{grid-template-columns:1fr}.abnormal-mtc-page .pagination{display:block}.abnormal-mtc-page .pagination>span{display:block;margin-bottom:8px}.abnormal-mtc-page .pagination>span:last-child{margin-bottom:0}}@media(max-width:430px){.abnormal-mtc-page .abn-grid{grid-template-columns:1fr}.abnormal-mtc-page .abn-item.wide{grid-column:span 1}}
    </style>

    <div class="abn-header">
        <div>
            <div class="eyebrow">PAR Mobile / Report Approval</div>
            <h1>Approval <span class="cyan">Abnormal Mtc</span></h1>
            <div class="muted">Antrean approval abnormal maintenance berdasarkan NIK approver yang sedang login.</div>
        </div>
    </div>

    @if (session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif
    @error('selectedAbnormal')<div class="error-box">{{ $message }}</div>@enderror
    @error('confirmed')<div class="error-box">{{ $message }}</div>@enderror

    <div class="abn-layout">
        <section class="abn-card">
            <div class="abn-card-head"><h2>! Antrean Abnormal</h2><span class="muted">{{ $totalRows }} data</span></div>
            <div class="abn-toolbar">
                <label for="abnormalSearch">Cari No. ABN, kategori, object, atau lot</label>
                <input id="abnormalSearch" type="search" class="abn-control" wire:model.live.debounce.400ms="search" placeholder="Ketik kata pencarian...">
            </div>
            @if ($rows->count() > 0)
                <div class="abn-queue-scroll">
                    <table class="abn-table">
                        <thead><tr><th>No. ABN</th><th>Category / Object</th><th>Seq</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="abn-row {{ $selectedAbnormal === (string) $row['noabn'] ? 'selected' : '' }}" wire:key="abnormal-{{ $row['noabn'] }}">
                                    <td><button type="button" class="abn-link" wire:click="selectAbnormal(@js((string) $row['noabn']))"><strong>{{ $row['noabn'] }}</strong></button></td>
                                    <td>{{ $row['category'] ?? '-' }}<br><span class="muted">{{ $row['nm_object'] ?? '-' }}</span><br><span class="muted">Lot: {{ $row['ref_lot'] ?? '-' }}</span></td>
                                    <td>{{ $row['seq_no'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <span>{{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }}</span>
                    <span><button type="button" class="page-btn" wire:click="previousPage" @disabled($rows->onFirstPage())>‹</button> <strong>{{ $rows->currentPage() }} / {{ $rows->lastPage() }}</strong> <button type="button" class="page-btn" wire:click="nextPage" @disabled($rows->currentPage() === $rows->lastPage())>›</button></span>
                </div>
            @else
                <div class="abn-queue-empty">Tidak ada antrean approval Abnormal Mtc untuk NIK login saat ini.</div>
            @endif
            <div class="abn-queue-foot"><button type="button" class="btn btn-ghost" wire:click="refreshQueue" wire:loading.attr="disabled">↻ Refresh Antrean</button></div>
        </section>

        <section class="abn-card">
            @if ($selectedAbnormal !== '')
                <div class="abn-card-head"><h2>Review Abnormal: {{ $activeDetail['noabn'] ?? $selectedAbnormal }}</h2><span class="muted">{{ $activeDetail['tgl_problem'] ?? '-' }}</span></div>
                <div class="abn-detail-body">
                    <div class="abn-grid">
                        <div class="abn-item"><span class="abn-label">No. ABN</span><span class="abn-value accent">{{ $activeDetail['noabn'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">Tanggal Problem</span><span class="abn-value">{{ $activeDetail['tgl_problem'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">Founded By</span><span class="abn-value">{{ $activeDetail['nama_kary'] ?? $activeDetail['found_by'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">Category</span><span class="abn-value">{{ $activeDetail['category'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">Object Name</span><span class="abn-value">{{ $activeDetail['nm_object'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">Lot Reference</span><span class="abn-value">{{ $activeDetail['ref_lot'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">Area</span><span class="abn-value">{{ $activeDetail['nm_sequence'] ?? $activeDetail['area'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">PIC Dept</span><span class="abn-value">{{ $activeDetail['pic_dept'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">PIC Handler</span><span class="abn-value">{{ $activeDetail['pic_handler'] ?? '-' }}</span></div>
                        <div class="abn-item"><span class="abn-label">Status</span><span class="abn-value">{{ $activeDetail['status'] ?? '-' }}</span></div>
                    </div>

                    <div class="abn-section">Detail Problem</div>
                    <div class="abn-text" style="color:#ffb4b8">{{ $activeDetail['detail_problem'] ?? '-' }}</div>

                    <div class="abn-grid" style="margin-top:12px">
                        <div class="abn-item wide"><span class="abn-label">Analyze Problem</span><div class="abn-text">{{ $activeDetail['analisa'] ?? '-' }}</div></div>
                        <div class="abn-item"><span class="abn-label">Correction (Temp)</span><div class="abn-text">{{ $activeDetail['correction'] ?? '-' }}</div></div>
                        <div class="abn-item"><span class="abn-label">Corrective Action</span><div class="abn-text">{{ $activeDetail['corrective'] ?? '-' }}</div></div>
                    </div>

                    <div class="abn-confirm">
                        <label class="abn-confirm-label"><input type="checkbox" wire:model.live="confirmed"> <span>Saya menyatakan telah memeriksa data abnormal maintenance ini dengan benar.</span></label>
                        <div class="abn-actions">
                            <button type="button" class="btn btn-cyan" wire:click="process('approve')" wire:confirm="Konfirmasi APPROVE untuk Abnormal {{ $selectedAbnormal }}?" wire:loading.attr="disabled" @disabled(! $confirmed)>✓ APPROVE</button>
                            <button type="button" class="btn btn-danger" wire:click="process('reject')" wire:confirm="Konfirmasi REJECT untuk Abnormal {{ $selectedAbnormal }}?" wire:loading.attr="disabled" @disabled(! $confirmed)>✕ REJECT</button>
                        </div>
                    </div>
                    <div class="note">Approval dilakukan sesuai urutan sequence. Approver berikutnya baru aktif setelah approver sebelumnya menyelesaikan proses.</div>
                </div>
            @else
                <div class="placeholder"><div><strong>Menunggu Peninjauan</strong><span>Pilih salah satu antrean Abnormal Mtc untuk menampilkan detail review.</span></div></div>
            @endif
        </section>
    </div>
</div>
