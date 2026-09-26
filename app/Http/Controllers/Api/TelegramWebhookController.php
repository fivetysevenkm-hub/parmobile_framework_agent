<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessTelegramUpdate;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Titik masuk webhook Telegram untuk bot produksi.
 *
 * Endpoint ini harus sesingkat mungkin. Telegram akan mengirim ulang update yang
 * belum dijawab dalam 10 detik, jadi controller hanya memvalidasi, meneruskan
 * ke antrian, lalu membalas 200.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $telegram): JsonResponse
    {
        if (! $telegram->enabled()) {
            return $this->forbidden('Production bot belum diaktifkan.');
        }

        if (! $this->secretIsValid($request)) {
            Log::warning('Webhook Telegram ditolak karena secret tidak cocok.', [
                'ip' => $request->ip(),
            ]);

            return $this->forbidden('Secret token tidak valid.');
        }

        $update = $request->json()->all();

        if (! is_array($update) || ! isset($update['update_id'])) {
            return $this->ok();
        }

        if (! $this->isAllowed($update)) {
            Log::info('Update Telegram diabaikan karena di luar whitelist.', [
                'update_id' => $update['update_id'],
                'chat_id' => $this->chatId($update),
                'user_id' => $this->userId($update),
            ]);

            return $this->ok();
        }

        $queue = trim((string) config('telegram.production_bot.queue', 'telegram'));

        ProcessTelegramUpdate::dispatch($update)->onQueue($queue);

        return $this->ok();
    }

    /**
     * Bandingkan secret dengan header yang dikirim Telegram.
     *
     * Perbandingan dilakukan dengan hash_equals agar tidak bocor lewat selisih
     * waktu proses.
     */
    private function secretIsValid(Request $request): bool
    {
        $expected = trim((string) config('telegram.production_bot.webhook_secret', ''));

        // Secret kosong berarti webhook belum diamankan. Menolak seluruh
        // request lebih aman daripada membukanya untuk siapa saja.
        if ($expected === '') {
            Log::warning('Webhook Telegram ditolak karena TELEGRAM_BOT_WEBHOOK_SECRET belum diisi.');

            return false;
        }

        $provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        return hash_equals($expected, $provided);
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function isAllowed(array $update): bool
    {
        $allowedChats = (array) config('telegram.production_bot.allowed_chats', []);
        $allowedUsers = (array) config('telegram.production_bot.allowed_users', []);

        // Tanpa whitelist sama sekali hanya sah saat bot dimatikan lewat env,
        // supaya instalasi produksi tidak pernah terbuka oleh accident.
        if ($allowedChats === [] && $allowedUsers === []) {
            return ! (bool) config('telegram.production_bot.require_whitelist', true);
        }

        $chatId = $this->chatId($update);
        $userId = $this->userId($update);

        if ($allowedChats !== [] && $chatId !== null && in_array((string) $chatId, array_map(
            'strval',
            $allowedChats
        ), true)) {
            return true;
        }

        return $allowedUsers !== []
            && $userId !== null
            && in_array((string) $userId, array_map('strval', $allowedUsers), true);
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function chatId(array $update): ?int
    {
        $source = $update['message']
            ?? $update['edited_message']
            ?? $update['callback_query']['message']
            ?? null;

        if (! is_array($source) || ! isset($source['chat']['id'])) {
            return null;
        }

        return (int) $source['chat']['id'];
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function userId(array $update): ?int
    {
        $source = $update['message']
            ?? $update['edited_message']
            ?? $update['callback_query']['from']
            ?? null;

        if (! is_array($source) || ! isset($source['from']['id'])) {
            return null;
        }

        return (int) $source['from']['id'];
    }

    private function ok(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], 403);
    }
}
