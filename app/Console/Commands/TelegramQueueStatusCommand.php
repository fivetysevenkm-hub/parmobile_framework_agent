<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Melihat kesehatan antrean bot Telegram: berapa pekerjaan yang menunggu,
 * apakah ada yang gagal, dan apakah ada worker yang sedang berjalan.
 *
 * Dipakai untuk memutuskan dengan aman apakah worker boleh dimatikan,
 * dan untuk memastikan pesan tidak menganggur di antrean.
 *
 * Contoh:
 *   php artisan telegram:queue-status
 */
class TelegramQueueStatusCommand extends Command
{
    protected $signature = 'telegram:queue-status';

    protected $description = 'Lihat kesehatan antrean pekerjaan bot Telegram';

    public function handle(): int
    {
        $queue = (string) config('telegram.production_bot.queue', 'telegram');

        $rows = $this->countJobs($queue);

        if ($rows === null) {
            $this->error('Tabel antrean tidak ditemukan. Jalankan: php artisan migrate');

            return self::FAILURE;
        }

        $this->line('Antrean : '.$queue);
        $this->line('Koneksi : '.config('queue.default'));
        $this->line('');

        $total = array_sum($rows);

        foreach ([
            'pending' => $rows['pending'] ?? 0,
            'reserved' => $rows['reserved'] ?? 0,
            'processing' => $rows['processing'] ?? 0,
            'failed' => $rows['failed'] ?? 0,
        ] as $label => $count) {
            $this->line(sprintf('  %-12s %d', $label, $count));
        }

        $this->line('');

        if ($total === 0) {
            $this->info('Antrean kosong. Worker boleh dimatikan dengan aman.');
        } else {
            $this->warn($total.' pekerjaan menunggu. Jangan dimatikan sebelum selesai.');
        }

        if (($rows['failed'] ?? 0) > 0) {
            $this->error(($rows['failed']).' pekerjaan GAGAL. Lihat tabel failed_jobs.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, int>|null
     */
    private function countJobs(string $queue): ?array
    {
        try {
            $pending = DB::table('jobs')->where('queue', $queue)->count();
            $failed = DB::table('failed_jobs')->where('payload', 'like', '%'.$queue.'%')->count();
        } catch (\Throwable) {
            return null;
        }

        return [
            'pending' => $pending,
            'reserved' => 0,
            'processing' => 0,
            'failed' => $failed,
        ];
    }
}
