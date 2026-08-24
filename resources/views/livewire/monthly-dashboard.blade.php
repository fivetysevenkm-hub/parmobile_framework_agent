<div class="app-shell" wire:poll.10m="loadData">
    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / Monthly Analytics</div>
            <h1>Monthly <span class="cyan">Monitoring</span></h1>
            <div class="muted">Analisa performa produksi bulanan — tahun {{ $selectedYear }}</div>
        </div>
        <div class="toolbar">
            <div class="timer">AUTO REFRESH <span id="monthly-refresh-countdown" class="cyan">10:00</span></div>
        </div>
    </div>

    <form wire:submit="loadData" class="filter monthly-filter">
        <div>
            <label for="selectedYear">Pilih tahun produksi</label>
            <select id="selectedYear" wire:model="selectedYear" class="monthly-year-select">
                @forelse ($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @empty
                    <option value="{{ $selectedYear }}">{{ $selectedYear }}</option>
                @endforelse
            </select>
        </div>
        <button class="btn btn-cyan" type="submit" wire:loading.attr="disabled" wire:target="loadData">Tampilkan</button>
    </form>

    <section class="charts monthly-charts" wire:ignore>
        <div class="panel"><h2 class="cyan">Quantity trend (Pcs)</h2><div class="chart-wrap"><canvas id="monthlyQtyChart"></canvas></div></div>
        <div class="panel"><h2 class="orange">Lot count trend (Lot)</h2><div class="chart-wrap"><canvas id="monthlyLotChart"></canvas></div></div>
        <div class="panel monthly-chart-wide"><h2 class="red">Akakami trend (Case)</h2><div class="chart-wrap chart-wrap-tall"><canvas id="monthlyAkaChart"></canvas></div></div>
    </section>

    <section class="panel table-panel monthly-table-panel">
        <div class="monthly-table-heading">
            <div>
                <h2 style="margin:0">Monthly data breakdown</h2>
                <div class="mobile-table-hint">Geser tabel ke kiri atau kanan untuk melihat semua sequence</div>
            </div>
            <div class="monthly-heading-actions">
                <span class="muted year-badge">YEAR {{ $selectedYear }}</span>
                <button class="btn btn-ghost monthly-export" id="monthlyExport" type="button">Export CSV</button>
            </div>
        </div>
        <div class="table-scroll" id="monthlyTableToExport">
            <table>
                <thead>
                    <tr><th>Bulan / Detail</th>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @foreach ($months as $monthNumber => $monthName)
                        @php($month = $monthlyData[$monthNumber] ?? ['qty' => 0, 'lot' => 0, 'aka' => 0, 'sequences' => []])
                        <tr class="month-qty-row">
                            <td class="month-label"><strong>{{ $monthName }}</strong><small class="cyan">QTY (PCS)</small></td>
                            @foreach ($columns as $column)
                                @php($value = $month['sequences'][$column]['qty'] ?? 0)
                                <td class="{{ $value > 0 ? 'bright' : 'zero' }}">{{ $value > 0 ? number_format($value, 0, ',', '.') : '-' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="row-label orange">TOTAL LOT</td>
                            @foreach ($columns as $column)
                                @php($value = $month['sequences'][$column]['lot'] ?? 0)
                                <td class="{{ $value > 0 ? 'orange strong' : 'zero' }}">{{ $value > 0 ? number_format($value, 0, ',', '.') : '-' }}</td>
                            @endforeach
                        </tr>
                        <tr class="month-end-row">
                            <td class="row-label red">AKAKAMI (CASE)</td>
                            @foreach ($columns as $column)
                                @php($value = $month['sequences'][$column]['aka'] ?? 0)
                                <td class="{{ $value > 0 ? 'red strong' : 'zero' }}">{{ $value > 0 ? number_format($value, 0, ',', '.') : '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @script
    <script>
        const monthlyInitialData = @js($this->chartData());
        const monthlyCharts = {};

        function monthlyLineOptions(color) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 450, easing: 'easeOutQuart' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    datalabels: { anchor: 'end', align: 'top', color: '#fff', font: { size: 9, weight: '700' }, formatter: value => value > 0 ? Number(value).toLocaleString() : '' }
                },
                scales: {
                    y: { beginAtZero: true, grace: '15%', grid: { color: 'rgba(255,255,255,.06)' }, ticks: { color: '#91a0a6', font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { color: '#91a0a6', font: { size: window.innerWidth <= 480 ? 8 : 10 }, maxRotation: window.innerWidth <= 480 ? 45 : 0, minRotation: window.innerWidth <= 480 ? 45 : 0 } }
                },
                elements: { line: { borderColor: color, backgroundColor: color.replace('1)', '.18)'), tension: .4, fill: true }, point: { radius: window.innerWidth <= 480 ? 2.5 : 4, hoverRadius: 5, backgroundColor: color } }
            };
        }

        function upsertMonthlyChart(id, data, values, color) {
            if (monthlyCharts[id]) {
                monthlyCharts[id].data.labels = data.labels;
                monthlyCharts[id].data.datasets[0].data = values;
                monthlyCharts[id].update('none');
                return;
            }
            const canvas = document.getElementById(id);
            if (!canvas) return;
            monthlyCharts[id] = new Chart(canvas, { type: 'line', data: { labels: data.labels, datasets: [{ data: values, borderColor: color, backgroundColor: color, fill: true, tension: .4, pointRadius: window.innerWidth <= 480 ? 2.5 : 4, pointBackgroundColor: color }] }, options: monthlyLineOptions(color) });
        }

        function drawMonthlyCharts(data) {
            if (typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);
            upsertMonthlyChart('monthlyQtyChart', data, data.qty, '#00e5ff');
            upsertMonthlyChart('monthlyLotChart', data, data.lot, '#ff9f1c');
            upsertMonthlyChart('monthlyAkaChart', data, data.aka, '#ff5964');
        }

        drawMonthlyCharts(monthlyInitialData);
        $wire.on('monthly-updated', event => drawMonthlyCharts(event.data));

        document.getElementById('monthlyExport')?.addEventListener('click', () => {
            const table = document.querySelector('#monthlyTableToExport table');
            if (!table) return;
            const csv = [...table.rows].map(row => [...row.cells].map(cell => `"${cell.innerText.replaceAll('"', '""')}"`).join(',')).join('\n');
            const blob = new Blob([String.fromCharCode(0xFEFF) + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `Report_Bulanan_${@js($selectedYear)}.csv`;
            link.click();
            URL.revokeObjectURL(url);
        });

        let monthlyTimeLeft = 600;
        setInterval(() => {
            monthlyTimeLeft = monthlyTimeLeft <= 0 ? 600 : monthlyTimeLeft - 1;
            const el = document.getElementById('monthly-refresh-countdown');
            if (el) el.textContent = `${String(Math.floor(monthlyTimeLeft / 60)).padStart(2, '0')}:${String(monthlyTimeLeft % 60).padStart(2, '0')}`;
        }, 1000);
    </script>
    @endscript
</div>
