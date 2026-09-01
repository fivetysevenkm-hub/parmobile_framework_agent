<?php

namespace App\Livewire;

use App\Services\AuditLogger;
use App\Services\LegacyPasswordService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ChangePassword extends Component
{
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    protected function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string'],
            'newPassword' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'same:newPasswordConfirmation',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            ],
            'newPasswordConfirmation' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'newPassword.min' => 'Password baru minimal 8 karakter.',
            'newPassword.max' => 'Password baru maksimal 15 karakter karena mengikuti kolom legacy tbl_user.pwd.',
            'newPassword.same' => 'Konfirmasi password baru tidak sama.',
            'newPassword.regex' => 'Password harus mengandung huruf kecil, huruf besar, angka, dan karakter khusus.',
        ];
    }

    public function save(LegacyPasswordService $passwords, AuditLogger $audit): void
    {
        $this->validate();

        $user = Auth::user();
        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if (! $passwords->matches($this->currentPassword, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'currentPassword' => 'Password saat ini tidak sesuai.',
            ]);
        }

        if ($this->currentPassword === $this->newPassword) {
            throw ValidationException::withMessages([
                'newPassword' => 'Password baru harus berbeda dari password saat ini.',
            ]);
        }

        DB::table('tbl_user')
            ->where('id_user', $user->id_user)
            ->update(['pwd' => $passwords->storePlaintext($this->newPassword)]);

        $audit->log(
            AuditLogger::EVENT_PASSWORD_CHANGE,
            'Pengguna mengganti password sendiri',
            'user-management',
            newData: ['password_changed' => true],
            userId: (int) $user->id_user,
            username: (string) ($user->username ?: ''),
        );

        request()->session()->regenerate();
        request()->session()->forget('must_change_password');
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        session()->flash('success', 'Password berhasil diperbarui.');
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.change-password')->layout('layouts.guest');
    }
}
