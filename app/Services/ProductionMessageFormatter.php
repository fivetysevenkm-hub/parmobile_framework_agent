<?php

namespace App\Services;

/**
 * Render jawaban chatbot dalam HTML Telegram beserta inline keyboard.
 *
 * Telegram hanya mendukung tag HTML sempit (<b>, <i>, <code>, <a>, ...) dan
 * membatasi pesan pada 4096 karakter, sehingga:
 *  - semua nilai dari database wajib di-escape lebih dulu,
 *  - daftar proses panjang dipaginasi alih-alih dipaksakan dalam satu pesan.
 *
 * Class ini tidak menyentuh database; seluruh isinya berasal dari
 * ProductionQueryService.
 */
class ProductionMessageFormatter
{
    private const MAX_LENGTH = 4096;

    public const PROCESSES_PER_PAGE = 6;

    private const STATUS_ICON = [
        'finished' => '✅',
        'running' => '⏳',
        'hold' => '⏸',
        'waiting' => '⚪',
    ];

    private const STATUS_LABEL = [
        'finished' => 'Finish',
        'running' => 'On Progress',
        'hold' => 'Hold',
        'waiting' => 'Belum Mulai',
    ];

    /**
     * @param  array<string, mixed>  $lot
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function lotSummary(array $lot): array
    {
        $master = $lot['master'];
        $current = $lot['current'];
        $isFinished = (bool) $lot['is_finished'];

        $lines = [];
        $lines[] = '<b>POSISI REAL TIME PRODUKSI</b>';
        $lines[] = '<code>'.$this->e($lot['no_lot']).'</code>';

        $statusIcon = $isFinished ? '🏁' : ($current['status'] === 'hold' ? '⏸' : '⏳');
        $lines[] = $statusIcon.' '.$this->positionLabel($lot)."\n";

        $lines[] = $this->section('DATA LOT');
        $lines[] = $this->field('No Part', $master['no_part'] ?? null);
        $lines[] = $this->field('Nama Part', $master['nm_part'] ?? null);
        $lines[] = $this->field('Customer', $master['customer'] ?? null);
        $lines[] = $this->field('No SRP', $master['no_srp'] ?? null);
        $lines[] = $this->field('SJ Customer', $master['no_sj_cust'] ?? null);
        $lines[] = $this->field('Lot Customer', $master['no_lot_cust'] ?? null);
        $lines[] = $this->field('Qty', isset($master['qty']) ? (string) $master['qty'] : null);

        $lines[] = $this->section('POSISI SAAT INI');
        $lines[] = $this->positionDetail($current, $isFinished, $lot['next_process'] ?? null);

        $heatTreatment = $lot['heat_treatment'];
        $lines[] = $this->section('PROSES HEAT TREATMENT');
        $lines[] = $heatTreatment === []
            ? 'Belum ada tahap Heat Treatment yang tercatat.'
            : implode("\n", array_map(fn (array $p): string => $this->heatTreatmentLine($p), $heatTreatment));

        $finished = count(array_filter($lot['processes'], fn (array $p): bool => $p['status'] === 'finished'));
        $lines[] = $this->section('RINGKASAN');
        $lines[] = $this->field('Tercatat', count($lot['processes']).' tahap');
        $lines[] = $this->field('Selesai', $finished.' tahap');
        $lines[] = $this->field('Waktu reply', now('Asia/Jakarta')->format('d-m-Y H:i').' WIB');

        $noLot = $lot['no_lot'];

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📋 Semua Proses', 'callback_data' => 'flow|'.$noLot.'|0'],
                    ['text' => '🔥 Detail HT', 'callback_data' => 'ht|'.$noLot],
                ],
                [
                    ['text' => '🍱 Tray', 'callback_data' => 'tray|'.$noLot],
                    ['text' => '👷 PIC', 'callback_data' => 'pic|'.$noLot],
                ],
                [
                    ['text' => '🔄 Refresh', 'callback_data' => 'lot|'.$noLot],
                    ['text' => '🏠 Menu', 'callback_data' => 'menu'],
                ],
            ],
        ];

        return ['text' => $this->clamp($lines), 'keyboard' => $keyboard];
    }

    /**
     * @param  array<string, mixed>  $lot
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function lotFlow(array $lot, int $page = 0): array
    {
        $processes = $lot['processes'];
        $totalPages = max(1, (int) ceil(count($processes) / self::PROCESSES_PER_PAGE));
        $page = max(0, min($page, $totalPages - 1));

        $slice = array_slice($processes, $page * self::PROCESSES_PER_PAGE, self::PROCESSES_PER_PAGE);

        $lines = [];
        $lines[] = '<b>RIWAYAT PROSES</b> <code>'.$this->e($lot['no_lot']).'</code>';
        $lines[] = 'Halaman '.($page + 1).' dari '.$totalPages."\n";

        foreach ($slice as $process) {
            $lines[] = $this->processBlock($process);
        }

        $noLot = $lot['no_lot'];

        $navRow = [];

        if ($page > 0) {
            $navRow[] = ['text' => '◀️ Sebelumnya', 'callback_data' => 'flow|'.$noLot.'|'.($page - 1)];
        }

        if ($page < $totalPages - 1) {
            $navRow[] = ['text' => 'Berikutnya ▶️', 'callback_data' => 'flow|'.$noLot.'|'.($page + 1)];
        }

        $keyboard = [];

        if ($navRow !== []) {
            $keyboard['inline_keyboard'][] = $navRow;
        }

        $keyboard['inline_keyboard'][] = [
            ['text' => '🏠 Ringkasan', 'callback_data' => 'lot|'.$noLot],
            ['text' => '🏠 Menu', 'callback_data' => 'menu'],
        ];

        return ['text' => $this->clamp($lines), 'keyboard' => $keyboard];
    }

    /**
     * @param  array<string, mixed>  $lot
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function lotHeatTreatment(array $lot): array
    {
        $heatTreatment = $lot['heat_treatment'];
        $current = $lot['current'];

        $lines = [];
        $lines[] = '<b>DETAIL HEAT TREATMENT</b>';
        $lines[] = '<code>'.$this->e($lot['no_lot']).'</code> · '.$this->e($lot['master']['nm_part'] ?? '-')."\n";

        if ($heatTreatment === []) {
            $lines[] = 'Lot ini belum melewati tahap Heat Treatment.';
            $lines[] = "\n".'Tahap yang sudah dilewati:';

            $done = array_filter($lot['processes'], fn (array $p): bool => $p['status'] === 'finished');

            $lines[] = $done === []
                ? 'Belum ada proses yang selesai.'
                : implode("\n", array_map(
                    fn (array $p): string => $this->statusIcon($p).' '.$this->processTitle($p),
                    array_values($done)
                ));
        } else {
            foreach ($heatTreatment as $process) {
                $lines[] = $this->processBlock($process);
            }

            $inHt = $current !== null && $current['is_heat_treatment'];
            $passed = count($heatTreatment);

            $lines[] = $this->section('RINGKASAN HT');
            $lines[] = $this->field('Tahap HT', $passed.' tahap');
            $lines[] = $this->field('HT terakhir', $this->processTitle(end($heatTreatment)));
            $lines[] = $this->field('Sedang di', $inHt ? $this->processTitle($current) : '-');

            $next = $lot['next_process'] ?? null;
            $lines[] = $this->field('Tahap berikutnya', $next ?? '-');
        }

        $noLot = $lot['no_lot'];

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📋 Semua Proses', 'callback_data' => 'flow|'.$noLot.'|0'],
                    ['text' => '🏠 Ringkasan', 'callback_data' => 'lot|'.$noLot],
                ],
                [
                    ['text' => '🏠 Menu', 'callback_data' => 'menu'],
                ],
            ],
        ];

        return ['text' => $this->clamp($lines), 'keyboard' => $keyboard];
    }

    /**
     * @param  array<string, mixed>  $lot
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function lotTray(array $lot): array
    {
        $processes = $lot['processes'];

        $lines = [];
        $lines[] = '<b>NOMOR TRAY PER PROSES</b>';
        $lines[] = '<code>'.$this->e($lot['no_lot']).'</code> · '.$this->e($lot['master']['nm_part'] ?? '-')."\n";

        $rows = [];

        foreach ($processes as $process) {
            $tray = $process['tray_no'];
            $small = $process['tray_nokecil'];

            $display = match (true) {
                $tray !== null && $small !== null => $tray.' / '.$small,
                $tray !== null => $tray,
                $small !== null => $small,
                default => '-',
            };

            $rows[] = $this->statusIcon($process).' '.$this->processTitle($process).'  →  <code>'.$this->e($display).'</code>';
        }

        $lines[] = $rows === [] ? 'Belum ada data tray.' : implode("\n", $rows);

        $current = $lot['current'];

        if ($current !== null && ($current['tray_no'] !== null || $current['tray_nokecil'] !== null)) {
            $lines[] = $this->section('TRAY POSISI SEKARANG');
            $lines[] = $this->field('Tahap', $this->processTitle($current));
            $lines[] = $this->field('Tray', $current['tray_no'] ?? '-');
            $lines[] = $this->field('Tray Kecil', $current['tray_nokecil'] ?? '-');
        }

        $noLot = $lot['no_lot'];

        return [
            'text' => $this->clamp($lines),
            'keyboard' => [
                'inline_keyboard' => [
                    [
                        ['text' => '📋 Semua Proses', 'callback_data' => 'flow|'.$noLot.'|0'],
                        ['text' => '🏠 Ringkasan', 'callback_data' => 'lot|'.$noLot],
                    ],
                    [['text' => '🏠 Menu', 'callback_data' => 'menu']],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $lot
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function lotPic(array $lot): array
    {
        $processes = $lot['processes'];

        $lines = [];
        $lines[] = '<b>PEKERJA YANG MENGERJAKAN</b>';
        $lines[] = '<code>'.$this->e($lot['no_lot']).'</code> · '.$this->e($lot['master']['nm_part'] ?? '-')."\n";

        $lines = array_merge($lines, $this->picLines($processes));

        $noLot = $lot['no_lot'];

        return [
            'text' => $this->clamp($lines),
            'keyboard' => [
                'inline_keyboard' => [
                    [
                        ['text' => '📋 Semua Proses', 'callback_data' => 'flow|'.$noLot.'|0'],
                        ['text' => '🏠 Ringkasan', 'callback_data' => 'lot|'.$noLot],
                    ],
                    [['text' => '🏠 Menu', 'callback_data' => 'menu']],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $processes
     * @return array<int, string>
     */
    private function picLines(array $processes): array
    {
        $withPic = array_values(array_filter(
            $processes,
            fn (array $p): bool => ($p['pic_text'] ?? null) !== null
        ));

        if ($withPic === []) {
            return ['Belum ada data operator.'];
        }

        $lines = [];

        foreach ($withPic as $process) {
            $time = $this->timeRange($process);
            $lines[] = $this->statusIcon($process).' <b>'.$this->e($process['pic_text']).'</b>';
            $lines[] = '    '.$this->processTitle($process).($time !== null ? ' · '.$time : '');
        }

        return $lines;
    }

