<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelegramUpdate;
use App\Support\SchemaCache;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Uji alur penuh bot produksi: request webhook -> antrian -> job -> balasan.
 *
 * Telegram tidak dikoneksikan sungguhan; semua panggilan Bot API dipalsukan
 * sehingga yang diuji adalah logika aplikasi kita, bukan jaringan.
 */
class ProductionBotWebhookTest extends TestCase
{
    private const CHAT_ID = 123456789;

    private const USER_ID = 555000111;

    private const SECRET = 'test-secret-token';

    protected function setUp(): void
    {
        parent::setUp();

        SchemaCache::flush();

        config([
            'telegram.bots.production_bot.token' => '123456:TEST-TOKEN',
            'telegram.production_bot.enabled' => true,
            'telegram.production_bot.webhook_secret' => self::SECRET,
            'telegram.production_bot.allowed_chats' => [(string) self::CHAT_ID],
            'telegram.production_bot.allowed_users' => [],
            'telegram.production_bot.require_whitelist' => true,
        ]);

        $this->createProductionTables();
        $this->createSequenceTable();
        $this->createSampleLot();
    }

    /**
     * Skema minimal yang dipakai ProductionQueryService.
     */
    private function createProductionTables(): void
    {
        Schema::create('tbl_seq_all', function (Blueprint $table): void {
            $table->id();
            $table->string('no_lot', 30);
            $table->string('no_srp', 30)->nullable();
            $table->integer('seq')->default(1);
            $table->string('id_seq', 10)->nullable();
            $table->string('nm_seq', 100)->nullable();
            $table->string('no_part', 60)->nullable();
            $table->string('nm_part', 120)->nullable();
            $table->string('customer', 120)->nullable();
            $table->string('no_sj_cust', 60)->nullable();
            $table->string('no_lot_cust', 60)->nullable();
            $table->string('no_mesin', 30)->nullable();
            $table->string('tray_no', 30)->nullable();
            $table->string('tray_nokecil', 30)->nullable();
            $table->string('akakami', 20)->nullable();
            $table->string('ojt', 20)->nullable();
            $table->string('hold', 20)->nullable();
            $table->string('finish', 20)->nullable();
            $table->string('pic_01', 100)->nullable();
            $table->string('pic_02', 100)->nullable();
            $table->string('pic_03', 100)->nullable();
            $table->string('pic_04', 100)->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('finish_time')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('qty')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    private function createSequenceTable(): void
    {
        Schema::create('tblsequence', function (Blueprint $table): void {
            $table->string('id_seq', 10)->primary();
            $table->string('nm_sequence', 100)->nullable();
        });

        \Illuminate\Support\Facades\DB::table('tblsequence')->insert([
            ['id_seq' => 'S02', 'nm_sequence' => 'Setting'],
            ['id_seq' => 'S06', 'nm_sequence' => 'HCQT Batch'],
            ['id_seq' => 'S26', 'nm_sequence' => 'Delivery'],
        ]);
    }

    /**
     * Satu lot dengan tiga tahap: dua selesai, satu sedang jalan dan hold.
     *
     * Huruf kapital kecil pada lot sengaja dipakai untuk memastikan penggabungan
     * lot tetap case-insensitive seperti data produksi sebenarnya.
     */
    private function createSampleLot(): void
    {
        \Illuminate\Support\Facades\DB::table('tbl_seq_all')->insert([
            [
                'no_lot' => 'cf3-030626-20', 'no_srp' => '00040025', 'seq' => 1,
                'id_seq' => 'S02', 'nm_seq' => 'Setting', 'no_part' => '28251-K0J-N000-H1',
                'nm_part' => 'SPINDLE', 'customer' => 'PT.DIAMETRAL INVOLUTE',
                'no_sj_cust' => '260602/43231/DISP-SC', 'no_lot_cust' => '2026-03F-0526--011',
                'tray_no' => 'GSN 1', 'tray_nokecil' => null, 'no_mesin' => 'CF3',
                'akakami' => null, 'ojt' => 'yes', 'hold' => null, 'finish' => 'yes',
                'pic_01' => 'Ibnu Abdul Munir', 'start_time' => '2026-06-03 13:36:00',
                'finish_time' => '2026-06-03 14:19:00', 'duration' => 43, 'qty' => 5200,
                'updated_at' => '2026-06-03 14:19:00',
            ],
            [
                'no_lot' => 'CF3-030626-20', 'no_srp' => '00040025', 'seq' => 2,
                'id_seq' => 'S06', 'nm_seq' => 'HCQT Batch', 'no_part' => '28251-K0J-N000-H1',
                'nm_part' => 'SPINDLE', 'customer' => 'PT.DIAMETRAL INVOLUTE',
                'no_sj_cust' => '260602/43231/DISP-SC', 'no_lot_cust' => '2026-03F-0526--011',
                'tray_no' => 'GSN 1', 'tray_nokecil' => null, 'no_mesin' => 'CF3',
                'akakami' => null, 'ojt' => null, 'hold' => null, 'finish' => 'yes',
                'pic_01' => 'Jakarya', 'start_time' => '2026-06-05 11:35:00',
                'finish_time' => '2026-06-05 21:07:00', 'duration' => 572, 'qty' => 5200,
                'updated_at' => '2026-06-05 21:07:00',
            ],
            [
                'no_lot' => 'CF3-030626-20', 'no_srp' => '00040025', 'seq' => 3,
                'id_seq' => 'S26', 'nm_seq' => 'Delivery', 'no_part' => '28251-K0J-N000-H1',
                'nm_part' => 'SPINDLE', 'customer' => 'PT.DIAMETRAL INVOLUTE',
                'no_sj_cust' => '260602/43231/DISP-SC', 'no_lot_cust' => '2026-03F-0526--011',
                'tray_no' => 'GSN 1', 'tray_nokecil' => null, 'no_mesin' => null,
                'akakami' => null, 'ojt' => null, 'hold' => 'yes', 'finish' => null,
                'pic_01' => 'Zaini Aminullah', 'start_time' => '2026-06-08 06:46:00',
                'finish_time' => null, 'duration' => null, 'qty' => 5200,
                'updated_at' => '2026-06-08 06:46:00',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function textUpdate(string $text, int $updateId = 900001): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 42,
                'from' => ['id' => self::USER_ID, 'username' => 'tester'],
                'chat' => ['id' => self::CHAT_ID, 'type' => 'private'],
                'text' => $text,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function callbackUpdate(string $data, int $updateId = 900002): array
    {
        return [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => ['id' => self::USER_ID, 'username' => 'tester'],
                'data' => $data,
                'message' => [
                    'message_id' => 42,
                    'chat' => ['id' => self::CHAT_ID, 'type' => 'private'],
                    'text' => 'pesan sebelumnya',
                ],
            ],
        ];
    }

    /**
     * Kirim update lewat HTTP dan jalankan job-nya dalam proses yang sama.
     */
    private function postUpdate(array $update, ?string $secret = self::SECRET): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', $secret ?? '')
            ->postJson(route('telegram.webhook'), $update);
    }

    /**
     * Semua panggilan Bot API yang diharapkan tertangkap di fake HTTP.
     */
    private function fakeTelegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 99, 'description' => 'Message is not modified'],
            ]),
        ]);
    }

    /**
     * @return array<int, array{method: string, payload: array<string, mixed>}>
     */
    /**
     * Kumpulkan payload setiap panggilan Bot API tertentu, mis. sendMessage.
     *
     * @return array<int, array{method: string, payload: array<string, mixed>, url: string}>
     */
    private function telegramCalls(string $method): array

    {
        return collect(Http::recorded())
            ->map(fn (array $pair): array => [
                'method' => $pair[0]->method(),
                'payload' => $pair[0]->data(),
                'url' => $pair[0]->url(),
            ])
            ->filter(fn (array $call): bool => str_contains($call['url'], $method))
            ->values()
            ->all();
    }

    public function test_webhook_menolak_request_tanpa_secret(): void
    {
        Queue::fake();

        $this->postUpdate($this->textUpdate('cek lot CF3-030626-20'), secret: null)
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_webhook_menolak_secret_yang_salah(): void
    {
        Queue::fake();

        $this->postUpdate($this->textUpdate('cek lot CF3-030626-20'), 'secret-salah')
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_webhook_menolak_secret_kosong_di_konfigurasi(): void
    {
        Queue::fake();
        config(['telegram.production_bot.webhook_secret' => '']);

        $this->postUpdate($this->textUpdate('cek lot CF3-030626-20'))->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_update_dari_chat_di_luar_whitelist_diabaikan(): void
    {
        Queue::fake();

        $update = $this->textUpdate('cek lot CF3-030626-20');
        $update['message']['chat']['id'] = 999999999;

        $this->postUpdate($update)->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_update_valid_didorong_ke_antrian(): void
    {
        Queue::fake();

        $this->postUpdate($this->textUpdate('cek lot CF3-030626-20'))->assertOk();

        Queue::assertPushed(ProcessTelegramUpdate::class, 1);
    }

    public function test_bot_membalas_ringkasan_lot_yang_menggabungkan_kapitalisasi(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('cek lot CF3-030626-20'))->assertOk();

        $sent = $this->telegramCalls('sendMessage');

        $this->assertNotEmpty($sent, 'Bot tidak mengirim balasan apa pun.');

        $text = (string) $sent[0]['payload']['text'];

        // Lot dengan kapitalisasi berbeda harus digabung jadi satu lot.
        $this->assertStringContainsString('CF3-030626-20', $text);
        $this->assertStringContainsString('SPINDLE', $text);
        $this->assertStringContainsString('PT.DIAMETRAL INVOLUTE', $text);
        $this->assertStringContainsString('28251-K0J-N000-H1', $text);
        $this->assertStringContainsString('260602/43231/DISP-SC', $text);

        // Posisi saat ini: tahap terakhir masih hold.
        $this->assertStringContainsString('Hold', $text);
        $this->assertStringContainsString('Zaini Aminullah', $text);
        $this->assertStringContainsString('GSN 1', $text);

        // Ringkasan jumlah tahap.
        $this->assertStringContainsString('Tercatat', $text);
        $this->assertStringContainsString('3 tahap', $text);

        $keyboard = $sent[0]['payload']['reply_markup']['inline_keyboard'] ?? [];
        $this->assertNotEmpty($keyboard, 'Balasan lot harus punya tombol inline.');
    }

    public function test_perintah_ht_menampilkan_riwayat_heat_treatment(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('cek ht CF3-030626-20', 900010))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('HCQT', $text);
        $this->assertStringContainsString('Jakarya', $text);
    }

    public function test_perintah_pic_menampilkan_pekerja(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('siapa yang proses lot CF3-030626-20', 900011))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('Ibnu Abdul Munir', $text);
        $this->assertStringContainsString('Jakarya', $text);
        $this->assertStringContainsString('Zaini Aminullah', $text);
    }

    public function test_pencarian_surat_jalan_menghasilkan_daftar_lot(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('cari sj 260602/43231/DISP-SC', 900012))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('CF3-030626-20', $text);

        $keyboard = $sent[0]['payload']['reply_markup']['inline_keyboard'] ?? [];
        $this->assertSame('lot|CF3-030626-20', $keyboard[0][0]['callback_data'] ?? null);
    }

    public function test_pencarian_customer_yang_tidak_ada_menampilkan_contoh(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('customer TIDAK ADA SAMA SEKALI', 900013))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('tidak ditemukan', $text);
    }

    public function test_perintah_tanpa_nilai_meminta_user_mengetik(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('cek lot', 900014))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('Masukkan nomor lot', $text);
    }

    public function test_state_percakapan_disimpan_lalu_dipakai(): void
    {
        $this->fakeTelegram();

        // Tombol "Cari Lot" membuat bot menunggu nomor lot.
        $this->postUpdate($this->callbackUpdate('ask|lot', 900015))->assertOk();
        $this->assertSame('lot_status', Cache::get('bot:state:'.self::CHAT_ID));

        // Pesan berikutnya memakai state tadi tanpa menyebut "cek lot".
        $this->postUpdate($this->textUpdate('CF3-030626-20', 900016))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('SPINDLE', $text);
    }

    public function test_callback_answered_dan_mengedit_pesan_asal(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->callbackUpdate('ht|CF3-030626-20', 900017))->assertOk();

        $answered = $this->telegramCalls('answerCallbackQuery');
        $this->assertNotEmpty($answered, 'Callback query harus dijawab agar tombol tidak berputar.');

        $edited = $this->telegramCalls('editMessageText');
        $this->assertNotEmpty($edited, 'Pesan lama harus diedit, bukan ditumpuk balasan baru.');
        $this->assertStringContainsString('HCQT', (string) $edited[0]['payload']['text']);
    }

    public function test_update_yang_sama_hanya_diproses_satu_kali(): void
    {
        $this->fakeTelegram();

        $update = $this->textUpdate('cek lot CF3-030626-20', 900018);

        $this->postUpdate($update)->assertOk();
        $this->postUpdate($update)->assertOk();

        $this->assertCount(
            1,
            $this->telegramCalls('sendMessage'),
            'Update kembar harus diproses tepat satu kali.'
        );
    }

    public function test_sapaan_dijawab_tanpa_menunggu_ai(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('halo bot', 900019))->assertOk();

        $sent = $this->telegramCalls('sendMessage');

        $this->assertNotEmpty($sent);
        $this->assertStringContainsString(
            'bot data produksi',
            (string) $sent[0]['payload']['text']
        );
    }

    public function test_lot_tidak_ditemukan_memberi_petunjuk(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('cek lot ZZ9-010126-99', 900020))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('tidak ditemukan', $text);
        $this->assertStringContainsString('Format nomor lot', $text);
    }

    public function test_balasan_sapaan_bervariasi_antarsesi(): void
    {
        $this->fakeTelegram();

        foreach ([900031, 900032, 900033] as $updateId) {
            $this->postUpdate($this->textUpdate('halo', $updateId))->assertOk();
        }

        $texts = array_map(
            fn (array $call): string => (string) $call['payload']['text'],
            $this->telegramCalls('sendMessage')
        );

        $this->assertCount(3, $texts);
        $this->assertGreaterThan(
            1,
            count(array_unique($texts)),
            'Balasan sapaan tidak boleh selalu sama, kalau tidak bot terbaca seperti script.'
        );

        // Variasinya kalimat, bukan isi: semua versi tetap menyebut diri bot.
        foreach ($texts as $text) {
            $this->assertStringContainsString('bot data produksi', $text);
        }
    }

    public function test_terima_kasih_dijawab_berbeda_dari_sapaan(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('terima kasih', 900034))->assertOk();

        $sent = $this->telegramCalls('sendMessage');
        $text = (string) $sent[0]['payload']['text'];

        $this->assertStringContainsString('Sama-sama', $text);
    }

    public function test_sapaan_tidak_mengirim_indikator_typing(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('halo bot', 900035))->assertOk();

        $this->assertEmpty(
            $this->telegramCalls('sendChatAction'),
            'Sapaan dijawab seketika, jadi tidak perlu indikator typing.'
        );
    }

    public function test_pencarian_lot_tetap_mengirim_indikator_typing(): void
    {
        $this->fakeTelegram();

        $this->postUpdate($this->textUpdate('cek lot CF3-030626-20', 900036))->assertOk();

        $this->assertNotEmpty(
            $this->telegramCalls('sendChatAction'),
            'Pencarian menyentuh database, jadi indikator typing tetap dikirim.'
        );
    }
}
