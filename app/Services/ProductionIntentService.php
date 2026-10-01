<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menerjemahkan pesan Telegram menjadi "intent" + kata kunci pencarian.
 *
 * Urutan monolayer ini penting:
 *  1. Perintah slash dibaca langsung (paling deterministik).
 *  2. Ekstraksi kata kunci berbasis aturan / kamus mencoba lebih dulu.
 *  3. Baru bilautter gagal, LLM lokal dipanggil untuk mem-parsing kalimat bebas.
 *
 * Dengan urutan ini chatbot tetap berfungsi penuh ketika Ollama mati atau
 * lambat, dan biaya latency hanya ditanggung untuk pesan yang ambigu.
 *
 * Catatan performa: model qwen2.5:1.5b di mesin tanpa GPU bekerja sekitar
 * 2 token/detik, sehingga jalur LLM sengaja memakai prompt sangat pendek dan
 * selalu punya batas waktu. Hasil klasifikasi juga di-cache.
 */
class ProductionIntentService
{
    public const INTENT_LOT = 'lot_status';
    public const INTENT_LOT_HT = 'lot_heat_treatment';
    public const INTENT_LOT_TRAY = 'lot_tray';
    public const INTENT_LOT_PIC = 'lot_pic';
    public const INTENT_SEARCH_SJ = 'search_surat_jalan';
    public const INTENT_SEARCH_CUSTOMER = 'search_customer';
    public const INTENT_SEARCH_PART = 'search_part';
    public const INTENT_ACTIVE = 'active_lots';
    public const INTENT_HELP = 'help';
    public const INTENT_CANCEL = 'cancel';
    public const INTENT_UNKNOWN = 'unknown';

    /**
     * Intent yang membutuhkan satu nilai dari user (nomor lot, SJ, part, dll).
     *
     * Kalau intent ini dikenali tapi nilainya belum ada, bot langsung meminta
     * nilai, bukan meminta AI menebak.
     */
    private const SLOT_INTENTS = [
        self::INTENT_LOT,
        self::INTENT_LOT_HT,
        self::INTENT_LOT_TRAY,
        self::INTENT_LOT_PIC,
        self::INTENT_SEARCH_SJ,
        self::INTENT_SEARCH_PART,
        self::INTENT_SEARCH_CUSTOMER,
    ];

    /**
     * Perintah yang bisa dipakai user tanpa AI sama sekali.
     */
    private const COMMANDS = [
        '/start' => self::INTENT_HELP,
        '/help' => self::INTENT_HELP,
        '/menu' => self::INTENT_HELP,
        '/batal' => self::INTENT_CANCEL,
        '/cancel' => self::INTENT_CANCEL,
        '/refresh' => self::INTENT_LOT,
        '/lot' => self::INTENT_LOT,
        '/sj' => self::INTENT_SEARCH_SJ,
        '/suratjalan' => self::INTENT_SEARCH_SJ,
        '/part' => self::INTENT_SEARCH_PART,
        '/customer' => self::INTENT_SEARCH_CUSTOMER,
        '/tray' => self::INTENT_LOT_TRAY,
        '/ht' => self::INTENT_LOT_HT,
        '/pic' => self::INTENT_LOT_PIC,
        '/hold' => self::INTENT_ACTIVE,
        '/progress' => self::INTENT_ACTIVE,
    ];

    /**
     * Nomor lot internal selalu berbentuk MESIN-DDMMYY-NN, contoh
     * VW4-300626-40-B. Pemisah WAJIB tanda hubung supaya nomor surat jalan
     * (G00038729) atau nomor part (3257912500) tidak salah dibaca sebagai lot.
     */
    private const LOT_PATTERN = '/\b([A-Z]{1,5}\d{0,2}-\d{6}-\d{1,3}(?:-[A-Z0-9]{1,3})?)\b/i';

    /**
     * Versi longgar untuk user yang mengetik spasi sebagai pemisah.
     * Hanya dipakai bila pesan menyebut kata "lot".
     */
    private const LOT_PATTERN_LOOSE = '/\b([A-Z]{1,5}\d{0,2}[\s-]\d{6}[\s-]\d{1,3}(?:[\s-][A-Z0-9]{1,3})?)\b/i';

