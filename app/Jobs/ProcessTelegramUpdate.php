<?php

namespace App\Jobs;

use App\Services\ProductionBotHandler;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Memproses satu update dari Telegram di luar request webhook.
 *
 * Telegram mengirim webhook ulang bila tidak dijawab dalam 10 detik, sehingga
 * update yang sama bisa masuk berkali-kali. Karena itu update_id dipakai
 * sebagai kunci idempotensi: update yang sudah diproses dilewati diam-diam.
 */
class ProcessTelegramUpdate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Berapa kali percobaan sebelum update dianggap gagal.
     */
    public int $tries = 2;

    /**
     * Berapa detik job boleh berjalan sebelum dibuang.
     */
    public int $timeout = 90;

    /**
     * @param  array<string, mixed>  $update
     */
    public function __construct(private readonly array $update)
    {
    }

    /**
     * Update mentah dari Telegram.
     *
     * Diperlukan untuk membaca job yang sudah tersimpan di tabel jobs, mis.
     * saat menambah chat/user ke whitelist.
     *
     * @return array<string, mixed>
     */
    public function getUpdate(): array
    {
        return $this->update;
    }

    public function handle(
        ProductionBotHandler $handler,
        TelegramBotService $telegram
    ): void {
        $updateId = (int) ($this->update['update_id'] ?? 0);

        if ($updateId > 0 && ! $this->claim($updateId)) {
            return;
        }

        if (isset($this->update['callback_query'])) {
            $this->handleCallback($this->update['callback_query'], $handler, $telegram);

            return;
        }

        $message = $this->update['message'] ?? $this->update['edited_message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        $this->handleMessage($message, $handler, $telegram);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleMessage(
        array $message,
        ProductionBotHandler $handler,
        TelegramBotService $telegram
    ): void {
        $chatId = $this->chatId($message);

        if ($chatId === null) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));

        // Pesan tanpa teks (foto, stiker, voice) tidak ada yang bisa dijawab.
        if ($text === '') {
            return;
        }

        $telegram->sendChatAction($chatId, 'typing');

        $state = (string) Cache::get($this->stateKey($chatId), '');

        try {
            $result = $handler->setChatId($chatId)->handleText($text, $state);
        } catch (\Throwable $exception) {
            $this->reportFailure($exception, ['chat_id' => $chatId, 'text' => $text]);

            $telegram->sendMessage(
                $chatId,
                'Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.'
            );

            return;
        }

        $this->rememberState($chatId, (string) $result['state']);

        $telegram->sendMessage($chatId, $result['text'], $result['keyboard']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function handleCallback(
        array $query,
        ProductionBotHandler $handler,
        TelegramBotService $telegram
    ): void {
        $queryId = (string) ($query['id'] ?? '');

        // Tombol di Telegram berputar di layar sampai dijawab; ini harus
        // dikirim lebih dulu dan tanpa satu query database pun.
        if ($queryId !== '') {
            $telegram->answerCallbackQuery($queryId);
        }

        $message = $query['message'] ?? null;
        $chatId = is_array($message) ? $this->chatId($message) : null;

        if ($chatId === null) {
            return;
        }

        $data = (string) ($query['data'] ?? '');

        if ($data === '') {
            return;
        }

        $telegram->sendChatAction($chatId, 'typing');

        try {
            $result = $handler->setChatId($chatId)->handleCallback($data);
        } catch (\Throwable $exception) {
            $this->reportFailure($exception, ['chat_id' => $chatId, 'data' => $data]);

            $telegram->sendMessage($chatId, 'Terjadi kesalahan. Silakan coba lagi.');

            return;
        }

        $this->rememberState($chatId, (string) $result['state']);

        $messageId = (int) ($message['message_id'] ?? 0);

        // Edit pesan asal supaya chat tidak menumpuk salinan jawaban.
        if ($messageId > 0 && $telegram->editMessageText($chatId, $messageId, $result['text'], $result['keyboard'])) {
            return;
        }

        $telegram->sendMessage($chatId, $result['text'], $result['keyboard']);
    }

    /**
     * Pastikan satu update hanya diproses sekali.
     *
     * Cache::add bersifat atomik, sehingga dua worker yang kebetulan menerima
     * webhook kembar tetap menghasilkan satu proses saja.
     */
    private function claim(int $updateId): bool
    {
        $key = 'bot:update:'.$updateId;

        if (Cache::add($key, now()->timestamp, now()->addHours(6))) {
            return true;
        }

        Log::info('Update Telegram duplikat dilewati.', ['update_id' => $updateId]);

        return false;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function chatId(array $message): ?int
    {
        $chat = $message['chat'] ?? null;

        if (! is_array($chat) || ! isset($chat['id'])) {
            return null;
        }

        $chatId = (int) $chat['id'];

        return $chatId !== 0 ? $chatId : null;
    }

    private function stateKey(int $chatId): string
    {
        return 'bot:state:'.$chatId;
    }

    private function rememberState(int $chatId, string $state): void
    {
        $ttl = max(1, (int) config('telegram.production_bot.state_ttl_minutes', 15));

        // State kosong dibuang, bukan disimpan sebagai string kosong, supaya
        // tidak selalu terlihat aktif saat dicek.
        if ($state === '') {
            Cache::forget($this->stateKey($chatId));

            return;
        }

        Cache::put($this->stateKey($chatId), $state, now()->addMinutes($ttl));
    }

    private function reportFailure(\Throwable $exception, array $context): void
    {
        Log::error('Pemrosesan update Telegram gagal.', $context + [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile().':'.$exception->getLine(),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Job Telegram gagal setelah semua percobaan.', [
            'update_id' => $this->update['update_id'] ?? null,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
