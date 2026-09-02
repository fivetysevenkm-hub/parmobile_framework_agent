@if ($loading)
    <div class="card" style="background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:14px;padding:24px;text-align:center;color:var(--footer-text);font-size:.82rem;">
        Memuat data...
    </div>
@elseif(empty($detail))
    <div class="card" style="background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:14px;padding:24px;text-align:center;color:var(--footer-text);font-size:.82rem;">
        Data lot tidak ditemukan.
    </div>
@else
    <div class="card" style="background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:14px;padding:24px;box-shadow:0 18px 45px color-mix(in srgb,var(--shadow-color) 32%,transparent);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
            <div>
                <h2 style="color:var(--ink);font-size:1.1rem;margin:0;font-weight:700;">LOT: {{ $detail['master']['no_lot'] }}</h2>
                <p style="color:var(--footer-text);font-size:.78rem;margin:4px 0 0;">
                    Part: {{ $detail['master']['nm_part'] }} ({{ $detail['master']['no_part'] }})
                    @if($detail['master']['no_lot_cust'])
                        &nbsp;|&nbsp; No Lot Cust: {{ $detail['master']['no_lot_cust'] }}
                    @endif
                </p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:.78rem;color:var(--footer-text);">Qty</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--ink);">{{ number_format($detail['master']['qty']) }}</div>
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:.78rem;color:var(--footer-text);">Progress</span>
                <span style="font-size:.78rem;font-weight:600;color:var(--ink);">{{ $detail['finished_seq'] }}/{{ $detail['total_seq'] }} proses ({{ $detail['progress'] }}%)</span>
            </div>
            <div style="width:100%;height:8px;background:var(--line);border-radius:4px;overflow:hidden;">
                <div style="width:{{ $detail['progress'] }}%;height:100%;background:{{ $detail['progress'] == 100 ? 'var(--success)' : 'var(--cyan)' }};border-radius:4px;transition:width .5s;"></div>
            </div>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            @foreach($detail['processes'] as $proc)
                @php
                    $borderColor = match($proc['status']) {
                        'finished' => 'var(--success)',
                        'hold' => 'var(--danger)',
                        'in_progress' => 'var(--cyan)',
                        default => 'var(--line)',
                    };
                    $bgColor = match($proc['status']) {
                        'finished' => 'color-mix(in srgb,var(--success) 6%,transparent)',
                        'hold' => 'color-mix(in srgb,var(--danger) 6%,transparent)',
                        'in_progress' => 'color-mix(in srgb,var(--cyan) 6%,transparent)',
                        default => 'transparent',
                    };
                    $statusLabel = match($proc['status']) {
                        'finished' => 'Selesai',
                        'hold' => 'Hold',
                        'in_progress' => 'Berlangsung',
                        default => 'Menunggu',
                    };
                    $statusColor = match($proc['status']) {
                        'finished' => 'var(--success)',
                        'hold' => 'var(--danger)',
                        'in_progress' => 'var(--cyan)',
                        default => 'var(--footer-text)',
                    };
                @endphp
                <div style="flex:1 1 200px;max-width:280px;border:1px solid {{ $borderColor }};border-radius:10px;padding:14px;background:{{ $bgColor }};transition:all .2s;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <span style="font-size:.85rem;font-weight:700;color:var(--ink);">Seq {{ $proc['seq'] }}</span>
                        <span style="font-size:.68rem;padding:2px 8px;border-radius:12px;font-weight:600;background:color-mix(in srgb,{{ $statusColor }} 12%,transparent);color:{{ $statusColor }};">{{ $statusLabel }}</span>
                    </div>
                    <div style="font-size:.78rem;font-weight:600;color:var(--body-text);margin-bottom:6px;">{{ $proc['nm_seq'] ?? '-' }}</div>

                    @if($proc['start_time'] || $proc['finish_time'])
                        <div style="font-size:.72rem;color:var(--footer-text);margin-bottom:4px;">
                            @if($proc['start_time'])
                                Start: {{ $proc['start_time'] }}
                            @endif
                            @if($proc['finish_time'])
                                <br>Finish: {{ $proc['finish_time'] }}
                            @endif
                        </div>
                    @endif

                    @if($proc['duration'])
                        <div style="font-size:.72rem;color:var(--footer-text);">Durasi: {{ $proc['duration'] }}</div>
                    @endif

                    @if($proc['no_mesin'])
                        <div style="font-size:.72rem;color:var(--footer-text);margin-top:4px;">Mesin: {{ $proc['no_mesin'] }}</div>
                    @endif

                    @if($proc['pic_01'])
                        <div style="font-size:.72rem;color:var(--footer-text);">PIC: {{ $proc['pic_01'] }}{{ $proc['pic_02'] ? ', '.$proc['pic_02'] : '' }}</div>
                    @endif

                    @if($proc['akakami'] === 'yes')
                        <span style="display:inline-block;margin-top:6px;font-size:.68rem;padding:2px 8px;border-radius:12px;font-weight:600;background:color-mix(in srgb,var(--danger) 12%,transparent);color:var(--danger);">FLPP</span>
                    @endif
                    @if($proc['ojt'] === 'yes')
                        <span style="display:inline-block;margin-top:6px;font-size:.68rem;padding:2px 8px;border-radius:12px;font-weight:600;background:color-mix(in srgb,var(--warning) 12%,transparent);color:var(--warning);">OJT</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
