<?php

namespace App\Services;

use App\Support\SchemaCache;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sumber data tunggal untuk chatbot produksi.
 *
 * Semua angka yang dikirim ke user Telegram berasal dari kelas ini. Tidak ada
 * satupun nilai yang berasal dari output LLM.
 *
 * Kontrak data mengikuti tabel `tbl_seq_all` yang sudah dipakai oleh menu
 * Real Time Production, ditambah `tbl_validator` / `tbl_sequence` untuk
 * memproyeksikan proses berikutnya.
 */
class ProductionQueryService
{
    /**
     * Daftar kode proses yang termasuk tahap Heat Treatment.
     *
     * Dipakai untuk menjawab pertanyaan "sudah lewat proses HT apa saja?"
     * dan "proses HT berikutnya apa?".
     */
    public const HEAT_TREATMENT_STAGES = [
        'S04' => 'Pre Heat',
        'S06' => 'HCQT Batch',
        'S07' => 'HCNQT Batch',
        'S08' => 'HCNQT Continous',
        'S10' => 'HCQT Continous',
        'S11' => 'HQT',
        'S18' => 'Tempering',
        'S21' => 'Vacuum Treatment',
        'S22' => 'Sub Zero',
        'S24' => 'Tempering Ke 2',
        'S32' => 'Jigset Press Temper',
        'S34' => 'Hantenki Before',
        'S35' => 'Hantenki After',
        'S37' => 'Hantenki After 2',
    ];

    public const MAX_LOT_RESULTS = 8;

    public function findLots(string $noLot): array
    {
        $exact = $this->normalizeLot($noLot);

        /*
         * Pencocokan memakai LIKE tanpa wildcard, bukan operator "=".
         *
         * Lot yang sama bisa tersimpan dengan kapitalisasi berbeda
         * (cf3-030626-20 dan CF3-030626-20). Operator "=" bergantung pada
         * collation database: di MySQL default ia tidak membedakan huruf
         * besar-kecil, di SQLite atau collation yang case-sensitive ia
         * membedakan, sehingga satu tahap bisa hilang tanpa jejak. LIKE tanpa
         * wildcard bersifat case-insensitive di kedua mesin dan tetap bisa
         * memakai index.
         */
        $rows = DB::table('tbl_seq_all')
            ->where('no_lot', 'like', $this->escapeLike($exact))
            ->orderBy('seq')
            ->orderBy('id')
            ->get();

        if ($rows->isNotEmpty()) {
            return $this->lotPayload($rows);
        }

        // Fallback: pengguna mungkin mengetik sebagian nomor lot.
        $rows = DB::table('tbl_seq_all')
            ->where('no_lot', 'like', $this->escapeLike($exact).'%')
            ->orderBy('no_lot')
            ->orderBy('seq')
            ->get();

        return $rows->isEmpty() ? [] : $this->lotPayload($rows);
    }