    /**
     * Kata kunci penanda intent. Pencocokan memakai kata terpanjang agar
     * "lagi jalan" menang atas "lot".
     */
    private const KEYWORDS = [
        self::INTENT_LOT_HT => ['heat treatment', 'heat treat', 'proses ht', 'tahapan ht', 'tempering', 'temper ke', 'hantenki', 'sub zero', 'vacuum treatment', 'hqt', 'hcqt', 'hcnqt', 'ht'],
        self::INTENT_LOT_TRAY => ['nomor tray', 'no tray', 'tray'],
        self::INTENT_LOT_PIC => ['operator', 'pekerja', 'pengerjaan oleh', 'dikerjakan', 'siapa yang proses', 'siapa yg proses', 'pic'],
        self::INTENT_SEARCH_SJ => ['surat jalan', 'sj customer', 'sj cust', 'no sj', 'nomor sj', 'sj'],
        self::INTENT_SEARCH_CUSTOMER => ['customer', 'pelanggan', 'cust'],
        self::INTENT_SEARCH_PART => ['no part', 'nama part', 'kode part', 'part number', 'part', 'item'],
        self::INTENT_ACTIVE => ['sedang proses', 'sedang jalan', 'sedang berjalan', 'lagi diproses', 'lagi jalan', 'lg jalan', 'on progress', 'yang jalan', 'lot aktif', 'status produksi', 'tertahan', 'hold'],
        self::INTENT_LOT => ['cek lot', 'status lot', 'posisi lot', 'dimana lot', 'proses lot', 'tracking lot', 'lot'],
        self::INTENT_HELP => ['bantuan', 'help', 'menu', 'perintah', 'bisa apa'],
        self::INTENT_CANCEL => ['batal', 'cancel', 'berhenti'],
    ];

    /**
     * Kata umum yang dibuang saat mencari kata kunci, agar query tidak
     * membawa sisa kalimat seperti "sekarang" atau "dong".
     */
    private const FILLERS = [
        'tolong', 'mohon', 'bisa', 'mau', 'boleh', 'donk', 'dear', 'kak', 'please',
        'carikan', 'cari', 'cariin', 'cek', 'check', 'dikasih', 'tas', 'info', 'informasi',
        'status', 'posisi', 'proses', 'sudah', 'sudah lewat', 'sudah melalui', 'sudah selesai',
        'belum', 'belom', 'sekarang', 'sekarangnya', 'yang', 'dengan',
        'untuk', 'dari', 'pada', 'itu', 'ini', 'dong', 'deal', 'sih', 'kok', 'ya', 'saja',
        'aja', 'nih', 'yuk', 'oke', 'ok', 'gimana', 'kabarnya', 'tentang', 'soal',
        'nya', 'ku', 'mu', 'bang', 'mas', 'kakak', 'nama', 'ada', 'apa', 'apakah', 'yg', 'ybs',
        'nomor', 'no', 'berapa', 'mana', 'kilometers', 'km',
    ];

    /**
     * Batas waktu panggil LLM saat mem/classify intent.
     *
     * Sengaja jauh lebih pendek dari timeout default: pada model 1.5B di CPU
     * satu panggilan bisa memakan 20 detik, dan user akan mengulang pesan
     * kalau menunggu terlalu lama.
     */
    private const LLM_TIMEOUT_SECONDS = 10;

    /**
     * Sapaan dan obrolan ringan yang jelas bukan pertanyaan produksi.
     *
     * Dikelompokkan per jenis, bukan satu daftar datar, supaya handler bisa
     * membalas "terima kasih" dan "sampai jumpa" dengan cara yang berbeda.
     * Kalau digabung, semua obrolan akan mendapat kalimat yang sama dan bot
     * langsung terbaca sebagai script.
     *
     * Daftar ini dilewati lebih dulu supaya "halo" tidak menghabiskan 20 detik
     * hanya untuk dijawab "tidak paham".
     */
    private const SMALLTALK = [
        'greeting' => [
            'halo', 'hai', 'hallo', 'hello', 'hi', 'hey', 'hei', 'pagi', 'siang',
            'sore', 'malam', 'selamat pagi', 'selamat siang', 'selamat sore',
            'selamat malam', 'assalamualaikum', 'assalamu alaikum', 'good morning',
            'good afternoon', 'good evening', 'met pagi', 'selamatsiang',
        ],
        'thanks' => [
            'terima kasih', 'terimaksih', 'terima kasih banyak', 'terima kasih banyak sekali',
            'thanks', 'thank you', 'thank', 'makasih', 'oke thanks', 'oke makasih',
            'thanks ya', 'tq', 'thanks a lot',
        ],
        'bye' => [
            'bye', 'bye bye', 'daah', 'dadah', 'daho', 'sampai jumpa', 'sampai jumpa ya',
            'sampai nanti', 'see you', 'cu yu',
        ],
        'howareyou' => [
            'apa kabar', 'gimana kabar', 'apa kabar?', 'gimana kabar?', 'apa news',
            'kabar apa', 'how are you', 'apa Kabar', 'gimana Kabarnya',
        ],
        'identity' => [
            'botnya siapa', 'kamu siapa', 'nama kamu siapa', 'kamu bot apa',
            'who are you', 'apa ini', 'apa ini?', 'kamu apa',
        ],
        'probe' => [
            'test', 'tes', 'ping', 'pong',
        ],
    ];

