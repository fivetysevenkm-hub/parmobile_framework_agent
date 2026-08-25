<?php

namespace App\Livewire;

use App\Services\AbnormalReportService;
use App\Services\AbnormalReportTelegramMessage;
use App\Services\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AbnormalReport extends Component
{
    use WithPagination;

    public string $activeTab = 'open';
    public string $search = '';
    public int $perPage = 20;
    public string $approvedFrom = '';
    public string $approvedTo = '';

    public string $selectedNoAbn = '';
    public array $activeDetail = [];
    public string $detailProblem = '';
    public string $analisa = '';
    public string $correction = '';
    public string $corrective = '';
    public string $picDept = '';
    public array $departments = [];
    public array $masterApprovers = [];
    public array $masterSelected = [];
    public array $judgmentSelected = [];
    public array $judgmentApprovers = [];
    public array $approvalProgress = [];

    public function mount(AbnormalReportService $service): void
    {
        $this->departments = $service->departments();
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
    }

    public function refreshGrid(): void
    {
        $this->resetPage();
    }

    public function openAction(string $noAbn, AbnormalReportService $service): void
    {
        $detail = $service->detail($noAbn);

        if ($detail === null) {
            $this->addError('selectedNoAbn', 'Data Abnormal Report tidak ditemukan.');
            return;
        }

        if (! in_array((string) ($detail['status'] ?? ''), ['open', 'rejected'], true)) {
            $this->addError('selectedNoAbn', 'Data hanya dapat diproses jika statusnya OPEN atau REJECTED.');
            return;
        }

        $this->selectedNoAbn = $noAbn;
        $this->activeDetail = $detail;
        $this->detailProblem = (string) ($detail['detail_problem'] ?? '');
        $this->analisa = (string) ($detail['analisa'] ?? '');
        $this->correction = (string) ($detail['correction'] ?? '');
        $this->corrective = (string) ($detail['corrective'] ?? '');
        $this->picDept = (string) ($detail['pic_dept'] ?? '');
        $this->masterSelected = [];
        $this->judgmentSelected = [];
        $this->judgmentApprovers = [];
        $this->approvalProgress = [];
        $this->activeTab = 'action';
        $this->resetErrorBag();
    }

    public function moveApproversToJudgment(): void
    {
        $this->judgmentApprovers = array_values(array_unique(array_merge(
            $this->judgmentApprovers,
            array_map('strval', $this->masterSelected),
        )));
        $this->masterSelected = [];
    }

    public function moveApproversBack(): void
    {
        $selected = array_map('strval', $this->judgmentSelected);
        $this->judgmentApprovers = array_values(array_diff($this->judgmentApprovers, $selected));
        $this->judgmentSelected = [];
    }

    public function commit(
        AbnormalReportService $service,
        TelegramService $telegram,
        AbnormalReportTelegramMessage $messageFormatter,
    ): void {
        $this->validate([
            'selectedNoAbn' => ['required', 'string', 'max:30'],
            'picDept' => ['required', 'string', 'max:12'],
            'detailProblem' => ['required', 'string', 'max:500'],
            'analisa' => ['required', 'string', 'max:500'],
            'correction' => ['nullable', 'string', 'max:500'],
            'corrective' => ['nullable', 'string', 'max:500'],
            'judgmentApprovers' => ['required', 'array', 'min:1'],
            'judgmentApprovers.*' => ['string', 'max:12'],
        ], [
            'picDept.required' => 'PIC Dept wajib dipilih.',
            'detailProblem.required' => 'Detail problem wajib diisi.',
            'analisa.required' => 'Analisa masalah wajib diisi.',
            'judgmentApprovers.required' => 'Minimal satu PIC Judgment harus dipilih.',
            'judgmentApprovers.min' => 'Minimal satu PIC Judgment harus dipilih.',
        ]);

        $detail = $service->detail($this->selectedNoAbn);
        if ($detail === null || ! in_array((string) ($detail['status'] ?? ''), ['open', 'rejected'], true)) {
            $this->addError('selectedNoAbn', 'Data sudah berubah atau tidak dapat diproses.');
            return;
        }

        $handlerId = (string) (auth()->user()->id_user ?? session('nik') ?? '');
        if ($handlerId === '') {
            $this->addError('selectedNoAbn', 'Identitas user login tidak ditemukan.');
            return;
        }

        try {
            $result = $service->commit([
                'noabn' => $this->selectedNoAbn,
                'detail_problem' => $this->detailProblem,
                'analisa' => $this->analisa,
                'correction' => $this->correction,
                'corrective' => $this->corrective,
                'pic_dept' => $this->picDept,
            ], $this->judgmentApprovers, $handlerId);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('selectedNoAbn', $exception->getMessage());
            return;
        }

        $userName = (string) (auth()->user()->username ?? auth()->user()->name ?? session('username') ?? 'System');
        $telegram->send(
            $messageFormatter->format(
                $result['detail'] ?? $detail,
                $userName,
                $result['approver_names'] ?? [],
                now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ),
            'abnormal_report',
            'abnormal_mtc',
        );

        $this->resetAction();
        $this->activeTab = 'eskalasi';
        session()->flash('success', 'Abnormal Report berhasil dieskalasikan dan approval sequence dibuat.');
    }

    public function loadProgress(string $noAbn, AbnormalReportService $service): void
    {
        $this->approvalProgress = $service->approvalProgress($noAbn);
    }

    public function render(AbnormalReportService $service)
    {
        $status = $this->activeTab === 'action' ? 'open' : $this->activeTab;
        $from = $this->activeTab === 'approved' ? $this->approvedFrom : null;
        $to = $this->activeTab === 'approved' ? $this->approvedTo : null;

        $rows = $this->activeTab === 'action'
            ? new LengthAwarePaginator([], 0, $this->pageSize(), 1)
            : $service->listQuery($status, $this->search, $from, $to)
                ->paginate($this->pageSize())
                ->through(fn ($row) => (array) $row);

        return view('livewire.abnormal-report', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
            'pageSize' => $this->pageSize(),
        ])->layout('layouts.app');
    }

    private function pageSize(): int
    {
        return match ($this->activeTab) {
            'open' => 15,
            default => $this->perPage,
        };
    }

    private function resetAction(): void
    {
        $this->selectedNoAbn = '';
        $this->activeDetail = [];
        $this->detailProblem = '';
        $this->analisa = '';
        $this->correction = '';
        $this->corrective = '';
        $this->picDept = '';
        $this->masterSelected = [];
        $this->judgmentSelected = [];
        $this->judgmentApprovers = [];
        $this->approvalProgress = [];
        $this->resetErrorBag();
        $this->resetPage();
    }
}
