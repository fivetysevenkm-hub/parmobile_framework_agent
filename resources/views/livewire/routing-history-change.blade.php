<div class="routing-history-page">
    <style>
        .routing-history-page{padding:14px}.routing-history-page .history-card{background:var(--panel);border:1px solid var(--line);border-radius:10px;overflow:hidden}.routing-history-page .history-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.04)}.routing-history-page .history-header h2{margin:0;font-size:.92rem}.routing-history-page .history-tabs{display:flex;gap:6px;padding:14px 14px 0;overflow-x:auto;border-bottom:1px solid var(--line)}.routing-history-page .history-tab{flex:0 0 auto;padding:10px 13px;border:0;border-bottom:2px solid transparent;background:transparent;color:var(--muted);font-size:.75rem;cursor:pointer;white-space:nowrap}.routing-history-page .history-tab:hover,.routing-history-page .history-tab.active{color:var(--cyan);border-bottom-color:var(--cyan)}.routing-history-page .history-body{padding:18px}.routing-history-page label{display:block;color:var(--muted);font-size:.68rem;letter-spacing:.07em;margin-bottom:6px;text-transform:uppercase}.routing-history-page .control{width:100%;background:var(--legacy_0d1418);border:1px solid var(--line);border-radius:7px;color:var(--legacy_f5f7f8);padding:10px 11px;outline:none}.routing-history-page .control:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,229,255,.1)}.routing-history-page .grid-tools{display:flex;align-items:end;justify-content:space-between;gap:10px;margin-bottom:14px}.routing-history-page table{min-width:900px}.routing-history-page th,.routing-history-page td{padding:11px 10px;border-bottom:1px solid var(--legacy_26333a);text-align:left;vertical-align:top}.routing-history-page th{position:sticky;top:0;z-index:2;background:var(--legacy_0b1013);color:var(--cyan);font-size:.63rem;text-transform:uppercase;letter-spacing:.05em}.routing-history-page td{color:var(--legacy_d9e0e3);font-size:.73rem;white-space:normal}.routing-history-page .table-scroll{max-height:560px;border:1px solid var(--line);border-radius:8px;overflow:auto;-webkit-overflow-scrolling:touch}.routing-history-page .mono{font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--cyan)}.routing-history-page .seq{font-family:'JetBrains Mono',monospace;font-weight:700}.routing-history-page .center{text-align:center}.routing-history-page .muted{color:var(--muted)}.routing-history-page .changed{color:var(--legacy_2dd4bf);font-weight:700}.routing-history-page .pager{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px;color:var(--muted);font-size:.7rem}.routing-history-page .pager .btn{font-size:.7rem}.routing-history-page .empty{padding:36px 20px;color:var(--muted);text-align:center}.routing-history-page .flash{margin-bottom:14px;padding:11px 13px;border:1px solid var(--legacy_1e8f6d);border-radius:7px;background:rgba(45,212,191,.12);color:var(--legacy_8ff5df);font-size:.78rem}
        @media(max-width:720px){.routing-history-page{padding:12px}.routing-history-page .history-body{padding:12px}.routing-history-page .history-header{padding:12px}.routing-history-page .history-header h2{font-size:.82rem}.routing-history-page .history-tab{padding:9px 10px;font-size:.67rem}.routing-history-page .grid-tools{display:block}.routing-history-page .grid-tools>div:first-child{margin-bottom:10px}.routing-history-page .pager{display:block}.routing-history-page .pager>div{margin-top:8px}.routing-history-page .pager .btn{width:100%}}
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / Routing</div>
            <h1>History <span class="cyan">Perubahan Routing</span></h1>
            <div class="muted">Kronologi perubahan urutan routing proses dan update pairing routing ke SRP</div>
        </div>
        <div class="toolbar"><button class="btn btn-ghost" type="button" wire:click="refreshGrid" wire:loading.attr="disabled" wire:target="refreshGrid">Refresh Data</button></div>
    </div>

    <section class="history-card">
        <div class="history-header"><h2>HISTORY PERUBAHAN ROUTING</h2><span class="muted">{{ $totalRows }} data</span></div>
        <div class="history-tabs" role="tablist">
            <button type="button" class="history-tab {{ $activeTab === 'routing' ? 'active' : '' }}" wire:click="$set('activeTab', 'routing')">Perubahan Urutan Routing</button>
            <button type="button" class="history-tab {{ $activeTab === 'pairing' ? 'active' : '' }}" wire:click="$set('activeTab', 'pairing')">Update Pairing Routing</button>
        </div>

        <div class="history-body">
            <div class="grid-tools">
                <div style="flex:1;max-width:420px">
                    <label for="routingHistorySearch">Cari {{ $activeTab === 'pairing' ? 'No SRP, part, routing, atau pengubah' : 'No routing, sequence, atau pengubah' }}</label>
                    <input id="routingHistorySearch" class="control" type="search" wire:model.live.debounce.400ms="search" placeholder="Ketik kata pencarian...">
                </div>
            </div>

            @if ($rows->count() > 0)
                <div class="table-scroll">
                    <table>
                        @if ($activeTab === 'routing')
                            <thead><tr><th>Tanggal &amp; Jam</th><th>No Routing</th><th>Nama Routing</th><th>Slot</th><th>Sequence Sebelum</th><th>Sequence Sesudah</th><th>Oleh</th></tr></thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="routing-history-{{ $row['id'] }}">
                                        <td>{{ \Illuminate\Support\Carbon::parse($row['changed_at'])->format('d-m-Y H:i') }}</td>
                                        <td class="mono">{{ $row['id_routing'] }}</td>
                                        <td>{{ $row['nm_routing'] ?? '-' }}</td>
                                        <td>Seq_{{ str_pad((string) $row['slot_no'], 2, '0', STR_PAD_LEFT) }}</td>
                                        <td>@if ($row['seq_before'])<span class="seq">{{ $row['seq_before'] }}</span>@if ($row['nama_before']) · {{ $row['nama_before'] }}@endif@else<span class="muted">— kosong —</span>@endif</td>
                                        <td>@if ($row['seq_after'])<span class="seq changed">{{ $row['seq_after'] }}</span>@if ($row['nama_after']) · {{ $row['nama_after'] }}@endif@else<span class="muted">— kosong —</span>@endif</td>
                                        <td>{{ $row['changed_by'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @else
                            <thead><tr><th>Tanggal &amp; Jam</th><th>No SRP</th><th>No Part</th><th>Nama Part</th><th>Routing Sebelum</th><th>Routing Sesudah</th><th>Oleh</th></tr></thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="pairing-history-{{ $row['id'] }}">
                                        <td>{{ \Illuminate\Support\Carbon::parse($row['changed_at'])->format('d-m-Y H:i') }}</td>
                                        <td class="mono">{{ $row['no_srp'] }}</td>
                                        <td>{{ $row['no_part'] ?? '-' }}</td>
                                        <td>{{ $row['nm_part'] ?? '-' }}</td>
                                        <td>@if ($row['routing_before'])<span class="seq">{{ $row['routing_before'] }}</span>@if ($row['nama_before']) · {{ $row['nama_before'] }}@endif@else<span class="muted">— kosong —</span>@endif</td>
                                        <td><span class="seq changed">{{ $row['routing_after'] }}</span>@if ($row['nama_after']) · {{ $row['nama_after'] }}@endif</td>
                                        <td>{{ $row['changed_by'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @endif
                    </table>
                </div>
                <div class="pager">
                    <span>Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $totalRows }} data · Halaman {{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>
                    <div style="display:flex;gap:6px"><button type="button" class="btn btn-ghost" wire:click="previousPage" @disabled($rows->onFirstPage())>‹ Sebelumnya</button><button type="button" class="btn btn-ghost" wire:click="nextPage" @disabled($rows->currentPage() === $rows->lastPage())>Berikutnya ›</button></div>
                </div>
            @else
                <div class="empty">Belum ada data {{ $activeTab === 'pairing' ? 'update pairing routing' : 'perubahan urutan routing' }}.</div>
            @endif
        </div>
    </section>
</div>