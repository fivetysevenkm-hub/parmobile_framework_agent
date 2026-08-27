<?php

namespace App\Livewire;

use App\Services\LotVerifikatorService;
use Livewire\Component;

class LotVerifikator extends Component
{
    public string $noLot = '';

    public bool $hasSearched = false;

    public array $processes = [];

    public array $result = [];

    public string $message = '';

    public string $messageType = '';

    public function loadLot(LotVerifikatorService $service): void
    {
        $this->resetValidation();
        $this->noLot = trim($this->noLot);
        $this->hasSearched = false;
        $this->processes = [];
        $this->result = [];
        $this->message = '';
        $this->messageType = '';

        if ($this->noLot === '') {
            $this->addError('noLot', 'Masukan No Lot terlebih dahulu.');
            return;
        }

        $result = $service->verify($this->noLot);
        if (! ($result['ok'] ?? false)) {
            $this->addError('noLot', (string) ($result['message'] ?? 'No Lot tidak dapat diverifikasi.'));
            return;
        }

        $this->hasSearched = true;
        $this->result = $result;
        $this->processes = $result['processes'] ?? [];
        $this->message = (string) ($result['message'] ?? '');
        $this->messageType = ($result['finished'] ?? false) ? 'success' : 'info';
    }

    public function clearLot(): void
    {
        $this->reset(['noLot', 'hasSearched', 'processes', 'result', 'message', 'messageType']);
        $this->resetValidation();
    }

    public function openProcess(int $sequenceNo, LotVerifikatorService $service): void
    {
        if (! $this->hasSearched) {
            return;
        }

        $data = $service->processForSelection($this->noLot, $sequenceNo);
        if (! $data || ! ($data['process']['clickable'] ?? false)) {
            $this->addError('process', 'Proses tersebut belum menjadi proses aktif berikutnya.');
            return;
        }

        $this->redirectRoute('gemba.input.process', [
            'noLot' => $this->noLot,
            'sequenceNo' => $sequenceNo,
        ]);
    }

    public function render()
    {
        return view('livewire.lot-verifikator')->layout('layouts.app');
    }
}
