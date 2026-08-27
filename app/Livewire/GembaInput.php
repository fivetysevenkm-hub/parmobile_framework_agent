<?php

namespace App\Livewire;

use App\Services\AbnormalReportService;
use App\Services\AbnormalReportTelegramMessage;
use App\Services\AkakamiTelegramMessage;
use App\Services\GembaInputService;
use App\Services\LotVerifikatorService;
use App\Services\TelegramService;
use Livewire\Component;

class GembaInput extends Component
{
    public string $noLot = '';
    public string $scanInput = '';
    public int $sequenceNo = 0;
    public array $context = [];
    public array $lotContext = [];
    public array $process = [];
    public array $state = [];
    public array $operators = [];
    public array $machines = [];
    public array $problemCategories = [];
    public array $problemRanks = [];
    public bool $requiresMachine = false;

    public string $operatorNik = '';
    public string $employeeSearch = '';
    public array $employeeResults = [];
    public bool $showEmployeePicker = false;
    public string $employeePickerMessage = '';
    public string $trayNo = '';
    public string $traySmall = '';
    public string $machineNo = '';
    public string $flashMessage = '';
    public string $flashType = '';
    public int $flashSequence = 0;
    public bool $showStatus = false;
    public bool $showAkakami = false;

    public string $akakamiNoReg = '';
    public string $akakamiCategory = '';
    public string $akakamiRank = '';
    public string $akakamiQty = '';
    public string $akakamiDescription = '';
    public string $akakamiReporterNik = '';

    public bool $showAbnormalReport = false;
    public string $abnNoReg = '';
    public string $abnCategory = '';
    public string $abnObjectName = '';
    public string $abnDescription = '';
    public bool $abnConfirmed = false;

    public function mount(?string $noLot = null, ?int $sequenceNo = null): void
    {
        $service = app(GembaInputService::class);
        $this->noLot = trim((string) ($noLot ?? ''));
        $this->sequenceNo = (int) ($sequenceNo ?? 0);
        $this->machines = $service->machines();
        $this->problemCategories = $service->problemCategories();
        $this->problemRanks = $service->problemRanks();
        if ($this->noLot !== '' && $this->sequenceNo > 0) {
            $this->loadContext($service);
        }
    }

    public function processScanInput(LotVerifikatorService $verifikator, GembaInputService $service): void
    {
        $value = trim($this->scanInput);
        $this->scanInput = '';
        if ($value === '') {
            $this->addError('scanInput', 'Masukan atau scan No Lot/NIK terlebih dahulu.');
            return;
        }

        if (ctype_digit(substr($value, 0, 1))) {
            $this->operatorNik = $value;
            $this->addOperator($service);
            return;
        }

        $this->noLot = $value;
        $this->loadLot($verifikator, $service);
    }

    public function loadLot(LotVerifikatorService $verifikator, GembaInputService $service): void
    {
        $this->noLot = trim($this->noLot);
        if ($this->noLot === '') {
            $this->addError('noLot', 'No Lot wajib diisi.');
            return;
        }

        $result = $verifikator->verify($this->noLot);
        if (! ($result['ok'] ?? false)) {
            $this->addError('noLot', (string) ($result['message'] ?? 'No Lot tidak ditemukan.'));
            return;
        }

        $clickable = collect($result['processes'] ?? [])->first(fn (array $item): bool => (bool) ($item['clickable'] ?? false));
        if ($clickable) {
            $this->sequenceNo = (int) $clickable['sequence_no'];
        }
        $this->loadContext($service);
        $this->flash('Konteks lot dan proses aktif berhasil dimuat.', 'success');
    }

    public function openEmployeePicker(GembaInputService $service): void
    {
        if ($this->context === []) {
            $this->employeePickerMessage = 'Muat No Lot dan proses aktif terlebih dahulu.';
            $this->employeeResults = [];
            $this->showEmployeePicker = true;
            return;
        }

        $this->employeePickerMessage = '';
        $this->showEmployeePicker = true;
        $this->searchEmployees($service);
    }

    public function searchEmployees(GembaInputService $service): void
    {
        if ($this->context === []) {
            $this->employeeResults = [];
            $this->employeePickerMessage = 'Muat No Lot dan proses aktif terlebih dahulu.';
            return;
        }

        $this->employeeResults = $service->searchOperators($this->employeeSearch, $this->process);
        $this->employeePickerMessage = $this->employeeResults === []
            ? 'Karyawan tidak ditemukan berdasarkan kata pencarian.'
            : '';
    }

