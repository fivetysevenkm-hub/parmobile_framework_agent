<?php

namespace App\Livewire;

use App\Services\AkakamiTelegramMessage;
use App\Services\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AkakamiApproval extends Component
{
    use WithPagination;

    public string $activeTab = 'pengajuan';

    public string $search = '';

    public int $perPage = 20;

    public array $exportYears = [];

    public array $exportMonthsByYear = [];

    public bool $showExportModal = false;

    public int $exportYear = 0;

    public int $exportMonth = 0;

    public array $activeDetail = [];

    public array $departments = [];

    public array $problems = [];

    public array $masterApprovers = [];

    public array $masterSelected = [];

    public array $judgmentSelected = [];

    public array $judgmentApprovers = [];

    public array $approvalProgress = [];

    public string $selectedNoflpp = '';

    public string $picDept = '';

    public string $idProblem = '';

    public string $qty = '';

    public string $masalah = '';

    public string $analisa = '';

    public string $tempAction = '';

    public string $correctiveAction = '';

    public string $fishbone = '';

    public string $judgmentType = 'ok';

    public bool $rankA = false;

    public function mount(): void
    {
        $this->loadExportPeriods();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectTab(string $tab): void
    {
        if (! in_array($tab, ['pengajuan', 'action', 'eskalasi', 'release', 'approved', 'rejected'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function refreshGrid(): void
    {
        $this->resetPage();
    }

    private function gridQuery()
    {
        return DB::table('vw_flpp_list_hold')
            ->select('noflpp', 'tgl_problem', 'nmproblem', 'no_lots', 'qty_problem', 'rank', 'status_flpp', 'deskripsi_problem')
            ->where('status_flpp', $this->statusForTab($this->activeTab))
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($query) use ($term) {
                    $query->where('noflpp', 'like', $term)
                        ->orWhere('no_lots', 'like', $term)
                        ->orWhere('nmproblem', 'like', $term);
                });
            })
            ->orderByDesc('tgl_problem');
    }

    private function loadExportPeriods(): void
    {
        $periods = Cache::remember('akakami-approval.export-periods', now()->addMinutes(10), function () {
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
            $this->exportMonthsByYear[$year][] = $month;
        }

        foreach ($this->exportMonthsByYear as $year => $months) {
            $this->exportMonthsByYear[$year] = array_values(array_unique($months));
        }

        $this->exportYears = array_map('intval', array_keys($this->exportMonthsByYear));
    }

    public function openExportModal(): void
    {
        $this->loadExportPeriods();
        $now = now('Asia/Jakarta');
        $this->exportYear = $this->exportYear ?: ($this->exportYears[0] ?? (int) $now->year);
        $availableMonths = $this->exportMonthsByYear[$this->exportYear] ?? [];
        $this->exportMonth = in_array($this->exportMonth, $availableMonths, true)
            ? $this->exportMonth
            : ($availableMonths[0] ?? (int) $now->month);
        $this->showExportModal = true;
    }

    public function updatedExportYear(): void
    {
        $availableMonths = $this->exportMonthsByYear[$this->exportYear] ?? [];
        if (! in_array($this->exportMonth, $availableMonths, true)) {
            $this->exportMonth = $availableMonths[0] ?? 1;
        }
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
    }

    public function openAction(string $noflpp): void
    {
        $detail = $this->findActiveDetail($noflpp);

        if ($detail === null) {
            $this->addError('selectedNoflpp', "Data FLPP {$noflpp} tidak ditemukan.");
            return;
        }

        $this->selectedNoflpp = $noflpp;
        $this->activeDetail = $detail;
        $this->picDept = (string) ($detail['pic_dept_id'] ?? $detail['pic_dept'] ?? '');
        $this->idProblem = (string) ($detail['idproblems'] ?? '');
        $this->qty = (string) ($detail['qty_problem'] ?? '0');
        $this->masalah = (string) ($detail['deskripsi_problem'] ?? '');
        $this->analisa = (string) ($detail['analisa'] ?? '');
        $this->tempAction = (string) ($detail['temp_action'] ?? '');
        $this->correctiveAction = (string) ($detail['corrective_act'] ?? '');
        $this->fishbone = (string) ($detail['fish_bone'] ?? '');
        $this->judgmentType = 'ok';
        $this->rankA = (string) ($detail['rank_a'] ?? '0') === '1';
        $this->masterSelected = [];
        $this->judgmentSelected = [];
        $this->judgmentApprovers = [];
        $this->departments = DB::table('tbldept')->select('Id_Dept', 'Dept')->orderBy('Dept')->get()->map(fn ($row) => (array) $row)->all();
        $this->problems = DB::table('tblrankproblem')->select('idrankproblem', 'nmrankproblem')->orderBy('nmrankproblem')->get()->map(fn ($row) => (array) $row)->all();
        $this->masterApprovers = DB::table('vw_pic_approve')->select('nik', 'user')->orderBy('user')->get()->map(fn ($row) => (array) $row)->all();
        $this->activeTab = 'action';
        $this->resetErrorBag();
    }

    public function updatedJudgmentType(string $value): void
    {
        if ($value === 'ok') {
            $this->masterSelected = [];
            $this->judgmentSelected = [];
            $this->judgmentApprovers = [];
        }
    }

    public function moveApproversToJudgment(): void
    {
        if ($this->judgmentType !== 'eskalasi') {
            return;
        }

        $this->judgmentApprovers = array_values(array_unique(array_merge($this->judgmentApprovers, $this->masterSelected)));
        $this->masterSelected = [];
    }

    public function moveApproversBack(): void
    {
        if ($this->judgmentType !== 'eskalasi') {
            return;
        }

        $this->judgmentApprovers = array_values(array_diff($this->judgmentApprovers, $this->judgmentSelected));
        $this->judgmentSelected = [];
    }

    public function commitFlpp(TelegramService $telegram): void
    {
        $this->validate([
            'selectedNoflpp' => ['required', 'string', 'max:100'],
            'masalah' => ['required', 'string'],
            'analisa' => ['required', 'string'],
            'judgmentType' => ['required', Rule::in(['ok', 'eskalasi'])],
            'picDept' => ['nullable', 'string', 'max:50'],
            'idProblem' => ['nullable', 'string', 'max:50'],
            'fishbone' => ['nullable', Rule::in(['', 'Man', 'Machine', 'Material', 'Method'])],
        ], [
            'masalah.required' => 'Detail masalah wajib diisi.',
            'analisa.required' => 'Analisa penyebab wajib diisi.',
        ]);

        if ($this->judgmentType === 'eskalasi' && $this->judgmentApprovers === []) {
            $this->addError('judgmentApprovers', 'Silakan tentukan urutan approver.');
            return;
        }

        $noflpp = $this->selectedNoflpp;
        $isApproved = $this->judgmentType === 'ok';
        $now = now('Asia/Jakarta');
        $user = auth()->user();
        // pic_handler is a legacy identifier field (VARCHAR(12)), not a display-name field.
        // The login flow maps the legacy id_user to session nik, so keep that identifier here.
        $handlerId = (string) ($user->id_user ?? session('nik') ?? auth()->id() ?? '');
        $approverNames = [];
        $rankNumber = null;

        DB::transaction(function () use ($noflpp, $isApproved, $now, $handlerId, &$approverNames, &$rankNumber) {
            DB::table('tblakakami')->where('noflpp', $noflpp)->update([
                'status_flpp' => $isApproved ? 'approved' : 'eskalasi',
                'pic_dept' => $this->picDept,
                'judgment_level' => $isApproved ? 'apv' : 'esk',
                'idproblems' => $this->idProblem,
                'deskripsi_problem' => $this->masalah,
                'qty_problem' => $this->qty,
                'temp_action' => $this->tempAction,
                'corrective_act' => $this->correctiveAction,
                'analisa' => $this->analisa,
                'fish_bone' => $this->fishbone,
                'rank_a' => $this->rankA ? '1' : '0',
                'pic_handler' => $handlerId,
                'tgl_approved' => $now,
            ]);

            $detail = DB::table('tblakakami')->where('noflpp', $noflpp)->first();
            if (! $detail) {
                throw new \RuntimeException('Data FLPP untuk update tidak ditemukan.');
            }

            if ($isApproved) {
                $this->releaseLot($detail);
            }

            DB::table('tbl_approval_akakami')->where('id_flpp', $noflpp)->delete();

            if (! $isApproved) {
                $approverRows = DB::table('vw_pic_approve')
                    ->whereIn('nik', $this->judgmentApprovers)
                    ->orderByDesc('class')
                    ->get();
                $sequence = 1;
                foreach ($approverRows as $approver) {
                    $approverNames[] = $approver->user;
                    DB::table('tbl_approval_akakami')->insert([
                        'id_flpp' => $noflpp,
                        'seq_no' => $sequence,
                        'nik_approver' => $approver->nik,
                        'approve_status' => $sequence === 1 ? 'waiting' : 'pending',
                    ]);
                    $sequence++;
                }
            }

            if ($this->rankA) {
                $rankNumber = 'RKA-'.$now->format('YmdHis');
                DB::table('tblranka')->insert([
                    'no_rank' => $rankNumber,
                    'noflpp' => $noflpp,
                    'tgl_pengajuan' => $now,
                    'status' => 'open',
                ]);
            }
        });

        $message = $this->formatTelegramMessage(
            $isApproved ? 'JUDGMENT OK (APPROVED)' : 'ESKALASI',
            ! $isApproved,
            implode(', ', $approverNames),
        );
        $telegram->send($message, 'approval_flpp');

        if ($rankNumber !== null) {
            $telegram->send($this->formatTelegramMessage('PENGAJUAN RANK A', false, '', true, $rankNumber), 'rank_a', 'rank_a');
        }

        $this->resetActionForm();
        $this->activeTab = 'pengajuan';
        $this->resetPage();
        session()->flash('success', 'Data FLPP berhasil diproses.');
    }

    public function alirkanRelease(string $noflpp, TelegramService $telegram): void
    {
        $detail = DB::table('tblakakami')->where('noflpp', $noflpp)->first();
        if (! $detail) {
            $this->addError('grid', 'Data FLPP tidak ditemukan.');
            return;
        }

        DB::transaction(function () use ($detail, $noflpp) {
            $updated = DB::table('tblakakami')->where('noflpp', $noflpp)->where('status_flpp', 'release')->update(['status_flpp' => 'approved']);
            if ($updated === 0) {
                throw new \RuntimeException('Data tidak ditemukan atau status bukan release.');
            }
            $this->releaseLot($detail);
        });

        $messageDetail = $this->findActiveDetail($noflpp) ?? [
            'no_flpp' => $detail->noflpp,
            'no_lots' => $detail->no_lots,
            'nm_part' => '-',
            'qty_problem' => $detail->qty_problem,
            'nm_customer' => '-',
            'deskripsi_problem' => $detail->deskripsi_problem,
        ];
        $telegram->send($this->formatTelegramMessage('RELEASED & APPROVED', false, '', false, null, $messageDetail), 'approval_flpp');
        $this->resetPage();
        session()->flash('success', 'FLPP berhasil di-approve dan hold dilepas.');
    }

    public function loadProgress(string $noflpp): void
    {
        $this->approvalProgress = DB::table('tbl_approval_akakami AS a')
            ->leftJoin('tbl_user AS u', 'a.nik_approver', '=', 'u.id_user')
            ->where('a.id_flpp', $noflpp)
            ->orderBy('a.seq_no')
            ->get(['a.seq_no', 'a.approve_status', 'u.username AS nama'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function render()
    {
        $rows = $this->activeTab === 'action'
            ? new LengthAwarePaginator([], 0, $this->perPage, LengthAwarePaginator::resolveCurrentPage())
            : $this->gridQuery()->paginate($this->perPage)->through(fn ($row) => (array) $row);

        return view('livewire.akakami-approval', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
        ])->layout('layouts.app');
    }

    private function releaseLot(object $detail): void
    {
        $lot = (string) ($detail->no_lots ?? '');
        $location = (string) ($detail->nm_tabel ?? '');
        $department = (string) ($detail->id_depts ?? '');

        if ($lot === '') {
            throw new \RuntimeException('Nomor lot tidak ditemukan.');
        }

        if ($location !== '') {
            if (! preg_match('/^[A-Za-z0-9_]+$/', $location)) {
                throw new \RuntimeException('Nama tabel produksi tidak valid.');
            }
            DB::table($location)->where('no_lot', $lot)->update(['hold' => 'no']);
        }

        DB::table('tbl_seq_all')->where('no_lot', $lot)->where('id_seq', $department)->update(['hold' => 'no']);
    }

    private function formatTelegramMessage(string $status, bool $isEscalation = false, string $targetApprovers = '', bool $isRankA = false, ?string $rankNumber = null, ?array $detailOverride = null): string
    {
        $detail = $detailOverride ?? ($this->activeDetail ?: ($this->findActiveDetail($this->selectedNoflpp) ?? []));
        $user = auth()->user();
        $userName = (string) ($user->username ?? $user->name ?? session('username') ?? 'System');

        return app(AkakamiTelegramMessage::class)->format(
            $detail,
            $status,
            $userName,
            $isEscalation,
            $targetApprovers,
            $isRankA,
            (string) ($rankNumber ?? ''),
        );
    }

    private function findActiveDetail(string $noflpp): ?array
    {
        if ($noflpp === '') {
            return null;
        }

        $approval = DB::table('tblakakami')->where('noflpp', $noflpp)->first();
        if (! $approval) {
            return null;
        }

        $sequence = DB::table('tbl_seq_all')
            ->where('no_lot', $approval->no_lots)
            ->orderBy('id')
            ->first(['no_lot_cust', 'customer', 'no_part', 'nm_part']);
        $problem = $approval->idproblems
            ? DB::table('tblproblem')->where('idproblem', $approval->idproblems)->first(['nmproblem'])
            : null;
        $employee = $approval->pic
            ? DB::table('tblkaryawan')->where('nik', $approval->pic)->first(['nama_kary'])
            : null;
        $department = $approval->pic_dept
            ? DB::table('tbldept')->where('Id_Dept', $approval->pic_dept)->first(['Dept'])
            : null;

        return [
            'no_flpp' => $approval->noflpp,
            'noflpp' => $approval->noflpp,
            'tgl_problem' => $approval->tgl_problem,
            'no_lots' => $approval->no_lots,
            'no_lot_cust' => $sequence->no_lot_cust ?? null,
            'nm_customer' => $sequence->customer ?? 'INTERNAL',
            'no_brg_asli' => $sequence->no_part ?? null,
            'nm_part' => $sequence->nm_part ?? null,
            'qty_problem' => $approval->qty_problem,
            'idproblems' => $approval->idproblems,
            'nmproblem' => $problem->nmproblem ?? null,
            'deskripsi_problem' => $approval->deskripsi_problem,
            'rank' => $approval->rank,
            'analisa' => $approval->analisa,
            'temp_action' => $approval->temp_action,
            'fish_bone' => $approval->fish_bone,
            'corrective_act' => $approval->corrective_act,
            'pic' => $approval->pic,
            'nama_kary' => $employee->nama_kary ?? 'INTERNAL',
            'pic_dept_id' => $approval->pic_dept,
            'pic_dept' => $department->Dept ?? $approval->pic_dept,
            'judgment_level' => $approval->judgment_level,
            'rank_a' => $approval->rank_a,
            'pic_handler' => $approval->pic_handler,
            'status_flpp' => $approval->status_flpp,
            'nm_tabel' => $approval->nm_tabel,
            'id_depts' => $approval->id_depts,
        ];
    }

    private function resetActionForm(): void
    {
        $this->selectedNoflpp = '';
        $this->activeDetail = [];
        $this->masterSelected = [];
        $this->judgmentSelected = [];
        $this->judgmentApprovers = [];
        $this->approvalProgress = [];
    }

    private function statusForTab(string $tab): string
    {
        return match ($tab) {
            'eskalasi' => 'eskalasi',
            'release' => 'release',
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => 'hold',
        };
    }
}
