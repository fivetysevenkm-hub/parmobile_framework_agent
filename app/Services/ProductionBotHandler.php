<?php

namespace App\Services;

use App\Services\ProductionIntentService as Intent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Otak percakapan bot produksi.
 *
 * Menggabungkan tiga lapis yang sudah dipisah agar mudah diuji:
 *  - ProductionIntentService     -> mengubah pesan menjadi intent + kata kunci
 *  - ProductionQueryService      -> mengambil data nyata dari database
 *  - ProductionMessageFormatter  -> merender jawaban HTML + tombol
 *
 * State percakapan (mis. "user sedang ditanya nomor lot") disimpan di cache
 * dengan masa berlaku terbatas sehingga context akan kedaluwarsa sendiri.
 */
class ProductionBotHandler
{
    /**
     * Lama cache hasil pencarian, dalam detik.
     *
     * Cukup pendek supaya data baru tetap terpantau, cukup panjang supaya user
     * tidak menunggu scan 141 ribu baris untuk pertanyaan yang sama.
     */
    private const SEARCH_CACHE_SECONDS = 120;

    public function __construct(
        private readonly Intent $intents,
        private readonly ProductionQueryService $query,
        private readonly ProductionMessageFormatter $formatter,
    ) {
    }

    /**
     * Id chat Telegram yang sedang dilayani.
     *
     * Dipakai untuk menyimpan konteks hasil pencarian terakhir, sehingga tombol
     * "Berikutnya" tahu harus membuka halaman mana.
     */
    private ?int $chatId = null;

