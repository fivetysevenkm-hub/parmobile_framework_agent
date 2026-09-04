<?php

namespace App\Livewire;

use App\Services\AuditLogger;
use App\Services\CustomerPortalAuthService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CustomerPortalUserManagement extends Component
{
    public string $userSearch = '';

    public array $users = [];

    public ?int $selectedUserId = null;

    public function mount(CustomerPortalAuthService $service): void
    {
        $this->loadUsers($service);
    }

    public function updatedUserSearch(CustomerPortalAuthService $service): void
    {
        $this->loadUsers($service);
    }

    public function loadUsers(CustomerPortalAuthService $service): void
    {
        $query = \App\Models\CustomerPortalUser::query();

        if ($this->userSearch) {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', "%{$this->userSearch}%")
                    ->orWhere('email', 'LIKE', "%{$this->userSearch}%")
                    ->orWhere('no_customer', 'LIKE', "%{$this->userSearch}%")
                    ->orWhereIn('no_customer', DB::table('tblcustomer')->where('nm_customer', 'LIKE', "%{$this->userSearch}%")->pluck('no_customer'));
            });
        }

        $rows = $query->latest()->get();

        $noCustomers = $rows->pluck('no_customer')->filter()->unique()->toArray();
        $customerNames = DB::table('tblcustomer')
            ->whereIn('no_customer', $noCustomers)
            ->pluck('nm_customer', 'no_customer')
            ->toArray();

        $this->users = $rows->map(function ($user) use ($customerNames) {
            $data = $user->toArray();
            $data['customer_name'] = $customerNames[$user->no_customer] ?? $user->no_customer;

            return $data;
        })->toArray();
    }

    public function approve(int $userId, CustomerPortalAuthService $service, AuditLogger $audit): void
    {
        $user = \App\Models\CustomerPortalUser::find($userId);
        if (! $user) {
            return;
        }

        $adminId = (int) auth()->id();
        $service->approve($user, $adminId);

        $audit->log(
            AuditLogger::EVENT_UPDATE,
            'Customer portal user disetujui: '.$user->email,
            'customer_portal_management',
            newData: ['status' => 'approved', 'email' => $user->email],
        );

        $this->loadUsers($service);
        session()->flash('success', 'Akun customer '.$user->email.' berhasil disetujui.');
    }

    public function reject(int $userId, CustomerPortalAuthService $service, AuditLogger $audit): void
    {
        $user = \App\Models\CustomerPortalUser::find($userId);
        if (! $user) {
            return;
        }

        $adminId = (int) auth()->id();
        $service->reject($user, $adminId);

        $audit->log(
            AuditLogger::EVENT_UPDATE,
            'Customer portal user ditolak: '.$user->email,
            'customer_portal_management',
            newData: ['status' => 'rejected', 'email' => $user->email],
        );

        $this->loadUsers($service);
        session()->flash('success', 'Akun customer '.$user->email.' berhasil ditolak.');
    }

    public function suspend(int $userId, CustomerPortalAuthService $service, AuditLogger $audit): void
    {
        $user = \App\Models\CustomerPortalUser::find($userId);
        if (! $user) {
            return;
        }

        $service->suspend($user);

        $audit->log(
            AuditLogger::EVENT_UPDATE,
            'Customer portal user ditangguhkan: '.$user->email,
            'customer_portal_management',
            newData: ['status' => 'suspended', 'email' => $user->email],
        );

        $this->loadUsers($service);
        session()->flash('success', 'Akun customer '.$user->email.' berhasil ditangguhkan.');
    }

    public function render()
    {
        return view('livewire.customer-portal-user-management')->layout('layouts.app');
    }
}
