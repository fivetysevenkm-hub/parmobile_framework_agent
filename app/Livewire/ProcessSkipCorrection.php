<?php

namespace App\Livewire;

use App\Services\LotVerifikatorService;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProcessSkipCorrection extends Component
{
    public string $no_lot = '';

    public string $no_srp = '';

    public string $id_routing = '';

    public string $current_seq_code = '';

    public string $next_seq_code = '';

    public string $next_process = '';

    public string $curr_process = '';

    public string $current_seq_count = '';

    public string $next_seq_count = '';

    public string $total_seq_count = '';

    public string $total_seq_remain = '';

    public string $finish = '';

    public bool $lookedUp = false;

    public string $notice = '';

    public string $savedNotice = '';

    public array $interpreter = [];

    public array $seqAll = [];

    public function updatedNoLot(): void
    {
        if (trim($this->no_lot) === '') {
            $this->resetForm();

            return;
        }

        $this->savedNotice = '';
        $this->lookup();
    }

    public function lookup(): void
    {
        $this->lookedUp = true;
        $this->notice = '';
        $this->interpreter = [];
        $this->seqAll = [];

        $noLot = trim($this->no_lot);
        if ($noLot === '') {
            $this->notice = 'Ketik No Lot terlebih dahulu.';

            return;
        }

        $validator = SchemaCache::hasTable('tbl_validator')
            ? DB::table('tbl_validator')->where('no_lot', $noLot)->first()
            : null;

        $seqAllRows = SchemaCache::hasTable('tbl_seq_all')
            ? DB::table('tbl_seq_all')->where('no_lot', $noLot)->orderBy('seq')->get()
            : collect();

        $lastSeq = $seqAllRows->last();

        $noSrp = $validator ? trim((string) ($validator->no_srp ?? '')) : '';
        $idRouting = $validator ? trim((string) ($validator->id_routing ?? '')) : '';
        if ($noSrp === '' && $lastSeq) {
            $noSrp = trim((string) ($lastSeq->no_srp ?? ''));
        }
        if ($idRouting === '' && $lastSeq) {
            $idRouting = trim((string) ($lastSeq->id_routing ?? ''));
        }
        if ($noSrp === '') {
            $noSrp = trim((string) ($this->lotMaster($noLot)['no_srps'] ?? ''));
        }

        if ($validator) {
            $this->no_srp = $noSrp;
            $this->id_routing = $idRouting;
            $this->current_seq_code = trim((string) ($validator->Current_seq_code ?? ''));
            $this->next_seq_code = trim((string) ($validator->next_seq_code ?? ''));
            $this->next_process = trim((string) ($validator->next_process ?? ''));
            $this->curr_process = trim((string) ($validator->Curr_process ?? ''));
            $this->current_seq_count = $this->intProp($validator->current_seq_count);
            $this->next_seq_count = $this->intProp($validator->next_seq_count);
            $this->total_seq_count = $this->intProp($validator->total_seq_count);
            $this->total_seq_remain = $this->intProp($validator->total_seq_remain);
            $this->finish = strtolower(trim((string) ($validator->finish ?? '')));
            $this->capNextSeqCount();
        } elseif ($lastSeq) {
            $this->fillRowTwoComputed($noSrp, $idRouting, $seqAllRows, $lastSeq);
        } else {
            $this->no_srp = '';
            $this->id_routing = '';
            $this->current_seq_code = '';
            $this->next_seq_code = '';
            $this->next_process = '';
            $this->curr_process = '';
            $this->current_seq_count = '';
            $this->next_seq_count = '';
            $this->total_seq_count = '';
            $this->total_seq_remain = '';
            $this->finish = '';
        }

        $this->computeInterpreters($noLot, $noSrp, $idRouting, $validator, $seqAllRows, $lastSeq);

        if (! $validator && ! $lastSeq && $noSrp === '' && $idRouting === '') {
            $this->notice = 'No Lot "'.$noLot.'" tidak ditemukan di tbl_validator maupun tbl_seq_all.';

            return;
        }

        if ($validator) {
            $this->notice = 'Data validator ditemukan untuk No Lot '.$noLot.'.';
        } else {
            $this->notice = 'Belum ada record tbl_validator untuk No Lot ini. Baris input telah diisi dari data aktual (tbl_seq_all / routing) — teliti lalu klik Simpan untuk membuat record validator.';
        }
    }

    public function save(): void
    {
        $this->notice = '';
        $this->savedNotice = '';

        $noLot = trim($this->no_lot);
        if ($noLot === '') {
            $this->notice = 'No Lot wajib diisi sebelum menyimpan.';

            return;
        }
        if (! SchemaCache::hasTable('tbl_validator')) {
            $this->notice = 'Tabel tbl_validator tidak tersedia.';

            return;
        }

        DB::table('tbl_validator')->updateOrInsert(['no_lot' => $noLot], [
            'no_srp' => $this->no_srp !== '' ? $this->no_srp : null,
            'id_routing' => $this->id_routing !== '' ? $this->id_routing : null,
            'Current_seq_code' => $this->current_seq_code !== '' ? $this->current_seq_code : null,
            'next_seq_code' => $this->next_seq_code !== '' ? $this->next_seq_code : null,
            'next_process' => $this->next_process !== '' ? strtoupper(trim($this->next_process)) : null,
            'Curr_process' => $this->curr_process !== '' ? strtoupper(trim($this->curr_process)) : null,
            'current_seq_count' => $this->intProp($this->current_seq_count) !== '' ? (int) $this->current_seq_count : null,
            'next_seq_count' => $this->intProp($this->next_seq_count) !== '' ? (int) $this->next_seq_count : null,
            'total_seq_count' => $this->intProp($this->total_seq_count) !== '' ? (int) $this->total_seq_count : null,
            'total_seq_remain' => $this->intProp($this->total_seq_remain) !== '' ? (int) $this->total_seq_remain : null,
            'finish' => $this->finish !== '' ? strtolower(trim($this->finish)) : null,
        ]);

        $this->savedNotice = 'Data validator untuk No Lot '.$noLot.' berhasil disimpan.';
        $this->lookup();
    }

    public function resetForm(): void
    {
        $this->no_lot = '';
        $this->no_srp = '';
        $this->id_routing = '';
        $this->current_seq_code = '';
        $this->next_seq_code = '';
        $this->next_process = '';
        $this->curr_process = '';
        $this->current_seq_count = '';
        $this->next_seq_count = '';
        $this->total_seq_count = '';
        $this->total_seq_remain = '';
        $this->finish = '';
        $this->lookedUp = false;
        $this->notice = '';
        $this->savedNotice = '';
        $this->interpreter = [];
        $this->seqAll = [];
    }

    public function render()
    {
        return view('livewire.process-skip-correction', [
            'routingSeqCodes' => $this->routingSeqCodes(),
            'sequenceOptions' => $this->sequenceOptions(),
        ])->layout('layouts.app');
    }

    private function computeInterpreters(string $noLot, string $noSrp, string $idRouting, ?object $validator, Collection $seqAllRows, ?object $lastSeq): void
    {
        $actualCount = $seqAllRows->pluck('seq')->filter()
            ->unique()
            ->count();
        $totalSequences = $idRouting !== '' ? $this->routingCount($idRouting) : 0;
        $currentValue = (int) ($this->current_seq_count ?? 0);
        $totalValue = (int) ($this->total_seq_count ?? 0);
        if ($totalValue <= 0) {
            $totalValue = $totalSequences;
        }
        if ($currentValue <= 0 && $actualCount > 0) {
            $currentValue = $actualCount;
        }
        $maxedOut = $totalValue > 0 && $currentValue >= $totalValue;
        $remainValue = $totalValue > 0 ? max(0, $totalValue - $currentValue) : 0;

        $this->interpreter = [
            'no_lot' => $lastSeq
                ? trim((string) ($lastSeq->no_part ?? '')).' · '.trim((string) ($lastSeq->nm_part ?? '')).' · '.trim((string) ($lastSeq->customer ?? ''))
                : '',
            'no_srp' => $lastSeq && strtolower(trim((string) ($lastSeq->no_srp ?? ''))) !== strtolower($noSrp)
                ? 'SRP acuan di tbl_seq_all: '.trim((string) ($lastSeq->no_srp ?? '-'))
                : 'SRP acuan di tbl_seq_all: '.($noSrp !== '' ? $noSrp : '-'),
            'id_routing' => $this->routingName($idRouting),
            'current_seq_code' => $this->describeSeqCode($idRouting, $this->current_seq_code !== '' ? $this->current_seq_code : ''),
            'next_seq_code' => $this->describeSeqCode($idRouting, $this->next_seq_code !== '' ? $this->next_seq_code : ''),
            'next_process' => $this->describeProcess($this->next_process !== '' ? $this->next_process : ''),
            'curr_process' => $this->describeProcess($this->curr_process !== '' ? $this->curr_process : ''),
            'current_seq_count' => $currentValue > 0
                ? 'Seharusnya di Count '.$currentValue.' (acuan '.$noLot.')'
                : 'Belum ada record proses di tbl_seq_all.',
            'next_seq_count' => $maxedOut
                ? 'Sudah mentok di Count '.$totalValue.' (maksimal) — tidak boleh +1 lagi (acuan '.$noLot.')'
                : ($currentValue > 0
                    ? 'Seharusnya di Count '.($totalValue > 0 ? min($currentValue + 1, $totalValue) : $currentValue + 1).' (acuan '.$noLot.')'
                    : 'Belum ada record proses di tbl_seq_all.'),
            'total_seq_count' => $totalSequences > 0
                ? 'Total sequence routing '.($idRouting !== '' ? $idRouting : '-').': '.$totalSequences.' proses'
                : 'Routing '.($idRouting !== '' ? $idRouting : '-').' tidak ditemukan.',
            'total_seq_remain' => $maxedOut
                ? '0 proses tersisa — lot sudah di proses terakhir'.($totalValue > 0 ? ' (mentok di '.$totalValue.')' : '')
                : ($totalValue > 0 && $currentValue > 0
                    ? 'Total '.$totalValue.' − current '.$currentValue.' = '.$remainValue.' proses tersisa'
                    : 'Menunggu total & count terisi.'),
            'finish' => $lastSeq
                ? 'Terakhir di Seq '.trim((string) ($lastSeq->seq ?? '-')).' · '.trim((string) ($lastSeq->id_seq ?? '')).' '.trim((string) ($lastSeq->nm_seq ?? '')).' · finish: '.strtolower(trim((string) ($lastSeq->finish ?? '-')))
                : 'Belum ada record proses di tbl_seq_all.',
        ];

        $this->seqAll = $seqAllRows->map(fn ($row): array => (array) $row)->all();
    }

    private function capNextSeqCount(): void
    {
        $current = (int) $this->current_seq_count;
        $total = (int) $this->total_seq_count;
        $next = (int) $this->next_seq_count;
        if ($total > 0 && ($next > $total || $current >= $total)) {
            $this->next_seq_count = (string) $total;
            $this->next_seq_code = $this->seqCodeFor($total);
        }
        $remain = (int) $this->total_seq_remain;
        $expectedRemain = $total > 0 && $current > 0 ? max(0, $total - $current) : -1;
        if ($expectedRemain >= 0 && $remain !== $expectedRemain) {
            $this->total_seq_remain = (string) $expectedRemain;
        }
    }

    private function fillRowTwoComputed(string $noSrp, string $idRouting, Collection $seqAllRows, ?object $lastSeq): void
    {
        $this->no_srp = $noSrp;
        $this->id_routing = $idRouting;
        $total = $idRouting !== '' ? $this->routingCount($idRouting) : 0;
        $current = $seqAllRows->pluck('seq')->filter()
            ->unique()
            ->count();
        $maxedOut = $total > 0 && $current >= $total;

        $lastFinish = $lastSeq ? strtolower(trim((string) ($lastSeq->finish ?? ''))) : '';
        if (! in_array($lastFinish, ['yes', 'no'], true)) {
            $lastFinish = $total > 0 ? ($maxedOut ? 'yes' : 'no') : '';
        }

        $this->current_seq_code = $current > 0 ? $this->seqCodeFor($current) : '';
        $this->next_seq_code = (! $maxedOut && $current > 0) ? $this->seqCodeFor($current + 1) : '';
        $this->curr_process = $current > 0 ? $this->processAtSeq($idRouting, $current) : '';
        if ($this->curr_process === '' && $lastSeq) {
            $this->curr_process = strtoupper(trim((string) ($lastSeq->id_seq ?? '')));
        }
        $this->next_process = (! $maxedOut && $current > 0) ? $this->processAtSeq($idRouting, $current + 1) : '';
        $this->current_seq_count = $current > 0 ? (string) $current : '';
        $this->next_seq_count = $total > 0
            ? (string) ($maxedOut ? $total : min($current + 1, $total))
            : ($current > 0 ? (string) ($current + 1) : '');
        $this->total_seq_count = $total > 0 ? (string) $total : '';
        $this->total_seq_remain = $total > 0 && $current > 0 ? (string) max(0, $total - $current) : '';
        $this->finish = $lastFinish;
    }

    private function seqCodeFor(int $position): string
    {
        return 'seq_'.str_pad((string) $position, 2, '0', STR_PAD_LEFT);
    }

    private function processAtSeq(string $idRouting, int $position): string
    {
        $row = $this->routingRow($idRouting);
        if (! $row || $position < 1) {
            return '';
        }
        $column = 'Seq_'.str_pad((string) $position, 2, '0', STR_PAD_LEFT);
        if (! SchemaCache::hasColumn('tblrouting', $column)) {
            return '';
        }

        return strtoupper(trim((string) ($row->{$column} ?? '')));
    }

    private function routingSeqCodes(): array
    {
        if (! SchemaCache::hasTable('tblrouting')) {
            return [];
        }

        $codes = [];
        for ($index = 1; $index <= LotVerifikatorService::MAX_SEQUENCE; $index++) {
            $column = 'Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            if (SchemaCache::hasColumn('tblrouting', $column)) {
                $codes[] = ['value' => strtolower($column), 'label' => $column];
            }
        }

        return $codes;
    }

    private function sequenceOptions(): array
    {
        $options = [['value' => '', 'label' => '— Pilih Proses —']];
        if (SchemaCache::hasTable('tblsequence')) {
            foreach (DB::table('tblsequence')->orderBy('id_seq')->get() as $sequence) {
                $options[] = [
                    'value' => strtoupper(trim((string) $sequence->id_seq)),
                    'label' => strtoupper(trim((string) $sequence->id_seq)).' · '.trim((string) $sequence->nm_sequence),
                ];
            }
        }

        return $options;
    }

    private function routingRow(string $idRouting): ?object
    {
        if ($idRouting === '' || ! SchemaCache::hasTable('tblrouting')) {
            return null;
        }

        return DB::table('tblrouting')->where('id_routing', $idRouting)->first() ?: null;
    }

    private function routingName(string $idRouting): string
    {
        $row = $this->routingRow($idRouting);

        return $row && trim((string) ($row->nm_routing ?? '')) !== ''
            ? $idRouting.' · '.trim((string) $row->nm_routing)
            : ($idRouting !== '' ? 'Routing '.$idRouting.' tidak ditemukan' : 'Menunggu id_routing.');
    }

    private function routingCount(string $idRouting): int
    {
        $row = $this->routingRow($idRouting);
        if (! $row) {
            return 0;
        }

        $count = 0;
        for ($index = 1; $index <= LotVerifikatorService::MAX_SEQUENCE; $index++) {
            $column = 'Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            if (SchemaCache::hasColumn('tblrouting', $column) && trim((string) ($row->{$column} ?? '')) !== '') {
                $count++;
            }
        }

        return $count;
    }

    private function describeSeqCode(string $idRouting, string $seqCode): string
    {
        $seqCode = strtolower(trim($seqCode));
        if ($seqCode === '' || ! preg_match('/^seq_(\d+)$/', $seqCode, $matches)) {
            return '';
        }

        $column = 'Seq_'.str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $row = $this->routingRow($idRouting);
        $processId = $row && SchemaCache::hasColumn('tblrouting', $column)
            ? trim((string) ($row->{$column} ?? ''))
            : '';

        return $this->describeProcess($processId);
    }

    private function describeProcess(string $processId): string
    {
        $processId = strtoupper(trim($processId));
        if ($processId === '') {
            return '';
        }
        if ($processId === 'FIN') {
            return 'FIN · Selesai / tidak ada proses berikutnya';
        }

        $name = SchemaCache::hasTable('tblsequence')
            ? (string) (DB::table('tblsequence')->where('id_seq', $processId)->value('nm_sequence') ?? '')
            : '';

        return $name !== '' ? $processId.' · '.$name : $processId;
    }

    private function lotMaster(string $noLot): array
    {
        foreach (['tbllotproduksi', 'tbllotdaily'] as $table) {
            if (SchemaCache::hasTable($table)) {
                $row = DB::table($table)->where('no_lot', $noLot)->first();
                if ($row) {
                    return (array) $row;
                }
            }
        }

        return [];
    }

    private function intProp(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }
}