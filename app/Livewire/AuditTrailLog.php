<?php

namespace App\Livewire;

use App\Models\AuditTrail;
use App\Services\MenuAuthorizationService;
use Livewire\Component;

class AuditTrailLog extends Component
{
    public const ROWS_PER_PAGE = 20;

    public string $search = '';

    public string $event = '';

    public string $module = '';

    public string $usernameFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $currentPage = 1;

    public int $totalPages = 1;

    public int $totalLogs = 0;

    public array $logs = [];

    public array $events = [];

    public array $modules = [];

    public ?int $selectedLogId = null;

    public function mount(): void
    {
        $this->events = [
            'login' => 'Login sukses',
            'login_failed' => 'Login gagal',
            'logout' => 'Logout',
            'password_change' => 'Ganti password',
            'access' => 'Akses menu',
            'access_denied' => 'Akses menu ditolak',
            'create' => 'Data dibuat',
            'update' => 'Data diubah',
            'delete' => 'Data dihapus',
        ];

        $this->modules = collect(app(MenuAuthorizationService::class)->registry())
            ->mapWithKeys(fn (array $menu): array => [(string) $menu['key'] => (string) ($menu['name'] ?? $menu['key'])])
            ->all();
        $this->modules['authentication'] = 'Authentication';
        $this->modules['user-management'] = 'User Management';

        $this->loadLogs();
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        $this->loadLogs();
    }

    public function updatedEvent(): void
    {
        $this->currentPage = 1;
        $this->loadLogs();
    }

    public function updatedModule(): void
    {
        $this->currentPage = 1;
        $this->loadLogs();
    }

    public function updatedUsernameFilter(): void
    {
        $this->currentPage = 1;
        $this->loadLogs();
    }

    public function updatedDateFrom(): void
    {
        $this->currentPage = 1;
        $this->loadLogs();
    }

    public function updatedDateTo(): void
    {
        $this->currentPage = 1;
        $this->loadLogs();
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->loadLogs();
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadLogs();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->event = '';
        $this->module = '';
        $this->usernameFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->currentPage = 1;
        $this->loadLogs();
    }

    public function showDetail(int $id): void
    {
        $this->selectedLogId = $id;
    }

    public function closeDetail(): void
    {
        $this->selectedLogId = null;
    }

    public function getSelectedLogProperty(): ?AuditTrail
    {
        if (! $this->selectedLogId) {
            return null;
        }

        return AuditTrail::find($this->selectedLogId);
    }

    public function render()
    {
        return view('livewire.audit-trail')->layout('layouts.app');
    }

    private function buildQuery()
    {
        $query = AuditTrail::query();

        if (trim($this->event) !== '') {
            $query->where('event', $this->event);
        }
        if (trim($this->module) !== '') {
            $query->where('module', $this->module);
        }
        if (trim($this->usernameFilter) !== '') {
            $value = trim($this->usernameFilter);
            $query->where(function ($q) use ($value): void {
                $q->where('username', 'like', '%'.$value.'%')
                    ->orWhere('user_id', 'like', '%'.$value.'%');
            });
        }
        if (trim($this->dateFrom) !== '') {
            $query->where('created_at', '>=', $this->dateFrom.' 00:00:00');
        }
        if (trim($this->dateTo) !== '') {
            $query->where('created_at', '<=', $this->dateTo.' 23:59:59');
        }
        if (trim($this->search) !== '') {
            $value = trim($this->search);
            $query->where(function ($q) use ($value): void {
                $q->where('action', 'like', '%'.$value.'%')
                    ->orWhere('url', 'like', '%'.$value.'%')
                    ->orWhere('module', 'like', '%'.$value.'%');
            });
        }

        return $query;
    }

    private function loadLogs(): void
    {
        $query = $this->buildQuery();
        $total = (clone $query)->count();
        $this->totalLogs = $total;
        $this->totalPages = max(1, (int) ceil($total / self::ROWS_PER_PAGE));
        $this->currentPage = min(max(1, $this->currentPage), $this->totalPages);

        $this->logs = $query
            ->orderBy('id', 'desc')
            ->forPage($this->currentPage, self::ROWS_PER_PAGE)
            ->get()
            ->toArray();
    }
}
