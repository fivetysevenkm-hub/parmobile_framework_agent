<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MonthlyDashboard extends Component
{
    public int $selectedYear;

    public array $years = [];

    public array $columns = [];

    public array $monthlyData = [];

    public array $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function mount(): void
    {
        $this->years = Cache::remember('monthly-report.available-years', now()->addMinutes(10), function () {
            return DB::table('tbl_seq_all')
                ->whereNotNull('created_at')
                ->selectRaw('YEAR(created_at) AS production_year')
                ->distinct()
                ->orderByDesc('production_year')
                ->pluck('production_year')
                ->map(fn ($year) => (int) $year)
                ->values()
                ->all();
        });

        $this->selectedYear = $this->years[0] ?? (int) now('Asia/Jakarta')->format('Y');
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->selectedYear = max(2000, min(2100, (int) $this->selectedYear));
        $start = Carbon::create($this->selectedYear, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $end = $start->copy()->addYear();
        $yearKey = $this->selectedYear;

        $sequenceMeta = Cache::remember("monthly-report.sequence-meta.{$yearKey}", now()->addMinutes(10), function () use ($start, $end) {
            return DB::table('tbl_seq_all')
                ->select('nm_seq')
                ->selectRaw('MIN(id_seq) AS first_sequence_id')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->whereNotNull('nm_seq')
                ->where('nm_seq', '<>', '')
                ->groupBy('nm_seq')
                ->orderBy('first_sequence_id')
                ->get()
                ->map(fn ($row) => [
                    'name' => (string) $row->nm_seq,
                    'first_sequence_id' => $row->first_sequence_id,
                ])
                ->values()
                ->all();
        });

        $this->columns = array_column($sequenceMeta, 'name');
        $firstSequenceId = $sequenceMeta[0]['first_sequence_id'] ?? null;

        $summaryRows = DB::table('tbl_seq_all')
            ->selectRaw('MONTH(created_at) AS month_number')
            ->selectRaw('COALESCE(SUM(CASE WHEN id_seq = ? THEN qty ELSE 0 END), 0) AS total_qty', [$firstSequenceId])
            ->selectRaw('COUNT(DISTINCT no_lot) AS total_lot')
            ->selectRaw("SUM(CASE WHEN akakami = 'yes' THEN 1 ELSE 0 END) AS total_aka")
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->groupByRaw('MONTH(created_at)')
            ->get()
            ->keyBy('month_number');

        $sequenceRows = DB::table('tbl_seq_all')
            ->select('nm_seq')
            ->selectRaw('MONTH(created_at) AS month_number')
            ->selectRaw('COALESCE(SUM(qty), 0) AS qty')
            ->selectRaw('COUNT(DISTINCT no_lot) AS lot')
            ->selectRaw("SUM(CASE WHEN akakami = 'yes' THEN 1 ELSE 0 END) AS aka")
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereNotNull('nm_seq')
            ->where('nm_seq', '<>', '')
            ->groupByRaw('MONTH(created_at), nm_seq')
            ->get();

        $this->monthlyData = [];
        foreach ($this->months as $monthNumber => $monthName) {
            $summary = $summaryRows->get($monthNumber);
            $this->monthlyData[$monthNumber] = [
                'name' => $monthName,
                'qty' => (int) ($summary->total_qty ?? 0),
                'lot' => (int) ($summary->total_lot ?? 0),
                'aka' => (int) ($summary->total_aka ?? 0),
                'sequences' => [],
            ];
        }

        foreach ($sequenceRows as $row) {
            $month = (int) $row->month_number;
            $this->monthlyData[$month]['sequences'][$row->nm_seq] = [
                'qty' => (int) $row->qty,
                'lot' => (int) $row->lot,
                'aka' => (int) $row->aka,
            ];
        }

        $this->dispatch('monthly-updated', data: $this->chartData());
    }

    public function chartData(): array
    {
        $monthNumbers = array_keys($this->months);

        return [
            'labels' => array_values($this->months),
            'qty' => array_map(fn ($month) => $this->monthlyData[$month]['qty'] ?? 0, $monthNumbers),
            'lot' => array_map(fn ($month) => $this->monthlyData[$month]['lot'] ?? 0, $monthNumbers),
            'aka' => array_map(fn ($month) => $this->monthlyData[$month]['aka'] ?? 0, $monthNumbers),
        ];
    }

    public function render()
    {
        return view('livewire.monthly-dashboard')->layout('layouts.app');
    }
}