    /**
     * Kata yang terlalu umum untuk dijadikan pemicu obrolan ringan.
     *
     * "sampai mana lotnya", "cek nama part", dan "oke cek lot" adalah
     * pertanyaan produksi biasa, bukan perpisahan atau sapaan. Tanpa daftar ini,
     * frasa seperti "nama kamu siapa" akan membuat setiap pesan ber Kata "nama"
     * ikut dianggap obrolan ringan.
     */
    private const SMALLTALK_TRIGGER_BLOCKLIST = [
        'nama', 'sampai', 'oke', 'ok', 'kamu', 'itu', 'sih', 'dong', 'itu',
    ];

    /**
     * Pemicu per kata: kata pertama tiap frasa, dipetakan ke kategorinya.
     *
     * Hanya kata PERTAMA yang dipakai, bukan semua kata di dalam frasa.
     * Kalau semua kata dipakai, frasa "thanks a lot" akan menjadikan kata
     * "lot" sebagai pemicu - dan "lot" justru kata yang paling sering muncul
     * di bot produksi ini, sehingga setiap pertanyaan lot akan salah klasifikasi
     * menjadi obrolan ringan.
     *
     * @return array<string, string>
     */
    private function smalltalkTriggers(): array
    {
        static $triggers = null;

        if ($triggers !== null) {
            return $triggers;
        }

        $triggers = [];

        foreach (self::SMALLTALK as $category => $phrases) {
            foreach ($phrases as $phrase) {
                $first = mb_strtolower((string) (preg_split('/\s+/', trim($phrase), 2)[0] ?? ''));

                // Kata pendek seperti "hi" atau "tq" terlalu mudah menabrak
                // kata lain, jadi hanya boleh cocok lewat pencocokan utuh.
                if (mb_strlen($first) < 4 || in_array($first, self::SMALLTALK_TRIGGER_BLOCKLIST, true)) {
                    continue;
                }

                $triggers[$first] = $category;
            }
        }

        return $triggers;
    }

    /**
     * Perintah yang selalu dijawab tanpa membaca database, sehingga tidak perlu
     * indikator "typing" sama sekali.
     */
    private const INSTANT_COMMANDS = ['/start', '/menu', '/batal', '/cancel'];

    /**
     * @return array{intent: string, query: string, source: string}
     */
    public function parse(string $text, ?string $pendingIntent = null): array
    {
        $text = trim($this->normalize($text));

        if ($text === '') {
            return $this->result(self::INTENT_UNKNOWN, '', 'empty');
        }

        if ($pendingIntent === null && ($smalltalk = $this->classifySmalltalk($text)) !== null) {
            return $this->result(self::INTENT_UNKNOWN, '', 'rule:smalltalk:'.$smalltalk);
        }

        [$command, $argument] = $this->splitCommand($text);

        if ($command !== null) {
            $intent = self::COMMANDS[$command];
            $query = trim($argument);

            if ($query === '' && $command !== '/refresh') {
                return $this->result($intent, '', 'command');
            }

            return $this->result($this->refineByKeyword($intent, $text, $query), $query, 'command');
        }

        $rule = $this->parseByRules($text, $pendingIntent);

        if ($rule['intent'] !== self::INTENT_UNKNOWN) {
            return $rule;
        }

        return $this->parseByLlm($text, $pendingIntent);
    }

    /**
     * @return array{intent: string, query: string, source: string}
     */
    private function parseByRules(string $text, ?string $pendingIntent): array
    {
        $lot = $this->extractLot($text);

        if ($lot !== null) {
            $intent = $this->intentFromKeywords($text);

            if ($intent === null || ! in_array(
                $intent,
                [self::INTENT_LOT, self::INTENT_LOT_HT, self::INTENT_LOT_TRAY, self::INTENT_LOT_PIC],
                true
            )) {
                $intent = $pendingIntent !== null
                    && in_array($pendingIntent, [self::INTENT_LOT, self::INTENT_LOT_HT, self::INTENT_LOT_TRAY, self::INTENT_LOT_PIC], true)
                        ? $pendingIntent
                        : self::INTENT_LOT;
            }

            return $this->result($intent, $lot, 'rule:lot');
        }

        $keywordIntent = $this->intentFromKeywords($text);

        if ($keywordIntent === self::INTENT_ACTIVE || $keywordIntent === self::INTENT_HELP || $keywordIntent === self::INTENT_CANCEL) {
            return $this->result($keywordIntent, '', 'rule:keyword');
        }

        $candidate = $this->extractCandidateQuery($text, $keywordIntent);

        if ($keywordIntent !== null && $candidate !== null) {
            return $this->result($keywordIntent, $candidate, 'rule:keyword');
        }

        // User sudah menyebut kata kunci pencarian tapi belum mengetik
        // nilainya (mis. "cek lot" atau "cari sj"). Kembalikan intent dengan
        // query kosong supaya bot langsung meminta nilai, tanpa memanggil LLM.
        if ($keywordIntent !== null && in_array($keywordIntent, self::SLOT_INTENTS, true)) {

            return $this->result($keywordIntent, '', 'rule:keyword_empty');
        }

        // Tanpa penanda intent, tapi user sudah ditanya sebelumnya: pakai intent itu.
        if ($pendingIntent !== null && $candidate !== null) {
            return $this->result($pendingIntent, $candidate, 'rule:pending');
        }

        if ($pendingIntent !== null && in_array($pendingIntent, self::SLOT_INTENTS, true)) {
            return $this->result($pendingIntent, '', 'rule:pending_empty');
        }

        return $this->result(self::INTENT_UNKNOWN, '', 'rule:none');
    }

