<?php

namespace App\Livewire;

use App\Models\LegacyUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $pengguna = '';

    public string $password = '';

    protected function rules(): array
    {
        return [
            'pengguna' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $credentials = $this->validate();

        $user = LegacyUser::query()
            ->where('username', $credentials['pengguna'])
            ->where('is_active', 1)
            ->whereIn('id_role', [1, 3, 5])
            ->first();

        if (! $user || ! $this->passwordMatches($credentials['password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'pengguna' => 'Akses ditolak. Periksa nama pengguna dan kata sandi Anda.',
            ]);
        }

        Auth::login($user);
        request()->session()->regenerate();
        request()->session()->put([
            'id_user' => $user->id_user,
            'username' => $user->username,
            'id_role' => $user->id_role,
            'status' => 'login',
            'nik' => $user->id_user,
        ]);

        $this->redirectRoute('dashboard', navigate: true);
    }

    private function passwordMatches(string $plainText, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        try {
            if (Hash::check($plainText, $storedPassword)) {
                return true;
            }
        } catch (\Throwable) {
            // Legacy installations may contain non-Hash passwords.
        }

        return hash_equals($storedPassword, $plainText);
    }

    public function render()
    {
        return view('livewire.login')->layout('layouts.guest');
    }
}
