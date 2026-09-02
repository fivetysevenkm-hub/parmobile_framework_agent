<?php

namespace App\Services;

use App\Models\CustomerPortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerPortalAuthService
{
    public function register(array $data): CustomerPortalUser
    {
        return CustomerPortalUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'company' => $data['company'],
            'no_customer' => $data['no_customer'],
            'phone' => $data['phone'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function login(string $email, string $password): CustomerPortalUser
    {
        $user = CustomerPortalUser::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        return $user;
    }

    public function approve(CustomerPortalUser $user, int $adminId): void
    {
        $user->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);
    }

    public function reject(CustomerPortalUser $user, int $adminId): void
    {
        $user->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);
    }

    public function suspend(CustomerPortalUser $user): void
    {
        $user->update(['status' => 'suspended']);
    }

    public function getCustomers(): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerPortalUser::all();
    }

    public function getPending(): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerPortalUser::where('status', 'pending')->latest()->get();
    }
}
