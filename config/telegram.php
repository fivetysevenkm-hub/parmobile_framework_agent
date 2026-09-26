<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Chat ID seluruh menu dipusatkan di file ini. Untuk mengganti tujuan
    | notifikasi, ubah variabel pada bagian chat_ids di bawah.
    | Token bot tetap dibaca dari .env dan tidak disimpan di repository.
    |
    */

    'bots' => [
        'standard' => [
            'token' => env('TELEGRAM_BOT_TOKEN'),
        ],
        'akakami' => [
            'token' => env('TELEGRAM_BOT_TOKEN_AKAKAMI'),
        ],
        'abnormal_report_mtc' => [
            'token' => env('TELEGRAM_BOT_TOKEN_ABNORMAL_REPORT_MTC'),
        ],
        'rank_a' => [
            'token' => env('TELEGRAM_BOT_TOKEN_RANK_A'),
        ],
        'abnormal_mtc' => [
            'token' => env('TELEGRAM_BOT_TOKEN_APPROVAL_ABNORMAL_MTC'),
        ],
        'carb_temper' => [
            'token' => env('TELEGRAM_BOT_TOKEN_CARB_TEMPER'),
        ],
        'production_bot' => [
            'token' => env('TELEGRAM_BOT_TOKEN_PRODUCTION'),
        ],
    ],

    'chat_ids' => [
        // Menu Proses Akakami QAQC / Approval FLPP.
        'approval_flpp' => env('TELEGRAM_CHAT_ID_APPROVAL_FLPP', '-1003862441020'),

        // Menu Approval Rank A.
        'rank_a' => env('TELEGRAM_CHAT_ID_RANK_A'),

        // Menu lain dapat diisi tanpa mengubah service atau komponen.
        'monthly_report' => env('TELEGRAM_CHAT_ID_MONTHLY_REPORT'),
        'approval_abnormal_mtc' => env('TELEGRAM_CHAT_ID_APPROVAL_ABNORMAL_MTC'),
        // Pengajuan Abnormal Report memakai tujuan legacy Abnormal Mtc jika override khusus belum diisi.
        'abnormal_report' => env('TELEGRAM_CHAT_ID_ABNORMAL_REPORT', env('TELEGRAM_CHAT_ID_APPROVAL_ABNORMAL_MTC')),

        'incoming_trace' => env('TELEGRAM_CHAT_ID_INCOMING_TRACE'),
        'realtime_production' => env('TELEGRAM_CHAT_ID_REALTIME_PRODUCTION'),
        'last_sequence' => env('TELEGRAM_CHAT_ID_LAST_SEQUENCE'),
        'performance_manpower' => env('TELEGRAM_CHAT_ID_PERFORMANCE_MANPOWER'),
        'productivity_process' => env('TELEGRAM_CHAT_ID_PRODUCTIVITY_PROCESS'),
        'carb_temper' => env('TELEGRAM_CHAT_ID_CARB_TEMPER'),

        // Customer Portal — notifikasi pendaftaran customer baru.
        'customer_portal' => env('TELEGRAM_CHAT_ID_CUSTOMER_PORTAL'),
    ],

    'parse_mode' => 'HTML',
    'timeout' => 8,

    /*
    |--------------------------------------------------------------------------
    | Production Bot (interaktif)
    |--------------------------------------------------------------------------
    |
    | Berbeda dengan bot notifikasi di atas, production bot dipakai dua arah:
    | Telegram mengirim webhook ke aplikasi, lalu aplikasi membalas chat.
    | Semua answer di-render sendiri oleh PHP (tidak memakai TelegramAdService)
    | supaya jawaban data produksi tetap bersih dan dapat dikoreksi.
    |
    */

    'production_bot' => [
        'enabled' => filter_var(env('TELEGRAM_PRODUCTION_BOT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

        // Secret token yang dibandingkan dengan header X-Telegram-Bot-Api-Secret-Token.
        'webhook_secret' => env('TELEGRAM_BOT_WEBHOOK_SECRET', ''),

        // Prefix URL webhook, relatif terhadap APP_URL.
        'webhook_path' => env('TELEGRAM_BOT_WEBHOOK_PATH', 'api/telegram/webhook'),

        // Antrian untuk memproses update di luar request (butuh php artisan queue:work).
        'queue' => env('TELEGRAM_BOT_QUEUE', 'telegram'),

        // Batas jumlah update per request webhook (Telegram mengirim maksimal 100).
        'max_updates_per_request' => (int) env('TELEGRAM_BOT_MAX_UPDATES', 20),

        /*
        | Whitelist akses. Pesan dari chat/user di luar daftar ini diabaikan
        | tanpa balasan. Kosongkan kedua daftar hanya untuk trial di lokal,
        | dan pastikan require_whitelist ikut dimatikan.
        */
        'allowed_chats' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TELEGRAM_BOT_ALLOWED_CHATS', ''))
        ))),

        'allowed_users' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TELEGRAM_BOT_ALLOWED_USERS', ''))
        ))),

        // Saat daftar di atas kosong, tolak semua request kecuali di allowances
        // eksplisit lewat env. Ini mencegah webhook terbuka tanpa sengaja.
        'require_whitelist' => filter_var(env('TELEGRAM_BOT_REQUIRE_WHITELIST', true), FILTER_VALIDATE_BOOLEAN),

        // Masa tunggu state percakapan (menunggu user mengetik nomor lot) dalam menit.
        'state_ttl_minutes' => (int) env('TELEGRAM_BOT_STATE_TTL', 15),
    ],
];
