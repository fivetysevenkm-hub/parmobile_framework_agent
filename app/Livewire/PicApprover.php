<?php

namespace App\Livewire;

use App\Services\PicApproverService;
use Livewire\Component;

class PicApprover extends Component
{
    public ?int $selectedNik = null;
    public string $userId = '';
    public string $jabatan = '';
    public string $class = '';
    public string $email = '';
    public string $noHp = '';
    public array $jabatans = [];
    public array $users = [];
    public array $approvers = [];
    public int $currentPage = 1;
    public int $totalPages = 1;
    public int $totalApprovers = 0;
    public string $approverSearch = '';

    public function mount(PicApproverService $service): void
    {
        $this->jabatans = $service->jabatans();
        $this->users = $service->users();
        $this->loadApprovers($service);
    }

    public function updatedApproverSearch(PicApproverService $service): void
    {
        $this->currentPage = 1;
        $this->loadApprovers($service);
    }

    public function nextPage(PicApproverService $service): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->loadApprovers($service);
        }
    }

    public function previousPage(PicApproverService $service): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadApprovers($service);
        }
    }

    public function selectApprover(int $nik, PicApproverService $service): void
    {
        $approver = $service->approver($nik);
        if (! $approver) {
            $this->addError('form', 'PIC Approver tidak ditemukan.');
            return;
        }

        $this->selectedNik = (int) $approver['nik'];
        $this->userId = (string) $approver['id_user'];
        $this->jabatan = (string) ($approver['jabatan'] ?? '');
        $this->class = (string) ($approver['class'] ?? '');
        $this->email = (string) ($approver['email'] ?? '');
        $this->noHp = (string) ($approver['no_hp'] ?? '');
        $this->resetValidation();
    }

    public function saveApprover(PicApproverService $service): void
    {
        $this->validate([
            'userId' => ['required', 'integer', 'min:1'],
            'jabatan' => ['required', 'string', 'max:30'],
            'class' => ['required', 'integer', 'min:1', 'max:99'],
            'email' => ['required', 'string', 'max:30'],
            'noHp' => ['required', 'string', 'max:30'],
        ], [
            'userId.required' => 'Pilih PIC Approver dari daftar user terlebih dahulu.',
            'userId.integer' => 'PIC Approver tidak valid.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
            'class.required' => 'Urutan approval wajib diisi.',
            'class.integer' => 'Urutan approval hanya boleh angka.',
            'class.min' => 'Urutan approval minimal 1 (jabatan tertinggi).',
            'email.required' => 'Email wajib diisi.',
            'email.max' => 'Email maksimal 30 karakter.',
            'noHp.required' => 'No HP wajib diisi.',
            'noHp.max' => 'No HP maksimal 30 karakter.',
        ]);

        try {
            $nik = (int) $this->userId;
            $class = (int) $this->class;
            if ($this->selectedNik) {
                $service->update($this->selectedNik, $this->jabatan, $class, $this->email, $this->noHp);
                $message = 'PIC Approver berhasil diupdate.';
            } else {
                $service->create($nik, $this->jabatan, $class, $this->email, $this->noHp);
                $message = 'PIC Approver berhasil disimpan.';
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadApprovers($service);
        $this->clearForm(false);
        session()->flash('success', $message);
    }

    public function deleteApprover(PicApproverService $service): void
    {
        if (! $this->selectedNik) {
            $this->addError('form', 'Pilih PIC Approver yang akan dihapus.');
            return;
        }

        try {
            $service->delete($this->selectedNik);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->currentPage = min($this->currentPage, $this->totalPages);
        $this->loadApprovers($service);
        $this->clearForm(false);
        session()->flash('success', 'PIC Approver berhasil dihapus.');
    }

    public function clearForm(bool $resetMessages = true): void
    {
        $this->selectedNik = null;
        $this->userId = '';
        $this->jabatan = '';
        $this->class = '';
        $this->email = '';
        $this->noHp = '';
        if ($resetMessages) {
            $this->resetValidation();
            session()->forget('success');
        }
    }

    public function render()
    {
        return view('livewire.pic-approver')->layout('layouts.app');
    }

    private function loadApprovers(PicApproverService $service): void
    {
        $result = $service->approvers($this->currentPage, $this->approverSearch);
        $this->approvers = $result['rows'];
        $this->totalApprovers = $result['total'];
        $this->totalPages = $result['totalPages'];
        $this->currentPage = $result['page'];
    }
}