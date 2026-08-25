<?php

namespace App\Livewire;

use App\Services\PttnSrpPairingService;
use Livewire\Component;

class PairingPttnToSrp extends Component
{
    public string $selectedSrp = '';
    public string $selectedSrpPart = '';
    public string $selectedSrpName = '';
    public string $selectedSrpCustomer = '';
    public string $selectedSrpProcess = '';
    public string $processCode = '';
    public string $patternCode = '';
    public string $mappingKeyword = '';
    public string $srpKeyword = '';
    public string $srpSearchBy = 'no_srp';
    public bool $showSrpModal = false;
    public array $srps = [];
    public array $processes = [];
    public array $patterns = [];
    public array $mappings = [];
    public int $mappingPage = 1;
    public int $mappingTotalPages = 1;
    public int $totalMappings = 0;
    public int $srpPage = 1;
    public int $srpTotalPages = 1;
    public int $totalSrps = 0;

    public function mount(PttnSrpPairingService $service): void
    {
        $this->processes = $service->processes();
        $this->patterns = $service->patterns();
        $this->loadMappings($service);
    }

    public function openSrpPicker(PttnSrpPairingService $service): void
    {
        $this->showSrpModal = true;
        $this->srpPage = 1;
        $this->loadSrps($service);
    }

    public function closeSrpPicker(): void
    {
        $this->showSrpModal = false;
    }

    public function updatedMappingKeyword(PttnSrpPairingService $service): void
    {
        $this->mappingPage = 1;
        $this->loadMappings($service);
    }

    public function nextMappingPage(PttnSrpPairingService $service): void
    {
        if ($this->mappingPage < $this->mappingTotalPages) {
            $this->mappingPage++;
            $this->loadMappings($service);
        }
    }

    public function previousMappingPage(PttnSrpPairingService $service): void
    {
        if ($this->mappingPage > 1) {
            $this->mappingPage--;
            $this->loadMappings($service);
        }
    }

    public function updatedSrpKeyword(PttnSrpPairingService $service): void
    {
        $this->srpPage = 1;
        $this->loadSrps($service);
    }

    public function updatedSrpSearchBy(PttnSrpPairingService $service): void
    {
        $this->srpPage = 1;
        $this->loadSrps($service);
    }

    public function nextSrpPage(PttnSrpPairingService $service): void
    {
        if ($this->srpPage < $this->srpTotalPages) {
            $this->srpPage++;
            $this->loadSrps($service);
        }
    }

    public function previousSrpPage(PttnSrpPairingService $service): void
    {
        if ($this->srpPage > 1) {
            $this->srpPage--;
            $this->loadSrps($service);
        }
    }

    public function chooseSrp(string $noSrp, PttnSrpPairingService $service): void
    {
        $result = $service->srps(1, 'no_srp', $noSrp);
        $row = collect($result['rows'])->first(fn (array $item): bool => (string) $item['no_srp'] === $noSrp);
        if (! $row) {
            $this->addError('form', 'SRP tidak ditemukan.');
            return;
        }

        $this->selectedSrp = (string) $row['no_srp'];
        $this->selectedSrpPart = (string) ($row['no_brg_asli'] ?? '');
        $this->selectedSrpName = (string) ($row['nm_part'] ?? '');
        $this->selectedSrpCustomer = (string) ($row['nm_customer'] ?? '');
        $this->selectedSrpProcess = (string) ($row['proses'] ?? '');
        $this->showSrpModal = false;
        $this->resetValidation();
    }

    public function saveMapping(PttnSrpPairingService $service): void
    {
        try {
            $service->saveMapping($this->selectedSrp, $this->processCode, $this->patternCode);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadMappings($service);
        $this->processCode = '';
        $this->patternCode = '';
        $this->resetValidation();
        session()->flash('success', 'Mapping berhasil disimpan.');
    }

    public function deleteMapping(int $id, PttnSrpPairingService $service): void
    {
        try {
            $service->deleteMapping($id);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            return;
        }

        $this->loadMappings($service);
        session()->flash('success', 'Mapping berhasil dihapus.');
    }

    public function clearForm(): void
    {
        $this->selectedSrp = '';
        $this->selectedSrpPart = '';
        $this->selectedSrpName = '';
        $this->selectedSrpCustomer = '';
        $this->selectedSrpProcess = '';
        $this->processCode = '';
        $this->patternCode = '';
        $this->resetValidation();
        session()->forget('success');
    }

    public function render()
    {
        return view('livewire.pairing-pttn-to-srp')->layout('layouts.app');
    }

    private function loadSrps(PttnSrpPairingService $service): void
    {
        $result = $service->srps($this->srpPage, $this->srpSearchBy, $this->srpKeyword);
        $this->srps = $result['rows'];
        $this->totalSrps = $result['total'];
        $this->srpTotalPages = $result['totalPages'];
        $this->srpPage = $result['page'];
    }

    private function loadMappings(PttnSrpPairingService $service): void
    {
        $result = $service->mappings($this->mappingPage, $this->mappingKeyword);
        $this->mappings = $result['rows'];
        $this->totalMappings = $result['total'];
        $this->mappingTotalPages = $result['totalPages'];
        $this->mappingPage = $result['page'];
    }
}
