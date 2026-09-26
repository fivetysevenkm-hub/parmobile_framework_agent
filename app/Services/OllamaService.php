<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien ringan untuk Ollama lokal.
 *
 * Peran kelas ini di chatbot produksi sangat sengaja dibatasi: hanya mengubah
 * kalimat bebas pengguna menjadi "intent" dan kata kunci pencarian. Model
 * tidak pernah menyentuh angka produksi apa pun, sehingga jawaban ke user
 * tetap 100% berasal dari database.
 */
class OllamaService
{
    public function isAvailable(): bool
    {
        if (! config('ollama.fallback_to_rules')) {
            return false;
        }

        return Cache::remember(
            'ollama:available:'.config('ollama.model'),
            (int) config('ollama.health_cache_seconds', 60),
            function (): bool {
                try {
                    $response = Http::timeout(2)
                        ->connectTimeout(1)
                        ->get(rtrim((string) config('ollama.host'), '/').'/api/tags');

                    return $response->successful() && $response->json('models') !== null;
                } catch (ConnectionException) {
                    return false;
                } catch (\Throwable) {
                    return false;
                }
            }
        );
    }

    /**
     * Jalankan chat completion dan kembalikan isi teks respons.
     *
     * @param  int|null  $timeout  override timeout, dipakai untuk membatasi
     *                              waktu tunggu saat user sudah mengetik ulang.
     */
    public function chat(array $messages, ?string $format = null, ?int $timeout = null): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $payload = [
            'model' => config('ollama.model'),
            'messages' => $messages,
            'stream' => false,
            'keep_alive' => config('ollama.keep_alive', '10m'),
            'options' => array_merge((array) config('ollama.options', []), [
                'num_ctx' => (int) config('ollama.num_ctx', 512),
            ]),
        ];

        if ($format !== null) {
            $payload['format'] = $format;
        }

        try {
            $response = Http::timeout($timeout ?? (int) config('ollama.timeout', 12))
                ->connectTimeout(3)
                ->acceptJson()
                ->post(rtrim((string) config('ollama.host'), '/').'/api/chat', $payload);

            if (! $response->successful()) {
                Log::warning('Ollama chat tidak successful.', [
                    'status' => $response->status(),
                    'body' => \Illuminate\Support\Str::limit($response->body(), 300),
                ]);

                Cache::forget('ollama:available:'.config('ollama.model'));

                return null;
            }

            $content = $response->json('message.content');

            return is_string($content) ? trim($content) : null;
        } catch (ConnectionException $exception) {
            Log::warning('Ollama tidak dapat dihubungi.', ['error' => $exception->getMessage()]);
            Cache::forget('ollama:available:'.config('ollama.model'));

            return null;
        } catch (\Throwable $exception) {
            Log::error('Ollama chat exception.', ['error' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * Panggil model dengan format JSON dan decode hasil ke array.
     *
     * Model kecil kadang membungkus JSON di dalam ```json ... ``` atau
     * menambahkan teks penjelasan, jadi parsernya dibuat toleran.
     */
    public function chatJson(array $messages, ?int $timeout = null): ?array
    {
        $content = $this->chat($messages, 'json', $timeout);

        if ($content === null || $content === '') {
            return null;
        }

        $decoded = $this->decodeJson($content);

        if ($decoded === null) {
            Log::warning('Ollama tidak mengembalikan JSON yang valid.', [
                'raw' => \Illuminate\Support\Str::limit($content, 500),
            ]);
        }

        return $decoded;
    }

    private function decodeJson(string $content): ?array
    {
        $candidates = [$content];

        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $content, $matches) === 1) {
            array_unshift($candidates, trim($matches[1]));
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $candidates[] = substr($content, $start, $end - $start + 1);
        }

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