    /**
     * Lot terbaru sebagai contoh pada pesan "tidak ditemukan".
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentLots(int $limit = 3): array
    {
        $rows = DB::table('tbl_seq_all')
            ->select($this->listColumns())
            ->where('seq', 1)
            ->orderByDesc('updated_at')
            ->limit(max(1, $limit))
            ->get();

        return $this->mapListRows($rows->all());
    }

    /**
     * Status ringkas beberapa lot dalam satu query.
     *
     * Dipakai oleh daftar hasil pencarian supaya tidak terjadi N+1 query saat
     * merender 5-10 baris hasil.
     *
     * @param  array<int, string>  $noLots
     * @return array<string, array{icon: string, status: string, label: string}>
     */
    public function lotStatuses(array $noLots): array
    {
        $noLots = array_values(array_filter(array_map(
            fn (string $noLot): string => strtoupper(trim($noLot)),
            $noLots
        )));

        if ($noLots === []) {
            return [];
        }

        $rows = DB::table('tbl_seq_all')
            ->select(['no_lot', 'seq', 'id_seq', 'nm_seq', 'finish', 'hold', 'start_time', 'no_srp'])
            ->whereIn('no_lot', $noLots)
            ->orderBy('seq')
            ->orderBy('id')
            ->get();

        $grouped = $rows->groupBy(fn (object $row): string => strtoupper((string) $row->no_lot));

        $result = [];

        foreach ($grouped as $key => $lotRows) {
            $processes = array_values(array_map(
                fn (object $row): array => $this->processPayload($row, (string) $key),
                $lotRows->all()
            ));

            $current = $this->currentProcess($processes);
            $isFinished = $this->isLotFinished($processes);

            // Lot boleh sudah selesai di tahap terakhirnya tetapi belum sampai
            // tahap akhir routing, jadi status lot harus berasal dari
            // isFinished dulu, bukan dari status proses terakhir.
            $status = $isFinished
                ? 'finished'
                : $this->lotStatusFromProcess($current);

            $result[(string) $key] = [
                'icon' => match ($status) {
                    'finished' => '🏁',
                    'hold' => '⏸',
                    'running' => '⏳',
                    default => '⚪',
                },
                'status' => $status,
                'label' => self::statusLabel($status),
            ];
        }

        return $result;
    }

