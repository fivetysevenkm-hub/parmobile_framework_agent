<?php

namespace App\Livewire;

use App\Models\CustomerPortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerForgotPassword extends Component
{
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $token = '';

    public bool $showResetForm = false;

    protected function rules(): array
    {
        if ($this->showResetForm) {
            return [
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        return [
            'email' => ['required', 'email', 'exists:tbl_customer_portal_users,email'],
        ];
    }

    public function sendResetLink(): void
    {
        $this->validate();

        $user = CustomerPortalUser::where('email', $this->email)->first();

        if (! $user || ! $user->isApproved()) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak ditemukan atau akun belum disetujui.',
            ]);
        }

        $token = Password::createToken($user, 'customers');

        $this->token = $token;
        $this->showResetForm = true;
    }

    public function resetPassword(): void
    {
        $this->validate();

        $user = CustomerPortalUser::where('email', $this->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak ditemukan.',
            ]);
        }

        $user->update(['password' => Hash::make($this->password)]);
        Password::deleteToken($user);

        session()->flash('success', 'Kata sandi berhasil diubah! Silakan masuk.');

        $this->redirectRoute('customer.login', navigate: true);
    }

    public function render()
    {
        return view('livewire.customer-forgot-password')->layout('layouts.customer-portal');
    }
}
