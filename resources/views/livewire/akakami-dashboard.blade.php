@php
    $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
@endphp

<div class="app-shell akakami-dashboard" wire:poll.10m="loadData">
    <style>
        .akakami-dashboard .analytics-filter { display:flex; align-items:end; flex-wrap:wrap; gap:10px; margin-bottom:18px; padding:14px; background:#050708; border:1px solid var(--line); border-radius:10px; }
        .akakami-dashboard .analytics-filter > div { flex:1 1 180px; }
        .akakami-dashboard .analytics-filter label { display:block; margin-bottom:6px; color:var(--muted); font-size:.68rem; letter-spacing:.08em; text-transform:uppercase; }
        .akakami-dashboard .analytics-filter select { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:7px; background:#10171b; color:var(--cyan); color-scheme:dark; }
        .akakami-dashboard .analytics-filter .btn { min-width:120px; }
        .akakami-dashboard .summary-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .akakami-dashboard .summary-card { position:relative; overflow:hidden; padding:17px 18px; border:1px solid var(--line); border-radius:10px; background:var(--panel); }
        .akakami-dashboard .summary-card::after { position:absolute; right:-20px; bottom:-35px; width:110px; height:110px; border-radius:50%; background:rgba(0,229,255,.08); content:''; }
        .akakami-dashboard .summary-label { color:var(--muted); font-size:.68rem; letter-spacing:.1em; text-transform:uppercase; }
        .akakami-dashboard .summary-value { display:block; margin-top:8px; font-family:'JetBrains Mono',monospace; font-size:clamp(1.4rem,3vw,2.1rem); font-weight:700; }
        .akakami-dashboard .summary-card.case { border-left:4px solid var(--orange); } .akakami-dashboard .summary-card.pcs { border-left:4px solid var(--cyan); }
        .akakami-dashboard .chart-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .akakami-dashboard .chart-panel { min-width:0; padding:16px; border:1px solid var(--line); border-radius:10px; background:var(--panel); }
        .akakami-dashboard .chart-panel.wide { grid-column:1 / -1; }
        .akakami-dashboard .chart-title { min-height:34px; margin:0 0 10px; color:#e5edf0; font-size:.72rem; letter-spacing:.08em; line-height:1.4; text-transform:uppercase; }
        .akakami-dashboard .chart-title span { color:var(--cyan); }
        .akakami-dashboard .chart-box { position:relative; height:260px; }
        .akakami-dashboard .chart-panel.wide .chart-box { height:300px; }
        .akakami-dashboard .empty-chart { display:grid; height:100%; place-items:center; color:var(--muted); font-size:.75rem; text-align:center; }
        .akakami-dashboard .period-badge { align-self:center; color:var(--muted); font-family:'JetBrains Mono',monospace; font-size:.68rem; }
        @media (max-width:720px) { .akakami-dashboard { padding:14px 12px 24px; } .akakami-dashboard .analytics-filter { display:grid; grid-template-columns:1fr 1fr; gap:8px; } .akakami-dashboard .analytics-filter > div { min-width:0; } .akakami-dashboard .analytics-filter .btn { width:100%; min-width:0; grid-column:1 / -1; } .akakami-dashboard .chart-grid { grid-template-columns:1fr; gap:10px; } .akakami-dashboard .chart-panel.wide { grid-column:auto; } .akakami-dashboard .chart-box, .akakami-dashboard .chart-panel.wide .chart-box { height:245px; } .akakami-dashboard .chart-title { font-size:.68rem; } }
        @media (max-width:430px) { .akakami-dashboard .summary-grid { gap:8px; } .akakami-dashboard .summary-card { padding:13px; } .akakami-dashboard .summary-label { font-size:.58rem; } .akakami-dashboard .summary-value { font-size:1.15rem; } .akakami-dashboard .analytics-filter { padding:10px; } .akakami-dashboard .analytics-filter label { font-size:.59rem; } .akakami-dashboard .analytics-filter select { padding:9px 8px; font-size:.75rem; } .akakami-dashboard .chart-box, .akakami-dashboard .chart-panel.wide .chart-box { height:220px; } }
    </style>

    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / Akakami Analytics</div>
            <h1>Akakami <span class="cyan">Dashboard</span></h1>
            <div class="muted">Trend masalah QAQC — {{ $monthNames[$selectedMonth] ?? $selectedMonth }} {{ $selectedYear }}</div>
        </div>
        <div class="toolbar"><span class="period-badge">{{ count($monthlyRows) }} record</span></div>
    </div>

    <form class="analytics-filter" wire:submit="loadData">
        <div>
            <label for="akakamiYear">Tahun</label>
            <select id="akakamiYear" wire:model="selectedYear" wire:change="yearChanged">
                @forelse ($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @empty
                    <option value="{{ $selectedYear }}">{{ $selectedYear }}</option>
                @endforelse
            </select>
        </div>
        <div>
            <label for="akakamiMonth">Bulan dengan record</label>
            <select id="akakamiMonth" wire:model="selectedMonth">
                @forelse (($monthsByYear[$selectedYear] ?? []) as $month)
                    <option value="{{ $month }}">{{ $monthNames[$month] ?? $month }}</option>
                @empty
                    <option value="{{ $selectedMonth }}">{{ $monthNames[$selectedMonth] ?? $selectedMonth }}</option>
                @endforelse
            </select>
        </div>
        <button type="submit" class="btn btn-cyan" wire:loading.attr="disabled" wire:target="loadData,yearChanged">Tampilkan</button>
    </form>

    <section class="summary-grid">
        <div class="summary-card case"><span class="summary-label">Total Case Bulan Ini</span><strong class="summary-value orange" id="akakamiTotalCases">{{ number_format($summary['cases'], 0, ',', '.') }}</strong></div>
        <div class="summary-card pcs"><span class="summary-label">Total Pcs Bulan Ini</span><strong class="summary-value cyan" id="akakamiTotalPcs">{{ number_format($summary['pcs'], 0, ',', '.') }}</strong></div>
    </section>

    <section class="chart-grid" wire:ignore>
        @foreach ([
            'customerQty' => '5 Customer Problem Terbanyak (Pcs)',
            'customerCase' => '5 Customer Problem Terbanyak (Case)',
            'problemQty' => '5 Masalah NG Terbanyak (Qty Pcs)',
            'problemCase' => '5 Masalah NG Terbanyak (Case)',
            'partQty' => '5 Part Masalah NG Terbanyak (Qty Pcs)',
            'partCase' => '5 Part Masalah NG Terbanyak (Case)',
            'sequenceCase' => 'Urutan Proses Penemu Terbanyak sampai Sedikit (Case)',
            'operatorCase' => 'Operator Penemu Masalah Terbanyak sampai Sedikit (Case)',
            'fishboneCase' => 'Kategori 4M Masalah Terbanyak sampai Sedikit (Case)',
            'rankCase' => 'Rank Masalah Terbanyak sampai Sedikit (Case)',
        ] as $key => $title)
            <div class="chart-panel {{ in_array($key, ['sequenceCase', 'operatorCase', 'fishboneCase', 'rankCase'], true) ? 'wide' : '' }}">
                <h2 class="chart-title">{{ $title }} <span>• {{ $monthNames[$selectedMonth] ?? $selectedMonth }} {{ $selectedYear }}</span></h2>
                <div class="chart-box"><canvas id="akakamiChart-{{ $key }}"></canvas><div id="akakamiEmpty-{{ $key }}" class="empty-chart" hidden>Tidak ada data pada periode ini.</div></div>
            </div>
        @endforeach
    </section>

    @script
    <script>
        const akakamiInitialCharts = @js($chartData);
        const akakamiCharts = {};
        const akakamiColors = ['#00e5ff', '#ff9f1c', '#ff5964', '#caff2e', '#a78bfa'];
        const akakamiWideColors = ['#00e5ff', '#ff9f1c', '#ff5964', '#caff2e', '#a78bfa', '#2dd4bf', '#ff7aa2', '#66a3ff', '#fbbf24', '#fb7185'];

        function akakamiOptions(indexAxis = 'y', wide = false) {
            return {
                indexAxis,
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 300 },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => ` ${Number(context.raw).toLocaleString('id-ID')} case/pcs` } } },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.06)' }, ticks: { color: '#91a0a6', font: { size: 10 }, callback: value => Number(value).toLocaleString('id-ID') } },
                    y: { grid: { display: false }, ticks: { color: '#d1dade', font: { size: wide ? 10 : 9 }, autoSkip: false, maxTicksLimit: wide ? 20 : 8 } }
                },
                elements: { bar: { borderRadius: 4, borderSkipped: false } }
            };
        }

        function drawAkakamiChart(key, values) {
            const data = Array.isArray(values) ? values : [];
            const empty = document.getElementById(`akakamiEmpty-${key}`);
            const canvas = document.getElementById(`akakamiChart-${key}`);
            if (!canvas || !empty) return;
            empty.hidden = data.length > 0;
            canvas.hidden = data.length === 0;
            if (!data.length) {
                if (akakamiCharts[key]) { akakamiCharts[key].destroy(); delete akakamiCharts[key]; }
                return;
            }
            const wide = ['sequenceCase', 'operatorCase', 'fishboneCase', 'rankCase'].includes(key);
            const labels = data.map(item => item.label);
            const numbers = data.map(item => item.value);
            if (akakamiCharts[key]) {
                akakamiCharts[key].data.labels = labels;
                akakamiCharts[key].data.datasets[0].data = numbers;
                akakamiCharts[key].update('none');
                return;
            }
            akakamiCharts[key] = new Chart(canvas, { type: 'bar', data: { labels, datasets: [{ data: numbers, backgroundColor: (wide ? akakamiWideColors : akakamiColors), borderColor: (wide ? akakamiWideColors : akakamiColors), borderWidth: 1 }] }, options: akakamiOptions('y', wide) });
        }

        function drawAkakamiDashboard(data) { Object.entries(data || {}).forEach(([key, values]) => drawAkakamiChart(key, values)); }
        drawAkakamiDashboard(akakamiInitialCharts);
        $wire.on('akakami-dashboard-updated', event => drawAkakamiDashboard(event.data));
    </script>
    @endscript
</div>
