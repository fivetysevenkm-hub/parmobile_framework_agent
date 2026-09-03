<div style="width:min(100%,1040px);margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="color:var(--ink);font-size:1.4rem;margin:0;font-weight:700;">Lot Tracking</h1>
            <p style="color:var(--footer-text);font-size:.78rem;margin:4px 0 0;">{{ $this->customerName }}</p>
            <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                <span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:.72rem;font-weight:600;background:color-mix(in srgb,var(--accent) 12%,transparent);color:var(--accent);">
                    Periode: {{ $this->periodLabel }}
                </span>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ route('customer.logout') }}" onclick="event.preventDefault();document.getElementById('customer-logout-form').submit();" style="color:var(--danger);font-size:.78rem;text-decoration:none;font-weight:500;">Keluar</a>
            <form id="customer-logout-form" action="{{ route('customer.logout') }}" method="POST" style="display:none;">@csrf</form>
        </div>
    </div>

    <div class="card" style="background:color-mix(in srgb,var(--panel) 96%,transparent);border:1px solid var(--line);border-radius:14px;padding:24px;box-shadow:0 18px 45px color-mix(in srgb,var(--shadow-color) 32%,transparent);">
        <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <select wire:model.live="searchCol" class="control" style="width:170px;height:40px;border:1px solid var(--line);border-radius:8px;padding:0 12px;background:var(--panel-deep);color:var(--body-text);font-size:.82rem;appearance:none;">
                @foreach($searchColumns as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" wire:model.live.debounce.300ms="search" class="control" placeholder="Ketik kata kunci pencarian..." style="flex:1;min-width:200px;height:40px;border:1px solid var(--line);border-radius:8px;padding:0 12px;background:var(--panel-deep);color:var(--body-text);font-size:.82rem;">
            <select wire:model.live="statusFilter" class="control" style="width:170px;height:40px;border:1px solid var(--line);border-radius:8px;padding:0 12px;background:var(--panel-deep);color:var(--body-text);font-size:.82rem;appearance:none;">
                <option value="">Semua Status</option>
                <option value="diproses">Diproses</option>
                <option value="selesai">Selesai</option>
                <option value="belum">Belum Diproses</option>
            </select>
        </div>

        @if ($this->lots->isEmpty())
            <div style="text-align:center;padding:40px 20px;color:var(--footer-text);font-size:.85rem;">
                Tidak ada data lot ditemukan.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--line);">
                            <th style="text-align:left;padding:10px 12px;color:var(--ink);font-weight:600;">No. Lot (PAR)</th>
                            <th style="text-align:left;padding:10px 12px;color:var(--ink);font-weight:600;">No. Lot Cust.</th>
                            <th style="text-align:left;padding:10px 12px;color:var(--ink);font-weight:600;">No. SJ</th>
                            <th style="text-align:left;padding:10px 12px;color:var(--ink);font-weight:600;">Part</th>
                            <th style="text-align:right;padding:10px 12px;color:var(--ink);font-weight:600;">Qty</th>
                            <th style="text-align:center;padding:10px 12px;color:var(--ink);font-weight:600;">Progress</th>
                            <th style="text-align:center;padding:10px 12px;color:var(--ink);font-weight:600;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->lots as $lot)
                            <tr wire:click="selectLot('{{ $lot['no_lot'] }}')" style="border-bottom:1px solid var(--line);cursor:pointer;transition:background .15s;" onmouseover="this.style.background='color-mix(in srgb,var(--accent) 5%,transparent)'" onmouseout="this.style.background='transparent'">
                                <td style="padding:10px 12px;font-weight:600;color:var(--ink);white-space:nowrap;">{{ $lot['no_lot'] }}</td>
                                <td style="padding:10px 12px;color:var(--body-text);white-space:nowrap;">{{ $lot['no_lot_cust'] ?: '-' }}</td>
                                <td style="padding:10px 12px;color:var(--body-text);white-space:nowrap;">{{ $lot['no_sj_cust'] ?: '-' }}</td>
                                <td style="padding:10px 12px;">
                                    <div style="color:var(--body-text);font-weight:500;">{{ $lot['nm_part'] }}</div>
                                    <div style="color:var(--footer-text);font-size:.72rem;">{{ $lot['no_part'] }}</div>
                                </td>
                                <td style="padding:10px 12px;text-align:right;color:var(--body-text);white-space:nowrap;">{{ number_format($lot['qty']) }}</td>
                                <td style="padding:10px 12px;">
                                    <div style="display:flex;align-items:center;gap:8px;justify-content:center;">
                                        <div style="flex:1;max-width:80px;height:6px;background:var(--line);border-radius:3px;overflow:hidden;">
                                            <div style="width:{{ $lot['progress'] }}%;height:100%;background:{{ $lot['status_color'] === 'teal' ? 'var(--success)' : ($lot['status_color'] === 'blue' ? 'var(--cyan)' : 'var(--footer-text)') }};border-radius:3px;transition:width .3s;"></div>
                                        </div>
                                        <span style="color:var(--footer-text);font-size:.72rem;white-space:nowrap;">{{ $lot['finished_seq'] }}/{{ $lot['total_seq'] }}</span>
                                    </div>
                                </td>
                                <td style="padding:10px 12px;text-align:center;">
                                    <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600;background:color-mix(in srgb,var(--{{ $lot['status_color'] }}) 15%,transparent);color:var(--{{ $lot['status_color'] }});">{{ $lot['status_label'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($this->lots->hasPages())
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;flex-wrap:wrap;gap:10px;">
                    <span style="color:var(--footer-text);font-size:.76rem;">
                        Menampilkan {{ $this->lots->firstItem() ?? 0 }}–{{ $this->lots->lastItem() ?? 0 }} dari {{ $this->lots->total() }} lot
                    </span>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button type="button" wire:click="previousPage" @disabled(!$this->lots->onFirstPage()) style="padding:6px 14px;border:none;border-radius:6px;font-size:.76rem;font-weight:600;cursor:pointer;background:color-mix(in srgb,var(--accent) 15%,transparent);color:var(--accent);{{ $this->lots->onFirstPage() ? 'opacity:.4;cursor:not-allowed;' : '' }}">‹ Sebelumnya</button>
                        <span style="color:var(--footer-text);font-size:.76rem;">Hal {{ $this->lots->currentPage() }} / {{ $this->lots->lastPage() }}</span>
                        <button type="button" wire:click="nextPage" @disabled(!$this->lots->hasMorePages()) style="padding:6px 14px;border:none;border-radius:6px;font-size:.76rem;font-weight:600;cursor:pointer;background:color-mix(in srgb,var(--accent) 15%,transparent);color:var(--accent);{{ $this->lots->hasMorePages() ? '' : 'opacity:.4;cursor:not-allowed;' }}">Berikutnya ›</button>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <div style="text-align:center;margin-top:20px;color:var(--footer-text);font-size:.7rem;">
        Customer Portal — {{ now()->year }} PT Dowa Thermotech Indonesia
    </div>

    <div wire:show="showModal" wire:click="closeModal" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;">
        <div wire:click.stop style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:min(100%,380px);max-height:80vh;overflow-y:auto;background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:0 20px 60px rgba(0,0,0,.5);">
            @if(empty($lotDetail))
                <div style="text-align:center;padding:40px 20px;color:var(--footer-text);font-size:.85rem;">
                    Data lot tidak ditemukan.
                </div>
            @else
                @php
                    $procs = $lotDetail['processes'];
                    $lastProc = collect($procs)
                        ->filter(fn($p) => $p['status'] === 'finished' || $p['status'] === 'in_progress' || $p['status'] === 'hold')
                        ->sortByDesc('seq')
                        ->first();
                    $allDone = !empty($procs) && collect($procs)->every(fn($p) => $p['status'] === 'finished');
                @endphp
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="color:var(--ink);font-size:1.1rem;margin:0;font-weight:700;">LOT: {{ $lotDetail['master']['no_lot'] }}</h2>
                        <p style="color:var(--footer-text);font-size:.78rem;margin:4px 0 0;">
                            {{ $lotDetail['master']['nm_part'] }} ({{ $lotDetail['master']['no_part'] }})
                            @if($lotDetail['master']['no_lot_cust'])
                                &nbsp;|&nbsp; {{ $lotDetail['master']['no_lot_cust'] }}
                            @endif
                        </p>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="text-align:right;">
                            <div style="font-size:.78rem;color:var(--footer-text);">Qty</div>
                            <div style="font-size:1.1rem;font-weight:700;color:var(--ink);">{{ number_format($lotDetail['master']['qty']) }}</div>
                        </div>
                        <button wire:click="closeModal" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--footer-text);padding:4px 8px;border-radius:6px;transition:background .15s;" onmouseover="this.style.background='color-mix(in srgb,var(--danger) 10%,transparent)'" onmouseout="this.style.background='none'">&times;</button>
                    </div>
                </div>

                <div style="margin-bottom:24px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:.78rem;color:var(--footer-text);">Progress</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--ink);">{{ $lotDetail['finished_seq'] }}/{{ $lotDetail['total_seq'] }} proses ({{ $lotDetail['progress'] }}%)</span>
                    </div>
                    <div style="width:100%;height:8px;background:var(--line);border-radius:4px;overflow:hidden;">
                        <div style="width:{{ $lotDetail['progress'] }}%;height:100%;background:{{ $allDone ? 'var(--success)' : 'var(--cyan)' }};border-radius:4px;transition:width .5s;"></div>
                    </div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <div style="flex:1 1 240px;border:1px solid var(--line);border-radius:10px;padding:16px;background:color-mix(in srgb,var(--cyan) 5%,transparent);">
                        <div style="font-size:.72rem;color:var(--footer-text);font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px;">Proses Terakhir</div>
                        @if($lastProc)
                            <div style="font-size:.95rem;font-weight:700;color:var(--ink);margin-bottom:4px;">{{ $lastProc['nm_seq'] ?? 'Seq '.$lastProc['seq'] }}</div>
                            <div style="font-size:.78rem;color:var(--body-text);">
                                {{ $lastProc['finish_time'] ?: $lastProc['start_time'] }}
                                @if($lastProc['status'] === 'in_progress') <span style="font-size:.72rem;color:var(--cyan);">(Berlangsung)</span>@endif
                            </div>
                        @else
                            <div style="font-size:.85rem;color:var(--footer-text);">Belum ada</div>
                        @endif
                    </div>

                    <div style="flex:1 1 240px;border:1px solid var(--line);border-radius:10px;padding:16px;">
                        <div style="font-size:.72rem;color:var(--footer-text);font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px;">Proses Selanjutnya</div>
                        @if($allDone || $nextProcess === '')
                            <div style="font-size:1rem;font-weight:700;color:var(--success);">Semua proses selesai</div>
                        @else
                            <div style="font-size:1rem;font-weight:700;color:var(--ink);">{{ $nextProcess }}</div>
                            <div style="font-size:.78rem;color:var(--footer-text);">menunggu diproses</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

