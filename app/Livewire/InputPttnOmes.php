<?php

namespace App\Livewire;

use App\Services\PttnOmesService;
use Livewire\Component;

class InputPttnOmes extends Component
{
    public string $noLot = '';
    public int $sequenceNo = 0;
    public bool $contextLocked = false;
    public bool $accessDenied = false;
    public string $accessMessage = '';
    public array $lotContext = [];
    public array $mappings = [];
    public string $selectedMappingIndex = '';
    public array $selectedMappingData = [];
    public array $pattern = [];
    public array $rows = [];
    public int $currentStepNo = 1;
    public int $totalSteps = 0;

    public string $currentUser = '';
    public string $pic1 = '';
    public string $lotPic1Name = '';
    public string $pic2 = '';
    public bool $doubleCheckMode = false;
    public bool $canEditPic1 = true;
    public bool $canDoubleCheck = false;
    public string $autoSaveStatus = '';
    public bool $cameraHint = true;

    public function mount(?string $noLot = null, ?int $sequenceNo = null): void
    {
        $this->currentUser = $this->sessionUser();
        $this->pic1 = $this->currentUser;
        $this->noLot = trim((string) ($noLot ?? ''));
        $this->sequenceNo = (int) ($sequenceNo ?? 0);
        $this->contextLocked = $this->noLot !== '' && $this->sequenceNo > 0;

        if ($this->contextLocked && $this->noLot !== '') {
            $this->loadLot(app(PttnOmesService::class));
        }
    }

    public function loadLot(PttnOmesService $service): void
    {
        $this->resetValidation();
        $this->accessDenied = false;
        $this->accessMessage = '';
        $this->noLot = trim($this->noLot);
        $this->doubleCheckMode = false;
        $this->pic2 = '';
        $this->autoSaveStatus = '';

        if ($this->noLot === '') {
            $this->addError('noLot', 'No Lot HT wajib diisi.');
            return;
        }

        $context = $service->lotContext($this->noLot);
        if (! $context) {
            $this->clearLoadedData();
            $this->addError('noLot', 'No Lot HT tidak ditemukan pada data produksi.');
            return;
        }

        $this->lotContext = $context;
        $this->mappings = $service->mappingsForLot($this->noLot, (string) ($context['no_srp'] ?? ''));
        if ($this->mappings === []) {
            $this->clearLoadedData(false);
            $this->addError('noLot', 'PTTN belum diinput oleh Engineering untuk SRP ini.');
            return;
        }

        $this->lotPic1Name = (string) ($service->lotPic1($this->noLot) ?? '');
        $this->pic1 = $this->lotPic1Name !== '' ? $this->lotPic1Name : $this->currentUser;
        $this->updateAccessFlags();

        if ($this->contextLocked) {
            $target = $service->mappingForLotSequence($this->noLot, (string) ($context['no_srp'] ?? ''), $this->sequenceNo);
            if (! $target) {
                $this->accessDenied = true;
                $this->accessMessage = 'Proses dari Gemba tidak memiliki PTTN yang terpasang untuk sequence ini.';
                $this->clearPttnData(false);
                return;
            }
            $this->selectedMappingIndex = (string) array_search($target, $this->mappings, true);
        } else {
            $this->selectedMappingIndex = '0';
        }

        $this->selectMapping($service);
    }

    public function updatedSelectedMappingIndex(PttnOmesService $service): void
    {
        $this->selectMapping($service);
    }

    public function selectMapping(PttnOmesService $service): void
    {
        if ($this->selectedMappingIndex === '' || ! isset($this->mappings[(int) $this->selectedMappingIndex])) {
            return;
        }
        $mapping = $this->mappings[(int) $this->selectedMappingIndex];
        if ($this->contextLocked && (int) ($mapping['sequence_no'] ?? 0) !== $this->sequenceNo) {
            $this->accessDenied = true;
            $this->accessMessage = 'PTTN dikunci pada sequence proses yang dipilih dari Gemba.';
            $this->clearPttnData(false);
            return;
        }
        if (! $service->operatorIsCompetent($this->sessionNik(), (string) ($mapping['process_code'] ?? ''))) {
            $this->accessDenied = true;
            $this->accessMessage = 'Akses ditolak. Operator OMES yang login tidak memiliki kompetensi pada proses PTTN ini.';
            $this->clearPttnData(false);
            return;
        }

        $this->accessDenied = false;
        $this->accessMessage = '';
        $this->selectedMappingData = $mapping;
        $header = $service->patternHeader((string) $mapping['pattern_code']);
        if (! $header) {
            $this->addError('form', 'Pattern PTTN tidak ditemukan.');
            return;
        }

        $this->pattern = $header;
        $this->totalSteps = $service->totalSteps((int) $header['id_pattern']);
        $this->currentStepNo = 1;
        $this->loadStep($service);
        $this->resetValidation();
    }

