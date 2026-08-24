<?php

namespace App\Livewire;

use App\Services\RankAApprovalService;
use App\Services\RankATelegramMessage;
use App\Services\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class RankAApproval extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 20;
    public array $queue = [];
    public string $selectedRank = '';
    public array $activeDetail = [];
    public bool $confirmed = false;

    public function mount(RankAApprovalService $service): void
    {
        $this->loadQueue($service);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function refreshQueue(RankAApprovalService $service): void
    {
        $this->resetPage();
        $this->loadQueue($service);

        if ($this->selectedRank !== '' && ! collect($this->queue)->contains('no_rank', $this->selectedRank)) {
            $this->resetSelection();
        }
    }

    public function selectRank(string $noRank, RankAApprovalService $service): void
    {
        $nik = $this->loggedInNik();
        $detail = $service->detailForApprover($noRank, $nik);

        if ($detail === null) {
            $this->addError('selectedRank', 'Data Rank A tidak ditemukan atau bukan antrean approval Anda.');
            return;
        }

        $this->selectedRank = $noRank;
        $this->activeDetail = $detail;
        $this->confirmed = false;
        $this->resetErrorBag();
    }

    public function process(
        string $type,
        RankAApprovalService $service,
        TelegramService $telegram,
        RankATelegramMessage $messageFormatter,
    ): void {
        $this->validate([
            'selectedRank' => ['required', 'string', 'max:50'],
            'confirmed' => ['accepted'],
        ], [
            'selectedRank.required' => 'Pilih antrean Rank A terlebih dahulu.',
            'confirmed.accepted' => 'Konfirmasi pemeriksaan data terlebih dahulu.',
        ]);

        $nik = $this->loggedInNik();
        $userName = $this->loggedInName();
        $detail = $service->detailForApprover($this->selectedRank, $nik);

        if ($detail === null) {
            $this->addError('selectedRank', 'Antrean Rank A sudah berubah atau tidak tersedia untuk user ini.');
            return;
        }

        try {
            $result = $service->process($this->selectedRank, $nik, $type);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('selectedRank', $exception->getMessage());
            return;
        }

        $telegramMessage = $messageFormatter->format(
            $detail,
            $result['status_label'],
            $userName,
            (int) $result['seq'],
            isset($result['next_seq']) ? (int) $result['next_seq'] : null,
        );
        $telegram->send($telegramMessage, 'rank_a', 'rank_a');

        $successMessage = $result['message'];
        $this->resetSelection();
        $this->loadQueue($service);
        session()->flash('success', $successMessage);
    }

    public function render()
    {
        $term = mb_strtolower(trim($this->search));
        $filtered = collect($this->queue)->filter(function (array $row) use ($term): bool {
            if ($term === '') {
                return true;
            }

            return str_contains(mb_strtolower((string) ($row['no_rank'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['noflpp'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['no_lots'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['deskripsi_problem'] ?? '')), $term);
        })->values();

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $rows = new LengthAwarePaginator(
            $filtered->forPage($page, $this->perPage)->values(),
            $filtered->count(),
            $this->perPage,
            $page,
            ['pageName' => 'page'],
        );

        return view('livewire.rank-a-approval', [
            'rows' => $rows,
            'totalRows' => $filtered->count(),
        ])->layout('layouts.app');
    }

    private function loadQueue(RankAApprovalService $service): void
    {
        $this->queue = $service->pendingForApprover($this->loggedInNik());
    }

    private function loggedInNik(): string
    {
        return (string) (auth()->user()->id_user ?? session('nik') ?? '');
    }

    private function loggedInName(): string
    {
        return (string) (auth()->user()->username ?? auth()->user()->name ?? session('username') ?? 'System');
    }

    private function resetSelection(): void
    {
        $this->selectedRank = '';
        $this->activeDetail = [];
        $this->confirmed = false;
        $this->resetPage();
        $this->resetErrorBag();
    }
}
