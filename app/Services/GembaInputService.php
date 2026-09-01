<?php

namespace App\Services;

use App\Support\SchemaCache;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GembaInputService
{
    public function __construct(private readonly LotVerifikatorService $lotVerifikator) {}

    public function lotContext(string $noLot): ?array
    {
        if (! SchemaCache::hasTable('tbllotdaily') || ! SchemaCache::hasTable('tblsrp')) {
            return null;
        }

        $query = DB::table('tbllotdaily as lot')
            ->leftJoin('tblsrp as srp', 'lot.no_srps', '=', 'srp.no_srp')
            ->where('lot.no_lot', trim($noLot));

        if (SchemaCache::hasTable('tblpart')) {
            $query->leftJoin('tblpart as part', 'srp.no_parts', '=', 'part.no_part');
        }
        if (SchemaCache::hasTable('tblcustomer')) {
            $query->leftJoin('tblcustomer as customer', 'srp.no_customers', '=', 'customer.no_customer');
        }
        if (SchemaCache::hasTable('tblproses')) {
            $query->leftJoin('tblproses as proses', 'srp.no_process', '=', 'proses.no_proses');
        }

        $columns = [
            'lot.no_lot',
            'lot.no_lot_cust',
            'lot.no_srps as no_srp',
            'lot.no_sj_cust',
            'lot.qty',
            'srp.id_routing',
        ];
        if (SchemaCache::hasTable('tblpart')) {
            $columns[] = 'part.no_brg_asli as no_part';
            $columns[] = 'part.nm_part';
        }
        if (SchemaCache::hasTable('tblcustomer')) {
            $columns[] = 'customer.nm_customer as customer';
        }
        if (SchemaCache::hasTable('tblproses')) {
            $columns[] = 'proses.proses as proses';
        }

        $row = $query->first($columns);
        if (! $row) {
            return null;
        }

        return [
            'no_lot' => (string) ($row->no_lot ?? $noLot),
            'no_srp' => (string) ($row->no_srp ?? ''),
            'no_part' => (string) ($row->no_part ?? ''),
            'nm_part' => (string) ($row->nm_part ?? ''),
            'no_sj_cust' => (string) ($row->no_sj_cust ?? ''),
            'no_lot_cust' => (string) ($row->no_lot_cust ?? ''),
            'qty' => (string) ($row->qty ?? ''),
            'customer' => (string) ($row->customer ?? ''),
            'proses' => (string) ($row->proses ?? ''),
            'id_routing' => (string) ($row->id_routing ?? ''),
        ];
    }

    public function checkLotAvailabilityAndDelivery(string $noLot): array
    {
        $noLot = trim($noLot);
        if ($noLot === '') {
            return [
                'ok' => false,
                'message' => 'No Lot wajib diisi.',
            ];
        }

        // 1. Cek apakah lot tercantum di tbllotdaily (field no_lot)
        if (SchemaCache::hasTable('tbllotdaily')) {
            $existsInDaily = DB::table('tbllotdaily')->where('no_lot', $noLot)->exists();
            if (! $existsInDaily) {
                return [
                    'ok' => false,
                    'message' => 'Lot tersebut tidak ada',
                ];
            }
        }

        // 2. Cek apakah status lot tersebut telah delivery di tbl_seq_all ((id_seq/id_sq) = S26 dan finish = yes)
        if (SchemaCache::hasTable('tbl_seq_all')) {
            $query = DB::table('tbl_seq_all')->where('no_lot', $noLot);
            $hasIdSeq = SchemaCache::hasColumn('tbl_seq_all', 'id_seq');
            $hasIdSq = SchemaCache::hasColumn('tbl_seq_all', 'id_sq');

            if ($hasIdSeq || $hasIdSq) {
                $query->where(function ($q) use ($hasIdSeq, $hasIdSq) {
                    if ($hasIdSeq) {
                        $q->where('id_seq', 'S26');
                    }
                    if ($hasIdSq) {
                        if ($hasIdSeq) {
                            $q->orWhere('id_sq', 'S26');
                        } else {
                            $q->where('id_sq', 'S26');
                        }
                    }
                });

                if (SchemaCache::hasColumn('tbl_seq_all', 'finish')) {
                    $query->where(function ($q) {
                        $q->where('finish', 'yes')
                            ->orWhere('finish', '1')
                            ->orWhere('finish', 'true')
                            ->orWhere('finish', 'finish')
                            ->orWhere('finish', 'finished');
                    });
                }

                if ($query->exists()) {
                    return [
                        'ok' => false,
                        'message' => 'Lot Sudah selesai proses & telah di kirim ke customer',
                    ];
                }
            }
        }

        return ['ok' => true];
    }

    public function processContext(string $noLot, int $sequenceNo, ?array $verifyResult = null): ?array
    {
        $result = $verifyResult ?? $this->lotVerifikator->verify($noLot);
        if (! ($result['ok'] ?? false)) {
            return null;
        }

        foreach (($result['processes'] ?? []) as $process) {
            if ((int) ($process['sequence_no'] ?? 0) === $sequenceNo) {
                return [
                    'no_lot' => (string) $result['no_lot'],
                    'no_srp' => (string) $result['no_srp'],
                    'id_routing' => (string) $result['id_routing'],
                    'process' => $process,
                    'next_process' => (string) ($result['next_process'] ?? ''),
                    'finished' => (bool) ($result['finished'] ?? false),
                ];
            }
        }

        return null;
    }

    public function currentState(string $noLot, int $sequenceNo): array
    {
        $table = $this->processTable($sequenceNo);
        if (! SchemaCache::hasTable($table)) {
            return $this->emptyState();
        }

        $row = DB::table($table)->where('no_lot', $noLot)->orderByDesc('no_lot')->first();
        if (! $row) {
            return $this->emptyState();
        }

        return [
            'start_time' => $this->value($row, 'start_time'),
            'finish_time' => $this->value($row, 'finish_time'),
            'duration' => $this->value($row, 'duration'),
            'tray_no' => $this->value($row, 'tray_no'),
            'tray_nokecil' => $this->value($row, 'tray_nokecil'),
            'no_mesin' => $this->value($row, 'no_mesin'),
            'pic_01' => $this->value($row, 'pic_01'),
            'pic_02' => $this->value($row, 'pic_02'),
            'pic_03' => $this->value($row, 'pic_03'),
            'pic_04' => $this->value($row, 'pic_04'),
            'akakami' => $this->value($row, 'akakami'),
            'ojt' => $this->value($row, 'ojt'),
            'hold' => $this->value($row, 'hold'),
            'finish' => $this->value($row, 'finish'),
        ];
    }

    public function operators(string $noLot, int $sequenceNo): array
    {
        $state = $this->currentState($noLot, $sequenceNo);
        $niks = [];
        for ($slot = 1; $slot <= (int) config('gemba.max_operator_slots', 4); $slot++) {
            $nik = trim((string) ($state['pic_0'.$slot] ?? ''));
            if ($nik !== '') {
                $niks[] = $nik;
            }
        }

        if ($niks === [] || ! SchemaCache::hasTable('tblkaryawan')) {
            return [];
        }

        $employees = DB::table('tblkaryawan')->whereIn('nik', $niks)->get(['nik', 'nama_kary', 'ojt_a']);
        $byNik = [];
        foreach ($employees as $employee) {
            $byNik[(string) $employee->nik] = [
                'nik' => (string) $employee->nik,
                'name' => (string) ($employee->nama_kary ?? ''),
                'ojt' => strtolower(trim((string) ($employee->ojt_a ?? ''))),
            ];
        }

        $operators = [];
        foreach ($niks as $nik) {
            $operators[] = $byNik[$nik] ?? ['nik' => $nik, 'name' => $nik, 'ojt' => ''];
        }

        return $operators;
    }

    public function findOperator(string $nik, array $process): ?array
    {
        $nik = trim($nik);
        if ($nik === '' || ! SchemaCache::hasTable('tblkaryawan')) {
            return null;
        }

        $columns = ['nik', 'nama_kary', 'ojt_a'];
        for ($index = 1; $index <= 39; $index++) {
            $column = 'class_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            if (SchemaCache::hasColumn('tblkaryawan', $column)) {
                $columns[] = $column;
            }
        }

        $employee = DB::table('tblkaryawan')->where('nik', $nik)->first($columns);
        if (! $employee) {
            return null;
        }

        $skills = [];
        foreach (get_object_vars($employee) as $key => $value) {
            if (str_starts_with(strtolower((string) $key), 'class_') && trim((string) $value) !== '') {
                $skills[] = strtolower(trim((string) $value));
            }
        }

        $processCode = strtolower(trim((string) ($process['id_seq'] ?? '')));
        if ($processCode === '' || ! in_array($processCode, $skills, true)) {
            return [
                'allowed' => false,
                'nik' => (string) $employee->nik,
                'name' => (string) ($employee->nama_kary ?? ''),
                'ojt' => strtolower(trim((string) ($employee->ojt_a ?? ''))),
            ];
        }

        return [
            'allowed' => true,
            'nik' => (string) $employee->nik,
            'name' => (string) ($employee->nama_kary ?? ''),
            'ojt' => strtolower(trim((string) ($employee->ojt_a ?? ''))),
        ];
    }

    public function searchOperators(string $keyword, array $process): array
    {
        if (! SchemaCache::hasTable('tblkaryawan')) {
            return [];
        }

        $columns = ['nik', 'nama_kary', 'ojt_a'];
        for ($index = 1; $index <= 39; $index++) {
            $column = 'class_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            if (SchemaCache::hasColumn('tblkaryawan', $column)) {
                $columns[] = $column;
            }
        }

        $keyword = trim($keyword);
        $rows = DB::table('tblkaryawan')
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $nested) use ($keyword): void {
                    $nested->where('nik', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_kary', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy('nama_kary')
            ->limit(50)
            ->get($columns);

        $results = [];
        foreach ($rows as $employee) {
            $skills = [];
            foreach (get_object_vars($employee) as $key => $value) {
                if (str_starts_with(strtolower((string) $key), 'class_') && trim((string) $value) !== '') {
                    $skills[] = strtolower(trim((string) $value));
                }
            }
            $processCode = strtolower(trim((string) ($process['id_seq'] ?? '')));
            $allowed = $processCode !== '' && in_array($processCode, $skills, true);
            $results[] = [
                'nik' => (string) ($employee->nik ?? ''),
                'name' => (string) ($employee->nama_kary ?? ''),
                'ojt' => strtolower(trim((string) ($employee->ojt_a ?? ''))),
                'allowed' => $allowed,
                'reason' => $allowed ? '' : 'Skill tidak sesuai dengan process code aktif.',
            ];
        }

        return $results;
    }

    public function sequences(): array
    {
        if (! SchemaCache::hasTable('tblsequence')) {
            return [];
        }

        return DB::table('tblsequence')
            ->orderBy('id_seq')
            ->get(['id_seq', 'nm_sequence'])
            ->map(fn ($row): array => [
                'id_seq' => (string) ($row->id_seq ?? ''),
                'nm_sequence' => (string) ($row->nm_sequence ?? ''),
            ])
            ->all();
    }

    public function userGembaMode(?string $username, ?string $idUser = null): array
    {
        $default = [
            'mode' => 'berpindah',
            'id_seq' => '',
            'nm_sequence' => '',
        ];

        if (! SchemaCache::hasTable('tbl_user_gemba_mode')) {
            return $default;
        }

        $username = trim((string) $username);
        $idUser = trim((string) $idUser);

        if ($username === '' && $idUser === '') {
            return $default;
        }

        $query = DB::table('tbl_user_gemba_mode');
        if ($username !== '') {
            $query->where('username', $username);
        } else {
            $query->where('id_user', $idUser);
        }

        $row = $query->first();
        if (! $row) {
            return $default;
        }

        $mode = strtolower(trim((string) ($row->mode ?? '')));

        return [
            'mode' => in_array($mode, ['tetap', 'berpindah'], true) ? $mode : 'berpindah',
            'id_seq' => (string) ($row->id_seq ?? ''),
            'nm_sequence' => (string) ($row->nm_sequence ?? ''),
        ];
    }

    public function saveUserGembaMode(?string $username, ?string $idUser, string $mode, ?string $idSeq = null, ?string $nmSequence = null): void
    {
        if (! SchemaCache::hasTable('tbl_user_gemba_mode')) {
            return;
        }

        $username = trim((string) $username);
        $idUser = trim((string) $idUser);
        if ($username === '' && $idUser === '') {
            return;
        }

        $mode = strtolower(trim($mode));
        $mode = in_array($mode, ['tetap', 'berpindah'], true) ? $mode : 'berpindah';
        $idSeq = trim((string) $idSeq);
        $nmSequence = trim((string) $nmSequence);

        if ($idSeq !== '' && $nmSequence === '' && SchemaCache::hasTable('tblsequence')) {
            $nmSequence = (string) (DB::table('tblsequence')->where('id_seq', $idSeq)->value('nm_sequence') ?? '');
        } elseif ($nmSequence !== '' && $idSeq === '' && SchemaCache::hasTable('tblsequence')) {
            $idSeq = (string) (DB::table('tblsequence')->where('nm_sequence', $nmSequence)->value('id_seq') ?? '');
        }

        $identifier = $username !== '' ? ['username' => $username] : ['id_user' => $idUser];

        DB::table('tbl_user_gemba_mode')->updateOrInsert(
            $identifier,
            [
                'username' => $username ?: ($idUser ?: 'unknown'),
                'id_user' => $idUser ?: ($username ?: null),
                'mode' => $mode,
                'id_seq' => $idSeq ?: null,
                'nm_sequence' => $nmSequence ?: null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function resolveInitialTrayNumbers(string $noLot, int $currentSequenceNo): array
    {
        $trayNo = '';
        $traySmall = '';

        if ($noLot === '') {
            return [
                'tray_no' => '',
                'tray_nokecil' => '',
            ];
        }

        // 1. Primary source: check sequence 1 (tbl_seq_01)
        if (SchemaCache::hasTable('tbl_seq_01')) {
            $seq1 = DB::table('tbl_seq_01')->where('no_lot', $noLot)->first();
            if ($seq1) {
                $trayNo = trim((string) ($this->value($seq1, 'tray_no') ?? ''));
                $traySmall = trim((string) ($this->value($seq1, 'tray_nokecil') ?? ''));
            }
        }

        // 2. Check backwards from (currentSequenceNo - 1) down to 1
        if ($trayNo === '' || $traySmall === '') {
            for ($seq = $currentSequenceNo - 1; $seq >= 1; $seq--) {
                $table = $this->processTable($seq);
                if (! SchemaCache::hasTable($table)) {
                    continue;
                }
                $row = DB::table($table)->where('no_lot', $noLot)->first();
                if ($row) {
                    if ($trayNo === '') {
                        $trayNo = trim((string) ($this->value($row, 'tray_no') ?? ''));
                    }
                    if ($traySmall === '') {
                        $traySmall = trim((string) ($this->value($row, 'tray_nokecil') ?? ''));
                    }
                    if ($trayNo !== '' && $traySmall !== '') {
                        break;
                    }
                }
            }
        }

        // 3. Fallback: check tbl_seq_all if available
        if (($trayNo === '' || $traySmall === '') && SchemaCache::hasTable('tbl_seq_all')) {
            $seqAll = DB::table('tbl_seq_all')
                ->where('no_lot', $noLot)
                ->where('seq', '<', $currentSequenceNo)
                ->orderBy('seq')
                ->get();
            foreach ($seqAll as $row) {
                if ($trayNo === '' && trim((string) ($this->value($row, 'tray_no') ?? '')) !== '') {
                    $trayNo = trim((string) ($this->value($row, 'tray_no') ?? ''));
                }
                if ($traySmall === '' && trim((string) ($this->value($row, 'tray_nokecil') ?? '')) !== '') {
                    $traySmall = trim((string) ($this->value($row, 'tray_nokecil') ?? ''));
                }
            }
        }

        return [
            'tray_no' => $trayNo,
            'tray_nokecil' => $traySmall,
        ];
    }

    public function machines(): array
    {
        if (! SchemaCache::hasTable('tblmesin')) {
            return [];
        }

        return DB::table('tblmesin')
            ->orderBy('mesin')
            ->get(['no_mesin', 'mesin'])
            ->map(fn ($row): array => [
                'no_mesin' => (string) ($row->no_mesin ?? ''),
                'mesin' => (string) ($row->mesin ?? ''),
            ])
            ->all();
    }

    public function requiresMachine(array $process): bool
    {
        $haystack = strtolower(trim((string) ($process['name'] ?? '').' '.($process['id_seq'] ?? '')));
        foreach ((array) config('gemba.machine_process_keywords', []) as $keyword) {
            if ($keyword !== '' && str_contains($haystack, strtolower((string) $keyword))) {
                return true;
            }
        }

        return false;
    }

    public function problemCategories(): array
    {
        if (! SchemaCache::hasTable('tblproblem')) {
            return [];
        }

        return DB::table('tblproblem')
            ->orderBy('nmproblem')
            ->get(['idproblem', 'nmproblem'])
            ->map(fn ($row): array => [
                'idproblem' => (string) ($row->idproblem ?? ''),
                'nmproblem' => (string) ($row->nmproblem ?? ''),
            ])
            ->all();
    }

    public function problemRanks(): array
    {
        if (! SchemaCache::hasTable('tblrankproblem')) {
            return [];
        }

        return DB::table('tblrankproblem')
            ->orderBy('nmrankproblem')
            ->get(['idrankproblem', 'nmrankproblem'])
            ->map(fn ($row): array => [
                'idrankproblem' => (string) ($row->idrankproblem ?? ''),
                'nmrankproblem' => (string) ($row->nmrankproblem ?? ''),
            ])
            ->all();
    }

    public function submitAkakami(array $context, array $data): array
    {
        $noLot = trim((string) ($context['no_lot'] ?? ''));
        $sequenceNo = (int) ($context['process']['sequence_no'] ?? 0);
        $process = $context['process'] ?? [];
        $table = $this->processTable($sequenceNo);
        $noReg = trim((string) ($data['no_reg'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $reporterNik = trim((string) ($data['reporter_nik'] ?? ''));

        if ($noLot === '' || $noReg === '' || $category === '' || $description === '' || $reporterNik === '') {
            return ['ok' => false, 'message' => 'No Reg, kategori, pelapor, dan isi masalah wajib diisi.'];
        }
        if (! SchemaCache::hasTable('tblakakami')) {
            return ['ok' => false, 'message' => 'Tabel Akakami belum tersedia.'];
        }
        if (! SchemaCache::hasTable('tblproblem')) {
            return ['ok' => false, 'message' => 'Master kategori masalah tblproblem belum tersedia.'];
        }
        if (! DB::table('tblproblem')->where('idproblem', $category)->exists()) {
            return ['ok' => false, 'message' => 'Kategori masalah yang dipilih tidak valid.'];
        }
        $rank = trim((string) ($data['rank'] ?? ''));
        if ($rank === '') {
            return ['ok' => false, 'message' => 'Rank masalah wajib dipilih.'];
        }
        if (! SchemaCache::hasTable('tblrankproblem') || ! DB::table('tblrankproblem')->where('idrankproblem', $rank)->exists()) {
            return ['ok' => false, 'message' => 'Rank masalah yang dipilih tidak valid.'];
        }
        if (! SchemaCache::hasTable($table)) {
            return ['ok' => false, 'message' => "Tabel proses {$table} tidak tersedia."];
        }
        if (DB::table('tblakakami')->where('noflpp', $noReg)->exists()) {
            return ['ok' => false, 'message' => 'No Reg Akakami tersebut sudah digunakan.'];
        }

        $now = now('Asia/Jakarta')->format('Y-m-d H:i:s');
        $lot = $this->lotContext($noLot) ?? [];
        $payload = [
            'noflpp' => $noReg,
            'no_lots' => $noLot,
            'tgl_problem' => $now,
            'idproblems' => $category,
            'qty_problem' => trim((string) ($data['qty'] ?? ($lot['qty'] ?? ''))),
            'id_depts' => (string) ($process['id_seq'] ?? ''),
            'nm_tabel' => 'tbl_seq_'.str_pad((string) $sequenceNo, 2, '0', STR_PAD_LEFT),
            'deskripsi_problem' => $description,
            'rank' => trim((string) ($data['rank'] ?? '')),
            'temp_action' => '',
            'fish_bone' => '',
            'analisa' => '',
            'corrective_act' => '',
            'judgment_level' => '',
            'rank_a' => '',
            'pic_handler' => '',
            'pic_dept' => '',
            'pic' => $reporterNik,
            'tgl_approved' => null,
            'status_flpp' => 'hold',
        ];
        $payload = $this->onlyExistingColumns('tblakakami', $payload);

        DB::transaction(function () use ($payload, $table, $noLot, $sequenceNo, $now): void {
            DB::table('tblakakami')->insert($payload);
            $statusPayload = $this->onlyExistingColumns($table, ['hold' => 'yes', 'akakami' => 'yes']);
            DB::table($table)->where('no_lot', $noLot)->update($statusPayload);
            if (SchemaCache::hasTable('tbl_seq_all')) {
                DB::table('tbl_seq_all')->where('no_lot', $noLot)->where('seq', $sequenceNo)->update([
                    'hold' => 'yes',
                    'akakami' => 'yes',
                    'updated_at' => $now,
                ]);
            }
        });

        return [
            'ok' => true,
            'message' => 'Data Akakami berhasil dilaporkan dan proses diubah ke HOLD.',
            'no_reg' => $noReg,
            'payload' => $payload,
        ];
    }

    public function start(array $context, array $operators, string $trayNo, string $traySmall, ?string $machineNo): array
    {
        $noLot = trim((string) ($context['no_lot'] ?? ''));
        $sequenceNo = (int) ($context['process']['sequence_no'] ?? 0);
        $canonical = $this->processContext($noLot, $sequenceNo);
        if (! $canonical || ! ($canonical['process']['clickable'] ?? false)) {
            return ['ok' => false, 'message' => 'Proses aktif tidak valid atau sudah berubah. Muat ulang No Lot terlebih dahulu.'];
        }
        $context = $canonical;
        $process = $context['process'] ?? [];
        $noSrp = trim((string) ($context['no_srp'] ?? ''));
        $idRouting = trim((string) ($context['id_routing'] ?? ''));
        $idSeq = trim((string) ($process['id_seq'] ?? ''));
        $name = trim((string) ($process['name'] ?? ''));

        if ($noLot === '' || $sequenceNo < 1 || $idSeq === '') {
            return ['ok' => false, 'message' => 'Konteks proses belum lengkap.'];
        }
        if ($operators === []) {
            return ['ok' => false, 'message' => 'Input minimal satu operator terlebih dahulu.'];
        }
        $existingState = $this->currentState($noLot, $sequenceNo);
        if ($existingState['start_time'] !== '' && strtolower($existingState['finish']) !== 'yes') {
            return ['ok' => false, 'message' => 'Proses ini sudah berjalan dan belum finish.'];
        }
        if (strtolower($existingState['finish']) === 'yes') {
            return ['ok' => false, 'message' => 'Proses ini sudah selesai.'];
        }
        if (trim($trayNo) === '') {
            return ['ok' => false, 'message' => 'No Tray wajib diisi.'];
        }
        if ($this->requiresMachine($process) && trim((string) $machineNo) === '') {
            return ['ok' => false, 'message' => 'Mesin wajib dipilih untuk proses ini.'];
        }

        $processTable = $this->processTable($sequenceNo);
        if (! SchemaCache::hasTable($processTable)) {
            return ['ok' => false, 'message' => "Tabel proses {$processTable} tidak tersedia."];
        }

        $sequenceCode = 'seq_'.str_pad((string) $sequenceNo, 2, '0', STR_PAD_LEFT);
        $validatorResult = $this->validateAndAdvanceValidator($noLot, $noSrp, $idRouting, $sequenceCode);
        if (! ($validatorResult['ok'] ?? false)) {
            return $validatorResult;
        }

        $now = now()->format('Y-m-d H:i:s');
        $ojt = collect($operators)->contains(fn (array $operator): bool => strtolower((string) ($operator['ojt'] ?? '')) === 'yes') ? 'yes' : 'no';
        $operatorNiks = [];
        $operatorNames = [];
        for ($slot = 0; $slot < (int) config('gemba.max_operator_slots', 4); $slot++) {
            $operatorNiks[$slot] = (string) ($operators[$slot]['nik'] ?? '');
            $operatorNames[$slot] = (string) ($operators[$slot]['name'] ?? '');
        }

        DB::transaction(function () use ($processTable, $noLot, $noSrp, $idSeq, $name, $now, $trayNo, $traySmall, $machineNo, $ojt, $operatorNiks, $context, $sequenceNo): void {
            $processPayload = $this->onlyExistingColumns($processTable, [
                'no_lot' => $noLot,
                'no_srp' => $noSrp,
                'sequence' => $idSeq,
                'start_time' => $now,
                'finish_time' => null,
                'tray_no' => $trayNo,
                'tray_nokecil' => $traySmall !== '' ? $traySmall : null,
                'duration' => 0,
                'pic_01' => $operatorNiks[0] !== '' ? $operatorNiks[0] : null,
                'pic_02' => $operatorNiks[1] !== '' ? $operatorNiks[1] : null,
                'pic_03' => $operatorNiks[2] !== '' ? $operatorNiks[2] : null,
                'pic_04' => $operatorNiks[3] !== '' ? $operatorNiks[3] : null,
                'akakami' => 'no',
                'ojt' => $ojt,
                'hold' => 'no',
                'finish' => 'no',
                'no_mesin' => $machineNo !== '' ? $machineNo : null,
            ]);
            DB::table($processTable)->updateOrInsert(['no_lot' => $noLot], $processPayload);

            if (SchemaCache::hasTable('tbl_seq_all')) {
                $lot = $this->lotContext($noLot) ?? [];
                $allPayload = $this->onlyExistingColumns('tbl_seq_all', [
                    'no_lot' => $noLot,
                    'no_srp' => $noSrp,
                    'seq' => $sequenceNo,
                    'id_seq' => $idSeq,
                    'nm_seq' => $name,
                    'id_routing' => (string) ($context['id_routing'] ?? ''),
                    'start_time' => $now,
                    'finish_time' => null,
                    'duration' => 0,
                    'tray_no' => $trayNo,
                    'tray_nokecil' => $traySmall !== '' ? $traySmall : null,
                    'pic_01' => $operatorNiks[0] !== '' ? $operatorNiks[0] : null,
                    'pic_02' => $operatorNiks[1] !== '' ? $operatorNiks[1] : null,
                    'pic_03' => $operatorNiks[2] !== '' ? $operatorNiks[2] : null,
                    'pic_04' => $operatorNiks[3] !== '' ? $operatorNiks[3] : null,
                    'akakami' => 'no',
                    'ojt' => $ojt,
                    'hold' => 'no',
                    'finish' => 'no',
                    'no_mesin' => $machineNo !== '' ? $machineNo : null,
                    'no_part' => $lot['no_part'] ?? null,
                    'nm_part' => $lot['nm_part'] ?? null,
                    'customer' => $lot['customer'] ?? null,
                    'no_sj_cust' => $lot['no_sj_cust'] ?? null,
                    'no_lot_cust' => $lot['no_lot_cust'] ?? null,
                    'qty' => $lot['qty'] ?? null,
                    'updated_at' => $now,
                ]);
                DB::table('tbl_seq_all')->updateOrInsert(['no_lot' => $noLot, 'seq' => $sequenceNo], $allPayload);
            }
        });

        return ['ok' => true, 'message' => 'Proses berhasil di-start.', 'start_time' => $now];
    }

    public function finish(array $context): array
    {
        $noLot = trim((string) ($context['no_lot'] ?? ''));
        $sequenceNo = (int) ($context['process']['sequence_no'] ?? 0);
        $canonical = $this->processContext($noLot, $sequenceNo);
        if (! $canonical) {
            return ['ok' => false, 'message' => 'Konteks proses tidak valid. Muat ulang No Lot terlebih dahulu.'];
        }
        $context = $canonical;
        $process = $context['process'] ?? [];
        $table = $this->processTable($sequenceNo);
        if ($noLot === '' || ! SchemaCache::hasTable($table)) {
            return ['ok' => false, 'message' => 'Data proses belum tersedia.'];
        }

        $state = $this->currentState($noLot, $sequenceNo);
        if ($state['start_time'] === '') {
            return ['ok' => false, 'message' => 'Proses belum di-start.'];
        }
        if (strtolower($state['finish']) === 'yes') {
            return ['ok' => false, 'message' => 'Proses sudah selesai.'];
        }
        if (strtolower($state['hold']) === 'yes') {
            return ['ok' => false, 'message' => 'Lot masih dalam status HOLD.'];
        }

        $finishTime = now();
        $startTime = date_create($state['start_time']);
        $duration = $startTime ? round(($finishTime->getTimestamp() - $startTime->getTimestamp()) / 60, 2) : 0;
        $payload = $this->onlyExistingColumns($table, [
            'finish_time' => $finishTime->format('Y-m-d H:i:s'),
            'finish' => 'yes',
            'duration' => $duration,
        ]);
        DB::table($table)->where('no_lot', $noLot)->update($payload);

        if (SchemaCache::hasTable('tbl_seq_all')) {
            $seqAllPayload = $this->onlyExistingColumns('tbl_seq_all', $payload + ['updated_at' => $finishTime->format('Y-m-d H:i:s')]);
            if ($seqAllPayload !== []) {
                DB::table('tbl_seq_all')->where('no_lot', $noLot)->where('seq', $sequenceNo)->update($seqAllPayload);
            }
        }

        if (SchemaCache::hasTable('tbl_validator')) {
            DB::table('tbl_validator')->where('no_lot', $noLot)->update(['finish' => 'yes']);
            $nextProcess = strtolower(trim((string) ($this->validatorValue(DB::table('tbl_validator')->where('no_lot', $noLot)->first(), 'next_process') ?? '')));
            if ($nextProcess === 'fin' && SchemaCache::hasTable('tbllotdaily') && SchemaCache::hasColumn('tbllotdaily', 'delv')) {
                DB::table('tbllotdaily')->where('no_lot', $noLot)->update(['delv' => 1]);
            }
        }

        return ['ok' => true, 'message' => 'Proses berhasil di-finish.', 'finish_time' => $finishTime->format('Y-m-d H:i:s'), 'duration' => $duration];
    }

    public function hold(array $context): array
    {
        $noLot = trim((string) ($context['no_lot'] ?? ''));
        $sequenceNo = (int) (($context['process']['sequence_no'] ?? 0));
        $canonical = $this->processContext($noLot, $sequenceNo);
        if (! $canonical) {
            return ['ok' => false, 'message' => 'Konteks proses tidak valid. Muat ulang No Lot terlebih dahulu.'];
        }
        $context = $canonical;
        $table = $this->processTable($sequenceNo);
        if ($noLot === '' || ! SchemaCache::hasTable($table)) {
            return ['ok' => false, 'message' => 'Data proses belum tersedia.'];
        }

        $state = $this->currentState($noLot, $sequenceNo);
        if ($state['start_time'] === '') {
            return ['ok' => false, 'message' => 'Proses belum di-start.'];
        }
        DB::table($table)->where('no_lot', $noLot)->update($this->onlyExistingColumns($table, ['hold' => 'yes']));
        if (SchemaCache::hasTable('tbl_seq_all')) {
            DB::table('tbl_seq_all')->where('no_lot', $noLot)->where('seq', $sequenceNo)->update(['hold' => 'yes', 'updated_at' => now()->format('Y-m-d H:i:s')]);
        }

        return ['ok' => true, 'message' => 'Lot berhasil diubah ke status HOLD.'];
    }

    private function validateAndAdvanceValidator(string $noLot, string $noSrp, string $idRouting, string $sequenceCode): array
    {
        if (! SchemaCache::hasTable('tbl_validator')) {
            return ['ok' => false, 'message' => 'Tabel validator tidak tersedia.'];
        }

        $validator = DB::table('tbl_validator')->where('no_lot', $noLot)->first();
        $sequenceNo = (int) str_replace('seq_', '', strtolower($sequenceCode));
        if (! $validator) {
            if ($sequenceNo !== 1) {
                return ['ok' => false, 'message' => 'Kamu sedang LOMPAT PROSES. Proses pertama harus dimulai dari sequence 1.'];
            }
            $next = $this->routingProcessAt($idRouting, 2);
            DB::table('tbl_validator')->insert([
                'no_lot' => $noLot,
                'no_srp' => $noSrp,
                'id_routing' => $idRouting,
                'Current_seq_code' => $sequenceCode,
                'next_seq_code' => $next ? 'seq_02' : 'seq_02',
                'Curr_process' => $this->routingProcessAt($idRouting, 1) ?? '',
                'next_process' => $next ?? 'FIN',
                'current_seq_count' => 1,
                'next_seq_count' => 2,
                'total_seq_count' => $this->routingCount($idRouting),
                'total_seq_remain' => max(0, $this->routingCount($idRouting) - 1),
                'finish' => 'no',
            ]);

            return ['ok' => true];
        }

        $expected = strtolower(trim((string) $this->validatorValue($validator, 'next_seq_code')));
        $finish = strtolower(trim((string) $this->validatorValue($validator, 'finish')));
        if ($finish === 'no') {
            return ['ok' => false, 'message' => 'Proses sebelumnya belum finish. Selesaikan proses aktif terlebih dahulu.'];
        }
        if ($expected !== strtolower($sequenceCode)) {
            return ['ok' => false, 'message' => 'Kamu sedang LOMPAT PROSES. Pilih sequence aktif sesuai flow.'];
        }

        $nextNo = $sequenceNo + 1;
        $next = $this->routingProcessAt($idRouting, $nextNo);
        $total = $this->routingCount($idRouting);
        DB::table('tbl_validator')->where('no_lot', $noLot)->update([
            'Current_seq_code' => $sequenceCode,
            'next_seq_code' => 'seq_'.str_pad((string) $nextNo, 2, '0', STR_PAD_LEFT),
            'Curr_process' => $this->routingProcessAt($idRouting, $sequenceNo) ?? '',
            'next_process' => $next ?? 'FIN',
            'current_seq_count' => $sequenceNo,
            'next_seq_count' => $nextNo,
            'total_seq_count' => $total,
            'total_seq_remain' => max(0, $total - $sequenceNo),
            'finish' => 'no',
        ]);

        return ['ok' => true];
    }

    private function routingProcessAt(string $idRouting, int $sequenceNo): ?string
    {
        if (! SchemaCache::hasTable('tblrouting') || $sequenceNo < 1 || $sequenceNo > LotVerifikatorService::MAX_SEQUENCE) {
            return null;
        }

        $column = 'Seq_'.str_pad((string) $sequenceNo, 2, '0', STR_PAD_LEFT);
        if (! SchemaCache::hasColumn('tblrouting', $column)) {
            return null;
        }

        return trim((string) (DB::table('tblrouting')->where('id_routing', $idRouting)->value($column) ?? '')) ?: null;
    }

    private function routingCount(string $idRouting): int
    {
        $count = 0;
        for ($index = 1; $index <= LotVerifikatorService::MAX_SEQUENCE; $index++) {
            if ($this->routingProcessAt($idRouting, $index) !== null) {
                $count++;
            }
        }

        return $count;
    }

    private function processTable(int $sequenceNo): string
    {
        return 'tbl_seq_'.str_pad((string) max(1, min(LotVerifikatorService::MAX_SEQUENCE, $sequenceNo)), 2, '0', STR_PAD_LEFT);
    }

    private function onlyExistingColumns(string $table, array $values): array
    {
        $result = [];
        foreach ($values as $column => $value) {
            if (SchemaCache::hasColumn($table, $column)) {
                $result[$column] = $value;
            }
        }

        return $result;
    }

    private function value(?object $row, string $field): string
    {
        if (! $row) {
            return '';
        }
        foreach (get_object_vars($row) as $key => $value) {
            if (strtolower((string) $key) === strtolower($field)) {
                return trim((string) ($value ?? ''));
            }
        }

        return '';
    }

    private function validatorValue(?object $row, string $field): mixed
    {
        if (! $row) {
            return null;
        }
        foreach (get_object_vars($row) as $key => $value) {
            if (strtolower((string) $key) === strtolower($field)) {
                return $value;
            }
        }

        return null;
    }

    private function emptyState(): array
    {
        return array_fill_keys(['start_time', 'finish_time', 'duration', 'tray_no', 'tray_nokecil', 'no_mesin', 'pic_01', 'pic_02', 'pic_03', 'pic_04', 'akakami', 'ojt', 'hold', 'finish'], '');
    }
}