    public function chooseEmployee(string $nik, GembaInputService $service): void
    {
        $operator = $service->findOperator($nik, $this->process);
        if (! $operator) {
            $this->employeePickerMessage = 'Data NIK karyawan tidak ditemukan.';
            return;
        }
        if (! ($operator['allowed'] ?? false)) {
            $this->employeePickerMessage = 'Karyawan ditolak karena skill tidak sesuai dengan process code aktif.';
            return;
        }
        if (collect($this->operators)->contains(fn (array $item): bool => (string) ($item['nik'] ?? '') === (string) $operator['nik'])) {
            $this->employeePickerMessage = 'Karyawan tersebut sudah terdaftar sebagai operator.';
            return;
        }

        $this->operatorNik = (string) $operator['nik'];
        $this->showEmployeePicker = false;
        $this->employeePickerMessage = '';
    }

    public function closeEmployeePicker(): void
    {
        $this->showEmployeePicker = false;
        $this->employeePickerMessage = '';
    }

    public function addOperator(GembaInputService $service): void
    {
        $this->resetValidation('operatorNik');
        $nik = trim($this->operatorNik);
        if ($nik === '') {
            $this->addError('operatorNik', 'NIK operator wajib diisi atau di-scan.');
            return;
        }
        if (count($this->operators) >= (int) config('gemba.max_operator_slots', 4)) {
            $this->addError('operatorNik', 'Maksimal empat operator untuk satu proses.');
            return;
        }
        if (collect($this->operators)->contains(fn (array $operator): bool => (string) ($operator['nik'] ?? '') === $nik)) {
            $this->addError('operatorNik', 'Operator tersebut sudah terdaftar.');
            return;
        }

        $operator = $service->findOperator($nik, $this->process);
        if (! $operator) {
            $this->addError('operatorNik', 'Data NIK karyawan tidak ditemukan.');
            return;
        }
        if (! ($operator['allowed'] ?? false)) {
            $this->addError('operatorNik', 'Operator belum memiliki skill untuk proses ini.');
            return;
        }

        $this->operators[] = [
            'nik' => (string) $operator['nik'],
            'name' => (string) $operator['name'],
            'ojt' => (string) $operator['ojt'],
        ];
        $this->operatorNik = '';
    }

    public function removeOperator(int $index): void
    {
        if (isset($this->operators[$index])) {
            unset($this->operators[$index]);
            $this->operators = array_values($this->operators);
        }
    }

    public function toggleStatus(GembaInputService $service): void
    {
        $this->showStatus = ! $this->showStatus;
        if ($this->showStatus) {
            $this->refreshState($service);
        }
    }

    public function toggleAkakami(): void
    {
        if ($this->context === []) {
            $this->flash('Muat No Lot terlebih dahulu.', 'warning');
            return;
        }

        $this->showAkakami = ! $this->showAkakami;
        if ($this->showAkakami) {
            if ($this->akakamiNoReg === '') {
                $this->akakamiNoReg = 'FLPP'.now('Asia/Jakarta')->format('ymdHis');
            }
            if ($this->akakamiQty === '') {
                $this->akakamiQty = (string) ($this->lotContext['qty'] ?? '');
            }
            if ($this->akakamiReporterNik === '' && isset($this->operators[0]['nik'])) {
                $this->akakamiReporterNik = (string) $this->operators[0]['nik'];
            }
        }
    }

