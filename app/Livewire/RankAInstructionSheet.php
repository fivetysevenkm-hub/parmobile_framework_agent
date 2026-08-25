<?php

namespace App\Livewire;

use App\Services\RankAInstructionService;
use App\Services\RankAInstructionTelegramMessage;
use App\Services\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class RankAInstructionSheet extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $activeTab = 'open';
    public string $search = '';
    public int $perPage = 5;
    public string $approvedFrom = '';
    public string $approvedTo = '';

    public string $selectedRank = '';
    public array $activeDetail = [];
    public string $instruction = '';
    public $illustration;
    public string $existingIllustration = '';
    public array $masterApprovers = [];
    public array $masterSelected = [];
    public array $judgmentSelected = [];
    public array $judgmentApprovers = [];
    public array $approvalProgress = [];
    public bool $viewOnly = false;

    public function mount(RankAInstructionService $service): void
    {
        $this->masterApprovers = $service->approvers();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedApprovedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedApprovedTo(): void
    {
        $this->resetPage();
    }

    public function selectTab(string $tab): void
    {
        if (! in_array($tab, ['open', 'eskalasi', 'approved', 'rejected', 'action'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
        $this->approvalProgress = [];
        if ($tab !== 'action') {
            $this->resetAction();
        }
    }

    public function refreshGrid(): void
    {
        $this->resetPage();
    }

    public function openDetail(string $noRank, RankAInstructionService $service): void
    {
        $detail = $service->detail($noRank);
        if ($detail === null) {
            $this->addError('selectedRank', 'Data Rank A tidak ditemukan.');
            return;
        }

        $this->selectedRank = $noRank;
        $this->activeDetail = $detail;
        $this->instruction = (string) ($detail['instruksi_perbaikan'] ?? '');
        $this->existingIllustration = (string) ($detail['link_ilustrasi'] ?? '');
        $this->illustration = null;
        $this->masterSelected = [];
        $this->judgmentSelected = [];
        $this->judgmentApprovers = [];
        $this->approvalProgress = $service->approvalProgress($noRank);
        $this->viewOnly = ! in_array((string) ($detail['status'] ?? ''), ['open', 'rejected'], true);
        $this->activeTab = 'action';
        $this->resetErrorBag();
    }

    public function moveApproversToJudgment(): void
    {
        if ($this->viewOnly) {
            return;
        }

        $this->judgmentApprovers = array_values(array_unique(array_merge(
            $this->judgmentApprovers,
            array_map('strval', $this->masterSelected),
        )));
        $this->masterSelected = [];
    }

    public function moveApproversBack(): void
    {
        if ($this->viewOnly) {
            return;
        }

        $selected = array_map('strval', $this->judgmentSelected);
        $this->judgmentApprovers = array_values(array_diff($this->judgmentApprovers, $selected));
        $this->judgmentSelected = [];
    }

    public function commit(
        RankAInstructionService $service,
        TelegramService $telegram,
        RankAInstructionTelegramMessage $messageFormatter,
    ): void {
        if ($this->viewOnly) {
            $this->addError('selectedRank', 'Data Rank A pada status ini hanya dapat dilihat.');
            return;
        }

        $this->validate([
            'selectedRank' => ['required', 'string', 'max:50'],
            'instruction' => ['required', 'string', 'max:400'],
            'illustration' => ['nullable', 'image', 'mimes:jpg,jpeg,png,bmp,webp', 'max:5120'],
            'judgmentApprovers' => ['required', 'array', 'min:1'],
            'judgmentApprovers.*' => ['string', 'max:12'],
        ], [
            'instruction.required' => 'Instruksi perbaikan wajib diisi.',
            'judgmentApprovers.required' => 'Minimal satu PIC Judgment harus dipilih.',
            'judgmentApprovers.min' => 'Minimal satu PIC Judgment harus dipilih.',
        ]);

        $detail = $service->detail($this->selectedRank);
        if ($detail === null || ! in_array((string) ($detail['status'] ?? ''), ['open', 'rejected'], true)) {
            $this->addError('selectedRank', 'Data sudah berubah atau tidak dapat diproses.');
            return;
        }

        $illustrationPath = $this->existingIllustration;
        if ($this->illustration) {
            $safeName = 'RANKA_'.$this->selectedRank.'_'.now('Asia/Jakarta')->format('YmdHis').'.'.$this->illustration->getClientOriginalExtension();
            $illustrationPath = $this->illustration->storeAs('rank_a', $safeName, 'public');
        }

        try {
            $result = $service->updateAndEscalate([
                'no_rank' => $this->selectedRank,
                'instruksi_perbaikan' => $this->instruction,
                'link_ilustrasi' => $illustrationPath,
            ], $this->judgmentApprovers);
        } catch (\Throwable $exception) {
            report($exception);
            if ($this->illustration && $illustrationPath !== $this->existingIllustration) {
                Storage::disk('public')->delete($illustrationPath);
            }
            $this->addError('selectedRank', $exception->getMessage());
            return;
        }

        $userName = (string) (auth()->user()->username ?? auth()->user()->name ?? session('username') ?? 'System');
        $telegram->send(
            $messageFormatter->format($result['detail'] ?? $detail, $userName, $result['approver_names'] ?? [], $result['submitted_at'] ?? null),
            'rank_a',
            'rank_a',
        );

        $this->resetAction();
        $this->activeTab = 'eskalasi';
        session()->flash('success', 'Lembar Instruksi Perbaikan Rank A berhasil dikirim ke proses approval.');
    }

    public function render(RankAInstructionService $service)
    {
        $status = $this->activeTab === 'action' ? 'open' : $this->activeTab;
        $from = $this->activeTab === 'approved' ? $this->approvedFrom : null;
        $to = $this->activeTab === 'approved' ? $this->approvedTo : null;

        $rows = $this->activeTab === 'action'
            ? new LengthAwarePaginator([], 0, $this->perPage, 1)
            : $service->listQuery($status, $this->search, $from, $to)
                ->paginate($this->perPage)
                ->through(fn ($row) => (array) $row);

        return view('livewire.rank-a-instruction-sheet', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
        ])->layout('layouts.app');
    }

    private function resetAction(): void
    {
        $this->selectedRank = '';
        $this->activeDetail = [];
        $this->instruction = '';
        $this->illustration = null;
        $this->existingIllustration = '';
        $this->masterSelected = [];
        $this->judgmentSelected = [];
        $this->judgmentApprovers = [];
        $this->approvalProgress = [];
        $this->viewOnly = false;
        $this->resetErrorBag();
    }
}