    /**
     * Status lot dari proses yang sedang berjalan.
     *
     * Lot yang belum selesai memang sedang diproses, meskipun proses
     * terakhirnya sudah finish dan tahap berikutnya belum direkam.
     *
     * @param  array<string, mixed>|null  $current
     */
    private function lotStatusFromProcess(?array $current): string
    {
        if ($current === null) {
            return 'waiting';
        }

        return ($current['status'] ?? 'waiting') === 'hold' ? 'hold' : 'running';
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'finished' => 'Selesai',
            'running' => 'On Progress',
            'hold' => 'Hold',
            default => 'Belum Mulai',
        };
    }

    /**
     * Cari lot berdasarkan surat jalan customer.
     *
     * Satu surat jalan dapat berisi puluhan lot, jadi hasilnya berupa daftar.
     */
    public function findLotsBySuratJalan(string $sj, int $limit = self::MAX_LOT_RESULTS): array
    {
        return $this->searchWithRelaxation(
            fn (string $keyword): array => $this->searchSuratJalan($keyword, $limit),
            $sj
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLotsByCustomer(string $customer, int $limit = self::MAX_LOT_RESULTS): array
    {
        return $this->searchWithRelaxation(
            fn (string $keyword): array => $this->searchCustomer($keyword, $limit),
            $customer
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLotsByPart(string $part, int $limit = self::MAX_LOT_RESULTS): array
    {
        return $this->searchWithRelaxation(
            fn (string $keyword): array => $this->searchPart($keyword, $limit),
            $part
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLotsByLotCustomer(string $lotCust, int $limit = self::MAX_LOT_RESULTS): array
    {
        $results = $this->searchWithRelaxation(
            fn (string $keyword): array => $this->searchLotCustomer($keyword, $limit),
            $lotCust
        );

        return $results;
    }

    /**
     * Kata kunci dari Telegram sering diikuti kata lain yang tidak relevan
     * ("DIAMETRAL ada", "spindle apa"). Bila pencarian persis nihil, coba
     * lagi dengan potongan kata yang lebih pendek sebelum menyerah.
     *
     * @param  callable(string): array<int, array<string, mixed>>  $search
     * @return array<int, array<string, mixed>>
     */
    private function searchWithRelaxation(callable $search, string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $exact = $search($query);

        if ($exact !== []) {
            return $exact;
        }

        foreach ($this->relaxedCandidates($query) as $candidate) {
            $results = $search($candidate);

            if ($results !== []) {
                return $results;
            }
        }

        return [];
    }

    /**
     * Turunkan progressively: "PT DIAMETRAL ada" -> "PT DIAMETRAL" -> "DIAMETRAL".
     *
     * @return array<int, string>
     */
    private function relaxedCandidates(string $query): array
    {
        $candidates = [];
        $parts = preg_split('/\s+/u', $query) ?: [];

        for ($count = count($parts) - 1; $count >= 1; $count--) {
            $candidate = implode(' ', array_slice($parts, 0, $count));

            if (mb_strlen($candidate) >= 3) {
                $candidates[] = $candidate;
            }
        }

        // Untuk nomor surat jalan, bagian sebelum garis miring pertama juga
        // sering sudah unik, contoh "HPM/CR-CO/08/26" -> "HPM".
        if (str_contains($query, '/')) {
            $head = trim(explode('/', $query)[0]);

            if (mb_strlen($head) >= 3) {
                $candidates[] = $head;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchSuratJalan(string $sj, int $limit): array
    {
        $keyword = $this->escapeLike($sj);

        $rows = DB::table('tbl_seq_all')
            ->select($this->listColumns())
            ->where('seq', 1)
            ->where('no_sj_cust', 'like', '%'.$keyword.'%')
            ->orderByDesc('no_lot')
            ->limit($limit)
            ->get();

        return $this->mapListRows($rows->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchCustomer(string $customer, int $limit): array
    {
        $keyword = $this->escapeLike($customer);

        $rows = DB::table('tbl_seq_all')
            ->select($this->listColumns())
            ->where('seq', 1)
            ->where('customer', 'like', '%'.$keyword.'%')
            ->orderByDesc('no_lot')
            ->limit($limit)
            ->get();

        return $this->mapListRows($rows->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchPart(string $part, int $limit): array
    {
        $keyword = $this->escapeLike($part);

        $rows = DB::table('tbl_seq_all')
            ->select($this->listColumns())
            ->where('seq', 1)
            ->where(function (Builder $query) use ($keyword): void {
                $query->where('no_part', 'like', '%'.$keyword.'%')
                    ->orWhere('nm_part', 'like', '%'.$keyword.'%');
            })
            ->orderByDesc('no_lot')
            ->limit($limit)
            ->get();

        return $this->mapListRows($rows->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchLotCustomer(string $lotCust, int $limit): array
    {
        $keyword = $this->escapeLike($lotCust);

        $rows = DB::table('tbl_seq_all')
            ->select($this->listColumns())
            ->where('seq', 1)
            ->where('no_lot_cust', 'like', '%'.$keyword.'%')
            ->where('no_lot_cust', '<>', '-')
            ->orderByDesc('no_lot')
            ->limit($limit)
            ->get();

        return $this->mapListRows($rows->all());
    }

    /**
     * Lot yang sedang berjalan / tertahan, untuk perintah "/hold" dan "/progress".
     */
    public function findActiveLots(int $limit = self::MAX_LOT_RESULTS): array
    {
        $rows = DB::table('tbl_seq_all')
            ->select($this->listColumns())
            ->where('seq', 1)
            ->whereIn('no_lot', $this->activeLotNumbers($limit * 3))
            ->orderByDesc('no_lot')
            ->limit($limit)
            ->get();

        return $this->mapListRows($rows->all());
    }

    /**
     * Versi lengkap dari findActiveLots: sudah berisi posisi sekarang, PIC,
     * tray, dan proses berikutnya, sehingga siap langsung ditampilkan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeLotDetails(int $limit = self::MAX_LOT_RESULTS): array
    {
        $noLots = $this->activeLotNumbers($limit);

        if ($noLots === []) {
            return [];
        }

        $rows = DB::table('tbl_seq_all')
            ->whereIn('no_lot', $noLots)
            ->orderBy('seq')
            ->orderBy('id')
            ->get();

        $details = $this->lotPayload($rows);

        // Urutkan berdasarkan lot yang paling baru bergerak, bukan nomor lot.
        usort($details, function (array $a, array $b): int {
            $aTime = $a['current']['updated_at'] ?? $a['current']['start_time'] ?? '';
            $bTime = $b['current']['updated_at'] ?? $b['current']['start_time'] ?? '';

            return strcmp((string) $bTime, (string) $aTime);
        });

        return array_slice($details, 0, $limit);
    }

    /**
     * @return array<int, string>
     */
    private function activeLotNumbers(int $limit): array
    {
        return DB::table('tbl_seq_all')
            ->where('finish', '<>', 'yes')
            ->where(function (Builder $query): void {
                $query->where('hold', 'yes')
                    ->orWhereNotNull('start_time');
            })
            ->orderByDesc('updated_at')
            ->distinct()
            ->limit($limit)
            ->pluck('no_lot')
            ->all();
    }

    /**
     * Susun payload lengkap satu lot: master, daftar proses, posisi saat ini,
     * proses Heat Treatment, dan proses berikutnya.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function lotPayload($rows): array
    {
        /*
         * Tabel legacy menyimpan nomor lot dengan kapitalisasi tidak konsisten,
         * misalnya "cf3-030626-20" dan "CF3-030626-20" bisa menunjuk lot fisik
         * yang sama. Barisnya sudah terkumpul di $rows; di sini digabung lagi
         * dengan key uppercase agar satu lot tidak terpotong jadi dua jawaban.
         */
        $grouped = $rows->groupBy(fn (object $row): string => strtoupper((string) $row->no_lot));

        $result = [];

        foreach ($grouped as $lotRows) {
            $ordered = $lotRows->sortBy([['seq', 'asc'], ['id', 'asc']])->values();
            $all = $ordered->all();

            $displayNoLot = $this->dominantNoLot($all);
            $master = $this->masterRow($all);

            $processes = array_map(
                fn (object $row): array => $this->processPayload($row, $displayNoLot),
                $all
            );

            $current = $this->currentProcess($processes);
            $heatTreatment = array_values(array_filter(
                $processes,
                fn (array $process): bool => $this->isHeatTreatment((string) ($process['id_seq'] ?? ''))
            ));

            $isFinished = $this->isLotFinished($processes);

            $result[] = [
                'no_lot' => $displayNoLot,
                'master' => [
                    'no_lot' => $displayNoLot,
                    'no_srp' => $this->clean($master->no_srp ?? null),
                    'no_part' => $this->clean($master->no_part ?? null),
                    'nm_part' => $this->clean($master->nm_part ?? null),
                    'customer' => $this->clean($master->customer ?? null),
                    'no_sj_cust' => $this->clean($master->no_sj_cust ?? null),
                    'no_lot_cust' => $this->meaningful($master->no_lot_cust ?? null),
                    'qty' => $master->qty ?? null,
                    'id_routing' => $this->clean($master->id_routing ?? null),
                ],
                'processes' => $processes,
                'current' => $current,
                'heat_treatment' => $heatTreatment,
                'is_finished' => $isFinished,
                'next_process' => $isFinished ? null : $this->nextProcessName($processes),
            ];
        }

        return $result;
    }

    /**
     * Ambil kapitalisasi nomor lot yang paling sering dipakai.
     *
     * @param  array<int, object>  $rows
     */
    private function dominantNoLot(array $rows): string
    {
        $counts = [];

        foreach ($rows as $row) {
            $value = (string) $row->no_lot;
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * Baris seq=1 menjadi header lot. Bila tidak ada, pakai baris yang paling
     * lengkap isinya supaya no part / customer tetap terisi.
     *
     * @param  array<int, object>  $rows
     */
    private function masterRow(array $rows): object
    {
        foreach ($rows as $row) {
            if ((int) ($row->seq ?? 0) === 1) {
                return $row;
            }
        }

        usort($rows, fn (object $a, object $b): int => $this->completeness($b) <=> $this->completeness($a));

        return $rows[0];
    }

    private function completeness(object $row): int
    {
        $score = 0;

        foreach (['no_srp', 'no_part', 'nm_part', 'customer', 'no_sj_cust', 'no_lot_cust', 'qty'] as $column) {
            if ($this->meaningful($row->{$column} ?? null) !== null) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @return array<string, mixed>
     */
    private function processPayload(object $row, string $displayNoLot): array
    {
        $start = $this->parseDate($row->start_time ?? null);
        $finish = $this->parseDate($row->finish_time ?? null);

        $pics = array_values(array_filter([
            $this->clean($row->pic_01 ?? null),
            $this->clean($row->pic_02 ?? null),
            $this->clean($row->pic_03 ?? null),
            $this->clean($row->pic_04 ?? null),
        ]));

        return [
            'no_lot' => $displayNoLot,
            'seq' => (int) ($row->seq ?? 0),
            'id_seq' => (string) ($row->id_seq ?? ''),
            'nm_seq' => $this->processName($row),
            'no_srp' => $this->clean($row->no_srp ?? null),
            'status' => $this->processStatus($row, $start, $finish),
            'start_time' => $start?->format('Y-m-d H:i:s'),
            'finish_time' => $finish?->format('Y-m-d H:i:s'),
            'elapsed_label' => $this->elapsedLabel($start, $finish, (string) ($row->finish ?? '')),
            'duration' => $this->durationLabel($row->duration ?? null),
            'pics' => $pics,
            'pic_text' => $pics === [] ? null : implode(', ', $pics),
            'tray_no' => $this->meaningful($row->tray_no ?? null),
            'tray_nokecil' => $this->meaningful($row->tray_nokecil ?? null),
            'no_mesin' => $this->clean($row->no_mesin ?? null),
            'is_flpp' => $this->isYes($row->akakami ?? null),
            'is_ojt' => $this->isYes($row->ojt ?? null),
            'is_heat_treatment' => $this->isHeatTreatment((string) ($row->id_seq ?? '')),
            'updated_at' => $row->updated_at ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $processes
     * @return array<string, mixed>|null
     */
    private function currentProcess(array $processes): ?array
    {
        // Prioritas 1: proses yang tertahan (hold) - paling perlu diketahui.
        foreach ($processes as $process) {
            if ($process['status'] === 'hold') {
                return $process + ['priority' => 'hold'];
            }
        }

        // Prioritas 2: proses berjalan tanpa hold.
        foreach ($processes as $process) {
            if ($process['status'] === 'running') {
                return $process + ['priority' => 'running'];
            }
        }

        // Prioritas 3: proses terakhir yang benar-benar selesai.
        $finished = array_values(array_filter(
            $processes,
            fn (array $process): bool => $process['status'] === 'finished'
        ));

        if ($finished !== []) {
            return $finished[count($finished) - 1] + ['priority' => 'last_finished'];
        }

        return null;
    }

    /**
     * Lot dianggap selesai bila seluruh tahap pada routing SRP-nya sudah
     * tercatat finish. Angka tahap diambil dari tblrouting, bukan dari
     * jumlah baris, karena baris bisa saja belum dibuat untuk tahap terakhir.
     *
     * @param  array<int, array<string, mixed>>  $processes
     */
    private function isLotFinished(array $processes): bool
    {
        if ($processes === []) {
            return false;
        }

        $total = $this->routingStepCount($processes[0]['no_srp'] ?? null, count($processes));
        $finished = count(array_filter($processes, fn (array $p): bool => $p['status'] === 'finished'));

        return $total > 0 && $finished >= $total;
    }

    /**
     * Tentukan tahap berikutnya.
     *
     * Sumber utama adalah selisih antara routing SRP dan proses yang sudah
     * tercatat, karena selalu konsisten dengan data apa adanya. tbl_validator
     * hanya dipakai sebagai cadangan ketika routing tidak ditemukan.
     *
     * @param  array<int, array<string, mixed>>  $processes
     */
    private function nextProcessName(array $processes): ?string
    {
        $code = $this->nextRoutingCode($processes);

        if ($code !== null) {
            return $this->sequenceName($code);
        }

        return $this->validatorNextName($processes);
    }

    /**
     * @param  array<int, array<string, mixed>>  $processes
     */
    private function validatorNextName(array $processes): ?string
    {
        if (! SchemaCache::hasTable('tbl_validator') || $processes === []) {
            return null;
        }

        $noLot = (string) ($processes[0]['no_lot'] ?? '');
        $validator = DB::table('tbl_validator')->where('no_lot', $noLot)->first();

        if (! $validator) {
            return null;
        }

        $nextCode = strtoupper(trim((string) ($this->column($validator, 'next_process') ?? '')));
        $currentCode = strtoupper(trim((string) ($this->column($validator, 'Curr_process') ?? '')));
        $finish = strtolower(trim((string) ($this->column($validator, 'finish') ?? '')));

        $candidate = ($finish === 'no' || $nextCode === '') ? $currentCode : $nextCode;

        return $candidate !== '' && $candidate !== 'FIN' ? $this->sequenceName($candidate) : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $processes
     */
    private function nextRoutingCode(array $processes): ?string
    {
        $noSrp = $processes[0]['no_srp'] ?? null;

        if ($noSrp === null || ! SchemaCache::hasTable('tblsrp') || ! SchemaCache::hasTable('tblrouting')) {
            return null;
        }

        $srp = DB::table('tblsrp')->where('no_srp', $noSrp)->first();

        if (! $srp || empty($srp->id_routing)) {
            return null;
        }

        $routing = DB::table('tblrouting')->where('id_routing', $srp->id_routing)->first();

        if (! $routing) {
            return null;
        }

        $done = array_map(fn (array $p): string => strtoupper((string) $p['id_seq']), $processes);

        for ($index = 1; $index <= 26; $index++) {
            $code = strtoupper(trim((string) ($routing->{'Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)} ?? '')));

            if ($code === '') {
                break;
            }

            if (! in_array($code, $done, true)) {
                return $code;
            }
        }

        return null;
    }

    private function sequenceName(string $idSeq): string
    {
        if (isset(self::HEAT_TREATMENT_STAGES[$idSeq])) {
            return self::HEAT_TREATMENT_STAGES[$idSeq];
        }

        if (SchemaCache::hasTable('tblsequence')) {
            $name = DB::table('tblsequence')->where('id_seq', $idSeq)->value('nm_sequence');

            if (! empty($name)) {
                return (string) $name;
            }
        }

        return $idSeq;
    }

    private function routingStepCount(?string $noSrp, int $fallback): int
    {
        if ($noSrp === null || ! SchemaCache::hasTable('tblsrp') || ! SchemaCache::hasTable('tblrouting')) {
            return $fallback;
        }

        $count = Cache::remember(
            'bot:routing_steps:'.$noSrp,
            now()->addMinutes(30),
            function () use ($noSrp): int {
                $srp = DB::table('tblsrp')->where('no_srp', $noSrp)->first();

                if (! $srp || empty($srp->id_routing)) {
                    return 0;
                }

                $routing = DB::table('tblrouting')->where('id_routing', $srp->id_routing)->first();

                if (! $routing) {
                    return 0;
                }

                $steps = 0;

                for ($index = 1; $index <= 26; $index++) {
                    $value = trim((string) ($routing->{'Seq_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)} ?? ''));

                    if ($value === '') {
                        break;
                    }

                    $steps++;
                }

                return $steps;
            }
        );

        return $count > 0 ? $count : $fallback;
    }

    private function processName(object $row): string
    {
        $name = $this->clean($row->nm_seq ?? null);

        if ($name !== null) {
            return $name;
        }

        $idSeq = (string) ($row->id_seq ?? '');

        return $idSeq !== '' ? $this->sequenceName($idSeq) : '-';
    }

    private function processStatus(object $row, ?\DateTimeInterface $start, ?\DateTimeInterface $finish): string
    {
        if ($this->isYes($row->finish ?? null) || $finish !== null) {
            return 'finished';
        }

        if ($this->isYes($row->hold ?? null)) {
            return 'hold';
        }

        if ($start !== null) {
            return 'running';
        }

        return 'waiting';
    }

    private function isHeatTreatment(string $idSeq): bool
    {
        return array_key_exists(strtoupper($idSeq), self::HEAT_TREATMENT_STAGES);
    }

    private function isYes(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['yes', 'y', '1', 'true'], true);
    }

    private function elapsedLabel(?\DateTimeInterface $start, ?\DateTimeInterface $finish, string $finishFlag): ?string
    {
        if ($start === null) {
            return null;
        }

        $now = now('Asia/Jakarta');

        $end = ($finish !== null || $this->isYes($finishFlag)) ? $finish : $now;

        if ($end === null) {
            return null;
        }

        $minutes = (int) round(($end->getTimestamp() - $start->getTimestamp()) / 60);

        if ($minutes < 0) {
            $minutes = 0;
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remaining = $minutes % 60;

        if ($days > 0) {
            return $days.'h '.$hours.'j '.$remaining.'m';
        }

        if ($hours > 0) {
            return $hours.'j '.$remaining.'m';
        }

        return $remaining.'m';
    }

    private function durationLabel(mixed $duration): ?string
    {
        if ($duration === null || $duration === '') {
            return null;
        }

        $value = round((float) $duration, 2);

        if ($value <= 0) {
            return null;
        }

        return $value.' menit';
    }

    /**
     * Tabel legacy menyimpan waktu kosong sebagai '0000-00-00 00:00:00'.
     */
    private function parseDate(mixed $value): ?\DateTimeInterface
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $value, 'Asia/Jakarta')
                ?: \Carbon\Carbon::parse($value, 'Asia/Jakarta');
        } catch (\Throwable) {
            return null;
        }
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Nilai "-" pada lot customer berarti tidak ada, bukan data.
     */
    private function meaningful(mixed $value): ?string
    {
        $value = $this->clean($value);

        return ($value === null || $value === '-' || $value === 'N/A') ? null : $value;
    }

    public function normalizeLot(string $noLot): string
    {
        $noLot = strtoupper(trim($noLot));
        $noLot = preg_replace('/\s+/', '-', $noLot) ?? $noLot;

        return trim($noLot, '-');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    /**
     * @return array<int, string>
     */
    private function listColumns(): array
    {
        return ['no_lot', 'no_srp', 'no_part', 'nm_part', 'no_lot_cust', 'no_sj_cust', 'customer', 'qty'];
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapListRows(array $rows): array
    {
        return array_map(fn (object $row): array => [
            // Nomor lot selalu ditampilkan uppercase supaya konsisten dengan
            // tampilan detail, yang memakai key gabung uppercase.
            'no_lot' => strtoupper(trim((string) $row->no_lot)),
            'no_srp' => $this->clean($row->no_srp ?? null),
            'no_part' => $this->clean($row->no_part ?? null),
            'nm_part' => $this->clean($row->nm_part ?? null),
            'no_lot_cust' => $this->meaningful($row->no_lot_cust ?? null),
            'no_sj_cust' => $this->clean($row->no_sj_cust ?? null),
            'customer' => $this->clean($row->customer ?? null),
            'qty' => $row->qty ?? null,
        ], $rows);
    }

    private function column(object $row, string $name): mixed
    {
        foreach (get_object_vars($row) as $key => $value) {
            if (strtolower((string) $key) === strtolower($name)) {
                return $value;
            }
        }

        return null;
    }
}