    public function submitAkakami(
        GembaInputService $service,
        TelegramService $telegram,
        AkakamiTelegramMessage $formatter
    ): void {
        $this->resetValidation();
        $reporter = $service->findOperator($this->akakamiReporterNik, $this->process);
        if (! $reporter || ! ($reporter['allowed'] ?? false) || ! collect($this->operators)->contains(fn (array $item): bool => (string) ($item['nik'] ?? '') === $this->akakamiReporterNik)) {
            $this->addError('akakami', 'Pelapor Akakami harus berasal dari operator yang sudah dipilih dan sesuai skill.');
            return;
        }
        $result = $service->submitAkakami($this->context, [
            'no_reg' => $this->akakamiNoReg,
            'category' => $this->akakamiCategory,
            'rank' => $this->akakamiRank,
            'qty' => $this->akakamiQty,
            'description' => $this->akakamiDescription,
            'reporter_nik' => $this->akakamiReporterNik,
        ]);

        if (! ($result['ok'] ?? false)) {
            $this->addError('akakami', (string) ($result['message'] ?? 'Gagal melaporkan Akakami.'));
            return;
        }

        $notificationDetail = $result['payload'];
        $notificationDetail['area'] = (string) ($this->process['name'] ?? $this->process['id_seq'] ?? '-');
        $notificationDetail['nm_part'] = (string) ($this->lotContext['nm_part'] ?? '-');
        $notificationDetail['pic_name'] = (string) ($reporter['name'] ?? $this->akakamiReporterNik);
        $telegram->send(
            $formatter->formatGemba($notificationDetail, now('Asia/Jakarta')->format('Y-m-d H:i:s')),
            'approval_flpp',
            'akakami'
        );

        $this->showAkakami = false;
        $this->refreshState($service);
        $this->flash((string) ($result['message'] ?? 'Akakami dilaporkan.'), 'success');
    }

    public function toggleAbnormalReport(): void
    {
        if ($this->context === []) {
            $this->flash('Muat No Lot terlebih dahulu.', 'warning');
            return;
        }

        $this->showAbnormalReport = ! $this->showAbnormalReport;
        if ($this->showAbnormalReport) {
            $this->abnNoReg = 'ABN'.now('Asia/Jakarta')->format('ymdHisv');
            $this->abnConfirmed = false;
        }
    }

    public function submitAbnormalReport(
        AbnormalReportService $service,
        TelegramService $telegram,
        AbnormalReportTelegramMessage $formatter
    ): void {
        $allowedCategories = ['Mesin', 'Tool/Equipment', 'Jig HT', 'Alat Inspeksi', 'Other'];
        if (! in_array($this->abnCategory, $allowedCategories, true)) {
            $this->addError('abnormal', 'Silakan pilih kategori object terlebih dahulu.');
            return;
        }
        if (trim($this->abnObjectName) === '' || trim($this->abnDescription) === '') {
            $this->addError('abnormal', 'Nama object dan detail problem wajib diisi.');
            return;
        }
        if (! $this->abnConfirmed) {
            $this->addError('abnormal', 'Konfirmasi validitas laporan terlebih dahulu.');
            return;
        }

        try {
            $reporter = collect($this->operators)->first();
            if (! $reporter || trim((string) ($reporter['nik'] ?? '')) === '') {
                $this->addError('abnormal', 'Operator pelapor belum dipilih pada form Gemba.');
                return;
            }
            $detail = $service->createFromGemba([
                'noabn' => $this->abnNoReg,
                'category' => $this->abnCategory,
                'area' => (string) ($this->process['id_seq'] ?? ''),
                'nm_object' => $this->abnObjectName,
                'found_by' => (string) ($reporter['nik'] ?? ''),
                'ref_lot' => $this->noLot,
                'detail_problem' => $this->abnDescription,
            ]);
            $detail['found_by_name'] = (string) ($reporter['name'] ?? $reporter['nik']);

            $telegram->send(
                $formatter->formatGemba($detail, now('Asia/Jakarta')->format('Y-m-d H:i:s')),
                'abnormal_report',
                'abnormal_report_mtc'
            );

            $this->showAbnormalReport = false;
            $this->flash('Abnormal Report berhasil dilaporkan ke Maintenance.', 'success');
        } catch (\Throwable $e) {
            $this->addError('abnormal', $e->getMessage());
        }
    }

    public function canStart(): bool
    {
        return $this->context !== []
            && ! $this->isProcessStarted()
            && ! $this->isProcessFinished()
            && ! $this->isProcessHeld();
    }

    public function canAkakami(): bool
    {
        return $this->context !== []
            && $this->isProcessStarted()
            && ! $this->isProcessFinished()
            && ! $this->isProcessHeld();
    }

    public function canHold(): bool
    {
        return $this->canAkakami();
    }

    public function canFinish(): bool
    {
        return $this->context !== []
            && $this->isProcessStarted()
            && ! $this->isProcessFinished()
            && ! $this->isProcessHeld();
    }

    public function canReportAbnormal(): bool
    {
        return $this->context !== []
            && ! $this->isProcessFinished();
    }

    private function isProcessStarted(): bool
    {
        return trim((string) ($this->state['start_time'] ?? '')) !== '';
    }

