<?php

namespace App\Livewire;

use App\Models\LegacyUser;
use App\Services\LegacyPasswordService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $pengguna = '';

    public string $password = '';

    public string $turnstileToken = '';

    protected function rules(): array
    {
        return [
            'pengguna' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'turnstileToken' => ['required', 'string', 'max:2048'],
        ];
    }

    public function authenticate(LegacyPasswordService $passwords): void
    {
        $credentials = $this->validate();
        [$userThrottleKey, $ipThrottleKey] = $this->throttleKeys($credentials['pengguna']);

        if (RateLimiter::tooManyAttempts($userThrottleKey, 5) || RateLimiter::tooManyAttempts($ipThrottleKey, 20)) {
            $retryAfter = max(
                RateLimiter::availableIn($userThrottleKey),
                RateLimiter::availableIn($ipThrottleKey),
            );
            throw ValidationException::withMessages([
                'pengguna' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.$retryAfter.' detik.',
            ]);
        }

        if (! $this->verifyTurnstile($credentials['turnstileToken'])) {
            $this->hitThrottle($userThrottleKey, $ipThrottleKey);
            $this->resetTurnstile();
            throw ValidationException::withMessages([
                'turnstileToken' => 'Verifikasi keamanan gagal atau sudah kedaluwarsa. Silakan ulangi CAPTCHA.',
            ]);
        }

        $user = LegacyUser::query()
            ->where('username', $credentials['pengguna'])
            ->where('is_active', 1)
            ->first();

        if (! $user || ! $passwords->matches($credentials['password'], $user->getAuthPassword())) {
            $this->hitThrottle($userThrottleKey, $ipThrottleKey);
            $this->resetTurnstile();
            throw ValidationException::withMessages([
                'pengguna' => 'Akses ditolak. Periksa nama pengguna dan kata sandi Anda.',
            ]);
        }

        RateLimiter::clear($userThrottleKey);
        RateLimiter::clear($ipThrottleKey);
        Auth::login($user);
        request()->session()->regenerate();
        request()->session()->put([
            'id_user' => $user->id_user,
            'username' => $user->username,
            'id_role' => $user->id_role,
            'status' => 'login',
            'nik' => $user->id_user,
        ]);

        if ($passwords->requiresChange($credentials['password'], $user->getAuthPassword())) {
            request()->session()->put('must_change_password', true);
            $this->redirectRoute('password.change', navigate: true);
            return;
        }

        request()->session()->forget('must_change_password');
        $this->redirectRoute('home', navigate: true);
    }

    private function throttleKeys(string $username): array
    {
        $normalizedUsername = Str::lower(trim($username));
        $ip = (string) (request()->ip() ?? 'unknown');

        return [
            'login:user:'.hash('sha256', $normalizedUsername),
            'login:ip:'.hash('sha256', $ip),
        ];
    }

    private function hitThrottle(string $userKey, string $ipKey): void
    {
        RateLimiter::hit($userKey, 60);
        RateLimiter::hit($ipKey, 60);
    }

    private function verifyTurnstile(string $token): bool
    {
        $secret = (string) config('services.turnstile.secret_key');
        if ($secret === '' || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(8)
                ->post((string) config('services.turnstile.verify_url'), [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            return $response->successful() && $response->json('success') === true;
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    private function resetTurnstile(): void
    {
        $this->turnstileToken = '';
        $this->dispatch('turnstile-reset');
    }

    public function render()
    {
        return view('livewire.login')->layout('layouts.guest');
    }
}