<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $dateRange = '';

    public array $columns = [];

    public array $customers = [];

    public array $summary = [
        'grand_total_qty' => 0,
        'grand_total_lot' => 0,
        'grand_total_aka' => 0,
        'total_manpower' => 0,
        'lot_setting_count' => 0,
        'feeding_rate' => 0,
    ];

    public array $sequenceStats = [];

    public array $customerStats = [];

    public string $dateStart = '';

    public string $dateEnd = '';

    public float $hoursRunning = 1.0;

    public function mount(): void
    {
        $now = Carbon::now('Asia/Jakarta')->seconds(0);
        $start = $now->copy()->startOfDay();
        $this->dateRange = $start->format('Y-m-d H:i').' to '.$now->format('Y-m-d H:i');
        $this->loadData();
    }

    public function applyFilter(): void
    {
        $this->validate([
            'dateRange' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}( to \d{4}-\d{2}-\d{2} \d{2}:\d{2})?$/'],
        ], [
            'dateRange.regex' => 'Format tanggal harus YYYY-MM-DD HH:MM to YYYY-MM-DD HH:MM.',
        ]);

        $this->loadData();
    }

    public function loadData(): void
    {
        $dateRange = $this->parseDateRange();

        // Flatpickr can briefly emit an empty or half-filled value while the
        // user is selecting a range. Do not query until the value is valid.
        if ($dateRange === null) {
            return;
        }

        [$start, $end] = $dateRange;

        $this->dateStart = $start->format('Y-m-d H:i');
        $this->dateEnd = $end->format('Y-m-d H:i');

        $this->columns = DB::table('tbl_seq_all')
            ->select('id_seq', 'nm_seq')
            ->whereNotNull('nm_seq')
            ->where('nm_seq', '<>', '')
            ->distinct()
            ->orderBy('id_seq')
            ->pluck('nm_seq')
            ->values()
            ->all();

        $this->customers = DB::table('tbl_seq_all')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('customer')
            ->where('customer', '<>', '')
            ->distinct()
            ->orderBy('customer')
            ->pluck('customer')
            ->values()
            ->all();

        $this->sequenceStats = DB::table('tbl_seq_all')
            ->select('nm_seq')
            ->selectRaw('COALESCE(SUM(qty), 0) AS qty')
            ->selectRaw('COUNT(DISTINCT no_lot) AS lot')
            ->selectRaw("SUM(CASE WHEN akakami = 'yes' THEN 1 ELSE 0 END) AS aka")
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('nm_seq')
            ->groupBy('nm_seq')
            ->get()
            ->keyBy('nm_seq')
            ->map(fn ($row) => [
                'qty' => (int) $row->qty,
                'lot' => (int) $row->lot,
                'aka' => (int) $row->aka,
            ])
            ->all();

        $customerRows = DB::table('tbl_seq_all')
            ->select('customer', 'nm_seq')
            ->selectRaw('COUNT(DISTINCT no_lot) AS lot')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('customer')
            ->where('customer', '<>', '')
            ->whereNotNull('nm_seq')
            ->groupBy('customer', 'nm_seq')
            ->get();

        $this->customerStats = [];
        foreach ($customerRows as $row) {
            $this->customerStats[$row->customer][$row->nm_seq] = (int) $row->lot;
        }

        $firstSequenceId = DB::table('tbl_seq_all')
            ->whereNotNull('nm_seq')
            ->where('nm_seq', '<>', '')
            ->orderBy('id_seq')
            ->value('id_seq');

        $summary = DB::table('tbl_seq_all')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(DISTINCT no_lot) AS grand_total_lot')
            ->selectRaw('SUM(CASE WHEN id_seq = ? THEN qty ELSE 0 END) AS grand_total_qty', [$firstSequenceId])
            ->selectRaw("SUM(CASE WHEN akakami = 'yes' THEN 1 ELSE 0 END) AS grand_total_aka")
            ->selectRaw('COUNT(DISTINCT pic_01) AS total_manpower')
            ->first();

        $lotSettingCount = $this->sequenceStats['Setting']['lot'] ?? 0;
        if ($lotSettingCount === 0) {
            foreach ($this->sequenceStats as $name => $stats) {
                if (strtolower((string) $name) === 'setting') {
                    $lotSettingCount = $stats['lot'];
                    break;
                }
            }
        }

        $endForRate = $end->greaterThan(now('Asia/Jakarta')) ? now('Asia/Jakarta') : $end;
        $seconds = $endForRate->timestamp - $start->timestamp;
        $this->hoursRunning = max($seconds / 3600, 1.0);

        $this->summary = [
            'grand_total_qty' => (int) ($summary->grand_total_qty ?? 0),
            'grand_total_lot' => (int) ($summary->grand_total_lot ?? 0),
            'grand_total_aka' => (int) ($summary->grand_total_aka ?? 0),
            'total_manpower' => (int) ($summary->total_manpower ?? 0),
            'lot_setting_count' => (int) $lotSettingCount,
            'feeding_rate' => round($lotSettingCount / $this->hoursRunning, 1),
        ];

        $this->dispatch('dashboard-updated', data: $this->chartData());
    }

    public function chartData(): array
    {
        $topCustomers = collect($this->customers)
            ->mapWithKeys(fn ($customer) => [
                $customer => collect($this->customerStats[$customer] ?? [])->sum(),
            ])
            ->filter(fn ($total) => $total > 0)
            ->sortDesc()
            ->take(10);

        return [
            'labels' => $this->columns,
            'qty' => array_map(fn ($column) => $this->sequenceStats[$column]['qty'] ?? 0, $this->columns),
            'lot' => array_map(fn ($column) => $this->sequenceStats[$column]['lot'] ?? 0, $this->columns),
            'aka' => array_map(fn ($column) => $this->sequenceStats[$column]['aka'] ?? 0, $this->columns),
            'customerLabels' => $topCustomers->keys()->values()->all(),
            'customerData' => $topCustomers->values()->all(),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }

    private function parseDateRange(): ?array
    {
        $value = trim((string) $this->dateRange);

        if ($value === '') {
            return null;
        }

        $parts = preg_split('/\s+to\s+/', $value, 2) ?: [];
        $startValue = trim($parts[0] ?? '');
        $endValue = array_key_exists(1, $parts) ? trim($parts[1]) : $startValue;

        if ($startValue === '' || $endValue === '') {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d H:i', $startValue, 'Asia/Jakarta')->startOfMinute();
            $end = Carbon::createFromFormat('Y-m-d H:i', $endValue, 'Asia/Jakarta')->endOfMinute();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfMinute(), $start->copy()->endOfMinute()];
        }

        return [$start, $end];
    }
}
