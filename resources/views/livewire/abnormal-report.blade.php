<div class="abnormal-report-page">
    <style>
        .abnormal-report-page { padding:18px; }
        .abnormal-report-page .report-card { background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
        .abnormal-report-page .report-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid var(--line); background:rgba(255,255,255,.04); }
        .abnormal-report-page .report-header h2 { margin:0; font-size:1rem; letter-spacing:.04em; }
        .abnormal-report-page .tabs { display:flex; gap:6px; padding:14px 14px 0; overflow-x:auto; border-bottom:1px solid var(--line); }
        .abnormal-report-page .tab { flex:0 0 auto; padding:10px 13px; border:0; border-bottom:2px solid transparent; background:transparent; color:var(--muted); font-size:.75rem; cursor:pointer; white-space:nowrap; }
        .abnormal-report-page .tab:hover, .abnormal-report-page .tab.active { color:var(--cyan); border-bottom-color:var(--cyan); }
        .abnormal-report-page .body { padding:18px; }
        .abnormal-report-page .tools { display:flex; align-items:end; justify-content:space-between; gap:10px; margin-bottom:14px; }
        .abnormal-report-page label { display:block; color:var(--muted); font-size:.68rem; letter-spacing:.07em; margin-bottom:6px; text-transform:uppercase; }
        .abnormal-report-page .control { width:100%; background:#0d1418; border:1px solid var(--line); border-radius:7px; color:#f5f7f8; padding:10px 11px; outline:none; }
        .abnormal-report-page .control:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .abnormal-report-page textarea.control { min-height:82px; resize:vertical; }
        .abnormal-report-page select.control { color-scheme:dark; }
        .abnormal-report-page .date-tools { display:grid; grid-template-columns:repeat(2,minmax(0,160px)); gap:8px; }
        .abnormal-report-page .table-scroll { max-height:540px; border:1px solid var(--line); border-radius:8px; overflow:auto; -webkit-overflow-scrolling:touch; }
        .abnormal-report-page table { min-width:1050px; }
        .abnormal-report-page th, .abnormal-report-page td { padding:11px 10px; }
        .abnormal-report-page td { color:#d9e0e3; vertical-align:top; }
        .abnormal-report-page .action-cell { width:110px; min-width:110px; text-align:center; vertical-align:middle; }
        .abnormal-report-page .action-cell .btn { width:88px; min-height:32px; padding:7px 8px; font-size:.7rem; }
        .abnormal-report-page .pagination-wrap { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:12px; color:var(--muted); font-size:.72rem; }
        .abnormal-report-page .pagination { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:5px; align-items:center; list-style:none; padding:0; margin:0; }
        .abnormal-report-page .page-link, .abnormal-report-page .page-current, .abnormal-report-page .page-disabled { display:grid; min-width:32px; min-height:30px; place-items:center; padding:4px 8px; border:1px solid var(--line); border-radius:6px; color:#d9e0e3; font-size:.72rem; line-height:1; }
        .abnormal-report-page .page-link { background:#182228; cursor:pointer; }
        .abnormal-report-page .page-link:hover { border-color:var(--cyan); color:var(--cyan); }
        .abnormal-report-page .page-current { background:var(--cyan); color:#041014; border-color:var(--cyan); font-weight:700; }
        .abnormal-report-page .page-disabled { opacity:.4; }
        .abnormal-report-page .empty { padding:36px 20px; color:var(--muted); text-align:center; }
        .abnormal-report-page .banner { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; padding:14px; margin-bottom:18px; border-radius:8px; background:linear-gradient(100deg,#0754bd,#00a6bf); }
        .abnormal-report-page .banner span { display:block; font-size:.62rem; opacity:.8; text-transform:uppercase; }
        .abnormal-report-page .banner strong { display:block; margin-top:4px; font-size:.9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .abnormal-report-page .columns { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .abnormal-report-page .section { padding:15px; border:1px solid var(--line); border-radius:8px; background:#111a1f; }
        .abnormal-report-page .section h3 { margin:0 0 14px; color:var(--cyan); font-size:.78rem; letter-spacing:.08em; text-transform:uppercase; }
        .abnormal-report-page .field { margin-bottom:13px; }
        .abnormal-report-page .sequence { padding:13px; border:1px solid var(--line); border-radius:8px; }
        .abnormal-report-page .sequence-grid { display:grid; grid-template-columns:minmax(0,1fr) 44px minmax(0,1fr); align-items:center; gap:9px; }
        .abnormal-report-page .sequence-grid select { min-height:170px; }
        .abnormal-report-page .move-buttons { display:grid; gap:8px; }
        .abnormal-report-page .progress { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:14px; padding:12px; border:1px solid var(--line); border-radius:8px; color:var(--muted); font-size:.74rem; }
        .abnormal-report-page .progress-step { color:#f5f7f8; }
        .abnormal-report-page .progress-step.approved { color:#2dd4bf; }
        .abnormal-report-page .footer-actions { display:flex; justify-content:flex-end; gap:9px; margin-top:18px; padding-top:16px; border-top:1px solid var(--line); }
        .abnormal-report-page .flash { margin-bottom:14px; padding:11px 13px; border:1px solid #1e8f6d; border-radius:7px; background:rgba(45,212,191,.12); color:#8ff5df; font-size:.78rem; }
        .abnormal-report-page .error { color:#ff9eaa; font-size:.74rem; margin-top:6px; }
        .abnormal-report-page .muted { color:var(--muted); }
        .abnormal-report-page .success { color:#2dd4bf; } .abnormal-report-page .warning { color:#fbbf24; }
        @media (max-width:720px) {
            .abnormal-report-page { padding:12px; }
            .abnormal-report-page .body { padding:12px; }
            .abnormal-report-page .report-header { align-items:flex-start; padding:14px; }
            .abnormal-report-page .report-header h2 { font-size:.82rem; }
            .abnormal-report-page .tools { display:block; }
            .abnormal-report-page .tools > div:first-child { margin-bottom:10px; }
            .abnormal-report-page .date-tools { grid-template-columns:1fr 1fr; }
            .abnormal-report-page .columns { grid-template-columns:1fr; }
            .abnormal-report-page .banner { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .abnormal-report-page .sequence-grid { grid-template-columns:1fr; }
            .abnormal-report-page .move-buttons { grid-template-columns:1fr 1fr; }
            .abnormal-report-page .footer-actions { display:grid; grid-template-columns:1fr 1fr; }
            .abnormal-report-page .footer-actions .btn { width:100%; }
            .abnormal-report-page .pagination-wrap { display:block; }
            .abnormal-report-page .pagination-wrap > span { display:block; margin-bottom:8px; }
            .abnormal-report-page .pagination { justify-content:flex-start; }
        }
        @media (max-width:430px) { .abnormal-report-page .tab { padding:9px 10px; font-size:.67rem; } .abnormal-report-page th, .abnormal-report-page td { padding:8px 7px; font-size:.7rem; } }
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile/Abnormal Report</div>
            <h1>Abnormal <span class="cyan">Report</span></h1>
            <div class="muted">Kelola laporan abnormal, eskalasi, approval, dan revisi.</div>
        </div>
        <div class="toolbar"><button class="btn btn-ghost" type="button" wire:click="refreshGrid" wire:loading.attr="disabled" wire:target="refreshGrid">Refresh Data</button></div>
    </div>

    @if (session('success')) <div class="flash">{{ session('success') }}</div> @endif
    @error('selectedNoAbn') <div class="error">{{ $message }}</div> @enderror

    <section class="report-card">
        <div class="report-header"><h2>ABNORMAL REPORT</h2><span class="muted">{{ $totalRows }} data</span></div>
        <div class="tabs" role="tablist">
            @foreach (['open' => 'Open', 'action' => 'Action', 'eskalasi' => 'Eskalasi', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $tab => $label)
                <button type="button" class="tab {{ $activeTab === $tab ? 'active' : '' }}" wire:click="selectTab('{{ $tab }}')">{{ $label }}</button>
            @endforeach
        </div>

        <div class="body">
            @if ($activeTab !== 'action')
                <div class="tools">
                    <div style="flex:1;max-width:420px"><label for="abnormalSearch">Cari No ABN, lot, objek, kategori</label><input id="abnormalSearch" class="control" type="search" wire:model.live.debounce.400ms="search" placeholder="Ketik kata pencarian..."></div>
                    @if ($activeTab === 'approved')
                        <div class="date-tools"><div><label for="approvedFrom">Dari tanggal</label><input id="approvedFrom" class="control" type="date" wire:model.live="approvedFrom"></div><div><label for="approvedTo">Sampai tanggal</label><input id="approvedTo" class="control" type="date" wire:model.live="approvedTo"></div></div>
                    @endif
                </div>
            @endif

            @if ($activeTab === 'action')
                @if ($selectedNoAbn !== '')
                    <div class="banner">
                        <div><span>No Abnormal</span><strong>{{ $activeDetail['noabn'] ?? $selectedNoAbn }}</strong></div>
                        <div><span>Tanggal</span><strong>{{ $activeDetail['tgl_problem'] ?? '-' }}</strong></div>
                        <div><span>Ref Lot</span><strong>{{ $activeDetail['ref_lot'] ?? '-' }}</strong></div>
                        <div><span>Object</span><strong>{{ $activeDetail['nm_object'] ?? '-' }}</strong></div>
                    </div>
                    <div class="columns">
                        <div class="section">
                            <h3>Detail Abnormal Report</h3>
                            <div class="field"><label>Area / Proses</label><input class="control" value="{{ $activeDetail['nm_sequence'] ?? $activeDetail['area'] ?? '-' }}" readonly></div>
                            <div class="field"><label>Kategori</label><input class="control" value="{{ $activeDetail['category'] ?? '-' }}" readonly></div>
                            <div class="field"><label>Ditemukan Oleh</label><input class="control" value="{{ $activeDetail['nama_kary'] ?? $activeDetail['found_by'] ?? '-' }}" readonly></div>
                            <div class="field"><label>Part</label><input class="control" value="{{ $activeDetail['nm_part'] ?? $activeDetail['no_part'] ?? '-' }}" readonly></div>
                            <div class="field"><label>Customer</label><input class="control" value="{{ $activeDetail['nm_customer'] ?? 'INTERNAL' }}" readonly></div>
                            <div class="field"><label>PIC Dept <span class="warning">*</span></label><select class="control" wire:model="picDept"><option value="">-- Pilih Dept --</option>@foreach ($departments as $department)<option value="{{ $department['Id_Dept'] }}">{{ $department['Dept'] }}</option>@endforeach</select>@error('picDept')<div class="error">{{ $message }}</div>@enderror</div>
                            <div class="field"><label>Detail Problem <span class="warning">*</span></label><textarea class="control" wire:model="detailProblem" maxlength="500"></textarea>@error('detailProblem')<div class="error">{{ $message }}</div>@enderror</div>
                            <div class="field"><label>Analisa Masalah <span class="warning">*</span></label><textarea class="control" wire:model="analisa" maxlength="500"></textarea>@error('analisa')<div class="error">{{ $message }}</div>@enderror</div>
                        </div>
                        <div class="section">
                            <h3>Tindakan & Approval</h3>
                            <div class="field"><label>Correction</label><textarea class="control" wire:model="correction" maxlength="500"></textarea>@error('correction')<div class="error">{{ $message }}</div>@enderror</div>
                            <div class="field"><label>Corrective Action</label><textarea class="control" wire:model="corrective" maxlength="500"></textarea>@error('corrective')<div class="error">{{ $message }}</div>@enderror</div>
                            <div class="sequence"><div style="color:var(--cyan);font-size:.75rem;font-weight:700;margin-bottom:10px">APPROVAL SEQUENCE</div><div class="sequence-grid">
                                <div><label>Master Approver</label><select multiple class="control" wire:model="masterSelected">@foreach ($masterApprovers as $approver)@if (!in_array((string) $approver['nik'], array_map('strval', $judgmentApprovers), true))<option value="{{ $approver['nik'] }}">{{ $approver['user'] }}{{ $approver['jabatan'] ? ' - '.$approver['jabatan'] : '' }}</option>@endif @endforeach</select></div>
                                <div class="move-buttons"><button type="button" class="btn btn-ghost" wire:click="moveApproversToJudgment">→</button><button type="button" class="btn btn-ghost" wire:click="moveApproversBack">←</button></div>
                                <div><label>Judgment By</label><select multiple class="control" wire:model="judgmentSelected">@foreach ($masterApprovers as $approver)@if (in_array((string) $approver['nik'], array_map('strval', $judgmentApprovers), true))<option value="{{ $approver['nik'] }}">{{ $approver['user'] }}{{ $approver['jabatan'] ? ' - '.$approver['jabatan'] : '' }}</option>@endif @endforeach</select></div>
                            </div>@error('judgmentApprovers')<div class="error">{{ $message }}</div>@enderror</div>
                        </div>
                    </div>
                    <div class="footer-actions"><button type="button" class="btn btn-ghost" wire:click="selectTab('open')">Batal</button><button type="button" class="btn btn-cyan" wire:click="commit" wire:confirm="Apakah Anda yakin ingin mengeskalasikan Abnormal Report ini?" wire:loading.attr="disabled" wire:target="commit">COMMIT ESKALASI</button></div>
                @else
                    <div class="empty">Pilih data dari tab Open atau Rejected untuk membuka form action.</div>
                @endif
            @elseif ($rows->count() > 0)
                <div class="table-scroll"><table><thead><tr><th>No Abnormal</th><th>Tgl Kejadian</th><th>Kategori</th><th>Objek</th><th>Proses</th><th>Ref Lot</th><th>Detail Problem</th><th>Ditemukan Oleh</th><th>Status</th><th class="action-cell">Aksi</th>@if ($activeTab === 'approved')<th class="action-cell">Cetak Abnormal Report</th>@endif</tr></thead>
                    <tbody>@foreach ($rows as $row)<tr wire:key="abnormal-{{ $activeTab }}-{{ $row['noabn'] }}"><td>{{ $row['noabn'] ?? '-' }}</td><td>{{ $row['tgl_problem'] ?? '-' }}</td><td>{{ $row['category'] ?? '-' }}</td><td>{{ $row['nm_object'] ?? '-' }}</td><td>{{ $row['nm_sequence'] ?? '-' }}</td><td>{{ $row['ref_lot'] ?? '-' }}</td><td>{{ $row['detail_problem'] ?? '-' }}</td><td>{{ $row['nama_kary'] ?? '-' }}</td><td>{{ $row['status'] ?? '-' }}</td><td class="action-cell">@if (in_array($activeTab, ['open','rejected'], true))<button type="button" class="btn btn-ghost" wire:click="openAction(@js((string) $row['noabn']))">{{ $activeTab === 'rejected' ? 'Revisi' : 'Proses' }}</button>@elseif ($activeTab === 'eskalasi')<button type="button" class="btn btn-ghost" wire:click="loadProgress(@js((string) $row['noabn']))">Progress</button>@else<span class="muted">Selesai</span>@endif</td>@if ($activeTab === 'approved')<td class="action-cell"><a class="btn btn-cyan" href="{{ route('abnormal.report.print', ['noAbn' => $row['noabn']]) }}" target="_blank" rel="noopener">Cetak Report</a></td>@endif</tr>@endforeach</tbody>
                </table></div>
                <div class="pagination-wrap"><span>Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }} data</span>@if ($rows->hasPages())<nav class="pagination" aria-label="Pagination Abnormal Report">@if ($rows->onFirstPage())<span class="page-disabled">‹ Sebelumnya</span>@else<button type="button" class="page-link" wire:click="previousPage" wire:loading.attr="disabled">‹ Sebelumnya</button>@endif @php $firstPage=max(1,$rows->currentPage()-2);$lastPage=min($rows->lastPage(),$rows->currentPage()+2); @endphp @for ($page=$firstPage;$page<=$lastPage;$page++)@if ($page === $rows->currentPage())<span class="page-current" aria-current="page">{{ $page }}</span>@else<button type="button" class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>@endif @endfor @if ($rows->currentPage() === $rows->lastPage())<span class="page-disabled">Berikutnya ›</span>@else<button type="button" class="page-link" wire:click="nextPage">Berikutnya ›</button>@endif</nav>@endif</div>
                @if ($activeTab === 'eskalasi' && $approvalProgress !== [])<div class="progress"><strong>Flow Approval:</strong>@foreach ($approvalProgress as $index => $step)<span class="progress-step {{ strtolower((string) ($step['approve_status'] ?? '')) === 'approved' ? 'approved' : '' }}">{{ $step['nama'] ?? $step['nik_approver'] ?? 'Approver' }} ({{ $step['approve_status'] ?? '-' }})</span>@if ($index < count($approvalProgress)-1)<span>→</span>@endif @endforeach</div>@endif
            @else
                <div class="empty">Tidak ada data pada tab {{ ucfirst($activeTab) }}.</div>
            @endif
        </div>
    </section>
</div>