    public function setChatId(?int $chatId): self
    {
        $this->chatId = $chatId;

        return $this;
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    public function handleText(string $text, string $state = ''): array
    {
        $pendingIntent = $state !== '' ? $state : null;

        $parsed = $this->intents->parse($text, $pendingIntent);

        Log::info('Pesan bot produksi diklasifikasi.', [
            'text' => mb_substr($text, 0, 120),
            'intent' => $parsed['intent'],
            'query' => $parsed['query'],
            'source' => $parsed['source'],
        ]);

        return match ($parsed['intent']) {
            Intent::INTENT_CANCEL => $this->wrap($this->cancel(), ''),
            Intent::INTENT_HELP => $this->wrap($this->formatter->help(), ''),
            Intent::INTENT_ACTIVE => $this->wrap($this->activeLots(0), ''),
            Intent::INTENT_SEARCH_SJ => $this->searchSuratJalan($parsed['query']),
            Intent::INTENT_SEARCH_CUSTOMER => $this->searchCustomer($parsed['query']),
            Intent::INTENT_SEARCH_PART => $this->searchPart($parsed['query']),
            Intent::INTENT_LOT,
            Intent::INTENT_LOT_HT,
            Intent::INTENT_LOT_TRAY,
            Intent::INTENT_LOT_PIC => $this->lotIntent($parsed['intent'], $parsed['query']),
            default => $this->wrap($this->unknown($parsed['source']), ''),
        };
    }

    /**
     * Tangani tombol inline.
     *
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    public function handleCallback(string $data): array
    {
        $parts = explode('|', $data);
        $action = $parts[0] ?? '';

        return match ($action) {
            'lot' => $this->openLot($parts[1] ?? ''),
            'flow' => $this->openLotView($parts[1] ?? '', 'flow', (int) ($parts[2] ?? 0)),
            'ht' => $this->openLotView($parts[1] ?? '', 'ht'),
            'tray' => $this->openLotView($parts[1] ?? '', 'tray'),
            'pic' => $this->openLotView($parts[1] ?? '', 'pic'),
            'active' => $this->wrap($this->activeLots((int) ($parts[1] ?? 0)), ''),
            'ask' => $this->askFor($parts[1] ?? 'lot'),
            'more' => $this->nextResultPage((int) ($parts[1] ?? 0)),
            'help' => $this->wrap($this->formatter->help(), ''),
            'menu' => $this->wrap($this->formatter->help(), ''),
            'cancel' => $this->wrap($this->cancel(), ''),
            default => $this->wrap($this->unknown('callback'), ''),
        };
    }

    /**
     * Tombol yang meminta user mengetik nilai, mis. "Cari Lot".
     *
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function askFor(string $slot): array
    {
        $intent = match ($slot) {
            'sj' => Intent::INTENT_SEARCH_SJ,
            'part' => Intent::INTENT_SEARCH_PART,
            'customer' => Intent::INTENT_SEARCH_CUSTOMER,
            default => Intent::INTENT_LOT,
        };

        $prompt = match ($intent) {
            Intent::INTENT_SEARCH_SJ => [
                'Masukkan nomor surat jalan',
                'Contoh: <code>HPM/CR-CO/08/26</code> atau <code>G00038729</code>.',
                'HPM/CR-CO/08/26',
            ],
            Intent::INTENT_SEARCH_PART => [
                'Masukkan nomor atau nama part',
                'Contoh: <code>28251-K0J-N000-H1</code> atau <code>SPINDLE</code>.',
                'SPINDLE',
            ],
            Intent::INTENT_SEARCH_CUSTOMER => [
                'Masukkan nama customer',
                'Ketik sebagian nama saja, contoh <code>DIAMETRAL</code>.',
                'PT.DIAMETRAL INVOLUTE',
            ],
            default => [
                'Masukkan nomor lot',
                'Format: <code>MESIN-DDMMYY-NN</code>, contoh <code>CF3-030626-20</code>.',
                'CF3-030626-20',
            ],
        };

        $result = $this->formatter->prompt($prompt[0], $prompt[1], $prompt[2], $intent);

        return $this->wrap($result, $intent);
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function openLot(string $noLot): array
    {
        if (trim($noLot) === '') {
            return $this->askFor('lot');
        }

        $lots = $this->query->findLots($noLot);

        if ($lots === []) {
            return $this->wrap($this->notFoundLot($noLot), '');
        }

        if (count($lots) > 1) {
            return $this->wrap($this->multipleLotMatch($lots), '');
        }

        return $this->wrap($this->formatter->lotSummary($lots[0]), '');
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function openLotView(string $noLot, string $view, int $page = 0): array
    {
        if (trim($noLot) === '') {
            return $this->askFor('lot');
        }

        $lots = $this->query->findLots($noLot);

        if ($lots === []) {
            return $this->wrap($this->notFoundLot($noLot), '');
        }

        $lot = $lots[0];

        $result = match ($view) {
            'flow' => $this->formatter->lotFlow($lot, $page),
            'ht' => $this->formatter->lotHeatTreatment($lot),
            'tray' => $this->formatter->lotTray($lot),
            'pic' => $this->formatter->lotPic($lot),
            default => $this->formatter->lotSummary($lot),
        };

        return $this->wrap($result, '');
    }

    /**
     * Halaman berikutnya dari hasil pencarian sebelumnya.
     *
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function nextResultPage(int $page): array
    {
        $context = Cache::get($this->resultContextKey());

        if (! is_array($context) || ($context['intent'] ?? '') === '') {
            return $this->wrap($this->formatter->fallback(
                'Pencarian sebelumnya sudah kedaluwarsa.',
                ['cek lot CF3-030626-20', 'hold']
            ), '');
        }

        $intent = (string) $context['intent'];
        $query = (string) $context['query'];

        $rows = $this->searchRows($this->slotFromIntent($intent), $query);

        if ($rows === []) {
            return $this->askFor($this->slotFromIntent($intent));
        }

        $title = match ($intent) {
            Intent::INTENT_SEARCH_SJ => 'Hasil surat jalan',
            Intent::INTENT_SEARCH_CUSTOMER => 'Hasil pencarian customer',
            default => 'Hasil pencarian part',
        };

        $result = $this->formatter->searchResults(
            $title,
            $query,
            $rows,
            $page,
            5,
            $this->statuses($rows)
        );

        return $this->wrap($result, '');
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function lotIntent(string $intent, string $query): array
    {
        if ($query === '') {
            return $this->askFor($this->slotFromIntent($intent));
        }

        $lots = $this->query->findLots($query);

        if ($lots === []) {
            return $this->wrap($this->notFoundLot($query), '');
        }

        if (count($lots) > 1) {
            return $this->wrap($this->multipleLotMatch($lots), '');
        }

        $lot = $lots[0];

        $result = match ($intent) {
            Intent::INTENT_LOT_HT => $this->formatter->lotHeatTreatment($lot),
            Intent::INTENT_LOT_TRAY => $this->formatter->lotTray($lot),
            Intent::INTENT_LOT_PIC => $this->formatter->lotPic($lot),
            default => $this->formatter->lotSummary($lot),
        };

        return $this->wrap($result, '');
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function searchSuratJalan(string $query): array
    {
        if ($query === '') {
            return $this->askFor('sj');
        }

        $rows = $this->searchRows('sj', $query);

        if ($rows === []) {
            return $this->wrap(
                $this->formatter->notFound('Surat jalan', $query, $this->sampleSuratJalan()),
                ''
            );
        }

        return $this->wrap($this->formatter->searchResults(
            'Hasil surat jalan',
            $query,
            $rows,
            0,
            5,
            $this->statuses($rows)
        ), '', Intent::INTENT_SEARCH_SJ, $query);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchRows(string $slot, string $query): array
    {
        // Pencarian memakai LIKE '%...%' sehingga selalu memindai seluruh
        // tbl_seq_all (141 ribu baris). Cache singkat membuat pencarian yang
        // sama diulang menjadi instan.
        return Cache::remember(
            $this->searchCacheKey($slot, $query),
            now()->addSeconds(self::SEARCH_CACHE_SECONDS),
            fn (): array => match ($slot) {
                'sj' => $this->query->findLotsBySuratJalan($query, 50),
                'customer' => $this->query->findLotsByCustomer($query, 50),
                'part' => $this->query->findLotsByPart($query, 50),
                'lot_cust' => $this->query->findLotsByLotCustomer($query, 50),
                default => [],
            }
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array{icon: string, status: string, label: string}>
     */
    private function statuses(array $rows): array
    {
        $noLots = array_values(array_unique(array_column($rows, 'no_lot')));

        return Cache::remember(
            'bot:status:'.md5(implode('|', $noLots)),
            now()->addSeconds(self::SEARCH_CACHE_SECONDS),
            fn (): array => $this->query->lotStatuses($noLots)
        );
    }

    private function searchCacheKey(string $slot, string $query): string
    {
        return 'bot:search:'.$slot.':'.mb_strtolower(trim($query));
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function searchCustomer(string $query): array
    {
        if ($query === '') {
            return $this->askFor('customer');
        }

        $rows = $this->searchRows('customer', $query);

        if ($rows === []) {
            return $this->wrap(
                $this->formatter->notFound('Customer', $query, $this->sampleCustomers()),
                ''
            );
        }

        return $this->wrap($this->formatter->searchResults(
            'Hasil pencarian customer',
            $query,
            $rows,
            0,
            5,
            $this->statuses($rows)
        ), '', Intent::INTENT_SEARCH_CUSTOMER, $query);
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function searchPart(string $query): array
    {
        if ($query === '') {
            return $this->askFor('part');
        }

        $rows = $this->searchRows('part', $query);

        if ($rows === []) {
            return $this->wrap(
                $this->formatter->notFound('Part', $query, $this->sampleParts()),
                ''
            );
        }

        return $this->wrap($this->formatter->searchResults(
            'Hasil pencarian part',
            $query,
            $rows,
            0,
            5,
            $this->statuses($rows)
        ), '', Intent::INTENT_SEARCH_PART, $query);
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    private function activeLots(int $page = 0): array
    {
        $lots = $this->query->activeLotDetails(ProductionQueryService::MAX_LOT_RESULTS);

        if ($lots === []) {
            return $this->formatter->fallback('Tidak ada lot yang sedang berjalan atau tertahan.');
        }

        return $this->formatter->activeLots($lots, $page);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lots
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    private function multipleLotMatch(array $lots): array
    {
        $rows = array_map(fn (array $lot): array => [
            'no_lot' => $lot['no_lot'],
            'no_part' => $lot['master']['no_part'] ?? null,
            'nm_part' => $lot['master']['nm_part'] ?? null,
            'no_sj_cust' => $lot['master']['no_sj_cust'] ?? null,
            'qty' => $lot['master']['qty'] ?? null,
        ], $lots);

        return $this->formatter->searchResults(
            'Lot ditemukan',
            $lots[0]['no_lot'],
            $rows,
            0,
            5,
            $this->statuses($rows)
        );
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    private function notFoundLot(string $query): array
    {
        $normalized = strtoupper(trim($query));
        $escaped = htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8');

        $lines = [];
        $lines[] = '<b>Lot tidak ditemukan</b>';
        $lines[] = 'No. Lot <code>'.$escaped.'</code> tidak ada di sistem.';

        $suggestion = $this->closestLot($normalized);

        if ($suggestion !== null) {
            $lines[] = '';
            $lines[] = 'Mungkin yang dimaksud: <code>'.htmlspecialchars($suggestion, ENT_QUOTES, 'UTF-8').'</code>?';
        }

        $lines[] = '';
        $lines[] = 'Format nomor lot: <code>MESIN-DDMMYY-NN</code>, contoh <code>CF3-030626-20</code>.';

        $buttons = [];

        if ($suggestion !== null) {
            $buttons[] = [['text' => 'Cek '.$suggestion, 'callback_data' => 'lot|'.$suggestion]];
        }

        $buttons[] = [['text' => '🏠 Menu', 'callback_data' => 'menu']];

        return ['text' => implode("\n", $lines), 'keyboard' => ['inline_keyboard' => $buttons]];
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    private function cancel(): array
    {
        return $this->formatter->fallback('Pencarian dibatalkan.', ['/start untuk melihat bantuan']);
    }

    /**
     * @return array{text: string, keyboard: array<string, mixed>}
     */
    private function unknown(string $source): array
    {
        $message = match (true) {
            str_contains($source, 'smalltalk') => 'Halo! Saya bot data produksi. '
                .'Kirim nomor lot, surat jalan, nomor part, atau nama customer untuk mulai.',
            str_contains($source, 'llm:unavailable') => 'Layanan AI sedang tidak aktif, mode aturan dasar.',
            str_contains($source, 'llm') => 'Maaf, pertanyaan itu belum bisa saya pahami.',
            default => 'Saya belum memahami maksudnya.',
        };

        $suggestions = str_contains($source, 'smalltalk')
            ? ['cek lot CF3-030626-20', 'hold', 'bantuan']
            : [
                'cek lot CF3-030626-20',
                'cari sj HPM/CR-CO/08/26',
                'customer DIAMETRAL',
                'hold',
            ];

        return $this->formatter->fallback($message, $suggestions);
    }

    /**
     * Cari nomor lot terdekat berdasarkan prefix, supaya salah ketik tidak
     * membuat user buntu.
     */
    private function closestLot(string $normalized): ?string
    {
        $prefix = substr($normalized, 0, 7);

        if (strlen($prefix) < 5) {
            return null;
        }

        $rows = $this->query->findLots($prefix);

        return $rows[0]['no_lot'] ?? null;
    }

    private function slotFromIntent(string $intent): string
    {
        return match ($intent) {
            Intent::INTENT_SEARCH_SJ => 'sj',
            Intent::INTENT_SEARCH_PART => 'part',
            Intent::INTENT_SEARCH_CUSTOMER => 'customer',
            default => 'lot',
        };
    }

    /**
     * Kunci cache untuk menyimpan hasil pencarian terakhir, dipakai tombol
     * "Berikutnya" pada daftar hasil.
     */
    private function resultContextKey(): string
    {
        return 'bot:result_ctx:'.($this->chatId ?? 0);
    }

    public function rememberResultContext(string $intent, string $query): void
    {
        Cache::put(
            $this->resultContextKey(),
            ['intent' => $intent, 'query' => $query, 'at' => now()->timestamp],
            now()->addMinutes(30)
        );
    }

    /**
     * @param  array{text: string, keyboard: array<string, mixed>}  $result
     * @return array{text: string, keyboard: array<string, mixed>, state: string}
     */
    private function wrap(array $result, string $state, string $resultIntent = '', string $resultQuery = ''): array
    {
        if ($resultIntent !== '') {
            $this->rememberResultContext($resultIntent, $resultQuery);
        }

        return [
            'text' => $result['text'],
            'keyboard' => $result['keyboard'],
            'state' => $state,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function sampleSuratJalan(): array
    {
        return Cache::remember('bot:sample_sj', now()->addHours(6), function (): array {
            return array_values(array_filter(array_map(
                fn (array $row): ?string => $row['no_sj_cust'] ?? null,
                $this->query->recentLots(4)
            )));
        });
    }

    /**
     * @return array<int, string>
     */
    private function sampleCustomers(): array
    {
        return Cache::remember('bot:sample_customer', now()->addHours(6), function (): array {
            return array_values(array_filter(array_map(
                fn (array $row): ?string => $row['customer'] ?? null,
                $this->query->recentLots(4)
            )));
        });
    }

    /**
     * @return array<int, string>
     */
    private function sampleParts(): array
    {
        return Cache::remember('bot:sample_part', now()->addHours(6), function (): array {
            return array_values(array_filter(array_map(
                fn (array $row): ?string => $row['nm_part'] ?? null,
                $this->query->recentLots(4)
            )));
        });
    }
}
