<?php

namespace Tests\Unit;

use App\Services\ProductionIntentService as Intent;
use App\Support\SchemaCache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Uji klasifikasi pesan tanpa menyentuh Telegram maupun database produksi.
 *
 * Semua pesan di sini sudah pasti menyelesaikan intent lewat aturan, sehingga
 * tidak pernah jatuh ke LLM. Kalau ada test yang memanggil LLM, itu tanda
 * aturan sudah tidak cukup dan testnya perlu diperbaiki.
 */
class ProductionIntentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SchemaCache::flush();

        // Ollama dipalsukan mati supaya test tidak bergantung pada apakah
        // Ollama kebetulan menyala di mesin mana pun, dan tidak ikut lambat.
        Http::fake([
            '127.0.0.1:11434/*' => Http::response([], 503),
            '*' => Http::response([], 503),
        ]);
    }

    /**
     * @return array{intent: string, query: string, source: string}
     */
    private function parse(string $text, ?string $pending = null): array
    {
        return $this->intents()->parse($text, $pending);
    }

    private function intents(): Intent
    {
        return app(Intent::class);
    }

    // ------------------------------------------------------------------
    // Sapaan dan obrolan ringan
    // ------------------------------------------------------------------

    public function test_sapaan_dikelompokkan_sebagai_greeting(): void
    {
        $this->assertSame('rule:smalltalk:greeting', $this->parse('halo')['source']);
        $this->assertSame('rule:smalltalk:greeting', $this->parse('Halo Bot')['source']);
        $this->assertSame('rule:smalltalk:greeting', $this->parse('selamat pagi!')['source']);
    }

    public function test_terima_kasih_dipisahkan_dari_sapaan(): void
    {
        $this->assertSame('rule:smalltalk:thanks', $this->parse('terima kasih')['source']);
        $this->assertSame('rule:smalltalk:thanks', $this->parse('makasih ya')['source']);
    }

    public function test_perpisahan_dipisahkan_dari_sapaan(): void
    {
        $this->assertSame('rule:smalltalk:bye', $this->parse('sampai jumpa')['source']);
        $this->assertSame('rule:smalltalk:bye', $this->parse('bye')['source']);
    }

    public function test_pertanyaan_kabar_dipisahkan_dari_sapaan(): void
    {
        $this->assertSame('rule:smalltalk:howareyou', $this->parse('apa kabar?')['source']);
    }

    public function test_sapaan_dikenal_saat_diikuti_kata_lain(): void
    {
        // "halo bot" tidak persis ada di daftar, tapi kata sapaannya ada.
        $this->assertSame('rule:smalltalk:greeting', $this->parse('halo bot admin')['source']);
    }

    public function test_kata_umum_tidak_mengambil_alih_perintah_produksi(): void
    {
        // "nama" pernah membuat "cek nama part" salah dikira sapaan.
        $this->assertSame(Intent::INTENT_SEARCH_PART, $this->parse('cek nama part')['intent']);

        // "sampai" pernah membuat pertanyaan lot ikut dianggap perpisahan.
        $this->assertStringNotContainsString('smalltalk', $this->parse('sampai mana lotnya')['source']);

        // "oke" bukan alasan untuk mengabaikan perintah yang menyusul.
        $this->assertSame(Intent::INTENT_LOT, $this->parse('oke cek lot')['intent']);
    }

    public function test_kalimat_panjang_tidak_dianggap_sapaan(): void
    {
        // Ada kata "pagi" di dalamnya, tapi kalimatnya terlalu panjang untuk
        // diperlakukan sebagai sapaan.
        $result = $this->parse('selamat pagi saya mau cek lot yang sedang jalan di mesin CF3');

        $this->assertNotSame('rule:smalltalk:greeting', $result['source']);
        $this->assertSame(Intent::INTENT_ACTIVE, $result['intent']);
    }

    public function test_sapaan_dilewati_saat_user_sedang_diminta_menilai(): void
    {
        // Pending intent harus menang supaya "terima kasih" yang diketik di
        // tengah percakapan tidak membatalkan permintaan yang sedang berjalan.
        $result = $this->parse('terima kasih', Intent::INTENT_SEARCH_CUSTOMER);

        $this->assertStringNotContainsString('smalltalk', $result['source']);
    }

    // ------------------------------------------------------------------
    // Toleransi salah ketik
    // ------------------------------------------------------------------

    public function test_salah_ketik_kata_kunci_tetap_dikenali(): void
    {
        $result = $this->parse('custome DIAMETRAL');

        $this->assertSame(Intent::INTENT_SEARCH_CUSTOMER, $result['intent']);
        $this->assertSame('DIAMETRAL', $result['query'], 'Kata yang salah ketik harus dibuang dari query.');
    }

    public function test_salah_ketik_pada_nilai_besar_tidak_merusak_hasil(): void
    {
        // "SPINDEL" masih near-miss, tapi tidak boleh menggantikan nilai asli
        // yang sudah ketemu karena lot terdeteksi duluan.
        $result = $this->parse('cek lot CF3-030626-20');

        $this->assertSame(Intent::INTENT_LOT, $result['intent']);
        $this->assertSame('CF3-030626-20', $result['query']);
    }

    public function test_kata_yang_tidak_mirip_tidak_dipaksa_menjadi_intent(): void
    {
        // "gamblong" sama sekali tidak menyerupai kata kunci manapun.
        $result = $this->parse('gamblong parah sekali hari ini');

        $this->assertSame(Intent::INTENT_UNKNOWN, $result['intent']);
    }

    public function test_kata_pendek_tidak_dicocokkan_toleran(): void
    {
        // "ht" hanya boleh cocok sebagai kata utuh. "lot" harus tetap menang,
        // dan tidak boleh berubah jadi heat treatment.
        $result = $this->parse('cek lot LIGHT-030626-20');

        $this->assertSame(Intent::INTENT_LOT, $result['intent']);
        $this->assertStringNotContainsString('lot_heat_treatment', $result['intent']);
    }

    // ------------------------------------------------------------------
    // Perilaku lama yang harus tetap berlaku
    // ------------------------------------------------------------------

    public function test_perintah_dengan_nomor_lot_tetap_berjalan(): void
    {
        $result = $this->parse('cek lot CF3-030626-20');

        $this->assertSame(Intent::INTENT_LOT, $result['intent']);
        $this->assertSame('CF3-030626-20', $result['query']);
    }

    public function test_hold_tetap_dikenali(): void
    {
        $result = $this->parse('lot yang hold');

        $this->assertSame(Intent::INTENT_ACTIVE, $result['intent']);
    }

    public function test_perintah_batal_tetap_dikenali(): void
    {
        $this->assertSame(Intent::INTENT_CANCEL, $this->parse('batal')['intent']);
    }

    public function test_kata_kunci_tanpa_nilai_meminta_user_mengetik(): void
    {
        $result = $this->parse('cek lot');

        $this->assertSame(Intent::INTENT_LOT, $result['intent']);
        $this->assertSame('', $result['query']);
    }

    // ------------------------------------------------------------------
    // Indikator "typing"
    // ------------------------------------------------------------------

    public function test_pesan_instan_dikenali_tanpa_menunggu_ai(): void
    {
        $this->assertTrue($this->intents()->isInstantReply('terima kasih'));
        $this->assertTrue($this->intents()->isInstantReply('halo bot'));
        $this->assertTrue($this->intents()->isInstantReply('/start'));
        $this->assertTrue($this->intents()->isInstantReply('/batal'));
    }

    public function test_pesan_yang_butuh_data_bukan_pesan_instan(): void
    {
        $this->assertFalse($this->intents()->isInstantReply('cek lot CF3-030626-20'));
        $this->assertFalse($this->intents()->isInstantReply('hold'));
        $this->assertFalse($this->intents()->isInstantReply('terima kasih, cek lot CF3-030626-20'));
    }
}
