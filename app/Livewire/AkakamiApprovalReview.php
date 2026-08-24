<?php

namespace App\Livewire;

use App\Services\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AkakamiApprovalReview extends Component
{
    use WithPagination;

    public string $search = '';

    public string $selectedFlpp = '';

    public int $perPage = 20;

    public bool $confirmed = false;

    public array $activeDetail = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function refreshQueue(): void
    {
        $this->resetPage();
    }

    public function selectFlpp(string $idFlpp): void
    {
        $detail = $this->findActiveDetail($idFlpp);

        if ($detail === null || ! $this->canApprove($idFlpp)) {
            $this->addError('selectedFlpp', 'Data FLPP tidak ditemukan atau bukan antrean approval Anda.');
            return;
        }

        $this->selectedFlpp = $idFlpp;
        $this->activeDetail = $detail;
        $this->confirmed = false;
        $this->resetErrorBag();
    }

    public function process(string $type, TelegramService $telegram): void
    {
        if (! in_array($type, ['approve', 'reject'], true)) {
            return;
        }

        $this->validate([
            'selectedFlpp' => ['required', 'string', 'max:100'],
            'confirmed' => ['accepted'],
        ], [
            'selectedFlpp.required' => 'Pilih antrean FLPP terlebih dahulu.',
            'confirmed.accepted' => 'Konfirmasi pemeriksaan data terlebih dahulu.',
        ]);

        $idFlpp = $this->selectedFlpp;
        $nik = (string) (auth()->user()->id_user ?? session('nik') ?? '');
        $userName = (string) (auth()->user()->username ?? auth()->user()->name ?? session('username') ?? 'System');
        $detail = $this->findActiveDetail($idFlpp);

        if ($detail === null || $nik === '' || ! $this->canApprove($idFlpp)) {
            $this->addError('selectedFlpp', 'Antrean approval sudah berubah atau tidak tersedia untuk user ini.');
            return;
        }

        $now = now('Asia/Jakarta');
        $isApprove = $type === 'approve';
        $isFinal = false;

        try {
            DB::transaction(function () use ($idFlpp, $nik, $now, $isApprove, &$isFinal): void {
            $current = DB::table('tbl_approval_akakami')
                ->where('id_flpp', $idFlpp)
                ->where('nik_approver', $nik)
                ->where('approve_status', 'waiting')
                ->lockForUpdate()
                ->first(['seq_no']);

            if (! $current) {
                throw new \RuntimeException('PIC approver sebelum Anda belum menyelesaikan approval atau antrean telah berubah.');
            }

            if (! $isApprove) {
                DB::table('tbl_approval_akakami')
                    ->where('id_flpp', $idFlpp)
                    ->where('nik_approver', $nik)
                    ->where('approve_status', 'waiting')
                    ->update(['approve_status' => 'rejected', 'tgl_approve' => $now]);
                DB::table('tblakakami')->where('noflpp', $idFlpp)->update([
                    'status_flpp' => 'rejected',
                    'tgl_approved' => $now,
                ]);
                return;
            }

            $currentSeq = (int) $current->seq_no;
            $maxSeq = (int) DB::table('tbl_approval_akakami')
                ->where('id_flpp', $idFlpp)
                ->max('seq_no');

            DB::table('tbl_approval_akakami')
                ->where('id_flpp', $idFlpp)
                ->where('nik_approver', $nik)
                ->where('approve_status', 'waiting')
                ->update(['approve_status' => 'approved', 'tgl_approve' => $now]);

            if ($currentSeq < $maxSeq) {
                DB::table('tbl_approval_akakami')
                    ->where('id_flpp', $idFlpp)
                    ->where('seq_no', $currentSeq + 1)
                    ->where('approve_status', 'pending')
                    ->update(['approve_status' => 'waiting']);
            } else {
                $isFinal = true;
                DB::table('tblakakami')->where('noflpp', $idFlpp)->update([
                    'status_flpp' => 'release',
                    'tgl_approved' => $now,
                ]);
            }
        });
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('selectedFlpp', $exception->getMessage());
            return;
        }

        $status = $isApprove
            ? ($isFinal ? 'APPROVED - MENUNGGU RELEASE' : 'APPROVED - MENUNGGU APPROVER BERIKUTNYA')
            : 'REJECTED';
        $telegram->send($this->formatTelegramMessage($detail, $status, $userName), 'approval_flpp');

        $this->resetSelection();
        session()->flash('success', $isApprove
            ? ($isFinal ? 'Approval terakhir berhasil. FLPP menunggu proses release.' : 'Approval berhasil diteruskan ke approver berikutnya.')
            : 'FLPP telah di-reject.');
    }

    public function render()
    {
        $nik = (string) (auth()->user()->id_user ?? session('nik') ?? '');
        $term = '%'.$this->search.'%';

        $rows = DB::table('tbl_approval_akakami AS aa')
            ->join('tblakakami AS a', 'a.noflpp', '=', 'aa.id_flpp')
            ->where('aa.nik_approver', $nik)
            ->where('aa.approve_status', 'waiting')
            ->when($this->search !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('a.noflpp', 'like', $term)
                        ->orWhere('a.no_lots', 'like', $term)
                        ->orWhere('a.deskripsi_problem', 'like', $term);
                });
            })
            ->distinct()
            ->select([
                'a.noflpp',
                'a.tgl_problem',
                'a.no_lots',
                'a.qty_problem',
                'a.rank',
                'a.deskripsi_problem',
                'aa.seq_no',
            ])
            ->orderByDesc('a.tgl_problem')
            ->orderByDesc('a.noflpp')
            ->paginate($this->perPage)
            ->through(fn ($row) => (array) $row);

        return view('livewire.akakami-approval-review', [
            'rows' => $rows,
            'totalRows' => $rows->total(),
        ])->layout('layouts.app');
    }

    private function canApprove(string $idFlpp): bool
    {
        $nik = (string) (auth()->user()->id_user ?? session('nik') ?? '');

        return $nik !== '' && DB::table('tbl_approval_akakami')
            ->where('id_flpp', $idFlpp)
            ->where('nik_approver', $nik)
            ->where('approve_status', 'waiting')
            ->exists();
    }

    private function findActiveDetail(string $idFlpp): ?array
    {
        $approval = DB::table('tblakakami')->where('noflpp', $idFlpp)->first();

        if (! $approval) {
            return null;
        }

        $sequence = DB::table('tbl_seq_all')
            ->where('no_lot', $approval->no_lots)
            ->orderBy('id')
            ->first(['no_srp', 'no_lot_cust', 'customer', 'no_part', 'nm_part']);
        $problem = $approval->idproblems
            ? DB::table('tblproblem')->where('idproblem', $approval->idproblems)->first(['nmproblem'])
            : null;
        $employee = $approval->pic
            ? DB::table('tblkaryawan')->where('nik', $approval->pic)->first(['nama_kary'])
            : null;
        $department = $approval->pic_dept
            ? DB::table('tbldept')->where('Id_Dept', $approval->pic_dept)->first(['Dept'])
            : null;
        $area = $approval->id_depts
            ? DB::table('tblsequence')->where('id_seq', $approval->id_depts)->value('nm_sequence')
            : null;

        return [
            'no_flpp' => $approval->noflpp,
            'tgl_problem' => $approval->tgl_problem,
            'no_lots' => $approval->no_lots,
            'no_srp' => $sequence->no_srp ?? null,
            'no_lot_cust' => $sequence->no_lot_cust ?? null,
            'no_brg_asli' => $sequence->no_part ?? null,
            'nm_part' => $sequence->nm_part ?? null,
            'nm_customer' => $sequence->customer ?? 'INTERNAL',
            'qty_problem' => $approval->qty_problem,
            'nm_sequence' => $area,
            'nama_kary' => $employee->nama_kary ?? 'INTERNAL',
            'nmproblem' => $problem->nmproblem ?? $approval->deskripsi_problem,
            'deskripsi_problem' => $approval->deskripsi_problem,
            'rank' => $approval->rank,
            'pic_dept' => $department->Dept ?? $approval->pic_dept,
            'fish_bone' => $approval->fish_bone,
            'analisa' => $approval->analisa,
            'temp_action' => $approval->temp_action,
            'corrective_act' => $approval->corrective_act,
        ];
    }

    private function formatTelegramMessage(array $detail, string $status, string $userName): string
    {
        $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? '-'), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<b>PAR MOBILE APPROVAL-AKAKAMI</b>'
            ."\n━━━━━━━━━━━━━━━━━━━━━━\n"
            .'<b>No. FLPP :</b> '.$escape($detail['no_flpp'] ?? $this->selectedFlpp)."\n"
            .'<b>No. Lot :</b> '.$escape($detail['no_lots'] ?? '-')."\n"
            .'<b>Part Name :</b> '.$escape($detail['nm_part'] ?? '-')."\n"
            .'<b>Quantity :</b> '.$escape($detail['qty_problem'] ?? '-')."\n"
            .'<b>Customer :</b> '.$escape($detail['nm_customer'] ?? '-')."\n"
            .'<b>Problem :</b> <i>'.$escape($detail['deskripsi_problem'] ?? $detail['nmproblem'] ?? '-')."</i>\n"
            ."━━━━━━━━━━━━━━━━━━━━━━\n"
            .'<b>STATUS :</b> '.$escape($status)."\n"
            .'<b>Oleh :</b> '.$escape($userName);
    }

    private function resetSelection(): void
    {
        $this->selectedFlpp = '';
        $this->activeDetail = [];
        $this->confirmed = false;
        $this->resetPage();
    }
}