    public function activateDoubleCheck(PttnOmesService $service): void
    {
        $this->resetValidation();
        $owner = (string) ($service->lotPic1($this->noLot) ?? $this->lotPic1Name);
        $this->lotPic1Name = trim($owner);

        if ($this->lotPic1Name === '') {
            $this->addError('form', 'Double Check belum dapat dilakukan karena PIC 1 belum menyimpan data.');
            return;
        }
        if ($this->sameIdentity($this->lotPic1Name, $this->currentUser)) {
            $this->addError('form', 'Akun PIC 1 tidak dapat melakukan Double Check terhadap inputnya sendiri.');
            $this->updateAccessFlags();
            return;
        }

        $this->pic1 = $this->lotPic1Name;
        $this->pic2 = $this->currentUser;
        $this->doubleCheckMode = true;
        $this->updateAccessFlags();
        $this->loadStep($service);
    }

    public function exitDoubleCheck(): void
    {
        $this->doubleCheckMode = false;
        $this->pic2 = '';
        $this->updateAccessFlags();
        $this->resetValidation();
    }

    public function updateActual(int $index, string $actual, PttnOmesService $service): void
    {
        if (! $this->canEditPic1 || $this->doubleCheckMode) {
            $this->rejectPic1Action($service);
            return;
        }
        if (! isset($this->rows[$index]) || ! empty($this->rows[$index]['is_checkpoint'])) {
            return;
        }

        $actual = trim($actual);
        $this->rows[$index]['actual_check'] = $actual;
        try {
            if ($actual === '') {
                $service->clearPic1Row($this->noLot, $this->currentStepNo, (string) $this->rows[$index]['point'], $this->currentUser, $this->selectedMappingData);
                $this->rows[$index]['status_check'] = '';
                $this->rows[$index]['pic1'] = '';
                $this->rows[$index]['datetime1'] = '';
                $this->autoSaveStatus = 'PIC 1: perubahan dikosongkan otomatis.';
                return;
            }
            if (! is_numeric(str_replace(',', '.', $actual))) {
                $this->rows[$index]['status_check'] = '';
                $this->addError('rows.'.$index.'.actual_check', 'Actual harus numeric.');
                return;
            }

            $actualNumber = (float) str_replace(',', '.', $actual);
            $min = ($this->rows[$index]['std_min'] ?? '') === '' ? null : (float) str_replace(',', '.', (string) $this->rows[$index]['std_min']);
            $max = ($this->rows[$index]['std_max'] ?? '') === '' ? null : (float) str_replace(',', '.', (string) $this->rows[$index]['std_max']);
            $this->rows[$index]['status_check'] = match (strtoupper((string) ($this->rows[$index]['toleransi'] ?? ''))) {
                'MIN' => $min !== null && $actualNumber >= $min ? 'OK' : 'NG',
                'MAX' => $max !== null && $actualNumber <= $max ? 'OK' : 'NG',
                'RANGE' => $min !== null && $max !== null && $actualNumber >= $min && $actualNumber <= $max ? 'OK' : 'NG',
                'ACTUAL' => 'OK',
                default => '',
            };
            $this->rows[$index]['pic1'] = $this->currentUser;
            $service->savePic1Row($this->noLot, $this->pattern, $this->currentStepNo, $this->rows[$index], $this->currentUser, $this->selectedMappingData);
            $this->lotPic1Name = $this->lotPic1Name !== '' ? $this->lotPic1Name : $this->currentUser;
            $this->pic1 = $this->lotPic1Name;
            $this->updateAccessFlags();
            $this->autoSaveStatus = 'PIC 1: tersimpan otomatis.';
            $this->resetValidation('rows.'.$index.'.actual_check');
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            $this->loadStep($service);
        }
    }

    public function toggleCheckpoint(int $index, bool $checked, PttnOmesService $service): void
    {
        if (! $this->canEditPic1 || $this->doubleCheckMode) {
            $this->rejectPic1Action($service);
            return;
        }
        if (! isset($this->rows[$index]) || empty($this->rows[$index]['is_checkpoint'])) {
            return;
        }

        try {
            $checked = (bool) $checked;
            $this->rows[$index]['checkpoint'] = $checked;
            if ($checked) {
                $this->rows[$index]['status_check'] = 'OK';
                $this->rows[$index]['pic1'] = $this->currentUser;
                $service->savePic1Row($this->noLot, $this->pattern, $this->currentStepNo, $this->rows[$index], $this->currentUser, $this->selectedMappingData);
                $this->lotPic1Name = $this->lotPic1Name !== '' ? $this->lotPic1Name : $this->currentUser;
                $this->pic1 = $this->lotPic1Name;
                $this->autoSaveStatus = 'PIC 1: checkpoint tersimpan otomatis.';
            } else {
                $service->clearPic1Row($this->noLot, $this->currentStepNo, (string) $this->rows[$index]['point'], $this->currentUser, $this->selectedMappingData);
                $this->rows[$index]['status_check'] = '';
                $this->rows[$index]['pic1'] = '';
                $this->rows[$index]['datetime1'] = '';
                $this->autoSaveStatus = 'PIC 1: checkpoint dibatalkan otomatis.';
            }
            $this->updateAccessFlags();
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            $this->loadStep($service);
        }
    }

