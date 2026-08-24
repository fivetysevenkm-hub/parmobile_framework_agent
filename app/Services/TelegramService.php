<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function send(string $message, string $menu = 'approval_flpp', string $bot = 'standard'): bool
    {
        $token = (string) config("telegram.bots.{$bot}.token");
        $chatId = config("telegram.chat_ids.{$menu}");

        if ($token === '' || $chatId === null || $chatId === '') {
            Log::warning('Telegram notification skipped because token or Chat ID is not configured.', [
                'menu' => $menu,
                'bot' => $bot,
            ]);
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('telegram.timeout', 8))
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => config('telegram.parse_mode', 'HTML'),
                ]);

            if (! $response->successful() || $response->json('ok') !== true) {
                Log::warning('Telegram notification failed.', [
                    'menu' => $menu,
                    'bot' => $bot,
                    'status' => $response->status(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('Telegram notification exception.', [
                'menu' => $menu,
                'bot' => $bot,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }
}
