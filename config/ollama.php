<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ollama (Local AI) Configuration
    |--------------------------------------------------------------------------
    |
    | Bot Telegram produksi memakai LLM lokal Ollama HANYA untuk mengubah
    | kalimat bebas pengguna menjadi "intent" + kata kunci pencarian.
    | Seluruh data produksi (nomor lot, proses, PIC, tray, waktu) tetap
    | diambil langsung dari database oleh PHP, sehingga angka yang
    | dikirim ke user tidak pernah dihallucinate oleh model.
    |
    | Contoh menjalankan service lokal:
    |   ollama serve
    |   ollama pull qwen2.5:1.5b
    |
    */

    'host' => env('OLLAMA_HOST', 'http://127.0.0.1:11434'),

    'model' => env('OLLAMA_MODEL', 'qwen2.5:1.5b'),

    /*
    | Batas waktu (detik) per panggilan.
    |
    | PENTING: hasil pengukuran qwen2.5:1.5b di mesin tanpa GPU (2 core,
    | size_vram 0) adalah sekitar 2 token/detik saat generate dan 0,3 detik
    | per token saat memproses prompt. Contoh pengukuran nyata:
    |   prompt  33 token + output 14 token =  26 detik
    |   prompt  68 token + output 12 token =  35 detik
    |   prompt 384 token + output 13 token = 129 detik
    |
    | Karena itu jalur LLM hanya dipakai untuk pesan yang ambigu, prompt-nya
    | sengaja dibuat pendek, dan batas waktu di bawah cukup untuk satu
    | permintaan pendek. Bila memakai model lebih kecil (mis. qwen2.5:0.5b)
    | atau mesin dengan GPU, angka ini bisa dikecilkan lagi.
    */
    'timeout' => (int) env('OLLAMA_TIMEOUT', 30),

    /*
    | Jaga model tetap termuat di RAM antar permintaan. Tanpa ini setiap
    | pesan memuat ulang 940 MB model dan menambah 7-9 detik.
    */
    'keep_alive' => env('OLLAMA_KEEP_ALIVE', '10m'),

    /*
    | Jendela konteks. Makin kecil, prompt lebih cepat diproses karena model
    | tidak perlu membaca konteks yang tidak terpakai.
    */
    'num_ctx' => (int) env('OLLAMA_NUM_CTX', 512),

    'options' => [
        'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.0),
        'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
        'num_predict' => (int) env('OLLAMA_NUM_PREDICT', 32),
    ],



    /*
    | Ketika model tidak tersedia / timeout, chatbot tetap berfungsi penuh
    | memakai aturan (keyword) tanpa AI. Set true untuk mematikan panggilan
    | ke Ollama sama sekali.
    */
    'fallback_to_rules' => filter_var(env('OLLAMA_FALLBACK_TO_RULES', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Cache "model tersedia atau tidak" selama N detik agar bot tidak
    | menunggu timeout penuh setiap pesan saat Ollama mati.
    */
    'health_cache_seconds' => (int) env('OLLAMA_HEALTH_CACHE_SECONDS', 60),
];
