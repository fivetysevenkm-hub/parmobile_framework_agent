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
                <option value="hold">Hold</option>
                <option value="belum">Belum Diproses</option>
            </select>
        </div>

        <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:center;">
            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:.76rem;color:var(--footer-text);font-weight:600;">Dari</label>
                <input type="date" wire:model.live="fromDate" style="height:38px;border:1px solid var(--line);border-radius:8px;padding:0 10px;background:var(--panel-deep);color:var(--body-text);font-size:.8rem;">
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:.76rem;color:var(--footer-text);font-weight:600;">s/d</label>
                <input type="date" wire:model.live="toDate" style="height:38px;border:1px solid var(--line);border-radius:8px;padding:0 10px;background:var(--panel-deep);color:var(--body-text);font-size:.8rem;">
            </div>
            <button type="button" wire:click="resetPeriod" style="padding:6px 14px;border:none;border-radius:6px;font-size:.76rem;font-weight:600;cursor:pointer;background:color-mix(in srgb,var(--accent) 12%,transparent);color:var(--accent);">
                Reset H-{{ $periodDays }}
            </button>
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
                            <tr wire:click="selectLot('{{ $lot['no_lot'] }}')" style="border-bottom:1px solid var(--line);cursor:pointer;transition:background .15s;{{ $selectedLotIndex === $lot['no_lot'] ? 'background:color-mix(in srgb,var(--accent) 8%,transparent);' : '' }}" onmouseover="this.style.background='color-mix(in srgb,var(--accent) 5%,transparent)'" onmouseout="this.style.background='{{ $selectedLotIndex === $lot['no_lot'] ? 'color-mix(in srgb,var(--accent) 8%,transparent)' : 'transparent' }}'">
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
                                            <div style="width:{{ $lot['progress'] }}%;height:100%;background:{{ $lot['status_color'] === 'teal' ? 'var(--success)' : ($lot['status_color'] === 'red' ? 'var(--danger)' : ($lot['status_color'] === 'blue' ? 'var(--cyan)' : 'var(--footer-text)')) }};border-radius:3px;transition:width .3s;"></div>
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

    @if ($selectedLotIndex !== -1)
        @php($detailLot = collect($this->lots->getCollection())->firstWhere('no_lot', $selectedLotIndex))
        @if ($detailLot)
            <div style="margin-top:20px;" wire:poll.60s wire:key="detail-{{ $selectedLotIndex }}">
                @livewire('lot-detail', ['noLot' => $detailLot['no_lot']], key($detailLot['no_lot']))
            </div>
        @endif
    @endif

    <div style="text-align:center;margin-top:20px;color:var(--footer-text);font-size:.7rem;">
        Auto-refresh setiap 60 detik | Customer Portal — {{ now()->year }} PT Dowa Thermotech Indonesia
    </div>
</div>
