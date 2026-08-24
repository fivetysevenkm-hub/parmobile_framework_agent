<?php

namespace App\Livewire;

use App\Services\CarbTemperReport;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class CarbTemperMonitoring extends Component
{
    public string $machine = 'ALL';
    public string $statusFilter = 'ALL CONDITION';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $currentPage = 1;
    public int $perPage = 10;
    public array $details = [];
    public array $summary = ['total' => 0, 'safe' => 0, 'warning' => 0, 'critical' => 0, 'done' => 0, 'total_scope' => 0];
    public string $lastUpdated = '';
    public int $lastUpdatedTimestamp = 0;
    public string $calendarDate = '';
    public bool $isAcknowledged = false;
    public bool $hadPreviousAlert = false;

    public function mount(CarbTemperReport $report): void
    {
        $this->setRollingDates();
        $result = $this->refreshData($report);
        $this->sendTelegramUpdate($report, $result['all_details']);
    }

    public function refreshData(?CarbTemperReport $report = null): array
    {
        $report ??= app(CarbTemperReport::class);
        $today = now('Asia/Jakarta')->format('Y-m-d');

        if ($this->calendarDate !== '' && $this->calendarDate !== $today) {
            $this->setRollingDates();
            $this->currentPage = 1;
        }
        if ($this->calendarDate === '') $this->calendarDate = $today;

        [$from, $to] = $this->dateWindow();
        $result = $report->monitoringData($this->machine, $from, $to, $this->statusFilter);
        $this->details = $result['details']->map(fn ($row) => (array) $row)->values()->all();
        $this->summary = $result['summary'];
        $updatedAt = now('Asia/Jakarta');
        $this->lastUpdated = $updatedAt->format('d-m-Y H:i:s');
        $this->lastUpdatedTimestamp = $updatedAt->timestamp;

        return $result;
    }

    public function scheduledRefresh(CarbTemperReport $report): void
    {
        $result = $this->refreshData($report);
        $this->sendTelegramUpdate($report, $result['all_details']);
    }

    private function sendTelegramUpdate(CarbTemperReport $report, Collection $rows): void
    {
        $alerts = $rows->whereIn('level_status', ['WARNING', 'CRITICAL'])->values();

        if ($alerts->isNotEmpty()) {
            app(TelegramService::class)->send($report->alertMessage($alerts), 'carb_temper', 'carb_temper');
            $this->hadPreviousAlert = true;
        } elseif ($this->hadPreviousAlert) {
            app(TelegramService::class)->send($report->clearMessage(), 'carb_temper', 'carb_temper');
            $this->hadPreviousAlert = false;
        }
    }

    public function applyFilters(CarbTemperReport $report): void
    {
        $this->currentPage = 1;
        $this->resetErrorBag();
        try {
            $from = Carbon::createFromFormat('Y-m-d', $this->dateFrom);
            $to = Carbon::createFromFormat('Y-m-d', $this->dateTo);
            if ($to->lt($from)) [$this->dateFrom, $this->dateTo] = [$this->dateTo, $this->dateFrom];
            $this->calendarDate = now('Asia/Jakarta')->format('Y-m-d');
        } catch (\Throwable) {
            $this->addError('dateFrom', 'Tanggal harus menggunakan format yang valid.');
            return;
        }
        $this->refreshData($report);
    }

    public function resetFilters(CarbTemperReport $report): void
    {
        $this->machine = 'ALL';
        $this->statusFilter = 'ALL CONDITION';
        $this->setRollingDates();
        $this->currentPage = 1;
        $this->isAcknowledged = false;
        $this->refreshData($report);
    }

    public function acknowledgeAlarm(): void
    {
        $this->isAcknowledged = true;
    }

    public function previousPage(): void
    {
        $this->currentPage = max(1, $this->currentPage - 1);
    }

    public function nextPage(): void
    {
        $this->currentPage = min($this->totalPages(), $this->currentPage + 1);
    }

    public function totalPages(): int
    {
        return max(1, (int) ceil(count($this->details) / $this->perPage));
    }

    public function render(CarbTemperReport $report)
    {
        $totalPages = $this->totalPages();
        $this->currentPage = min($this->currentPage, $totalPages);
        $pagedDetails = array_slice($this->details, ($this->currentPage - 1) * $this->perPage, $this->perPage);

        return view('livewire.carb-temper-monitoring', [
            'machineOptions' => $report->machineOptions(),
            'pagedDetails' => $pagedDetails,
            'totalPages' => $totalPages,
            'lastUpdatedAt' => $this->lastUpdated,
        ])->layout('layouts.app');
    }

    private function setRollingDates(): void
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $this->dateFrom = $today->copy()->subDays(2)->format('Y-m-d');
        $this->dateTo = $today->format('Y-m-d');
        $this->calendarDate = $today->format('Y-m-d');
    }

    private function dateWindow(): array
    {
        try {
            $from = Carbon::createFromFormat('Y-m-d', $this->dateFrom, 'Asia/Jakarta')->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $this->dateTo, 'Asia/Jakarta')->startOfDay();
        } catch (\Throwable) {
            $this->setRollingDates();
            $from = Carbon::createFromFormat('Y-m-d', $this->dateFrom, 'Asia/Jakarta')->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $this->dateTo, 'Asia/Jakarta')->startOfDay();
        }
        if ($to->lt($from)) [$from, $to] = [$to, $from];
        return [$from, $to->addDay()];
    }

}
