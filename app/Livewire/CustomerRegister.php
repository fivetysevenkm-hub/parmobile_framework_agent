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

    public string $no_customer = '';

    public string $phone = '';

    public string $customerSearch = '';

    public bool $showCustomerPicker = false;

    public array $customers = [];

    public function mount(): void
    {
        $this->customers = DB::table('tblcustomer')
            ->select('no_customer', 'nm_customer')
            ->orderBy('nm_customer')
            ->get()
            ->map(fn ($row) => ['no_customer' => $row->no_customer, 'nm_customer' => $row->nm_customer])
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:tbl_customer_portal_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'no_customer' => ['required', 'string', 'exists:tblcustomer,no_customer'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function getCustomerNameProperty(): string
    {
        foreach ($this->customers as $customer) {
            if ($customer['no_customer'] === $this->no_customer) {
                return $customer['nm_customer'];
            }
        }

        return $this->no_customer;
    }

    public function getFilteredCustomersProperty(): array
    {
        $search = mb_strtolower(trim($this->customerSearch));
        if ($search === '') {
            return $this->customers;
        }

        return array_values(array_filter(
            $this->customers,
            fn ($customer) => str_contains(mb_strtolower($customer['nm_customer']), $search)
                || str_contains(mb_strtolower($customer['no_customer']), $search)
        ));
    }

    public function openCustomerPicker(): void
    {
        $this->customerSearch = '';
        $this->showCustomerPicker = true;
    }

    public function closeCustomerPicker(): void
    {
        $this->showCustomerPicker = false;
        $this->customerSearch = '';
    }

    public function selectCustomer(string $noCustomer): void
    {
        $this->no_customer = $noCustomer;
        $this->closeCustomerPicker();
    }

    public function register(CustomerPortalAuthService $auth, AuditLogger $audit, TelegramService $telegram): void
    {
        $this->validate();

        $user = $auth->register([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'no_customer' => $this->no_customer,
            'phone' => $this->phone,
        ]);

        $audit->log(
            AuditLogger::EVENT_CREATE,
            'Customer baru mendaftar: '.$this->email.' ('.$this->customerName.')',
            'customer_portal',
            newData: ['email' => $this->email, 'no_customer' => $this->no_customer],
        );

        $telegram->send(
            "🔔 <b>Customer Portal — Pendaftaran Baru</b>\n\n".
            "Nama: {$this->name}\n".
            "Email: {$this->email}\n".
            "Customer: {$this->customerName}\n".
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