    /**
     * Kata kunci pencarian, dipilih dengan heuristik per intent.
     */
    private function extractCandidateQuery(string $text, ?string $intent): ?string
    {
        // Bentuk eksplisit "sj: 123" / "no part = 456" selalu menang.
        $explicit = $this->extractExplicitValue($text, $intent);

        if ($explicit !== null) {
            return $explicit;
        }

        $tokens = $this->significantTokens($text);

        if ($tokens === []) {
            return null;
        }

        return match ($intent) {
            self::INTENT_SEARCH_SJ => $this->pickSuratJalan($tokens),
            self::INTENT_SEARCH_PART => $this->pickPart($tokens),
            self::INTENT_SEARCH_CUSTOMER => $this->pickCustomer($tokens, $text),
            default => $this->pickGeneric($tokens),
        };
    }

    /**
     * Ambil nilai setelah penanda intent, mis. "sj: HPM/1/26".
     */
    private function extractExplicitValue(string $text, ?string $intent): ?string
    {
        $patterns = match ($intent) {
            self::INTENT_SEARCH_SJ => [
                '/\b(?:no\.?\s*|nomor\s*)?sj(?:\s*)?(?:cust(?:omer)?)?\s*(?:adalah|:|=|-)\s*(\S.+)$/iu',
            ],
            self::INTENT_SEARCH_CUSTOMER => [
                '/\b(?:customer|cust|pelanggan)\s*(?:adalah|:|=)\s*(\S.+)$/iu',
            ],
            self::INTENT_SEARCH_PART => [
                '/\bno\.?\s*part\s*(?:adalah|:|=)\s*(\S.+)$/iu',
                '/\bnama\s*part\s*(?:adalah|:|=)\s*(\S.+)$/iu',
                '/\bpart\s*(?:number)?\s*(?:adalah|:|=)\s*(\S.+)$/iu',
            ],
            default => [],
        };

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                $value = $this->cleanQuery($matches[1]);

                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function pickSuratJalan(array $tokens): ?string
    {
        foreach ($tokens as $token) {
            if (str_contains($token, '/')) {
                return $token;
            }
        }

        // Surat jalan tanpa garis miring biasanya berawalan G / angka panjang.
        foreach ($tokens as $token) {
            if (preg_match('/^(?:G|D)?\d{6,12}$/i', $token) === 1) {
                return $token;
            }
        }

        return $this->pickGeneric($tokens);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function pickPart(array $tokens): ?string
    {
        foreach ($tokens as $token) {
            // 28251-K0J-N000-H1 / 3B63020100GSN : mengandung angka dan tanda hubung.
            if (preg_match('/^(?=[A-Z0-9\-\/]*\d)[A-Z0-9]+(?:-[A-Z0-9]+){1,4}$/i', $token) === 1) {
                return $token;
            }
        }

        foreach ($tokens as $token) {
            if (preg_match('/^\d{5,15}$/', $token) === 1) {
                return $token;
            }
        }

        foreach ($tokens as $token) {
            if (preg_match('/^(?=[A-Z0-9]*\d)[A-Z0-9]{5,}$/i', $token) === 1) {
                return $token;
            }
        }

        // Nama part bebas, misalnya "SPINDLE" atau "outer assy bls".
        return $this->pickGeneric($tokens, 4);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function pickCustomer(array $tokens, string $fullText): ?string
    {
        $known = $this->knownCustomers();

        if ($known !== []) {
            $haystack = mb_strtolower($fullText);

            $best = null;
            $bestLength = 0;

            foreach ($known as $customer) {
                $needle = mb_strtolower($customer);

                if ($needle !== '' && str_contains($haystack, $needle) && mb_strlen($needle) > $bestLength) {
                    $best = $customer;
                    $bestLength = mb_strlen($needle);
                }
            }

            if ($best !== null) {
                return $best;
            }
        }

        return $this->pickGeneric($tokens, 2);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function pickGeneric(array $tokens, int $maxTokens = 6): ?string
    {
        return $this->cleanQuery(implode(' ', array_slice($tokens, 0, $maxTokens)));
    }

    /**
     * Pecah pesan menjadi token bermakna, sambil membuang kata umum dan kata
     * penanda intent ("lot", "sj", "part", ...).
     *
     * Huruf besar kecil asli dipertahankan karena nilai hasil token ini
     * ditampilkan kembali ke user.
     *
     * @return array<int, string>
     */
    private function significantTokens(string $text): array
    {
        $normalized = preg_replace('/[^\p{L}\p{N}\/\.\-]+/u', ' ', $text) ?? $text;

        $tokens = array_values(array_filter(
            preg_split('/\s+/u', trim($normalized)) ?: [],
            fn (string $token): bool => mb_strlen($token) > 0
        ));

        $keywords = $this->allKeywords();

        $kept = [];

        foreach ($tokens as $token) {
            $lower = mb_strtolower(trim($token, ".-/"));

            if ($lower === '' || in_array($lower, self::FILLERS, true) || in_array($lower, $keywords, true)) {
                continue;
            }

            // Kata yang hanya salah ketik dari sebuah kata kunci tidak boleh
            // ikut terbawa ke query, karena akan membuat pencarian LIKE
            // tidak menemukan apa pun.
            if ($this->closestKeyword($lower) !== null) {
                continue;
            }

            $kept[] = $token;
        }

        return $kept;
    }

    /**
     * @return array<int, string>
     */
    private function allKeywords(): array
    {
        static $keywords = null;

        if ($keywords !== null) {
            return $keywords;
        }

        $keywords = ['lot', 'lots', 'nomor', 'no', 'cek', 'cari', 'check', 'status', 'posisi',
            'proses', 'sudah', 'belum', 'sekarang', 'yang', 'dll', 'dsb'];

        foreach (self::KEYWORDS as $words) {
            foreach ($words as $word) {
                $keywords[] = mb_strtolower($word);
            }
        }

        return array_values(array_unique($keywords));
    }

    /**
     * @return array<int, string>
     */
    private function knownCustomers(): array
    {
        return Cache::remember('bot:known_customers', now()->addHours(6), function (): array {
            $names = [];

            try {
                if (\App\Support\SchemaCache::hasTable('tblcustomer')) {
                    $names = DB::table('tblcustomer')->pluck('nm_customer')->all();
                }
            } catch (\Throwable $exception) {
                Log::warning('Gagal memuat daftar customer untuk bot.', [
                    'error' => $exception->getMessage(),
                ]);

                return [];
            }

            $names = array_values(array_filter(array_map(
                fn ($name): string => trim((string) $name),
                $names
            )));

            sort($names);

            return $names;
        });
    }

    /**
     * Kelompokkan obrolan ringan sesuai jenisnya, atau null bila bukan.
     *
     * Nilai kembalian ikut dibawa pada "source" supaya handler bisa membalas
     * sesuai jenis. Hasil categori juga dipakai ProductionBotHandler::unknown,
     * jadi jenis yang tidak dikenal harus tetap punya balasan default.
     */
    private function classifySmalltalk(string $text): ?string
    {
        $clean = mb_strtolower(trim($text, " \t\n\r\0\x0B.!?,;:-_"));

        foreach (self::SMALLTALK as $category => $phrases) {
            if (in_array($clean, $phrases, true)) {
                return $category;
            }
        }

        // Sapaan yang disusul kata lain, misalnya "halo bot" atau "hi admin".
        // Hanya dianggap sapaan kalau pesannya pendek, supaya kalimat yang
        // kebetulan memuat "hi" tidak ikut dianggap sapaan.
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) > 4) {
            return null;
        }

        foreach ($this->smalltalkTriggers() as $word => $category) {
            if (in_array($word, $words, true)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * @return array{intent: string, query: string, source: string}
     */
    private function parseByLlm(string $text, ?string $pendingIntent): array
    {
        $cacheKey = 'bot:intent:'.sha1(mb_strtolower($text).'|'.($pendingIntent ?? '-'));

        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['intent'])) {
            return $cached + ['source' => 'llm:cache'];
        }

        $ollama = app(OllamaService::class);

        if (! $ollama->isAvailable()) {
            return $this->result(self::INTENT_UNKNOWN, '', 'llm:unavailable');
        }

        $result = $ollama->chatJson([
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($text, $pendingIntent)],
        ], self::LLM_TIMEOUT_SECONDS);

        if ($result === null) {
            return $this->result(self::INTENT_UNKNOWN, '', 'llm:null');
        }

        $intent = $this->sanitizeIntent($result['intent'] ?? $result['i'] ?? null);
        $query = $this->cleanQuery($result['query'] ?? $result['q'] ?? null);

        if ($intent === null) {
            return $this->result(self::INTENT_UNKNOWN, '', 'llm:bad_intent');
        }

        /*
         * Nomor lot dari model bisa tidak akurat, sehingga selalu diambil ulang
         * dari teks asli pengguna bila ada.
         */
        $lot = $this->extractLot($text);

        if ($lot !== null) {
            return $this->result($intent, $lot, 'llm+rule:lot');
        }

        if (in_array($intent, [self::INTENT_LOT, self::INTENT_LOT_HT, self::INTENT_LOT_TRAY, self::INTENT_LOT_PIC], true)) {
            $intent = $pendingIntent ?? self::INTENT_LOT;
        }

        if ($query === null) {
            return $this->result(self::INTENT_UNKNOWN, '', 'llm:no_query');
        }

        $resolved = $this->result($intent, $query, 'llm');

        Cache::put($cacheKey, $resolved, now()->addHours(6));

        return $resolved;
    }

    /**
     * Prompt sengaja dibuat sangat pendek. Pada mesin CPU, biaya pemrosesan
     * prompt jauh lebih besar dibanding waktu generate, sehingga verbose
     * few-shot membuat bot tidak terbawa.
     */
    private function systemPrompt(): string
    {
        return 'Bot parser produksi. Balas HANYA JSON {"i":"<kode>","q":"<kata kunci>"}. '
            .'Kode i: LOT=cek lot, HT=proses heat treatment, TRAY=no tray, PIC=siaga pemproses, '
            .'SJ=cari no surat jalan, CUST=cari customer, PART=cari part, ACTIVE=lot jalan/hold, '
            .'HELP=bantuan, UNKNOWN=tidak paham. q kosong "" bila tidak ada kata kunci.';
    }

    private function userPrompt(string $text, ?string $pendingIntent): string
    {
        $context = $pendingIntent === null ? '' : 'TDL='.$this->shortCode($pendingIntent).' ';

        return $context.'"' . $text . '"';
    }

    private function shortCode(string $intent): string
    {
        return match ($intent) {
            self::INTENT_LOT => 'LOT',
            self::INTENT_LOT_HT => 'HT',
            self::INTENT_LOT_TRAY => 'TRAY',
            self::INTENT_LOT_PIC => 'PIC',
            self::INTENT_SEARCH_SJ => 'SJ',
            self::INTENT_SEARCH_CUSTOMER => 'CUST',
            self::INTENT_SEARCH_PART => 'PART',
            self::INTENT_ACTIVE => 'ACTIVE',
            default => 'UNKNOWN',
        };
    }

    /**
     * Terjemahkan keluaran model ke intent yang dikenal.
     *
     * Model 1.5B sering mengarang nama intent ("request assistance"), sehingga
     * selain kecocokan persis juga dilakukan pencocokan sebagian.
     */
    private function sanitizeIntent(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = mb_strtolower(trim($value));

        $exact = [
            'lot' => self::INTENT_LOT,
            'lot_status' => self::INTENT_LOT,
            'lot_heat_treatment' => self::INTENT_LOT_HT,
            'heat_treatment' => self::INTENT_LOT_HT,
            'ht' => self::INTENT_LOT_HT,
            'tray' => self::INTENT_LOT_TRAY,
            'lot_tray' => self::INTENT_LOT_TRAY,
            'pic' => self::INTENT_LOT_PIC,
            'lot_pic' => self::INTENT_LOT_PIC,
            'sj' => self::INTENT_SEARCH_SJ,
            'search_surat_jalan' => self::INTENT_SEARCH_SJ,
            'surat_jalan' => self::INTENT_SEARCH_SJ,
            'cust' => self::INTENT_SEARCH_CUSTOMER,
            'customer' => self::INTENT_SEARCH_CUSTOMER,
            'search_customer' => self::INTENT_SEARCH_CUSTOMER,
            'part' => self::INTENT_SEARCH_PART,
            'search_part' => self::INTENT_SEARCH_PART,
            'active' => self::INTENT_ACTIVE,
            'active_lots' => self::INTENT_ACTIVE,
            'progress' => self::INTENT_ACTIVE,
            'hold' => self::INTENT_ACTIVE,
            'help' => self::INTENT_HELP,
            'bantuan' => self::INTENT_HELP,
            'cancel' => self::INTENT_CANCEL,
            'unknown' => self::INTENT_UNKNOWN,
        ];

        $value = str_replace(['-', ' '], '_', $value);

        if (isset($exact[$value])) {
            return $exact[$value];
        }

        $fuzzy = [
            'temper' => self::INTENT_LOT_HT,
            'hantenki' => self::INTENT_LOT_HT,
            'tray' => self::INTENT_LOT_TRAY,
            'pic' => self::INTENT_LOT_PIC,
            'operator' => self::INTENT_LOT_PIC,
            'surat' => self::INTENT_SEARCH_SJ,
            '_sj' => self::INTENT_SEARCH_SJ,
            'sj_' => self::INTENT_SEARCH_SJ,
            'sj' => self::INTENT_SEARCH_SJ,
            'cust' => self::INTENT_SEARCH_CUSTOMER,
            'pelanggan' => self::INTENT_SEARCH_CUSTOMER,
            'part' => self::INTENT_SEARCH_PART,
            'item' => self::INTENT_SEARCH_PART,
            'hold' => self::INTENT_ACTIVE,
            'progress' => self::INTENT_ACTIVE,
            'active' => self::INTENT_ACTIVE,
            'running' => self::INTENT_ACTIVE,
            'help' => self::INTENT_HELP,
            'bantuan' => self::INTENT_HELP,
            'menu' => self::INTENT_HELP,
            'batal' => self::INTENT_CANCEL,
            'cancel' => self::INTENT_CANCEL,
            'lot' => self::INTENT_LOT,
        ];

        foreach ($fuzzy as $needle => $intent) {
            if (str_contains($value, $needle)) {
                return $intent;
            }
        }

        return null;
    }

    private function refineByKeyword(string $intent, string $text, string $query): string
    {
        $keywordIntent = $this->intentFromKeywords($text);

        if ($keywordIntent !== null
            && in_array($keywordIntent, [self::INTENT_LOT_HT, self::INTENT_LOT_TRAY, self::INTENT_LOT_PIC], true)
            && $intent === self::INTENT_LOT
        ) {
            return $keywordIntent;
        }

        return $intent;
    }

    /**
     * Apakah pesan ini dijawab seketika, tanpa satu pun query database?
     *
     * Dipakai worker untuk menahan indikator "typing". Untuk jawaban yang sudah
     * instan, mengirim "typing..." justru membuat jawaban terasa lebih lambat
     * karena user sempat berharap ada sesuatu yang diproses.
     */
    public function isInstantReply(string $text): bool
    {
        $normalized = $this->normalize($text);

        if ($this->classifySmalltalk($normalized) !== null) {
            return true;
        }

        [$command] = $this->splitCommand($normalized);

        return $command !== null && in_array($command, self::INSTANT_COMMANDS, true);
    }

    private function intentFromKeywords(string $text): ?string
    {
        $haystack = mb_strtolower($text);

        $best = null;
        $bestLength = 0;

        foreach (self::KEYWORDS as $intent => $words) {
            foreach ($words as $word) {
                $word = mb_strtolower($word);

                if (! $this->keywordMatches($haystack, $word)) {
                    continue;
                }

                if (mb_strlen($word) > $bestLength) {
                    $best = $intent;
                    $bestLength = mb_strlen($word);
                }
            }
        }

        return $best ?? $this->intentFromFuzzyKeywords($haystack);
    }

    /**
     * Cache kata yang sudah pernah diuji toleransinya, supaya tidak dihitung
     * ulang untuk setiap pesan karena user sering mengetik kata yang sama.
     *
     * @var array<string, string|null>
     */
    private array $fuzzyMatches = [];

    /**
     * Kata kunci satu kata yang boleh dicocokkan dengan toleransi typo.
     *
     * @return array<int, string>
     */
    private function typoTolerantKeywords(): array
    {
        static $keys = null;

        if ($keys !== null) {
            return $keys;
        }

        $keys = [];

        foreach (self::KEYWORDS as $words) {
            foreach ($words as $word) {
                $word = mb_strtolower($word);

                // Frasa yang terdiri dari beberapa kata mustahil cocok dengan satu
                // kata, dan kata pendek lebih sering menghasilkan intent
                // yang salah daripada otomatis terkena.
                if (mb_strlen($word) >= 6 && ! str_contains($word, ' ')) {
                    $keys[$word] = $word;
                }
            }
        }

        return $keys;
    }

    /**
     * Kata kunci terdekat dengan satu kata, bila masih dalam batas toleransi.
     *
     * Dipakai dua kali: mengenali intent saat tidak ada cocok persis, dan
     * membuang kata yang salah ketik dari query. Tanpa langkah kedua, intent
     *-nya dikenali tetapi query tetap berisi "customr", sehingga pencarian
     * LIKE tidak akan menemukan apa pun.
     */
    private function closestKeyword(string $word): ?string
    {
        if (array_key_exists($word, $this->fuzzyMatches)) {
            return $this->fuzzyMatches[$word];
        }

        $best = null;
        $bestLength = 0;

        foreach (array_keys($this->typoTolerantKeywords()) as $keyword) {
            $gap = mb_strlen($word) - mb_strlen($keyword);

            // Panjang harus mirip, supaya "gamblong" tidak dianggap salah
            // ketik dari "pelanggan".
            if ($gap < -2 || $gap > 2) {
                continue;
            }

            // Batas kesalahan ikut panjang kata: satu huruf untuk kata
            // pendek, dua huruf untuk yang sudah panjang.
            if (levenshtein($keyword, $word) > (mb_strlen($keyword) >= 9 ? 2 : 1)) {
                continue;
            }

            if (mb_strlen($keyword) > $bestLength) {
                $best = $keyword;
                $bestLength = mb_strlen($keyword);
            }
        }

        return $this->fuzzyMatches[$word] = $best;
    }

    /**
     * Intent milik sebuah kata kunci.
     */
    private function intentOfKeyword(string $keyword): ?string
    {
        foreach (self::KEYWORDS as $intent => $words) {
            foreach ($words as $word) {
                if (mb_strtolower($word) === $keyword) {
                    return $intent;
                }
            }
        }

        return null;
    }

    /**
     * Cocokkan kata kunci dengan toleransi salah ketik satu-dua huruf.
     *
     * Hanya dipanggil setelah pencarian persis gagal, sehingga tidak pernah
     * menimpa kecocokan yang sudah benar dan tidak menambah beban pada pesan
     * normal.
     */
    private function intentFromFuzzyKeywords(string $haystack): ?string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', $haystack, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $best = null;
        $bestLength = 0;

        foreach ($words as $word) {
            $keyword = $this->closestKeyword($word);

            if ($keyword === null) {
                continue;
            }

            $intent = $this->intentOfKeyword($keyword);

            if ($intent !== null && mb_strlen($keyword) > $bestLength) {
                $best = $intent;
                $bestLength = mb_strlen($keyword);
            }
        }

        return $best;
    }

    /**
     * Kata pendek seperti "ht" harus cocok sebagai kata utuh, kalau tidak akan
     * ikut cocok di dalam "LIGHT" atau "NIGHT".
     */
    private function keywordMatches(string $haystack, string $word): bool
    {
        if (mb_strlen($word) > 3) {
            return str_contains($haystack, $word);
        }

        return preg_match('/(?<![a-z0-9])'.preg_quote($word, '/').'(?![a-z0-9])/i', $haystack) === 1;
    }

    private function extractLot(string $text): ?string
    {
        $normalized = strtoupper($text);

        if (preg_match(self::LOT_PATTERN, $normalized, $matches) === 1) {
            return $this->normalizeLot($matches[1]);
        }

        // Hanya coba pola longgar bila user memang menyebut "lot".
        if (str_contains(mb_strtolower($text), 'lot')
            && preg_match(self::LOT_PATTERN_LOOSE, $normalized, $matches) === 1) {
            return $this->normalizeLot($matches[1]);
        }

        return null;
    }

    private function normalizeLot(string $lot): ?string
    {
        $lot = preg_replace('/\s+/', '-', trim($lot)) ?? $lot;
        $lot = trim($lot, '-');

        return strlen($lot) >= 8 ? $lot : null;
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function splitCommand(string $text): array
    {
        if (! str_starts_with($text, '/')) {
            return [null, $text];
        }

        // Dukung /command@NamaBot yang dikirim Telegram pada chat grup.
        $normalized = preg_replace('/^(\/\w+)@\S+/u', '$1', $text) ?? $text;
        $spacePosition = strpos($normalized, ' ');

        $command = $spacePosition === false ? $normalized : substr($normalized, 0, $spacePosition);
        $argument = $spacePosition === false ? '' : substr($normalized, $spacePosition + 1);

        $command = mb_strtolower(rtrim($command, ',.:;'));

        return isset(self::COMMANDS[$command]) ? [$command, $argument] : [null, $text];
    }

    private function cleanQuery(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? $value;
        $value = trim($value);
        $value = trim($value, " \t\n\r\0\x0B.,;:!?");

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 120);
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    public function intentLabel(string $intent): string
    {
        return match ($intent) {
            self::INTENT_LOT => 'nomor lot',
            self::INTENT_LOT_HT => 'nomor lot Heat Treatment',
            self::INTENT_LOT_TRAY, self::INTENT_LOT_PIC => 'nomor lot',
            self::INTENT_SEARCH_SJ => 'nomor surat jalan',
            self::INTENT_SEARCH_CUSTOMER => 'nama customer',
            self::INTENT_SEARCH_PART => 'nomor atau nama part',
            default => 'informasi',
        };
    }

    public function isLotIntent(string $intent): bool
    {
        return in_array($intent, [
            self::INTENT_LOT, self::INTENT_LOT_HT, self::INTENT_LOT_TRAY, self::INTENT_LOT_PIC,
        ], true);
    }

    /**
     * @return array{intent: string, query: string, source: string}
     */
    private function result(string $intent, string $query, string $source): array
    {
        return ['intent' => $intent, 'query' => $query, 'source' => $source];
    }
}
