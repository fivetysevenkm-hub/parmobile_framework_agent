<div class="app-shell rank-a-page">
    <style>
        .rank-a-page .rank-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.rank-a-page .rank-header h1{margin:5px 0 6px}.rank-a-page .rank-layout{display:grid;grid-template-columns:minmax(260px,320px) minmax(0,1fr);gap:14px}.rank-a-page .rank-card{background:var(--panel);border:1px solid var(--line);border-radius:10px;overflow:hidden}.rank-a-page .rank-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 15px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.025)}.rank-a-page .rank-card-head h2{margin:0;color:var(--cyan);font-size:.9rem}.rank-a-page .rank-toolbar{padding:12px;border-bottom:1px solid var(--line)}.rank-a-page .rank-toolbar label{display:block;margin-bottom:6px;color:var(--muted);font-size:.65rem;letter-spacing:.07em;text-transform:uppercase}.rank-a-page .rank-control{width:100%;padding:9px 10px;border:1px solid var(--line);border-radius:7px;background:#0d1418;color:#f5f7f8;outline:none}.rank-a-page .rank-control:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,229,255,.1)}.rank-a-page .queue-scroll{max-height:calc(100vh - 300px);min-height:260px;overflow:auto}.rank-a-page .queue-table{width:100%;border-collapse:collapse;font-size:.74rem}.rank-a-page .queue-table th,.rank-a-page .queue-table td{padding:9px 10px;border-bottom:1px solid #26333a;white-space:nowrap;text-align:left;vertical-align:middle}.rank-a-page .queue-table th{position:sticky;top:0;z-index:2;background:#0b1013;color:var(--cyan);font-size:.63rem;text-transform:uppercase}.rank-a-page .queue-row{cursor:pointer}.rank-a-page .queue-row:hover td{background:#1c3540}.rank-a-page .queue-row.selected td{background:#0754bd;color:#fff}.rank-a-page .rank-link{display:block;width:100%;padding:0;border:0;background:transparent;color:inherit;font:inherit;text-align:left;cursor:pointer}.rank-a-page .rank-link strong{color:var(--orange)}.rank-a-page .queue-foot{padding:10px 12px;color:var(--muted);font-size:.68rem;text-align:center}.rank-a-page .queue-empty{padding:45px 16px;color:var(--muted);font-size:.75rem;text-align:center}.rank-a-page .detail-body{padding:16px}.rank-a-page .detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px}.rank-a-page .detail-item{min-width:0}.rank-a-page .detail-item.wide{grid-column:span 2}.rank-a-page .detail-label{display:block;margin-bottom:5px;color:var(--muted);font-size:.63rem;letter-spacing:.07em;text-transform:uppercase}.rank-a-page .detail-value{display:block;min-height:32px;padding:7px 9px;border-bottom:1px solid #34464e;color:#f5f7f8;font-size:.78rem;overflow-wrap:anywhere}.rank-a-page .detail-value.accent{color:var(--orange);font-weight:700}.rank-a-page .detail-value.problem{color:#ffb4b8}.rank-a-page .section-title{margin:16px 0 8px;padding:7px 10px;border-left:4px solid #28a745;background:#111;color:#2dd4bf;font-size:.72rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.rank-a-page .instruction{min-height:130px;padding:11px;border:1px solid var(--line);border-radius:7px;background:#0d1418;color:#bfeef3;font-size:.78rem;line-height:1.5;white-space:pre-wrap}.rank-a-page .image-box{display:flex;min-height:220px;align-items:center;justify-content:center;padding:10px;border:1px solid var(--line);border-radius:7px;background:#050708}.rank-a-page .image-box img{width:100%;max-height:300px;object-fit:contain}.rank-a-page .image-empty{color:var(--muted);font-size:.75rem;text-align:center}.rank-a-page .confirm-box{margin-top:17px;padding:14px;border:1px solid #5d4c1e;border-radius:8px;background:rgba(255,159,28,.07)}.rank-a-page .confirm-label{display:flex;align-items:flex-start;gap:9px;color:#ffd98c;font-size:.78rem;cursor:pointer}.rank-a-page .confirm-label input{margin-top:3px;accent-color:var(--cyan)}.rank-a-page .decision-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:13px}.rank-a-page .decision-actions .btn{width:100%;min-height:40px}.rank-a-page .btn-danger{background:#b42335;color:#fff}.rank-a-page .flash{margin-bottom:14px;padding:11px 13px;border:1px solid #1e8f6d;border-radius:7px;background:rgba(45,212,191,.12);color:#8ff5df;font-size:.78rem}.rank-a-page .error-box{margin-bottom:14px;padding:10px 12px;border:1px solid #8b2e3d;border-radius:7px;background:rgba(180,35,53,.13);color:#ffb4b8;font-size:.76rem}.rank-a-page .pagination{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;color:var(--muted);font-size:.7rem}.rank-a-page .page-btn{min-width:30px;min-height:29px;padding:4px 8px;border:1px solid var(--line);border-radius:6px;background:#182228;color:#fff;cursor:pointer}.rank-a-page .page-btn:disabled{cursor:not-allowed;opacity:.35}.rank-a-page .placeholder{display:grid;min-height:540px;place-items:center;padding:30px;color:var(--muted);text-align:center}.rank-a-page .placeholder strong{display:block;margin-bottom:7px;color:#d9e0e3;font-size:1rem}.rank-a-page .note{margin-top:12px;color:var(--muted);font-size:.7rem;line-height:1.45}@media(max-width:900px){.rank-a-page .rank-layout{grid-template-columns:1fr}.rank-a-page .queue-scroll{max-height:38vh}.rank-a-page .placeholder{min-height:240px}.rank-a-page .detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.rank-a-page{padding:14px 12px 24px}.rank-a-page .rank-header{display:block}.rank-a-page .detail-body{padding:12px}.rank-a-page .detail-grid{grid-template-columns:1fr;gap:9px}.rank-a-page .detail-item.wide{grid-column:span 1}.rank-a-page .decision-actions{grid-template-columns:1fr}.rank-a-page .pagination{display:block}.rank-a-page .pagination>span{display:block;margin-bottom:8px}.rank-a-page .pagination>span:last-child{margin-bottom:0}}
    </style>

    <div class="rank-header">
        <div>
            <div class="eyebrow">PAR Mobile / Report Approval</div>
            <h1>Aprv. <span class="cyan">Rank A</span></h1>
            <div class="muted">Antrean approval Rank A berdasarkan NIK approver yang sedang login.</div>
        </div>
    </div>

    @if (session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif
    @error('selectedRank')<div class="error-box">{{ $message }}</div>@enderror
    @error('confirmed')<div class="error-box">{{ $message }}</div>@enderror

    <div class="rank-layout">
        <section class="rank-card">
            <div class="rank-card-head"><h2>★ Antrean Rank A</h2><span class="muted">{{ $totalRows }} data</span></div>
            <div class="rank-toolbar">
                <label for="rankSearch">Cari Rank, FLPP, lot, atau problem</label>
                <input id="rankSearch" type="search" class="rank-control" wire:model.live.debounce.400ms="search" placeholder="Ketik kata pencarian...">
            </div>
            @if ($rows->count() > 0)
                <div class="queue-scroll">
                    <table class="queue-table">
                        <thead><tr><th>No. Rank</th><th>FLPP / Lot</th><th>Seq</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="queue-row {{ $selectedRank === (string) $row['no_rank'] ? 'selected' : '' }}" wire:key="rank-a-{{ $row['no_rank'] }}">
                                    <td><button type="button" class="rank-link" wire:click="selectRank(@js((string) $row['no_rank']))"><strong>{{ $row['no_rank'] }}</strong></button></td>
                                    <td>{{ $row['noflpp'] ?? '-' }}<br><span class="muted">{{ $row['no_lots'] ?? '-' }}</span></td>
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
                <div class="queue-empty">Tidak ada antrean approval Rank A untuk NIK login saat ini.</div>
            @endif
            <div class="queue-foot"><button type="button" class="btn btn-ghost" wire:click="refreshQueue" wire:loading.attr="disabled">↻ Refresh Antrean</button></div>
        </section>

        <section class="rank-card">
            @if ($selectedRank !== '')
                <div class="rank-card-head"><h2>Review Rank A: {{ $activeDetail['no_rank'] ?? $selectedRank }}</h2><span class="muted">{{ $activeDetail['tgl_pengajuan'] ?? '-' }}</span></div>
                <div class="detail-body">
                    <div class="detail-grid">
                        <div class="detail-item"><span class="detail-label">No. Rank</span><span class="detail-value accent">{{ $activeDetail['no_rank'] ?? '-' }}</span></div>
                        <div class="detail-item"><span class="detail-label">No. FLPP</span><span class="detail-value">{{ $activeDetail['noflpp'] ?? '-' }}</span></div>
                        <div class="detail-item"><span class="detail-label">Tanggal Pengajuan</span><span class="detail-value">{{ $activeDetail['tgl_pengajuan'] ?? '-' }}</span></div>
                        <div class="detail-item"><span class="detail-label">Customer</span><span class="detail-value">{{ $activeDetail['nm_customer'] ?? '-' }}</span></div>
                        <div class="detail-item"><span class="detail-label">No. Lot DTI</span><span class="detail-value">{{ $activeDetail['no_lots'] ?? '-' }}</span></div>
                        <div class="detail-item"><span class="detail-label">No. Part</span><span class="detail-value">{{ $activeDetail['no_brg_asli'] ?? '-' }}</span></div>
                        <div class="detail-item"><span class="detail-label">Nama Part</span><span class="detail-value">{{ $activeDetail['nm_part'] ?? '-' }}</span></div>
                        <div class="detail-item"><span class="detail-label">Qty Problem</span><span class="detail-value accent">{{ $activeDetail['qty_problem'] ?? '-' }} PCS</span></div>
                    </div>

                    <div class="section-title">Detail Problem</div>
                    <div class="instruction" style="min-height:45px;color:#ffb4b8">{{ $activeDetail['deskripsi_problem'] ?? '-' }}</div>

                    <div class="detail-grid" style="margin-top:12px">
                        <div class="detail-item wide"><span class="detail-label">Instruksi Perbaikan</span><div class="instruction">{{ $activeDetail['instruksi_perbaikan'] ?? '-' }}</div></div>
                        <div class="detail-item"><span class="detail-label">Ilustrasi Perbaikan</span><div class="image-box">@if (!empty($activeDetail['link_ilustrasi']))<img src="{{ route('rank.a.image', ['noRank' => $selectedRank]) }}" alt="Ilustrasi perbaikan Rank A">@else<span class="image-empty">Tidak ada ilustrasi.</span>@endif</div></div>
                    </div>

                    <div class="confirm-box">
                        <label class="confirm-label"><input type="checkbox" wire:model.live="confirmed"> <span>Saya menyatakan telah memeriksa data Instruksi Perbaikan ini dengan benar.</span></label>
                        <div class="decision-actions">
                            <button type="button" class="btn btn-cyan" wire:click="process('approve')" wire:confirm="Konfirmasi APPROVE untuk Rank A {{ $selectedRank }}?" wire:loading.attr="disabled" @disabled(! $confirmed)>✓ APPROVE</button>
                            <button type="button" class="btn btn-danger" wire:click="process('reject')" wire:confirm="Konfirmasi REJECT untuk Rank A {{ $selectedRank }}?" wire:loading.attr="disabled" @disabled(! $confirmed)>✕ REJECT</button>
                        </div>
                    </div>
                    <div class="note">Approval dilakukan sesuai urutan sequence. Approver berikutnya baru aktif setelah approver sebelumnya menyelesaikan proses.</div>
                </div>
            @else
                <div class="placeholder"><div><strong>Menunggu Peninjauan</strong><span>Pilih salah satu antrean Rank A untuk menampilkan detail review.</span></div></div>
            @endif
        </section>
    </div>
</div>
