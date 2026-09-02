<?php

namespace App\Livewire;

use App\Services\AuditLogger;
use App\Services\CustomerPortalAuthService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerLogin extends Component
{
    public string $email = '';

    public string $password = '';

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(CustomerPortalAuthService $auth, AuditLogger $audit): void
    {
        $credentials = $this->validate();
        [$userThrottleKey, $ipThrottleKey] = $this->throttleKeys($credentials['email']);

        if (RateLimiter::tooManyAttempts($userThrottleKey, 5) || RateLimiter::tooManyAttempts($ipThrottleKey, 20)) {
            $retryAfter = max(
                RateLimiter::availableIn($userThrottleKey),
                RateLimiter::availableIn($ipThrottleKey),
            );
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.$retryAfter.' detik.',
            ]);
        }

        try {
            $user = $auth->login($credentials['email'], $credentials['password']);
        } catch (ValidationException) {
            $this->hitThrottle($userThrottleKey, $ipThrottleKey);
            $audit->log(
                AuditLogger::EVENT_LOGIN_FAILED,
                'Percobaan login customer gagal: '.$credentials['email'],
                'customer_portal',
                metadata: ['tried_email' => $credentials['email']],
            );
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        if (! $user->isApproved()) {
            RateLimiter::clear($userThrottleKey);
            RateLimiter::clear($ipThrottleKey);
            throw ValidationException::withMessages([
                'email' => 'Akun Anda belum disetujui oleh admin.',
            ]);
        }

        RateLimiter::clear($userThrottleKey);
        RateLimiter::clear($ipThrottleKey);

        $audit->log(
            AuditLogger::EVENT_LOGIN,
            'Customer berhasil masuk: '.$user->email,
            'customer_portal',
            userId: $user->id,
            username: $user->email,
        );

        auth()->guard('customer')->login($user);
        request()->session()->regenerate();

        $this->redirectRoute('customer.lot-tracking', navigate: true);
    }

    private function throttleKeys(string $email): array
    {
        $normalized = Str::lower(trim($email));
        $ip = (string) (request()->ip() ?? 'unknown');

        return [
            'customer-login:user:'.hash('sha256', $normalized),
            'customer-login:ip:'.hash('sha256', $ip),
        ];
    }

    private function hitThrottle(string $userKey, string $ipKey): void
    {
        RateLimiter::hit($userKey, 60);
        RateLimiter::hit($ipKey, 60);
    }

    public function render()
    {
        return view('livewire.customer-login')->layout('layouts.customer-portal');
    }
}
