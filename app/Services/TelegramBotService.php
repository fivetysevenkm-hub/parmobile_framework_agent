<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien Telegram Bot API untuk bot produksi interaktif.
 *
 * Sengaja dipisah dari TelegramService yang hanya bisa "kirim ke chat_id statis"
 * dan selalu menempelkan footer iklan. Bot produksi memerlukan:
 *  - membalas ke chat_id yang datang dari user,
 *  - mengedit pesan setelah data selesai diproses,
 *  - menjawab callback query dari inline keyboard,
 *  - mengelola webhook.
 */
class TelegramBotService
{
    private string $lastDescription = '';

    private int $lastMessageId = 0;

    public function token(): string
    {
        return trim((string) config('telegram.bots.production_bot.token'));
    }

    public function enabled(): bool
    {
        return (bool) config('telegram.production_bot.enabled', true) && $this->token() !== '';
    }

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     * @param  array<string, string>|null  $extra
     */
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null, bool $disableWebPagePreview = true, ?array $extra = null): bool
    {
        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => (string) config('telegram.parse_mode', 'HTML'),
            'disable_web_page_preview' => $disableWebPagePreview,
            'reply_markup' => $replyMarkup,
        ], fn ($value): bool => $value !== null) + ($extra ?? []));
    }

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null): bool
    {
        $result = $this->call('editMessageText', array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => (string) config('telegram.parse_mode', 'HTML'),
            'disable_web_page_preview' => true,
            'reply_markup' => $replyMarkup,
        ], fn ($value): bool => $value !== null), 'edit_message_text');

        // Telegram menolak edit ketika teks tidak berubah; itu bukan error.
        return $result || $this->lastErrorIsUnchanged();
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        return $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text !== '' ? mb_substr($text, 0, 200) : null,
            'show_alert' => $showAlert,
        ]);
    }

    public function sendChatAction(int|string $chatId, string $action = 'typing'): bool
    {
        return $this->call('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    /**
     * @return array{ok: bool, result: mixed, description: string}
     */
    public function setWebhook(string $url, ?string $secretToken = null, int $maxConnections = 10): array
    {
        return $this->request('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secretToken,
            'max_connections' => $maxConnections,
            'allowed_updates' => ['message', 'callback_query'],
        ], fn ($value): bool => $value !== null));
    }

    /**
     * @return array{ok: bool, result: mixed, description: string}
     */
    public function deleteWebhook(): array
    {
        return $this->request('deleteWebhook', ['drop_pending_updates' => true]);
    }

    /**
     * @return array{ok: bool, result: mixed, description: string}
     */
    public function getWebhookInfo(): array
    {
        return $this->request('getWebhookInfo', []);
    }

    /**
     * @return array{ok: bool, result: mixed, description: string}
     */
    public function getMe(): array
    {
        return $this->request('getMe', []);
    }

    public function lastErrorIsUnchanged(): bool
    {
        return str_contains($this->lastDescription, 'message is not modified');
    }

    public function lastMessageId(): int
    {
        return $this->lastMessageId;
    }

    private function call(string $method, array $payload, ?string $resultKey = null): bool
    {
        $response = $this->request($method, $payload);

        if (! $response['ok']) {
            Log::warning('Panggilan Telegram Bot API gagal.', [
                'method' => $method,
                'description' => $response['description'],
            ]);

            return false;
        }

        if ($resultKey !== null && is_array($response['result'])) {
            $this->lastMessageId = (int) ($response['result'][$resultKey] ?? 0);
        }

        return true;
    }

    /**
     * @return array{ok: bool, result: mixed, description: string}
     */
    private function request(string $method, array $payload): array
    {
        $token = $this->token();

        if ($token === '') {
            return ['ok' => false, 'result' => null, 'description' => 'Token bot produksi belum dikonfigurasi.'];
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('telegram.timeout', 8))
                ->connectTimeout(3)
                ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

            $json = $response->json();

            $this->lastDescription = (string) ($json['description'] ?? '');

            if (! $response->successful() || ! is_array($json)) {
                return [
                    'ok' => false,
                    'result' => null,
                    'description' => $this->lastDescription !== '' ? $this->lastDescription : 'HTTP '.$response->status(),
                ];
            }

            return [
                'ok' => $json['ok'] === true,
                'result' => $json['result'] ?? null,
                'description' => $this->lastDescription,
            ];
        } catch (ConnectionException $exception) {
            $this->lastDescription = $exception->getMessage();

            return ['ok' => false, 'result' => null, 'description' => $this->lastDescription];
        } catch (\Throwable $exception) {
            $this->lastDescription = $exception->getMessage();

            return ['ok' => false, 'result' => null, 'description' => $this->lastDescription];
        }
    }
}
