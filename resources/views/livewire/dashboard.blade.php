<div class="app-shell">
    <div class="topbar">
        <div>
            <div class="eyebrow">PAR Mobile / Operations Intelligence</div>
            <h1>Daily Report <span class="cyan">Dashboard</span></h1>
            <div class="muted">Monitoring real time seluruh proses produksi</div>
        </div>
        <div class="toolbar">
            <div class="timer">AUTO REFRESH <span id="refresh-countdown" class="cyan">600</span> DETIK</div>
        </div>
    </div>

    <form wire:submit="applyFilter" class="filter">
        <div>
            <label for="dateRange">Pilih tanggal dan masukan jam</label>
            <input id="dateRange" type="text" wire:model="dateRange" placeholder="YYYY-MM-DD HH:MM to YYYY-MM-DD HH:MM" autocomplete="off" readonly>
            @error('dateRange') <div class="error">{{ $message }}</div> @enderror
        </div>
        <button class="btn btn-cyan" type="submit" wire:loading.attr="disabled" wire:target="applyFilter">Cari</button>
        <a class="btn btn-ghost" id="btnExport" href="{{ route('export.excel', ['date_range' => $dateRange]) }}">Export XLS</a>
    </form>

    <section class="stats">
        <div class="stat"><span class="stat-label">Total Qty (Pcs)</span><span class="stat-value cyan">{{ number_format($summary['grand_total_qty']) }}</span></div>
        <div class="stat"><span class="stat-label">Total New Lot</span><span class="stat-value orange">{{ number_format($summary['lot_setting_count']) }}</span></div>
        <div class="stat"><span class="stat-label">Total Man Power Active</span><span class="stat-value orange">{{ number_format($summary['total_manpower']) }}</span><div class="stat-note">Based on {{ number_format($hoursRunning, 1) }}h range filter</div></div>
        <div class="stat"><span class="stat-label">Total Lot Transaction (Distinct)</span><span class="stat-value orange">{{ number_format($summary['grand_total_lot']) }}</span></div>
        <div class="stat"><span class="stat-label">Feeding Rate (Lot/hour)</span><span class="stat-value lime">{{ number_format($summary['feeding_rate'], 1) }}</span><div class="stat-note">Based on {{ number_format($hoursRunning, 1) }}h uptime</div></div>
        <div class="stat"><span class="stat-label">Total Akakami (Case)</span><span class="stat-value red">{{ number_format($summary['grand_total_aka']) }}</span></div>
    </section>

    <section class="charts" data-chart-ready="false" wire:ignore>
        <div class="panel"><h2>Quantity by sequence (Pcs)</h2><div class="chart-wrap"><canvas id="qtyChart"></canvas></div></div>
        <div class="panel"><h2>Lot count by sequence (Lot)</h2><div class="chart-wrap"><canvas id="lotChart"></canvas></div></div>
        <div class="panel"><h2>Akakami count by sequence (Case)</h2><div class="chart-wrap"><canvas id="akaChart"></canvas></div></div>
        <div class="panel"><h2>Top 10 customers (Lot)</h2><div class="chart-wrap"><canvas id="customerChart"></canvas></div></div>
    </section>

    <section class="panel table-panel">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:14px;">
            <div>
                <h2 style="margin:0">Data breakdown</h2>
                <div class="mobile-table-hint">Geser tabel ke kiri atau kanan untuk melihat semua kolom</div>
            </div>
            <span class="muted" style="font-family:'JetBrains Mono',monospace;font-size:.7rem">{{ $dateStart }} — {{ $dateEnd }}</span>
        </div>
        <div class="table-scroll" id="tableToExport">
            <table>
                <thead><tr><th>Category / Customer</th>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
                <tbody>
                    <tr><td class="row-label">Total Qty (Pcs)</td>@foreach ($columns as $column)<td class="cyan"><strong>{{ number_format($sequenceStats[$column]['qty'] ?? 0) }}</strong></td>@endforeach</tr>
                    <tr><td class="row-label">Total Lot (Lot)</td>@foreach ($columns as $column)<td class="orange"><strong>{{ number_format($sequenceStats[$column]['lot'] ?? 0) }}</strong></td>@endforeach</tr>
                    <tr><td class="row-label red">Akakami (Case)</td>@foreach ($columns as $column)<td class="red"><strong>{{ number_format($sequenceStats[$column]['aka'] ?? 0) }}</strong></td>@endforeach</tr>
                    @foreach ($customers as $customer)
                        <tr><td>{{ $customer }}</td>@foreach ($columns as $column)@php($value = $customerStats[$customer][$column] ?? 0)<td class="{{ $value > 0 ? 'orange' : 'zero' }}">{{ $value > 0 ? number_format($value) : '-' }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @script
    <script>
        const cssVar = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        const chartColors = Array.from({ length: 10 }, (_, index) => cssVar(`--chart-${index + 1}`));
        const initialData = @js($this->chartData());
        const dashboardCharts = {};
        const dateRangeInput = document.getElementById('dateRange');
        const exportUrl = @js(route('export.excel'));

        if (typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);

        function barOptions(logarithmic = false) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 450, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    datalabels: { color: cssVar('--text-title'), anchor: 'end', align: 'top', font: { size: 10, weight: '700' }, formatter: value => Number(value).toLocaleString() }
                },
                scales: {
                    y: { beginAtZero: !logarithmic, type: logarithmic ? 'logarithmic' : 'linear', min: logarithmic ? 1 : 0, grid: { color: cssVar('--chart-grid') }, ticks: { color: cssVar('--chart-label') } },
                    x: { grid: { color: cssVar('--chart-grid') }, ticks: { color: cssVar('--chart-label') } }
                }
            };
        }

        function upsertBarChart(id, data, values, color, logarithmic) {
            const chart = dashboardCharts[id];
            if (chart) {
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = values;
                chart.options = barOptions(logarithmic);
                chart.update('none');
                return;
            }

            const canvas = document.getElementById(id);
            if (!canvas) return;
            dashboardCharts[id] = new Chart(canvas, {
                type: 'bar',
                data: { labels: data.labels, datasets: [{ data: values, backgroundColor: color, borderColor: color, borderWidth: 1, borderRadius: 4 }] },
                options: barOptions(logarithmic)
            });
        }

        function upsertCustomerChart(data) {
            const chart = dashboardCharts.customerChart;
            if (chart) {
                chart.data.labels = data.customerLabels;
                chart.data.datasets[0].data = data.customerData;
                chart.update('none');
                return;
            }

            const canvas = document.getElementById('customerChart');
            if (!canvas) return;
            dashboardCharts.customerChart = new Chart(canvas, {
                type: 'doughnut',
                data: { labels: data.customerLabels, datasets: [{ data: data.customerData, backgroundColor: chartColors, borderColor: cssVar('--chart-border'), borderWidth: 2 }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    animation: { duration: 450, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { position: window.innerWidth <= 480 ? 'bottom' : 'right', labels: { color: cssVar('--chart-label'), usePointStyle: true, padding: 8, font: { size: window.innerWidth <= 480 ? 9 : 10 } } },
                        datalabels: { color: cssVar('--text-title'), font: { weight: '700', size: 10 }, formatter: (value, ctx) => { const sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0); return sum && value / sum > .05 ? `${Math.round(value / sum * 100)}%` : ''; } }
                    }
                }
            });
        }

        function drawCharts(data) {
            upsertBarChart('qtyChart', data, data.qty, cssVar('--chart-1'), true);
            upsertBarChart('lotChart', data, data.lot, cssVar('--chart-2'), false);
            upsertBarChart('akaChart', data, data.aka, cssVar('--chart-3'), false);
            upsertCustomerChart(data);
        }

        function initializeDateRangePicker() {
            if (!dateRangeInput || typeof flatpickr === 'undefined') return;
            const selectedValues = dateRangeInput.value ? dateRangeInput.value.split(' to ') : [];

            if (dateRangeInput._flatpickr) {
                dateRangeInput._flatpickr.setDate(selectedValues, false);
                return;
            }

            flatpickr(dateRangeInput, {
                mode: 'range',
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                separator: ' to ',
                defaultDate: selectedValues,
                theme: 'dark',
                disableMobile: true,
                position: 'auto center',
                onChange: selectedDates => {
                    // Do not push a half-completed range to Livewire.
                    if (selectedDates.length === 1) return;

                    const value = selectedDates.map(date => flatpickr.formatDate(date, 'Y-m-d H:i')).join(' to ');
                    dateRangeInput.value = value;
                    dateRangeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    dateRangeInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        const chartsSection = document.querySelector('.charts');

        function resizeDashboardCharts() {
            requestAnimationFrame(() => {
                Object.values(dashboardCharts).forEach(chart => chart?.resize());
            });
        }

        function markChartsReady() {
            if (chartsSection) chartsSection.dataset.chartReady = 'true';
        }

        function initializeDashboardCharts() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    drawCharts(initialData);
                    resizeDashboardCharts();
                    markChartsReady();
                });
            });
        }

        initializeDashboardCharts();
        initializeDateRangePicker();
        window.addEventListener('load', resizeDashboardCharts, { once: true });
        window.addEventListener('resize', resizeDashboardCharts);
        window.addEventListener('layout-resized', resizeDashboardCharts);
        const chartsContainer = document.querySelector('.dashboard-shell .charts, .charts');
        if (chartsContainer && typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(resizeDashboardCharts).observe(chartsContainer);
        }
        const refreshIntervalSeconds = 600;
        const countdownState = window.__parDailyReportCountdownState || (window.__parDailyReportCountdownState = {});
        if (!Number.isFinite(Number(countdownState.deadline)) || Number(countdownState.deadline) <= Date.now()) {
            countdownState.deadline = Date.now() + (refreshIntervalSeconds * 1000);
        }

        function renderRefreshCountdown() {
            const remaining = Math.max(0, Math.ceil((Number(countdownState.deadline) - Date.now()) / 1000));
            const el = document.getElementById('refresh-countdown');
            if (el) el.textContent = String(remaining);
        }

        function resetRefreshCountdown() {
            countdownState.deadline = Date.now() + (refreshIntervalSeconds * 1000);
            renderRefreshCountdown();
        }

        function requestScheduledRefresh() {
            if (countdownState.refreshing) return;

            countdownState.refreshing = true;
            Promise.resolve($wire.refreshData())
                .then(() => {
                    resetRefreshCountdown();
                })
                .catch(() => {
                    // Retry shortly after a transient request failure without
                    // resetting the countdown as if the data had refreshed.
                    countdownState.deadline = Date.now() + 5000;
                    renderRefreshCountdown();
                })
                .finally(() => {
                    countdownState.refreshing = false;
                });
        }

        $wire.on('dashboard-updated', event => {
            drawCharts(event.data);
            resizeDashboardCharts();
            markChartsReady();
        });

        $wire.on('dashboard-filter-updated', event => {
            const dateRange = event?.data?.dateRange ?? event?.detail?.dateRange ?? event?.dateRange;
            if (!dateRange) return;

            requestAnimationFrame(() => {
                const input = document.getElementById('dateRange');
                if (!input) return;

                input.value = dateRange;
                if (input._flatpickr) {
                    input._flatpickr.setDate(dateRange.split(' to '), false);
                }
            });
        });

        renderRefreshCountdown();
        if (!window.__parDailyReportCountdownTimer) {
            window.__parDailyReportCountdownTimer = window.setInterval(() => {
                renderRefreshCountdown();

                const remaining = Math.max(0, Math.ceil((Number(countdownState.deadline) - Date.now()) / 1000));
                if (remaining === 0) requestScheduledRefresh();
            }, 1000);
        }

        document.getElementById('btnExport')?.addEventListener('click', event => {
            event.preventDefault();
            const range = dateRangeInput?.value || '';
            const url = new URL(exportUrl, window.location.origin);
            if (range) url.searchParams.set('date_range', range);
            window.location.assign(url.toString());
        });
    </script>
    @endscript
</div>
