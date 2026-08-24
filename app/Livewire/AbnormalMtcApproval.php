<?php

namespace App\Livewire;

use App\Services\AbnormalMtcApprovalService;
use App\Services\AbnormalMtcTelegramMessage;
use App\Services\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AbnormalMtcApproval extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 20;
    public array $queue = [];
    public string $selectedAbnormal = '';
    public array $activeDetail = [];
    public bool $confirmed = false;

    public function mount(AbnormalMtcApprovalService $service): void
    {
        $this->loadQueue($service);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function refreshQueue(AbnormalMtcApprovalService $service): void
    {
        $this->resetPage();
        $this->loadQueue($service);

        if ($this->selectedAbnormal !== '' && ! collect($this->queue)->contains('noabn', $this->selectedAbnormal)) {
            $this->resetSelection();
        }
    }

    public function selectAbnormal(string $noAbn, AbnormalMtcApprovalService $service): void
    {
        $detail = $service->detailForApprover($noAbn, $this->loggedInNik());

        if ($detail === null) {
            $this->addError('selectedAbnormal', 'Data Abnormal Mtc tidak ditemukan atau bukan antrean approval Anda.');
            return;
        }

        $this->selectedAbnormal = $noAbn;
        $this->activeDetail = $detail;
        $this->confirmed = false;
        $this->resetErrorBag();
    }

    public function process(
        string $type,
        AbnormalMtcApprovalService $service,
        TelegramService $telegram,
        AbnormalMtcTelegramMessage $messageFormatter,
    ): void {
        $this->validate([
            'selectedAbnormal' => ['required', 'string', 'max:30'],
            'confirmed' => ['accepted'],
        ], [
            'selectedAbnormal.required' => 'Pilih antrean Abnormal Mtc terlebih dahulu.',
            'confirmed.accepted' => 'Konfirmasi pemeriksaan data terlebih dahulu.',
        ]);

        $detail = $service->detailForApprover($this->selectedAbnormal, $this->loggedInNik());
        if ($detail === null) {
            $this->addError('selectedAbnormal', 'Antrean Abnormal Mtc sudah berubah atau tidak tersedia untuk user ini.');
            return;
        }

        try {
            $result = $service->process($this->selectedAbnormal, $this->loggedInNik(), $type);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('selectedAbnormal', $exception->getMessage());
            return;
        }

        $telegram->send(
            $messageFormatter->format($detail, $result['status_label'], $this->loggedInName(), (int) $result['seq']),
            'approval_abnormal_mtc',
            'abnormal_mtc',
        );

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

            return str_contains(mb_strtolower((string) ($row['noabn'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['category'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['nm_object'] ?? '')), $term)
                || str_contains(mb_strtolower((string) ($row['ref_lot'] ?? '')), $term);
        })->values();

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $rows = new LengthAwarePaginator(
            $filtered->forPage($page, $this->perPage)->values(),
            $filtered->count(),
            $this->perPage,
            $page,
            ['pageName' => 'page'],
        );

        return view('livewire.abnormal-mtc-approval', [
            'rows' => $rows,
            'totalRows' => $filtered->count(),
        ])->layout('layouts.app');
    }

    private function loadQueue(AbnormalMtcApprovalService $service): void
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
        $this->selectedAbnormal = '';
        $this->activeDetail = [];
        $this->confirmed = false;
        $this->resetPage();
        $this->resetErrorBag();
    }
}