    public function toggleDoubleCheck(int $index, bool $checked, PttnOmesService $service): void
    {
        if (! $this->doubleCheckMode) {
            return;
        }
        if (! isset($this->rows[$index])) {
            return;
        }

        try {
            $checked = (bool) $checked;
            $this->rows[$index]['double_check'] = $checked;
            $service->setDoubleCheckRow($this->noLot, $this->currentStepNo, (string) $this->rows[$index]['point'], $this->currentUser, $checked, $this->selectedMappingData);
            $this->rows[$index]['pic2'] = $checked ? $this->currentUser : '';
            $this->rows[$index]['datetime2'] = $checked ? (string) now() : '';
            $this->autoSaveStatus = 'PIC 2: verifikasi tersimpan otomatis.';
            $this->resetValidation('form');
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('form', $exception->getMessage());
            $this->loadStep($service);
        }
    }

    public function nextStep(PttnOmesService $service): void
    {
        $this->resetValidation();
        if (! $this->doubleCheckMode && ! $this->stepIsComplete()) {
            return;
        }
        if ($this->currentStepNo < $this->totalSteps) {
            $this->currentStepNo++;
            $this->loadStep($service);
            $this->autoSaveStatus = '';
        }
    }

    public function previousStep(PttnOmesService $service): void
    {
        $this->resetValidation();
        if ($this->currentStepNo > 1) {
            $this->currentStepNo--;
            $this->loadStep($service);
            $this->autoSaveStatus = '';
        }
    }

    public function clearForm(): void
    {
        $this->noLot = '';
        $this->sequenceNo = 0;
        $this->contextLocked = false;
        $this->accessDenied = false;
        $this->accessMessage = '';
        $this->clearLoadedData();
        $this->resetValidation();
        session()->forget('success');
    }

    public function render()
    {
        return view('livewire.input-pttn-omes')->layout('layouts.app');
    }

    private function loadStep(PttnOmesService $service): void
    {
        if (! empty($this->pattern['id_pattern']) && $this->noLot !== '') {
            $relationId = $this->selectedMappingData === [] ? null : $service->relationIdForMapping($this->noLot, $this->selectedMappingData);
            $this->rows = $service->stepRows((int) $this->pattern['id_pattern'], $this->currentStepNo, $this->noLot, $relationId);
        }
    }

    private function stepIsComplete(): bool
    {
        $missing = collect($this->rows)
            ->filter(fn (array $row): bool => trim((string) ($row['status_check'] ?? '')) === '')
            ->map(fn (array $row): string => (string) ($row['point'] ?? '-'))
            ->values()
            ->all();

        if ($missing !== []) {
            $this->addError('form', 'Lengkapi parameter pada point: '.implode(', ', $missing).'. Data di-autosave, tetapi Step berikutnya belum dapat dibuka.');
            return false;
        }
        return true;
    }

    private function rejectPic1Action(PttnOmesService $service): void
    {
        $this->addError('form', $this->doubleCheckMode ? 'Mode Double Check aktif. Input PIC 1 dikunci.' : 'Data No Lot ini dibuat oleh PIC 1 lain. Gunakan tombol DOUBLE CHECK.');
        $this->loadStep($service);
    }

    private function updateAccessFlags(): void
    {
        $this->canEditPic1 = $this->lotPic1Name === '' || $this->sameIdentity($this->lotPic1Name, $this->currentUser);
        $this->canDoubleCheck = $this->lotPic1Name !== '' && ! $this->sameIdentity($this->lotPic1Name, $this->currentUser);
    }

    private function clearPttnData(bool $clearLotContext = true): void
    {
        if ($clearLotContext) {
            $this->lotContext = [];
        }
        $this->mappings = [];
        $this->selectedMappingIndex = '';
        $this->selectedMappingData = [];
        $this->pattern = [];
        $this->rows = [];
        $this->currentStepNo = 1;
        $this->totalSteps = 0;
    }

    private function clearLoadedData(bool $clearLot = true): void
    {
        $this->clearPttnData($clearLot);
        $this->lotPic1Name = '';
        $this->pic1 = $this->currentUser;
        $this->pic2 = '';
        $this->doubleCheckMode = false;
        $this->canEditPic1 = true;
        $this->canDoubleCheck = false;
        $this->autoSaveStatus = '';
    }

    private function sessionUser(): string
    {
        return trim((string) (session('username') ?: auth()->user()?->username ?: auth()->id() ?: ''));
    }

    private function sessionNik(): string
    {
        return trim((string) (session('nik') ?: session('id_user') ?: auth()->user()?->id_user ?: ''));
    }

    private function sameIdentity(string $left, string $right): bool
    {
        $left = trim($left);
        $right = trim($right);
        return $left !== '' && $right !== '' && mb_strtolower($left) === mb_strtolower($right);
    }
}