    private function isProcessFinished(): bool
    {
        $finishTime = trim((string) ($this->state['finish_time'] ?? ''));
        $finish = strtolower(trim((string) ($this->state['finish'] ?? '')));
        return $finishTime !== '' || in_array($finish, ['yes', '1', 'true', 'finish', 'finished'], true);
    }

    private function isProcessHeld(): bool
    {
        $hold = strtolower(trim((string) ($this->state['hold'] ?? '')));
        return in_array($hold, ['yes', '1', 'true', 'hold', 'held'], true);
    }

    public function startProcess(GembaInputService $service): void
    {
        $this->resetValidation();
        $result = $service->start($this->context, $this->operators, trim($this->trayNo), trim($this->traySmall), trim($this->machineNo) ?: null);
        if (! ($result['ok'] ?? false)) {
            $this->addError('form', (string) ($result['message'] ?? 'Proses gagal di-start.'));
            return;
        }
        $this->refreshState($service);
        $this->flash((string) ($result['message'] ?? 'Proses berhasil di-start.'), 'success');
    }

    public function finishProcess(GembaInputService $service): void
    {
        $result = $service->finish($this->context);
        if (! ($result['ok'] ?? false)) {
            $this->addError('form', (string) ($result['message'] ?? 'Proses gagal di-finish.'));
            return;
        }

        $message = (string) ($result['message'] ?? 'Proses berhasil di-finish.');
        $this->clearForm();
        $this->flash($message, 'success');
    }

    public function holdProcess(GembaInputService $service): void
    {
        $this->toggleAkakami();
    }

    public function clearForm(): void
    {
        $this->noLot = '';
        $this->sequenceNo = 0;
        $this->context = [];
        $this->lotContext = [];
        $this->process = [];
        $this->requiresMachine = false;
        $this->scanInput = '';
        $this->operatorNik = '';
        $this->employeeSearch = '';
        $this->employeeResults = [];
        $this->showEmployeePicker = false;
        $this->employeePickerMessage = '';
        $this->trayNo = '';
        $this->traySmall = '';
        $this->machineNo = '';
        $this->akakamiNoReg = '';
        $this->akakamiCategory = '';
        $this->akakamiRank = '';
        $this->akakamiQty = '';
        $this->akakamiDescription = '';
        $this->akakamiReporterNik = '';
        $this->abnNoReg = '';
        $this->abnCategory = '';
        $this->abnObjectName = '';
        $this->abnDescription = '';
        $this->abnConfirmed = false;
        $this->problemCategories = [];
        $this->problemRanks = [];
        $this->operators = [];
        $this->state = [];
        $this->showStatus = false;
        $this->showAkakami = false;
        $this->showAbnormalReport = false;
        $this->akakamiReporterNik = '';
        $this->flashMessage = '';
        $this->flashType = '';
        $this->resetValidation();
    }

    private function loadContext(GembaInputService $service): void
    {
        $this->problemCategories = $service->problemCategories();
        $this->problemRanks = $service->problemRanks();
        $this->context = $service->processContext($this->noLot, $this->sequenceNo) ?? [];
        if ($this->context === []) {
            $this->lotContext = [];
            $this->process = [];
            $this->requiresMachine = false;
            $this->state = [];
            $this->operators = [];
            return;
        }

        $this->lotContext = $service->lotContext($this->noLot) ?? [];
        $this->process = $this->context['process'] ?? [];
        $this->requiresMachine = $service->requiresMachine($this->process);
        $this->state = $service->currentState($this->noLot, $this->sequenceNo);
        $this->operators = $service->operators($this->noLot, $this->sequenceNo);
        $this->trayNo = $this->state['tray_no'] ?? '';
        $this->traySmall = $this->state['tray_nokecil'] ?? '';
        $this->machineNo = $this->state['no_mesin'] ?? '';
    }

    private function refreshState(GembaInputService $service): void
    {
        $this->state = $service->currentState($this->noLot, $this->sequenceNo);
        $this->trayNo = $this->state['tray_no'] ?? $this->trayNo;
        $this->traySmall = $this->state['tray_nokecil'] ?? $this->traySmall;
        $this->machineNo = $this->state['no_mesin'] ?? $this->machineNo;
        $this->operators = $service->operators($this->noLot, $this->sequenceNo) ?: $this->operators;
    }

    private function flash(string $message, string $type): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
        $this->flashSequence++;
    }

    public function render()
    {
        return view('livewire.gemba-input')->layout('layouts.app');
    }
}
