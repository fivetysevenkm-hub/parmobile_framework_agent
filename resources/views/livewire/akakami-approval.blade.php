<div class="app-shell akakami-page">
    <style>
        .akakami-page .approval-card { background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
        .akakami-page .approval-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid var(--line); background:rgba(255,255,255,.04); }
        .akakami-page .approval-header h2 { margin:0; font-size:1rem; letter-spacing:.04em; }
        .akakami-page .approval-tabs { display:flex; gap:6px; padding:14px 14px 0; overflow-x:auto; border-bottom:1px solid var(--line); }
        .akakami-page .approval-tab { flex:0 0 auto; padding:10px 13px; border:0; border-bottom:2px solid transparent; background:transparent; color:var(--muted); font-size:.75rem; cursor:pointer; white-space:nowrap; }
        .akakami-page .approval-tab:hover, .akakami-page .approval-tab.active { color:var(--cyan); border-bottom-color:var(--cyan); }
        .akakami-page .approval-body { padding:18px; }
        .akakami-page .grid-tools { display:flex; align-items:end; justify-content:space-between; gap:10px; margin-bottom:14px; }
        .akakami-page .grid-tools label, .akakami-page .form-label { display:block; color:var(--muted); font-size:.68rem; letter-spacing:.07em; margin-bottom:6px; text-transform:uppercase; }
        .akakami-page .dark-control { width:100%; background:#0d1418; border:1px solid var(--line); border-radius:7px; color:#f5f7f8; padding:10px 11px; outline:none; }
        .akakami-page .dark-control:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .akakami-page textarea.dark-control { min-height:82px; resize:vertical; }
        .akakami-page select.dark-control { color-scheme:dark; }
        .akakami-page .table-scroll { max-height:520px; border:1px solid var(--line); border-radius:8px; overflow:auto; -webkit-overflow-scrolling:touch; }
        .akakami-page .pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:12px; color:var(--muted); font-size:.72rem; }
        .akakami-page .results-summary { flex:1 1 auto; }
        .akakami-page .pagination { display:flex; flex:0 0 auto; flex-wrap:wrap; justify-content:flex-end; gap:5px; align-items:center; list-style:none; padding:0; margin:0; }
        .akakami-page .pagination .page-link, .akakami-page .pagination .page-current, .akakami-page .pagination .page-disabled { display:grid; min-width:32px; min-height:30px; place-items:center; padding:4px 8px; border:1px solid var(--line); border-radius:6px; color:#d9e0e3; font-size:.72rem; line-height:1; }
        .akakami-page .pagination .page-link { background:#182228; cursor:pointer; }
        .akakami-page .pagination .page-link:hover { border-color:var(--cyan); color:var(--cyan); }
        .akakami-page .pagination .page-current { background:var(--cyan); color:#041014; border-color:var(--cyan); font-weight:700; }
        .akakami-page .pagination .page-disabled { opacity:.4; }
        .akakami-page .action-cell { width:104px; min-width:104px; text-align:center; vertical-align:middle; }
        .akakami-page .action-cell .btn { display:inline-flex; width:82px; min-height:32px; align-items:center; justify-content:center; padding:7px 8px; white-space:nowrap; font-size:.7rem; line-height:1; }
        .akakami-page .action-cell .akakami-print-btn { width:108px; }
        .akakami-page .print-link { color:#8ff5df; text-decoration:none; } .akakami-page .print-link:hover { color:#fff; }
        .akakami-page .export-backdrop { position:fixed; inset:0; z-index:100; display:grid; place-items:center; padding:18px; background:rgba(0,0,0,.72); }
        .akakami-page .export-modal { width:min(100%,430px); padding:20px; border:1px solid var(--line); border-radius:12px; background:#111a1f; box-shadow:0 20px 70px rgba(0,0,0,.5); }
        .akakami-page .export-modal h3 { margin:0 0 6px; color:var(--cyan); font-size:1rem; } .akakami-page .export-modal p { margin:0 0 16px; color:var(--muted); font-size:.76rem; } .akakami-page .export-period-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; } .akakami-page .export-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:18px; }
        .akakami-page table { min-width:850px; }
        .akakami-page th, .akakami-page td { padding:11px 10px; }
        .akakami-page td { color:#d9e0e3; }
        .akakami-page .empty-state { padding:36px 20px; color:var(--muted); text-align:center; }
        .akakami-page .detail-banner { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:10px; padding:14px; margin-bottom:18px; border-radius:8px; background:linear-gradient(100deg,#0754bd,#00a6bf); }
        .akakami-page .detail-banner span { display:block; font-size:.62rem; opacity:.8; text-transform:uppercase; }
        .akakami-page .detail-banner strong { display:block; margin-top:4px; font-size:.9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .akakami-page .form-columns { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; }
        .akakami-page .form-section { padding:15px; border:1px solid var(--line); border-radius:8px; background:#111a1f; }
        .akakami-page .form-section h3 { margin:0 0 14px; color:var(--cyan); font-size:.78rem; letter-spacing:.08em; text-transform:uppercase; }
        .akakami-page .field { margin-bottom:13px; }
        .akakami-page .radio-line { display:flex; gap:10px; align-items:flex-start; padding:10px 0; color:#f5f7f8; font-size:.78rem; }
        .akakami-page .radio-line input, .akakami-page .rank-check input { accent-color:var(--cyan); margin-top:3px; }
        .akakami-page .radio-line small { display:block; color:var(--muted); margin-top:3px; }
        .akakami-page .sequence-box { margin-top:14px; padding:13px; border:1px solid var(--line); border-radius:8px; }
        .akakami-page .sequence-box.disabled { opacity:.45; pointer-events:none; }
        .akakami-page .sequence-grid { display:grid; grid-template-columns:minmax(0,1fr) 44px minmax(0,1fr); align-items:center; gap:9px; }
        .akakami-page .sequence-grid select { min-height:160px; }
        .akakami-page .move-buttons { display:grid; gap:8px; }
        .akakami-page .progress { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:14px; padding:12px; border:1px solid var(--line); border-radius:8px; color:var(--muted); font-size:.74rem; }
        .akakami-page .progress-step { color:#f5f7f8; }
        .akakami-page .progress-step.approved { color:#2dd4bf; }
        .akakami-page .progress-arrow { color:#65747b; }
        .akakami-page .action-footer { display:flex; justify-content:flex-end; gap:9px; margin-top:18px; padding-top:16px; border-top:1px solid var(--line); }
        .akakami-page .flash { margin-bottom:14px; padding:11px 13px; border:1px solid #1e8f6d; border-radius:7px; background:rgba(45,212,191,.12); color:#8ff5df; font-size:.78rem; }
        .akakami-page .danger-text { color:var(--red); } .akakami-page .warning-text { color:var(--orange); } .akakami-page .success-text { color:#2dd4bf; }
        @media (max-width:720px) { .akakami-page { padding:12px; } .akakami-page .pagination-wrap { display:block; } .akakami-page .results-summary { display:block; margin-bottom:8px; } .akakami-page .pagination { justify-content:flex-start; } .akakami-page .pagination .page-link, .akakami-page .pagination .page-current, .akakami-page .pagination .page-disabled { min-width:30px; } .akakami-page .action-cell { width:92px; min-width:92px; } .akakami-page .action-cell .btn { width:76px; } .akakami-page .action-cell .akakami-print-btn { width:108px; } .akakami-page .export-period-grid { grid-template-columns:1fr; } .akakami-page .export-actions { display:grid; grid-template-columns:1fr 1fr; } .akakami-page .export-actions .btn { width:100%; } .akakami-page .approval-header { align-items:flex-start; padding:14px; } .akakami-page .approval-header h2 { font-size:.82rem; } .akakami-page .approval-body { padding:12px; } .akakami-page .grid-tools { display:block; } .akakami-page .grid-tools > div:first-child { margin-bottom:10px; } .akakami-page .grid-tools .btn { width:100%; } .akakami-page .detail-banner { grid-template-columns:repeat(2,minmax(0,1fr)); } .akakami-page .form-columns { grid-template-columns:1fr; } .akakami-page .sequence-grid { grid-template-columns:1fr; } .akakami-page .move-buttons { grid-template-columns:1fr 1fr; } .akakami-page .move-buttons .btn:first-child { order:0; } .akakami-page .move-buttons .btn:last-child { order:1; } .akakami-page .action-footer { display:grid; grid-template-columns:1fr 1fr; } .akakami-page .action-footer .btn { width:100%; } }
        @media (max-width:430px) { .akakami-page .approval-tabs { padding-left:8px; padding-right:8px; } .akakami-page .approval-tab { padding:9px 10px; font-size:.67rem; } .akakami-page .detail-banner { padding:11px; } .akakami-page .detail-banner strong { font-size:.78rem; } .akakami-page th, .akakami-page td { padding:8px 7px; font-size:.7rem; } }
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / Quality Control</div>
            <h1>Proses <span class="cyan">Akakami QAQC</span></h1>
            <div class="muted">Kelola pengajuan, approval, eskalasi, dan release FLPP</div>
        </div>
        <div class="toolbar">
            <button class="btn btn-ghost" type="button" wire:click="refreshGrid" wire:loading.attr="disabled" wire:target="refreshGrid">Refresh Data</button>
        </div>
    </div>

    @if (session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif
    @error('grid') <div class="error">{{ $message }}</div> @enderror

    <section class="approval-card">
        <div class="approval-header">
            <h2>PROSES AKAKAMI QAQC</h2>
            <div class="muted" style="font-family:'JetBrains Mono',monospace;font-size:.68rem">{{ $totalRows }} data</div>
        </div>
        <div class="approval-tabs" role="tablist">
            @foreach ([
                'pengajuan' => 'Pengajuan',
                'action' => 'Action',
                'eskalasi' => 'Eskalasi',
                'release' => 'Belum Release',
                'approved' => 'Approved',
                'rejected' => 'Ditolak',
            ] as $tab => $label)
                <button type="button" class="approval-tab {{ $activeTab === $tab ? 'active' : '' }}" wire:click="selectTab('{{ $tab }}')">{{ $label }}</button>
            @endforeach
        </div>

        <div class="approval-body">
            @if ($activeTab !== 'action')
                <div class="grid-tools">
                    <div style="flex:1;max-width:420px">
                        <label for="approvalSearch">Cari FLPP, lot, atau problem</label>
                        <input id="approvalSearch" class="dark-control" type="search" wire:model.live.debounce.400ms="search" placeholder="Ketik kata pencarian...">
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end"><button class="btn btn-ghost" type="button" wire:click="refreshGrid" wire:loading.attr="disabled" wire:target="refreshGrid">Muat ulang</button><button class="btn btn-cyan" type="button" wire:click="openExportModal">Export Excel</button></div>
                </div>
            @endif

            @if ($activeTab === 'action')
                @if ($selectedNoflpp !== '')
                    <div class="detail-banner">
                        <div><span>No FLPP</span><strong>{{ $activeDetail['no_flpp'] ?? $selectedNoflpp }}</strong></div>
                        <div><span>Lot</span><strong>{{ $activeDetail['no_lots'] ?? '-' }}</strong></div>
                        <div><span>Customer</span><strong>{{ $activeDetail['nm_customer'] ?? 'INTERNAL' }}</strong></div>
                        <div><span>Pelapor</span><strong>{{ $activeDetail['nama_kary'] ?? 'INTERNAL' }}</strong></div>
                    </div>

                    <div class="form-columns">
                        <div class="form-section">
                            <h3>Detail masalah</h3>
                            <div class="field"><label class="form-label">PIC Dept Pelaksana</label><select class="dark-control" wire:model="picDept"><option value="">-- Pilih Dept --</option>@foreach ($departments as $department)<option value="{{ $department['Id_Dept'] }}">{{ $department['Dept'] }}</option>@endforeach</select></div>
                            <div class="field"><label class="form-label">Tindakan</label><select class="dark-control" wire:model="idProblem"><option value="">-- Pilih Problem --</option>@foreach ($problems as $problem)<option value="{{ $problem['idrankproblem'] }}">{{ $problem['nmrankproblem'] }}</option>@endforeach</select></div>
                            <div class="field"><label class="form-label">Nama Part / Item</label><input class="dark-control" value="{{ $activeDetail['nm_part'] ?? $activeDetail['no_brg_asli'] ?? '' }}" readonly></div>
                            <div class="field"><label class="form-label">Qty Problem</label><input class="dark-control" type="number" wire:model="qty"></div>
                            <div class="field"><label class="form-label">Jenis Problem</label><textarea class="dark-control" readonly>{{ $activeDetail['nmproblem'] ?? '' }}</textarea></div>
                            <div class="field"><label class="form-label">Deskripsi Detail <span class="danger-text">*</span></label><textarea class="dark-control" wire:model="masalah" placeholder="Detail masalah yang ditemukan..."></textarea>@error('masalah')<div class="error">{{ $message }}</div>@enderror</div>
                            <div class="field"><label class="form-label">Fishbone Category</label><div style="display:flex;flex-wrap:wrap;gap:12px;color:#d9e0e3;font-size:.76rem">@foreach (['Man','Machine','Material','Method'] as $category)<label><input type="radio" wire:model="fishbone" value="{{ $category }}"> {{ $category }}</label>@endforeach</div></div>
                            <div class="field"><label class="form-label">Analisa Penyebab <span class="danger-text">*</span></label><textarea class="dark-control" wire:model="analisa" placeholder="Detail analisa penyebab..."></textarea>@error('analisa')<div class="error">{{ $message }}</div>@enderror</div>
                            <div class="field"><label class="form-label">Tindakan Sementara</label><textarea class="dark-control" wire:model="tempAction" placeholder="Apa yang dilakukan saat kejadian?"></textarea></div>
                            <div class="field" style="margin-bottom:0"><label class="form-label">Tindakan Pencegahan</label><textarea class="dark-control" wire:model="correctiveAction" placeholder="Langkah agar tidak terulang kembali..."></textarea></div>
                        </div>

                        <div class="form-section">
                            <h3>Judgment approval</h3>
                            <label class="radio-line"><input type="radio" wire:model.live="judgmentType" value="ok"><span><strong class="success-text">JUDGMENT OK</strong><small>Lanjut proses atau close.</small></span></label>
                            <label class="radio-line"><input type="radio" wire:model.live="judgmentType" value="eskalasi"><span><strong class="warning-text">ESKALASI</strong><small>Butuh approval atasan.</small></span></label>

                            <div class="sequence-box {{ $judgmentType === 'eskalasi' ? '' : 'disabled' }}">
                                <div style="color:var(--cyan);font-size:.75rem;font-weight:700;margin-bottom:10px">APPROVAL SEQUENCE</div>
                                <div class="sequence-grid">
                                    <div><label class="form-label">Master Approver</label><select multiple class="dark-control" wire:model="masterSelected" @disabled($judgmentType !== 'eskalasi')>@foreach ($masterApprovers as $approver)@if (!in_array((string) $approver['nik'], array_map('strval', $judgmentApprovers), true))<option value="{{ $approver['nik'] }}">{{ $approver['user'] }}</option>@endif @endforeach</select></div>
                                    <div class="move-buttons"><button type="button" class="btn btn-ghost" wire:click="moveApproversToJudgment" @disabled($judgmentType !== 'eskalasi')>→</button><button type="button" class="btn btn-ghost" wire:click="moveApproversBack" @disabled($judgmentType !== 'eskalasi')>←</button></div>
                                    <div><label class="form-label">Judgment by</label><select multiple class="dark-control" wire:model="judgmentSelected" @disabled($judgmentType !== 'eskalasi')>@foreach ($masterApprovers as $approver)@if (in_array((string) $approver['nik'], array_map('strval', $judgmentApprovers), true))<option value="{{ $approver['nik'] }}">{{ $approver['user'] }}</option>@endif @endforeach</select></div>
                                </div>
                                @error('judgmentApprovers')<div class="error">{{ $message }}</div>@enderror
                            </div>

                            <label class="rank-check" style="display:block;margin-top:16px;padding:12px;border:1px solid var(--red);border-radius:7px;color:var(--red);font-size:.76rem;font-weight:700"><input type="checkbox" wire:model="rankA"> TERBITKAN RANK A</label>
                        </div>
                    </div>

                    <div class="action-footer"><button type="button" class="btn btn-ghost" wire:click="selectTab('pengajuan')">Batal</button><button type="button" class="btn btn-cyan" wire:click="commitFlpp" wire:confirm="Apakah Anda yakin ingin memproses data FLPP ini?" wire:loading.attr="disabled" wire:target="commitFlpp">COMMIT FLPP</button></div>
                @else
                    <div class="empty-state">Pilih data dari tab Pengajuan atau Ditolak untuk membuka form action.</div>
                @endif
            @elseif ($rows->count() > 0)
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>No FLPP</th><th>Tanggal Problem</th><th>Masalah</th><th>Lot No</th><th>Qty</th><th>Status</th><th class="action-cell">Aksi</th>@if ($activeTab === 'approved')<th class="action-cell">Cetak Akakami</th>@endif</tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row['noflpp'] ?? '-' }}</td><td>{{ $row['tgl_problem'] ?? '-' }}</td><td>{{ $row['nmproblem'] ?? $row['deskripsi_problem'] ?? '-' }}</td><td>{{ $row['no_lots'] ?? '-' }}</td><td>{{ $row['qty_problem'] ?? '-' }}</td><td>{{ $row['status_flpp'] ?? '-' }}</td>
                                    <td class="action-cell">@if ($activeTab === 'pengajuan' || $activeTab === 'rejected')<button class="btn btn-ghost" type="button" wire:click="openAction('{{ $row['noflpp'] }}')">{{ $activeTab === 'rejected' ? 'Revisi' : 'Proses' }}</button>@elseif ($activeTab === 'release')<button class="btn btn-cyan" type="button" wire:click="alirkanRelease('{{ $row['noflpp'] }}')" wire:confirm="Alirkan dan approve FLPP ini?">Alirkan</button>@elseif ($activeTab === 'eskalasi')<button class="btn btn-ghost" type="button" wire:click="loadProgress('{{ $row['noflpp'] }}')">Progress</button>@else<span class="muted">Selesai</span>@endif</td>
                                    @if ($activeTab === 'approved')<td class="action-cell"><a class="btn btn-cyan akakami-print-btn" href="{{ route('akakami.print', ['noflpp' => $row['noflpp']]) }}" target="_blank" rel="noopener">Cetak Akakami</a></td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap">
                    <span class="results-summary">Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }} data</span>
                    @if ($rows->hasPages())
                        <nav class="pagination" aria-label="Pagination Akakami QAQC">
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
                @if ($activeTab === 'eskalasi' && $approvalProgress !== [])
                    <div class="progress"><strong>Flow Approval:</strong>@foreach ($approvalProgress as $index => $step)<span class="progress-step {{ $step['approve_status'] === 'Approved' ? 'approved' : '' }}">{{ $step['nama'] ?? 'Approver' }} ({{ $step['approve_status'] }})</span>@if ($index < count($approvalProgress) - 1)<span class="progress-arrow">→</span>@endif @endforeach</div>
                @endif
            @else
                <div class="empty-state">Tidak ada data pada tab {{ ucfirst($activeTab) }}.</div>
            @endif
        </div>
    </section>

    @if ($showExportModal)
        <div class="export-backdrop" wire:click.self="closeExportModal">
            <div class="export-modal" role="dialog" aria-modal="true" aria-labelledby="akakamiExportTitle">
                <h3 id="akakamiExportTitle">Export Excel Akakami QAQC</h3>
                <p>Pilih periode data dari <strong>vw_exportexcel_akakami</strong> sebelum mengunduh laporan.</p>
                <form method="GET" action="{{ route('akakami.export') }}" target="_blank">
                    <div class="export-period-grid">
                        <div><label class="form-label" for="exportYear">Tahun</label><select id="exportYear" name="year" class="dark-control" wire:model.live="exportYear">@foreach ($exportYears as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach</select></div>
                        <div><label class="form-label" for="exportMonth">Bulan</label><select id="exportMonth" name="month" class="dark-control" wire:model="exportMonth">@foreach (($exportMonthsByYear[$exportYear] ?? []) as $month)<option value="{{ $month }}">{{ [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'][$month] ?? $month }}</option>@endforeach</select></div>
                    </div>
                    <div class="export-actions"><button type="button" class="btn btn-ghost" wire:click="closeExportModal">Batal</button><button type="submit" class="btn btn-cyan">Download Excel</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
