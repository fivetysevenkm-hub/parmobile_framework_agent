<?php

namespace App\Livewire;

use App\Services\AuditLogger;
use App\Services\CustomerPortalAuthService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerRegister extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $company = '';

    public string $no_customer = '';

    public string $phone = '';

    public array $customers = [];

    public function mount(): void
    {
        $this->customers = DB::table('tblcustomer')
            ->select('no_customer', 'nm_customer')
            ->orderBy('nm_customer')
            ->get()
            ->pluck('nm_customer', 'no_customer')
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:tbl_customer_portal_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company' => ['required', 'string', 'max:255'],
            'no_customer' => ['required', 'string', 'exists:tblcustomer,no_customer'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function register(CustomerPortalAuthService $auth, AuditLogger $audit, TelegramService $telegram): void
    {
        $this->validate();

        $user = $auth->register([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'company' => $this->company,
            'no_customer' => $this->no_customer,
            'phone' => $this->phone,
        ]);

        $audit->log(
            AuditLogger::EVENT_CREATE,
            'Customer baru mendaftar: '.$this->email.' ('.$this->company.')',
            'customer_portal',
            newData: ['email' => $this->email, 'company' => $this->company, 'no_customer' => $this->no_customer],
        );

        $customerName = $this->customers[$this->no_customer] ?? $this->no_customer;
        $telegram->send(
            "🔔 <b>Customer Portal — Pendaftaran Baru</b>\n\n".
            "Nama: {$this->name}\n".
            "Email: {$this->email}\n".
            "Perusahaan: {$this->company}\n".
            "Customer: {$customerName}\n".
            "Telepon: ".($this->phone ?: '-')."\n\n".
            "Silakan setujui atau tolak melalui menu <b>User Management → Customer Portal Users</b>.",
            'customer_portal',
        );

        session()->flash('success', 'Pendaftaran berhasil! Silakan tunggu persetujuan admin sebelum bisa masuk.');

        $this->redirectRoute('customer.login', navigate: true);
    }

    public function render()
    {
        return view('livewire.customer-register')->layout('layouts.customer-portal');
    }
}
