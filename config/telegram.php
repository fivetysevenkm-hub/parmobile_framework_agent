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
        'rank_a' => [
            'token' => env('TELEGRAM_BOT_TOKEN_RANK_A'),
        ],
    ],

    'chat_ids' => [
        // Menu Proses Akakami QAQC / Approval FLPP.
        'approval_flpp' => env('TELEGRAM_CHAT_ID_APPROVAL_FLPP', '-1003862441020'),

        // Menu Approval Rank A.
        'rank_a' => env('TELEGRAM_CHAT_ID_RANK_A', '-1003708795829'),

        // Menu lain dapat diisi tanpa mengubah service atau komponen.
        'monthly_report' => env('TELEGRAM_CHAT_ID_MONTHLY_REPORT'),
        'approval_abnormal_mtc' => env('TELEGRAM_CHAT_ID_APPROVAL_ABNORMAL_MTC'),
        'incoming_trace' => env('TELEGRAM_CHAT_ID_INCOMING_TRACE'),
        'realtime_production' => env('TELEGRAM_CHAT_ID_REALTIME_PRODUCTION'),
        'last_sequence' => env('TELEGRAM_CHAT_ID_LAST_SEQUENCE'),
        'performance_manpower' => env('TELEGRAM_CHAT_ID_PERFORMANCE_MANPOWER'),
        'productivity_process' => env('TELEGRAM_CHAT_ID_PRODUCTIVITY_PROCESS'),
        'carb_temper' => env('TELEGRAM_CHAT_ID_CARB_TEMPER'),
    ],

    'parse_mode' => 'HTML',
    'timeout' => 8,
];
