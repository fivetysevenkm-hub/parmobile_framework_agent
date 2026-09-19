<div class="process-skip-page">
    <style>
        .process-skip-page{padding:14px}
        .process-skip-page .panel{background:var(--panel);border:1px solid var(--line);border-radius:10px;overflow:hidden}
        .process-skip-page .panel+.panel{margin-top:14px}
        .process-skip-page .panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:1px solid var(--line)}
        .process-skip-page .panel-head h2{margin:0;color:var(--cyan);font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}
        .process-skip-page .notice{padding:11px 14px;margin-bottom:14px;border:1px solid var(--line);border-radius:9px;font-size:.76rem}
        .process-skip-page .notice.ok{color:var(--legacy_2dd4bf);background:color-mix(in srgb,var(--legacy_2dd4bf) 9%,transparent)}
        .process-skip-page .notice.info{color:var(--cyan);background:color-mix(in srgb,var(--cyan) 9%,transparent)}
        .process-skip-page .notice.bad{color:var(--legacy_ff5964);background:color-mix(in srgb,var(--legacy_ff5964) 9%,transparent)}
        .process-skip-page .actions{display:flex;gap:8px;padding:12px 14px;border-top:1px solid var(--line);flex-wrap:wrap}
        .process-skip-page .actions .btn{min-height:40px}
        .process-skip-page .scroll-both{overflow:auto;-webkit-overflow-scrolling:touch}
        .process-skip-page .v-table-wrap{max-height:480px}
        .process-skip-page .actual-wrap{max-height:calc(100vh - 320px);min-height:240px}
        .process-skip-page table{border-collapse:separate;border-spacing:0}
        .process-skip-page table.v-table{table-layout:fixed;width:100%;min-width:1100px;font-size:.72rem}
        .process-skip-page table.v-table th,.process-skip-page table.v-table td{overflow-wrap:break-word;word-break:break-word}
        .process-skip-page table.v-table col.c-narrow{width:64px}
        .process-skip-page table.v-table col.c-count{width:80px}
        .process-skip-page table.v-table col.c-mid{width:104px}
        .process-skip-page table.v-table col.c-lot{width:118px}
        .process-skip-page table.v-table col.c-srp{width:96px}
        .process-skip-page table.v-table col.c-fn{width:76px}
        .process-skip-page table.actual-table{min-width:2900px;font-size:.7rem}
        .process-skip-page th,.process-skip-page td{padding:8px 9px;border-bottom:1px solid var(--legacy_26333a);border-right:1px solid var(--legacy_26333a);vertical-align:middle;color:var(--legacy_d9e0e3)}
        .process-skip-page th{background:var(--legacy_0b1013);color:var(--cyan);font-size:.63rem;letter-spacing:.05em;text-transform:uppercase}
        .process-skip-page th{position:sticky;top:0;z-index:2}
        .process-skip-page td.stick-l,.process-skip-page th.stick-l{position:sticky;left:0;z-index:1;background:var(--legacy_0b1013)}
        .process-skip-page th.stick-l{z-index:4}
        .process-skip-page td.stick-l{background:var(--legacy_171f23)}
        .process-skip-page tr.v-input td{background:var(--legacy_0d1418);padding:9px}
        .process-skip-page tr.v-input td.stick-l{background:var(--legacy_121e24)}
        .process-skip-page tr.v-interp td{background:var(--legacy_111a1f);border-bottom:0;font-size:.66rem;color:var(--muted);white-space:normal;overflow-wrap:anywhere;word-break:break-word}
        .process-skip-page tr.v-interp td.stick-l{background:var(--legacy_121e24)}
        .process-skip-page input,.process-skip-page select{width:100%;min-width:0;padding:8px 9px;border:1px solid var(--line);border-radius:6px;background:var(--legacy_0d1418);color:var(--legacy_f5f7f8);font-size:.72rem;outline:none}
        .process-skip-page input:focus,.process-skip-page select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,229,255,.1)}
        .process-skip-page select{color-scheme:dark}
        .process-skip-page .mono{font-family:'JetBrains Mono',monospace}
        .process-skip-page .empty{display:grid;min-height:200px;place-items:center;padding:22px;color:var(--muted);font-size:.78rem;text-align:center}
        .process-skip-page .muted-sm{color:var(--muted);font-size:.66rem}
        @media(max-width:720px){
            .process-skip-page{padding:12px}
            .process-skip-page .panel-head{padding:11px 12px}
            .process-skip-page .panel-head h2{font-size:.7rem}
            .process-skip-page .actions{padding:10px 12px}
            .process-skip-page .actions .btn{flex:1 1 100%}
            .process-skip-page .notice{font-size:.72rem}
            .process-skip-page .v-table-wrap{max-height:400px}
            .process-skip-page .actual-wrap{max-height:60vh;min-height:220px}
            .process-skip-page input,.process-skip-page select{font-size:.7rem;padding:7px 8px}
        }
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / Routing</div>
            <h1>Koreksi <span class="cyan">Lompat Proses</span></h1>
            <div class="muted"></div>
        </div>
        <div class="toolbar">
            <button type="button" class="btn btn-ghost" wire:click="resetForm" wire:loading.attr="disabled">Reset</button>
        </div>
    </div>

    @if($savedNotice !== '')
        <div class="notice ok">{{ $savedNotice }}</div>
    @endif
    @if($notice !== '')
        <div class="notice {{ $lookedUp && str_contains($notice, 'tidak ditemukan') ? 'bad' : 'info' }}">{{ $notice }}</div>
    @endif

    @php
        $int = $interpreter + [
            'no_lot' => '', 'no_srp' => '', 'id_routing' => '',
            'current_seq_code' => '', 'next_seq_code' => '',
            'next_process' => '', 'curr_process' => '',
            'current_seq_count' => '', 'next_seq_count' => '',
            'total_seq_count' => '', 'total_seq_remain' => '', 'finish' => '',
        ];
    @endphp

    <section class="panel">
        <div class="panel-head">
            <h2>Validator Urutan Proses</h2>
            <span class="muted-sm"></span>
        </div>
        <div class="scroll-both v-table-wrap">
            <table class="v-table">
                <colgroup>
                    <col class="c-lot">
                    <col class="c-srp">
                    <col class="c-narrow">
                    <col class="c-mid">
                    <col class="c-mid">
                    <col class="c-mid">
                    <col class="c-mid">
                    <col class="c-count">
                    <col class="c-count">
                    <col class="c-count">
                    <col class="c-count">
                    <col class="c-fn">
                </colgroup>
                <tbody>
                    <tr class="v-head">
                        <th class="stick-l">No Lot</th>
                        <th>No SRP</th>
                        <th>Id Routing</th>
                        <th>Current Seq Code</th>
                        <th>Next Seq Code</th>
                        <th>Next Process</th>
                        <th>Curr Process</th>
                        <th>Current Seq Count</th>
                        <th>Next Seq Count</th>
                        <th>Total Seq Count</th>
                        <th>Total Seq Remain</th>
                        <th>Finish</th>
                    </tr>
                    <tr class="v-input">
                        <td class="stick-l"><input type="text" wire:model.blur="no_lot" wire:keydown.enter="lookup" placeholder="Ketik No Lot..."></td>
                        <td><input type="text" wire:model.defer="no_srp" placeholder="-"></td>
                        <td><input type="text" wire:model.defer="id_routing" placeholder="-"></td>
                        <td>
                            <select wire:model.defer="current_seq_code">
                                <option value="">— Pilih —</option>
                                @foreach($routingSeqCodes as $code)
                                    <option value="{{ $code['value'] }}">{{ $code['label'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select wire:model.defer="next_seq_code">
                                <option value="">— Pilih —</option>
                                @foreach($routingSeqCodes as $code)
                                    <option value="{{ $code['value'] }}">{{ $code['label'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select wire:model.defer="next_process">
                                @foreach($sequenceOptions as $process)
                                    <option value="{{ $process['value'] }}">{{ $process['label'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select wire:model.defer="curr_process">
                                @foreach($sequenceOptions as $process)
                                    <option value="{{ $process['value'] }}">{{ $process['label'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" min="0" wire:model.defer="current_seq_count" placeholder="0"></td>
                        <td><input type="number" min="0" wire:model.defer="next_seq_count" placeholder="0"></td>
                        <td><input type="number" min="0" wire:model.defer="total_seq_count" placeholder="0"></td>
                        <td><input type="number" min="0" wire:model.defer="total_seq_remain" placeholder="0"></td>
                        <td>
                            <select wire:model.defer="finish">
                                <option value="">— Pilih —</option>
                                <option value="no">no</option>
                                <option value="yes">yes</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="v-interp">
                        <td class="stick-l">{{ $int['no_lot'] }}</td>
                        <td>{{ $int['no_srp'] }}</td>
                        <td>{{ $int['id_routing'] }}</td>
                        <td>{{ $int['current_seq_code'] }}</td>
                        <td>{{ $int['next_seq_code'] }}</td>
                        <td>{{ $int['next_process'] }}</td>
                        <td>{{ $int['curr_process'] }}</td>
                        <td>{{ $int['current_seq_count'] }}</td>
                        <td>{{ $int['next_seq_count'] }}</td>
                        <td>{{ $int['total_seq_count'] }}</td>
                        <td>{{ $int['total_seq_remain'] }}</td>
                        <td>{{ $int['finish'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-cyan" wire:click="lookup" wire:loading.attr="disabled">Cari / Muat Data</button>
            <button type="button" class="btn btn-ghost" wire:click="save" wire:loading.attr="disabled">Simpan Perubahan</button>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Actual Lot di tbl_seq_all</h2>
            <span class="muted-sm">{{ count($seqAll) }} record proses · {{ $seqAll ? (collect($seqAll)->pluck('seq')->filter()->unique()->count()) : 0 }} urutan</span>
        </div>
        @if($seqAll)
            <div class="scroll-both actual-wrap">
                <table class="actual-table">
                    <thead>
                        <tr>
                            <th class="stick-l">no_lot</th>
                            <th>no_srp</th>
                            <th>seq</th>
                            <th>id_seq</th>
                            <th>nm_seq</th>
                            <th>id_routing</th>
                            <th>start_time</th>
                            <th>finish_time</th>
                            <th>duration</th>
                            <th>tray_no</th>
                            <th>tray_nokecil</th>
                            <th>pic_01</th>
                            <th>pic_02</th>
                            <th>pic_03</th>
                            <th>pic_04</th>
                            <th>akakami</th>
                            <th>ojt</th>
                            <th>hold</th>
                            <th>finish</th>
                            <th>no_mesin</th>
                            <th>created_at</th>
                            <th>updated_at</th>
                            <th>no_part</th>
                            <th>nm_part</th>
                            <th>customer</th>
                            <th>no_sj_cust</th>
                            <th>no_lot_cust</th>
                            <th>qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($seqAll as $row)
                            <tr wire:key="seq-all-{{ $loop->index }}">
                                <td class="stick-l">{{ $row['no_lot'] ?? '-' }}</td>
                                <td>{{ $row['no_srp'] ?? '-' }}</td>
                                <td>{{ $row['seq'] ?? '-' }}</td>
                                <td class="mono">{{ $row['id_seq'] ?? '-' }}</td>
                                <td>{{ $row['nm_seq'] ?? '-' }}</td>
                                <td>{{ $row['id_routing'] ?? '-' }}</td>
                                <td>{{ $row['start_time'] ?? '-' }}</td>
                                <td>{{ $row['finish_time'] ?? '-' }}</td>
                                <td>{{ $row['duration'] ?? '-' }}</td>
                                <td>{{ $row['tray_no'] ?? '-' }}</td>
                                <td>{{ $row['tray_nokecil'] ?? '-' }}</td>
                                <td>{{ $row['pic_01'] ?? '-' }}</td>
                                <td>{{ $row['pic_02'] ?? '-' }}</td>
                                <td>{{ $row['pic_03'] ?? '-' }}</td>
                                <td>{{ $row['pic_04'] ?? '-' }}</td>
                                <td>{{ $row['akakami'] ?? '-' }}</td>
                                <td>{{ $row['ojt'] ?? '-' }}</td>
                                <td>{{ $row['hold'] ?? '-' }}</td>
                                <td>{{ $row['finish'] ?? '-' }}</td>
                                <td>{{ $row['no_mesin'] ?? '-' }}</td>
                                <td>{{ $row['created_at'] ?? '-' }}</td>
                                <td>{{ $row['updated_at'] ?? '-' }}</td>
                                <td>{{ $row['no_part'] ?? '-' }}</td>
                                <td>{{ $row['nm_part'] ?? '-' }}</td>
                                <td>{{ $row['customer'] ?? '-' }}</td>
                                <td>{{ $row['no_sj_cust'] ?? '-' }}</td>
                                <td>{{ $row['no_lot_cust'] ?? '-' }}</td>
                                <td>{{ $row['qty'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">Belum ada record proses di tbl_seq_all untuk No Lot ini.</div>
        @endif
    </section>
</div>