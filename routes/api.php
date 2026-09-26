<?php

use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Hanya berisi endpoint yang memang butuh akses publik. Route di sini tidak
| memakai middleware "web", sehingga tidak ada session maupun CSRF.
|
*/

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->name('telegram.webhook');
