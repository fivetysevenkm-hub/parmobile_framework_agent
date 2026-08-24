<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AkakamiDashboard extends Component
{
    public int $selectedYear;

    public int $selectedMonth;

    public array $years = [];

    public array $monthsByYear = [];

    public array $monthlyRows = [];

    public array $chartData = [];

    public array $summary = [
        'cases' => 0,
        'pcs' => 0,
    ];

    public function mount(): void
    {
        $periods = Cache::remember('akakami-dashboard.available-periods', now()->addMinutes(10), function () {
            return DB::table('vw_exportexcel_akakami')
                ->whereNotNull('tgl_problem')
                ->selectRaw('YEAR(tgl_problem) AS production_year, MONTH(tgl_problem) AS production_month')
                ->distinct()
                ->orderByDesc('production_year')
                ->orderByDesc('production_month')
                ->get()
                ->map(fn ($period) => [(int) $period->production_year, (int) $period->production_month])
                ->all();
        });

        foreach ($periods as [$year, $month]) {
            $this->monthsByYear[$year][] = $month;
        }

        foreach ($this->monthsByYear as $year => $months) {
            $this->monthsByYear[$year] = array_values(array_unique($months));
        }

        $this->years = array_map('intval', array_keys($this->monthsByYear));
        $now = now('Asia/Jakarta');
        $this->selectedYear = in_array((int) $now->year, $this->years, true)
            ? (int) $now->year
            : ($this->years[0] ?? (int) $now->year);
        $availableMonths = $this->monthsByYear[$this->selectedYear] ?? [];
        $this->selectedMonth = in_array((int) $now->month, $availableMonths, true)
            ? (int) $now->month
            : ($availableMonths[0] ?? (int) $now->month);

        $this->loadData();
    }

    public function yearChanged(): void
    {
        $availableMonths = $this->monthsByYear[$this->selectedYear] ?? [];
        if (! in_array($this->selectedMonth, $availableMonths, true)) {
            $this->selectedMonth = $availableMonths[0] ?? 1;
        }
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->selectedYear = max(2000, min(2100, (int) $this->selectedYear));
        $this->selectedMonth = max(1, min(12, (int) $this->selectedMonth));
        $start = Carbon::create($this->selectedYear, $this->selectedMonth, 1, 0, 0, 0, 'Asia/Jakarta');
        $end = $start->copy()->addMonth();

        $rows = DB::table('vw_exportexcel_akakami')
            ->select([
                'noflpp',
                'tgl_problem',
                'no_part',
                'nm_part',
                'customer',
                'qty_problem',
                'nm_sequence',
                'deskripsi_problem',
                'penemu',
                'fish_bone',
                'rank',
            ])
            ->where('tgl_problem', '>=', $start)
            ->where('tgl_problem', '<', $end)
            ->get();

        $this->monthlyRows = $rows->map(fn ($row) => (array) $row)->all();
        $this->summary = [
            'cases' => $rows->pluck('noflpp')->filter()->unique()->count(),
            'pcs' => (int) $rows->sum(fn ($row) => (int) ($row->qty_problem ?? 0)),
        ];
        $this->chartData = [
            'customerQty' => $this->topMetrics($rows, 'customer', 'qty'),
            'customerCase' => $this->topMetrics($rows, 'customer', 'case'),
            'problemQty' => $this->topMetrics($rows, 'deskripsi_problem', 'qty'),
            'problemCase' => $this->topMetrics($rows, 'deskripsi_problem', 'case'),
            'partQty' => $this->topMetrics($rows, 'part_label', 'qty'),
            'partCase' => $this->topMetrics($rows, 'part_label', 'case'),
            'sequenceCase' => $this->rankedMetrics($rows, 'nm_sequence'),
            'operatorCase' => $this->rankedMetrics($rows, 'penemu'),
            'fishboneCase' => $this->rankedMetrics($rows, 'fish_bone'),
            'rankCase' => $this->rankedMetrics($rows, 'rank'),
        ];

        $this->dispatch('akakami-dashboard-updated', data: $this->chartData, summary: $this->summary);
    }

    public function render()
    {
        return view('livewire.akakami-dashboard')->layout('layouts.app');
    }

    private function topMetrics(Collection $rows, string $field, string $metric): array
    {
        $normalized = $rows->map(function ($row) use ($field) {
            if ($field === 'part_label') {
                $row->part_label = trim((string) ($row->no_part ?: $row->nm_part ?: 'Tidak diketahui'));
            }
            return $row;
        });

        return $normalized
            ->filter(fn ($row) => trim((string) ($row->{$field} ?? '')) !== '')
            ->groupBy(fn ($row) => trim((string) $row->{$field}))
            ->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'value' => $metric === 'qty'
                    ? (int) $items->sum(fn ($item) => (int) ($item->qty_problem ?? 0))
                    : $items->pluck('noflpp')->filter()->unique()->count(),
            ])
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();
    }

    private function rankedMetrics(Collection $rows, string $field): array
    {
        return $rows
            ->filter(fn ($row) => trim((string) ($row->{$field} ?? '')) !== '')
            ->groupBy(fn ($row) => trim((string) $row->{$field}))
            ->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'value' => $items->pluck('noflpp')->filter()->unique()->count(),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }
}