    /**
     * Daftar hasil pencarian (surat jalan / customer / part / lot customer).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, array{icon: string, status: string, label: string}>  $statuses
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function searchResults(
        string $title,
        string $keyword,
        array $rows,
        int $page = 0,
        int $perPage = 5,
        array $statuses = []
    ): array {
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(0, min($page, $totalPages - 1));
        $slice = array_slice($rows, $page * $perPage, $perPage);

        $lines = [];
        $lines[] = '<b>'.$this->e(mb_strtoupper($title)).'</b>';
        $lines[] = 'Kata kunci: <code>'.$this->e($keyword).'</code>';
        $lines[] = $total.' lot ditemukan'."\n";

        $buttons = [];
        $row = [];

        foreach ($slice as $item) {
            $status = $statuses[strtoupper((string) $item['no_lot'])] ?? null;
            $icon = $status['icon'] ?? '•';

            $label = $status['label'] ?? null;

            $lines[] = $icon.' <b>'.$this->e($item['no_lot']).'</b>'.($label !== null ? ' · '.$label : '');
            $lines[] = '    '.$this->e($item['nm_part'] ?? '-').' · '.$this->e($item['no_part'] ?? '-');
            $lines[] = '    Qty '.($item['qty'] ?? '-').' · SJ '.($item['no_sj_cust'] ?? '-');

            $row[] = ['text' => $item['no_lot'], 'callback_data' => 'lot|'.$item['no_lot']];

            if (count($row) === 2) {
                $buttons[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        if ($total === 0) {
            $lines[] = 'Tidak ada lot yang cocok.';
        } elseif ($totalPages > 1) {
            $lines[] = 'Halaman '.($page + 1).' dari '.$totalPages;
        }

        $nav = [];

        if ($page > 0) {
            $nav[] = ['text' => '◀️', 'callback_data' => 'more|'.($page - 1)];
        }

        if ($page < $totalPages - 1) {
            $nav[] = ['text' => '▶️', 'callback_data' => 'more|'.($page + 1)];
        }

        if ($nav !== []) {
            $buttons[] = $nav;
        }

        $buttons[] = [['text' => '🏠 Menu', 'callback_data' => 'menu']];

        return ['text' => $this->clamp($lines), 'keyboard' => ['inline_keyboard' => $buttons]];
    }

    /**
     * Lot yang sedang berjalan atau tertahan.
     *
     * @param  array<int, array<string, mixed>>  $rows  hasil activeLotDetails()
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function activeLots(array $rows, int $page = 0, int $perPage = 5): array
    {
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(0, min($page, $totalPages - 1));
        $slice = array_slice($rows, $page * $perPage, $perPage);

        $lines = [];
        $lines[] = '<b>LOT SEDANG BERJALAN / HOLD</b>';
        $lines[] = $total.' lot · '.now('Asia/Jakarta')->format('d-m-Y H:i').' WIB'."\n";

        $buttons = [];
        $row = [];

        foreach ($slice as $item) {
            $current = $item['current'];

            $lines[] = $this->statusIcon($current ?? []).' <b>'.$this->e($item['no_lot']).'</b>'
                .' · '.self::STATUS_LABEL[$current['status'] ?? 'waiting'];
            $lines[] = '    '.$this->e($item['master']['nm_part'] ?? '-').' · '.$this->e($item['master']['no_part'] ?? '-');
            $lines[] = '    '.$this->processTitle($current);

            if (($current['pic_text'] ?? null) !== null) {
                $lines[] = '    👷 '.$this->e($current['pic_text']);
            }

            $row[] = ['text' => $item['no_lot'], 'callback_data' => 'lot|'.$item['no_lot']];

            if (count($row) === 2) {
                $buttons[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        if ($totalPages > 1) {
            $nav = [];

            if ($page > 0) {
                $nav[] = ['text' => '◀️', 'callback_data' => 'active|'.($page - 1)];
            }

            if ($page < $totalPages - 1) {
                $nav[] = ['text' => '▶️', 'callback_data' => 'active|'.($page + 1)];
            }

            $buttons[] = $nav;
        }

        $buttons[] = [['text' => '🏠 Menu', 'callback_data' => 'menu']];

        return ['text' => $this->clamp($lines), 'keyboard' => ['inline_keyboard' => $buttons]];
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function help(): array
    {
        $lines = [];
        $lines[] = '<b>🤖 PAR PRODUCTION BOT</b>';
        $lines[] = 'Menjawab data real time produksi langsung dari sistem.';

        $lines[] = $this->section('CARA PAKAI', '💡');
        $lines[] = 'Kirim <b>nomor lot</b> dan bot akan reporting posisi, proses, PIC, tray, serta waktunya.';
        $lines[] = 'Boleh juga pakai kalimat bebas, misalnya <i>"dimana lot CF3-030626-20"</i>.';

        $lines[] = $this->section('YANG BISA DICARI', '🔎');
        $lines[] = '• <b>Nomor lot</b> — posisi &amp; riwayat proses';
        $lines[] = '• <b>Surat jalan</b> — lot atas satu SJ';
        $lines[] = '• <b>Lot customer</b> — lot atas nomor lot customer';
        $lines[] = '• <b>No / Nama part</b> — lot yang memakai part tersebut';
        $lines[] = '• <b>Customer</b> — lot milik customer';

        $lines[] = $this->section('PERINTAH', '⌨️');
        $lines[] = '<code>/lot &lt;no lot&gt;</code> — posisi real time';
        $lines[] = '<code>/ht &lt;no lot&gt;</code> — detail Heat Treatment';
        $lines[] = '<code>/tray &lt;no lot&gt;</code> — nomor tray';
        $lines[] = '<code>/pic &lt;no lot&gt;</code> — siapa yang mengerjakan';
        $lines[] = '<code>/sj &lt;nomor sj&gt;</code> — cari per surat jalan';
        $lines[] = '<code>/part &lt;no part&gt;</code> — cari per part';
        $lines[] = '<code>/customer &lt;nama&gt;</code> — cari per customer';
        $lines[] = '<code>/hold</code> — lot sedang jalan / tertahan';
        $lines[] = '<code>/refresh</code> — ulang data lot terakhir';
        $lines[] = '<code>/batal</code> — batalkan pencarian';

        $lines[] = $this->section('CONTOH', '💬');
        $lines[] = '<i>cek lot CF3-030626-20</i>';
        $lines[] = '<i>tray lot VW4-300626-40-B</i>';
        $lines[] = '<i>cari sj HPM/CR-CO/08/26</i>';
        $lines[] = '<i>customer DIAMETRAL</i>';

        return [
            'text' => $this->clamp($lines),
            'keyboard' => [
                'inline_keyboard' => [
                    [
                        ['text' => '📦 Cari Lot', 'callback_data' => 'ask|lot'],
                        ['text' => '📄 Cari SJ', 'callback_data' => 'ask|sj'],
                    ],
                    [
                        ['text' => '🔩 Cari Part', 'callback_data' => 'ask|part'],
                        ['text' => '🏭 Customer', 'callback_data' => 'ask|customer'],
                    ],
                    [
                        ['text' => '⏸ Lot Hold', 'callback_data' => 'active|0'],
                        ['text' => '📖 Bantuan', 'callback_data' => 'help'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function prompt(string $question, string $expected, string $example, ?string $pendingIntent = null): array
    {
        $lines = [];
        $lines[] = '<b>'.e($question).'</b>';
        $lines[] = $expected;
        $lines[] = '';
        $lines[] = 'Contoh: <code>'.$this->e($example).'</code>';

        $keyboard = ['inline_keyboard' => []];

        if ($pendingIntent !== null) {
            $keyboard['inline_keyboard'][] = [
                ['text' => '❌ Batal', 'callback_data' => 'cancel'],
            ];
        }

        $keyboard['inline_keyboard'][] = [['text' => '🏠 Menu', 'callback_data' => 'menu']];

        return ['text' => $this->clamp($lines), 'keyboard' => $keyboard];
    }

    /**
     * @param  array<int, string>  $suggestions
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    public function fallback(string $message, array $suggestions = []): array
    {
        $lines = [];
        $lines[] = '<b>'.e($message).'</b>';

        if ($suggestions !== []) {
            $lines[] = '';
            $lines[] = 'Coba salah satu:';

            foreach ($suggestions as $suggestion) {
                $lines[] = '• <code>'.$this->e($suggestion).'</code>';
            }
        }

        $keyboard = ['inline_keyboard' => [
            [
                ['text' => '📦 Cari Lot', 'callback_data' => 'ask|lot'],
                ['text' => '📄 Cari SJ', 'callback_data' => 'ask|sj'],
            ],
            [
                ['text' => '🔩 Cari Part', 'callback_data' => 'ask|part'],
                ['text' => '⏸ Lot Hold', 'callback_data' => 'active|0'],
            ],
            [['text' => '📖 Bantuan', 'callback_data' => 'help']],
        ]];

        return ['text' => $this->clamp($lines), 'keyboard' => $keyboard];
    }

    public function notFound(string $kind, string $keyword, array $examples): array
    {
        $lines = [];
        $lines[] = '<b>Data tidak ditemukan</b>';
        $lines[] = $kind.' <code>'.$this->e($keyword).'</code> tidak ada di sistem.';

        if ($examples !== []) {
            $lines[] = '';
            $lines[] = 'Mungkin coba salah satu:';

            foreach ($examples as $example) {
                $lines[] = '• <code>'.$this->e($example).'</code>';
            }
        }

        return [
            'text' => $this->clamp($lines),
            'keyboard' => [
                'inline_keyboard' => [
                    [['text' => '🏠 Menu', 'callback_data' => 'menu']],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function processBlock(array $process): string
    {
        $out = [];
        $out[] = $this->statusIcon($process).' <b>'.$this->processTitle($process).'</b> · '.self::STATUS_LABEL[$process['status']];

        $time = $this->timeRange($process);

        if ($time !== null) {
            $out[] = '    '.$time;
        }

        if (($process['pic_text'] ?? null) !== null) {
            $out[] = '    👷 '.$this->e($process['pic_text']);
        }

        $details = [];

        if (($process['tray_no'] ?? null) !== null) {
            $tray = $process['tray_no'];

            if (($process['tray_nokecil'] ?? null) !== null) {
                $tray .= ' / '.$process['tray_nokecil'];
            }

            $details[] = 'Tray '.$tray;
        }

        if (($process['no_mesin'] ?? null) !== null) {
            $details[] = 'Mesin '.$process['no_mesin'];
        }

        if (($process['elapsed_label'] ?? null) !== null) {
            $details[] = $process['status'] === 'finished'
                ? 'Durasi '.$process['elapsed_label']
                : 'Berjalan '.$process['elapsed_label'];
        }

        if ($details !== []) {
            $out[] = '    '.implode(' · ', array_map(fn (string $d): string => $this->e($d), $details));
        }

        return implode("\n", $out);
    }

    private function heatTreatmentLine(array $process): string
    {
        $icon = $this->statusIcon($process);
        $title = $this->e($this->processTitle($process));
        $time = $this->timeRange($process);
        $pic = $process['pic_text'] ?? null;

        $line = $icon.' '.$title;

        if ($time !== null) {
            $line .= ' · '.$time;
        }

        if ($pic !== null) {
            $line .= ' · 👷 '.$this->e($pic);
        }

        return $line;
    }

    private function timeRange(array $process): ?string
    {
        $start = $this->formatDate($process['start_time'] ?? null);
        $finish = $this->formatDate($process['finish_time'] ?? null);

        if ($start === null && $finish === null) {
            return null;
        }

        if ($start === null) {
            return 'Selesai '.$finish;
        }

        return $start.($finish !== null ? ' → '.$finish : ' → berjalan');
    }

    private function formatDate(?string $value): ?string
    {
        if ($value === null || $value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $value, 'Asia/Jakarta')
                ->format('d-m H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function processTitle(array $process): string
    {
        $seq = str_pad((string) ($process['seq'] ?? 0), 2, '0', STR_PAD_LEFT);
        $name = (string) ($process['nm_seq'] ?? '-');
        $code = (string) ($process['id_seq'] ?? '');

        $badges = '';

        if (! empty($process['is_flpp'])) {
            $badges .= ' [FLPP]';
        }

        if (! empty($process['is_ojt'])) {
            $badges .= ' [OJT]';
        }

        return $seq.' '.$this->e($name).($code !== '' ? ' ('.$this->e($code).')' : '').$badges;
    }

    private function positionLabel(array $lot): string
    {
        $current = $lot['current'];

        if (! empty($lot['is_finished'])) {
            return 'Lot selesai pada tahap '.$this->processTitle($current ?? ['seq' => 0, 'nm_seq' => '-', 'id_seq' => '']);
        }

        if ($current === null) {
            return 'Belum ada proses tercatat';
        }

        return match ($current['status']) {
            'hold' => 'HOLD di tahap '.$this->processTitle($current),
            'running' => 'Berjalan di tahap '.$this->processTitle($current),
            default => 'Tahap terakhir selesai: '.$this->processTitle($current),
        };
    }

    private function positionDetail(?array $current, bool $isFinished, ?string $next): string
    {
        if ($current === null) {
            return 'Belum ada data proses pada lot ini.';
        }

        $parts = [];

        $parts[] = $this->statusIcon($current).' <b>'.$this->processTitle($current).'</b> · '.self::STATUS_LABEL[$current['status']];

        $time = $this->timeRange($current);

        if ($time !== null) {
            $parts[] = 'Waktu: '.$time;
        }

        if (($current['pic_text'] ?? null) !== null) {
            $parts[] = '👷 PIC: '.$this->e($current['pic_text']);
        }

        $tray = $current['tray_no'] ?? null;

        if ($tray !== null) {
            $parts[] = '🍱 Tray: '.$this->e($tray.($current['tray_nokecil'] !== null ? ' / '.$current['tray_nokecil'] : ''));
        }

        if (($current['no_mesin'] ?? null) !== null) {
            $parts[] = '⚙️ Mesin: '.$this->e($current['no_mesin']);
        }

        if (! empty($current['is_flpp'])) {
            $parts[] = '🚨 <b>Ada FLPP (proses bermasalah)</b>';
        }

        if (($current['elapsed_label'] ?? null) !== null) {
            $parts[] = ($current['status'] === 'finished' ? 'Durasi: ' : 'Sudah berjalan: ').$this->e($current['elapsed_label']);
        }

        if ($isFinished) {
            $parts[] = '🏁 Lot sudah selesai seluruh proses.';
        } elseif ($next !== null) {
            $parts[] = '➡️ Berikutnya: <b>'.$this->e($next).'</b>';
        }

        return implode("\n", $parts);
    }

    private function statusIcon(array $process): string
    {
        return self::STATUS_ICON[$process['status'] ?? 'waiting'] ?? '⚪';
    }

    /**
     * Judul bagian. Emoji opsional supaya pemanggil bisa memilih bagian yang
     * perlu ikon tanpa memaksakan ikon di semua laporan.
     */
    private function section(string $title, string $emoji = ''): string
    {
        return "\n".'<b>'.$emoji.$this->e($title).'</b>';
    }

    private function field(string $label, ?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return $label.' : -';
        }

        return $label.' : '.$this->e($this->shorten($value, 60));
    }

    private function shorten(string $value, int $limit): string
    {
        return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1).'…';
    }

    /**
     * Escape karakter khusus HTML Telegram.
     */
    private function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function clamp(array $lines): string
    {
        $text = implode("\n", array_filter($lines, fn ($line): bool => $line !== null));

        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_LENGTH - 30)."\n\n… (pesan dipotong)";
    }
}
