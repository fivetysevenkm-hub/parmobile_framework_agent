<?php

namespace App\Livewire;

use App\Services\PatternItemMasterService;
use Livewire\Component;

class AddItemSatuan extends Component
{
    public ?int $selectedItemId = null;
    public string $itemName = '';
    public string $satuan = '';
    public string $keyword = '';
    public array $items = [];
    public int $currentPage = 1;
    public int $totalPages = 1;
    public int $totalItems = 0;

    public function mount(PatternItemMasterService $service): void
    {
        $this->loadItems($service);
    }

    public function updatedKeyword(PatternItemMasterService $service): void
    {
        $this->currentPage = 1;
        $this->loadItems($service);
    }

    public function nextPage(PatternItemMasterService $service): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->loadItems($service);
        }
    }

    public function previousPage(PatternItemMasterService $service): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadItems($service);
        }
    }

    public function selectItem(int $id, PatternItemMasterService $service): void
    {
        $item = $service->item($id);
        if (! $item) {
            $this->addError('form', 'Item tidak ditemukan.');
            return;
        }

        $this->selectedItemId = (int) $item['id_item'];
        $this->itemName = (string) ($item['item_name'] ?? '');
        $this->satuan = (string) ($item['satuan'] ?? '');
        $this->resetValidation();
    }

    public function saveItem(PatternItemMasterService $service): void
    {
        $this->validate([
            'itemName' => ['required', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:20'],
        ], [
            'itemName.required' => 'Nama Item wajib diisi.',
            'itemName.max' => 'Nama Item maksimal 100 karakter.',
            'satuan.required' => 'Satuan wajib diisi.',
            'satuan.max' => 'Satuan maksimal 20 karakter.',
        ]);

        try {
            if ($this->selectedItemId) {
                $service->update($this->selectedItemId, $this->itemName, $this->satuan);
                $message = 'Data item berhasil diupdate.';
            } else {
                $service->create($this->itemName, $this->satuan);
                $message = 'Item berhasil ditambahkan.';
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadItems($service);
        $this->clearForm(false);
        session()->flash('success', $message);
    }

    public function deleteItem(PatternItemMasterService $service): void
    {
        if (! $this->selectedItemId) {
            $this->addError('form', 'Pilih item yang akan dihapus.');
            return;
        }

        try {
            $service->delete($this->selectedItemId);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadItems($service);
        $this->clearForm(false);
        session()->flash('success', 'Item berhasil dihapus.');
    }

    public function clearForm(bool $resetMessages = true): void
    {
        $this->selectedItemId = null;
        $this->itemName = '';
        $this->satuan = '';
        if ($resetMessages) {
            $this->resetValidation();
            session()->forget('success');
        }
    }

    public function render()
    {
        return view('livewire.add-item-satuan')->layout('layouts.app');
    }

    private function loadItems(PatternItemMasterService $service): void
    {
        $result = $service->items($this->currentPage, $this->keyword);
        $this->items = $result['rows'];
        $this->totalItems = $result['total'];
        $this->totalPages = $result['totalPages'];
        $this->currentPage = $result['page'];
    }
}
