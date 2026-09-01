<?php

namespace App\Livewire;

use App\Services\HealthCheckService;
use Carbon\Carbon;
use Livewire\Component;

class HealthCheck extends Component
{
    public int $year;

    public int $month;

    public string $lampiranDate = '';

    public string $creatorName = '';

    public string $activeType = 'PAR';

    public ?array $par = null;

    public ?array $sms = null;

    protected $listeners = ['refresh' => '$refresh'];

    public function mount(): void
    {
        $now = now('Asia/Jakarta');
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
        $this->lampiranDate = $now->format('Y-m-d');
        $this->creatorName = $this->currentUsername();
    }

    public function updatedMonth(): void
    {
        $this->recompute();
    }

    public function updatedYear(): void
    {
        $this->recompute();
    }

    public function recompute(): void
    {
        $service = app(HealthCheckService::class);
        if (! $service->isValidMonth($this->year, $this->month)) {
            return;
        }
        $this->par = $service->evaluate(HealthCheckService::TYPE_PAR, $this->year, $this->month);
        $this->sms = $service->evaluate(HealthCheckService::TYPE_SMS, $this->year, $this->month);
    }

    public function updatedActiveType(string $value): void
    {
        $this->activeType = in_array($value, [HealthCheckService::TYPE_PAR, HealthCheckService::TYPE_SMS], true)
            ? $value
            : HealthCheckService::TYPE_PAR;
    }

    public function saveSnapshot(string $type): void
    {
        $service = app(HealthCheckService::class);
        $title = $type === 'PAR Health Check' ? 'PAR Health Check' : 'SMS Health Check';
        $record = $service->store($type, $this->year, $this->month, [
            'title' => $title.' - '.$this->periodLabel(),
            'created_by' => $this->creatorName,
            'lampiran_date' => $this->lampiranDate,
        ]);
        $this->refreshType($type, $record);
        $this->flash($title.' snapshot untuk '.$this->periodLabel().' berhasil disimpan.', 'success');
    }

    public function openCertificate(string $type): void
    {
        $this->activeType = $type;
        $this->dispatch('open-certificate', type: $type);
    }

    private function refreshType(string $type, array $record): void
    {
        $data = $record['payload'] ?? $record;
        if ($type === HealthCheckService::TYPE_PAR) {
            $this->par = $data;
        } else {
            $this->sms = $data;
        }
    }

    private function periodLabel(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    private function currentUsername(): string
    {
        $user = auth()->user();

        return (string) ($user?->username ?: session('username', ''));
    }

    public function render()
    {
        $service = app(HealthCheckService::class);
        if (! $service->isValidMonth($this->year, $this->month)) {
            $this->year = (int) now('Asia/Jakarta')->year;
            $this->month = (int) now('Asia/Jakarta')->month;
        }

        if ($this->par === null) {
            $this->recompute();
        }

        return view('livewire.health-check', [
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}
