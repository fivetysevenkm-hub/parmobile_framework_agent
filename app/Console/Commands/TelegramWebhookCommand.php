<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Mengelola webhook bot produksi: melihat status, memasang, dan melepas.
 *
 * Contoh:
 *   php artisan telegram:webhook --show
 *   php artisan telegram:webhook --set
 *   php artisan telegram:webhook --delete
 */
class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook
                            {--show : Tampilkan status webhook yang sekarang}
                            {--set : Pasang webhook memakai TELEGRAM_BOT_WEBHOOK_SECRET}
                            {--delete : Lepas webhook yang sekarang}
                            {--make-secret : Buat secret acak untuk diletakkan di .env}';

    protected $description = 'Kelola webhook bot Telegram untuk data produksi';

    public function handle(TelegramBotService $telegram): int
    {
        if ($this->option('make-secret')) {
            return $this->makeSecret();
        }

        if (! $telegram->enabled()) {
            $this->error('Production bot belum aktif. Isi TELEGRAM_BOT_TOKEN_PRODUCTION dan '
                .'pastikan TELEGRAM_PRODUCTION_BOT_ENABLED=true.');

            return self::FAILURE;
        }

        $wantShow = (bool) $this->option('show');
        $wantSet = (bool) $this->option('set');
        $wantDelete = (bool) $this->option('delete');

        if (! $wantShow && ! $wantSet && ! $wantDelete) {
            return $this->showInfo($telegram);
        }

        if ($wantDelete) {
            return $this->deleteWebhook($telegram);
        }

        if ($wantSet) {
            return $this->setWebhook($telegram);
        }

        return $this->showInfo($telegram);
    }

    private function setWebhook(TelegramBotService $telegram): int
    {
        $secret = trim((string) config('telegram.production_bot.webhook_secret', ''));

        if ($secret === '') {
            $this->error('TELEGRAM_BOT_WEBHOOK_SECRET belum diisi di .env.');
            $this->line('Buat nilai acak dengan: <fg=yellow>php artisan telegram:webhook --make-secret</>');

            return self::FAILURE;
        }

        $url = $this->webhookUrl();

        $this->line("Memasang webhook di:");
        $this->line('  <fg=green>'.$url.'</>');

        $result = $telegram->setWebhook($url, $secret);

        if (! $result['ok']) {
            $this->error('Gagal: '.($result['description'] ?: 'tidak diketahui'));

            return self::FAILURE;
        }

        $this->info('Webhook terpasang.');

        return self::SUCCESS;
    }

    private function deleteWebhook(TelegramBotService $telegram): int
    {
        $result = $telegram->deleteWebhook();

        if (! $result['ok']) {
            $this->error('Gagal: '.($result['description'] ?: 'tidak diketahui'));

            return self::FAILURE;
        }

        $this->info('Webhook dilepas.');

        return self::SUCCESS;
    }

    private function showInfo(TelegramBotService $telegram): int
    {
        $me = $telegram->getMe();

        if ($me['ok'] && is_array($me['result'])) {
            $this->line('Bot   : @'.((string) ($me['result']['username'] ?? '?')));
        } else {
            $this->warn('Tidak bisa membaca identitas bot: '.($me['description'] ?: 'tidak diketahui'));
        }

        $info = $telegram->getWebhookInfo();

        if (! $info['ok'] || ! is_array($info['result'])) {
            $this->error('Gagal membaca info webhook: '.($info['description'] ?: 'tidak diketahui'));

            return self::FAILURE;
        }

        $result = $info['result'];

        $this->newLine();
        $this->line('URL webhook    : '.((string) ($result['url'] ?? '(tidak terpasang)')));
        $this->line('Pending update : '.(string) ($result['pending_update_count'] ?? 0));

        $error = $result['last_error_message'] ?? null;

        if ($error !== null) {
            $this->error('Error terakhir : '.$error);
        }

        $secretConfigured = trim((string) config('telegram.production_bot.webhook_secret', '')) !== '';
        $this->line('Secret header  : '.($secretConfigured ? '<fg=green>terisi</>' : '<fg=red>kosong</>'));

        $chats = (array) config('telegram.production_bot.allowed_chats', []);
        $users = (array) config('telegram.production_bot.allowed_users', []);
        $this->line('Whitelist chat : '.($chats === [] ? '<fg=red>kosong</>' : count($chats).' chat'));
        $this->line('Whitelist user : '.($users === [] ? '<fg=red>kosong</>' : count($users).' user'));

        $this->newLine();
        $this->line('Perintah:');
        $this->line('  php artisan telegram:webhook --set');
        $this->line('  php artisan queue:work --queue='.config('telegram.production_bot.queue', 'telegram'));

        return self::SUCCESS;
    }

    private function makeSecret(): int
    {
        $secret = Str::random(48);

        $this->line('Salin baris berikut ke .env:');
        $this->newLine();
        $this->line('<fg=green>TELEGRAM_BOT_WEBHOOK_SECRET='.$secret.'</>');
        $this->newLine();
        $this->line('Setelah itu jalankan: <fg=yellow>php artisan telegram:webhook --set</>');

        return self::SUCCESS;
    }

    private function webhookUrl(): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = ltrim(trim((string) config('telegram.production_bot.webhook_path', 'api/telegram/webhook')), '/');

        return $base.'/'.$path;
    }
}
