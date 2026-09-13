<?php

namespace App\Livewire;

use App\Services\AbnormalReportService;
use App\Services\AbnormalReportTelegramMessage;
use App\Services\AkakamiTelegramMessage;
use App\Services\DocumentProcessService;
use App\Services\GembaInputService;
use App\Services\LotVerifikatorService;
use App\Services\PttnOmesService;
use App\Services\TelegramService;
use Livewire\Component;

class GembaInput extends Component
{
    public string $noLot = '';
    public string $scanInput = '';
    public int $sequenceNo = 0;
    public string $processMode = 'berpindah';
    public string $selectedSequence = '';
    public array $sequenceList = [];
    public array $context = [];
    public array $lotContext = [];
    public array $process = [];
    public array $state = [];
    public array $operators = [];
    public array $machines = [];
    public array $problemCategories = [];
    public array $problemRanks = [];
    public bool $requiresMachine = false;
    public bool $hasPttn = false;
    public bool $hasWorkInstruction = false;
    public int $wiDocumentId = 0;

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
        $this->clearForm();

        $this->machines = $service->machines();
        $this->problemCategories = $service->problemCategories();
        $this->problemRanks = $service->problemRanks();
        $this->sequenceList = $service->sequences();

        $savedMode = $service->userGembaMode($this->currentUsername(), $this->currentIdUser());
        $this->processMode = $savedMode['mode'] ?? 'berpindah';
        $this->selectedSequence = (string) ($savedMode['id_seq'] ?: ($savedMode['nm_sequence'] ?? ''));

        $lotToLoad = trim((string) ($noLot ?? ''));
        $seqToLoad = (int) ($sequenceNo ?? 0);

        if ($lotToLoad !== '' && $seqToLoad > 0) {
            $mode = $this->processMode;
            $selectedSeq = $this->selectedSequence;
            $seqList = $this->sequenceList;
            $machines = $this->machines;

            // Bersihkan seluruh record form terlebih dahulu
            $this->clearForm();

            $this->processMode = $mode;
            $this->selectedSequence = $selectedSeq;
            $this->sequenceList = $seqList;
            $this->machines = $machines;
            $this->noLot = $lotToLoad;
            $this->sequenceNo = $seqToLoad;

            // Cek keberadaan lot di tbllotdaily dan status delivery di tbl_seq_all
            $lotAvailability = $service->checkLotAvailabilityAndDelivery($lotToLoad);
            if (! ($lotAvailability['ok'] ?? false)) {
                $message = (string) ($lotAvailability['message'] ?? 'Lot tersebut tidak ada');
                $this->addError('noLot', $message);
                $this->flash($message, 'warning');
                return;
            }

            $result = app(LotVerifikatorService::class)->verify($lotToLoad);
            if (! ($result['ok'] ?? false)) {
                $message = (string) ($result['message'] ?? 'No Lot tidak dapat diproses.');
                $this->clearForm();
                $this->processMode = $mode;
                $this->selectedSequence = $selectedSeq;
                $this->sequenceList = $seqList;
                $this->machines = $machines;
                $this->noLot = $lotToLoad;
                $this->addError('noLot', $message);
                $this->flash($message, 'warning');
                return;
            }

            $clickable = collect($result['processes'] ?? [])->first(fn (array $item): bool => (bool) ($item['clickable'] ?? false));
            if ($clickable === null) {
                $message = 'Lot sudah selesai diproses. Tidak ada proses aktif yang dapat diinput.';
                $this->clearForm();
                $this->processMode = $mode;
                $this->selectedSequence = $selectedSeq;
                $this->sequenceList = $seqList;
                $this->machines = $machines;
                $this->noLot = $lotToLoad;
                $this->flash($message, 'warning');
                return;
            }

            $this->sequenceNo = (int) $clickable['sequence_no'];

            if ($this->processMode === 'tetap') {
                if (trim($this->selectedSequence) === '') {
                    $this->clearForm();
                    $this->processMode = $mode;
                    $this->selectedSequence = $selectedSeq;
                    $this->sequenceList = $seqList;
                    $this->machines = $machines;
                    $this->addError('selectedSequence', 'Pilih nama proses terlebih dahulu pada Mode Posisi Tetap.');
                    $this->flash('Silakan pilih nama proses pada Mode Posisi Tetap terlebih dahulu.', 'warning');
                    return;
                }

                $selectedItem = collect($this->sequenceList)->first(function (array $item): bool {
                    return (string) ($item['id_seq'] ?? '') === $this->selectedSequence
                        || strcasecmp((string) ($item['nm_sequence'] ?? ''), $this->selectedSequence) === 0;
                });

                $process = $clickable;

                $expectedSeqId = strtolower(trim((string) ($selectedItem['id_seq'] ?? $this->selectedSequence)));
                $expectedSeqName = strtolower(trim((string) ($selectedItem['nm_sequence'] ?? $this->selectedSequence)));

                $lotSeqId = strtolower(trim((string) ($process['id_seq'] ?? '')));
                $lotSeqName = strtolower(trim((string) ($process['name'] ?? '')));

                $isMatch = $process !== [] && (
                    ($expectedSeqId !== '' && $expectedSeqId === $lotSeqId) ||
                    ($expectedSeqName !== '' && $expectedSeqName === $lotSeqName)
                );

                if (! $isMatch) {
                    $this->clearForm();
                    $this->processMode = $mode;
                    $this->selectedSequence = $selectedSeq;
                    $this->sequenceList = $seqList;
                    $this->machines = $machines;
                    $this->flash('Anda Sedang Jumping Process', 'warning');
                    $this->dispatch('jumping-process-alert', message: 'Anda Sedang Jumping Process');
                    return;
                }
            }

            $this->loadContext($service);
            $this->flash('Konteks lot dan proses aktif berhasil dimuat.', 'success');
            $this->dispatch('stopwatch-sync');
        }
    }

    public function updatedProcessMode(string $value): void
    {
        $this->processMode = in_array(strtolower(trim($value)), ['tetap', 'berpindah'], true) ? strtolower(trim($value)) : 'berpindah';
        $this->saveCurrentMode(app(GembaInputService::class));
    }

    public function updatedSelectedSequence(string $value): void
    {
        $this->selectedSequence = trim($value);
        $this->saveCurrentMode(app(GembaInputService::class));
    }

    public function saveCurrentMode(GembaInputService $service): void
    {
        $username = $this->currentUsername();
        $idUser = $this->currentIdUser();
        if ($username === '' && $idUser === '') {
            return;
        }

        $selectedItem = collect($this->sequenceList)->first(function (array $item): bool {
            return (string) ($item['id_seq'] ?? '') === $this->selectedSequence
                || strcasecmp((string) ($item['nm_sequence'] ?? ''), $this->selectedSequence) === 0;
        });

        $idSeq = $selectedItem['id_seq'] ?? $this->selectedSequence;
        $nmSequence = $selectedItem['nm_sequence'] ?? null;

        $service->saveUserGembaMode($username, $idUser, $this->processMode, $idSeq, $nmSequence);
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
        $lotToLoad = trim($this->noLot);

        // Keep preferences and master lookups
        $mode = $this->processMode;
        $selectedSeq = $this->selectedSequence;
        $seqList = $this->sequenceList;
        $machines = $this->machines;

        // Bersihkan seluruh record di form gemba input dulu
        $this->clearForm();

        $this->processMode = $mode;
        $this->selectedSequence = $selectedSeq;
        $this->sequenceList = $seqList;
        $this->machines = $machines;
        $this->noLot = $lotToLoad;

        if ($lotToLoad === '') {
            $this->addError('noLot', 'No Lot wajib diisi.');
            return;
        }

        // Cek keberadaan lot di tbllotdaily dan status delivery di tbl_seq_all
        $lotAvailability = $service->checkLotAvailabilityAndDelivery($lotToLoad);
        if (! ($lotAvailability['ok'] ?? false)) {
            $message = (string) ($lotAvailability['message'] ?? 'Lot tersebut tidak ada');
            $this->addError('noLot', $message);
            $this->flash($message, 'warning');
            return;
        }

        if ($this->processMode === 'tetap' && trim($this->selectedSequence) === '') {
            $this->addError('selectedSequence', 'Pilih nama proses terlebih dahulu pada Mode Posisi Tetap.');
            $this->flash('Silakan pilih nama proses pada Mode Posisi Tetap terlebih dahulu.', 'warning');
            return;
        }

        $result = $verifikator->verify($lotToLoad);
        if (! ($result['ok'] ?? false)) {
            $this->addError('noLot', (string) ($result['message'] ?? 'No Lot tidak ditemukan.'));
            return;
        }

        $clickable = collect($result['processes'] ?? [])->first(fn (array $item): bool => (bool) ($item['clickable'] ?? false));

        if ($clickable === null) {
            $message = 'Lot sudah selesai diproses. Tidak ada proses aktif yang dapat diinput.';
            $this->clearForm();
            $this->processMode = $mode;
            $this->selectedSequence = $selectedSeq;
            $this->sequenceList = $seqList;
            $this->machines = $machines;
            $this->noLot = $lotToLoad;
            $this->flash($message, 'warning');
            return;
        }

        if ($this->processMode === 'tetap') {
            $selectedItem = collect($this->sequenceList)->first(function (array $item): bool {
                return (string) ($item['id_seq'] ?? '') === $this->selectedSequence
                    || strcasecmp((string) ($item['nm_sequence'] ?? ''), $this->selectedSequence) === 0;
            });

            $expectedSeqId = strtolower(trim((string) ($selectedItem['id_seq'] ?? $this->selectedSequence)));
            $expectedSeqName = strtolower(trim((string) ($selectedItem['nm_sequence'] ?? $this->selectedSequence)));

            $lotSeqId = strtolower(trim((string) ($clickable['id_seq'] ?? '')));
            $lotSeqName = strtolower(trim((string) ($clickable['name'] ?? '')));

            $isMatch = (
                ($expectedSeqId !== '' && $expectedSeqId === $lotSeqId) ||
                ($expectedSeqName !== '' && $expectedSeqName === $lotSeqName)
            );

            if (! $isMatch) {
                $this->clearForm();
                $this->processMode = $mode;
                $this->selectedSequence = $selectedSeq;
                $this->sequenceList = $seqList;
                $this->machines = $machines;
                $this->flash('Anda Sedang Jumping Process', 'warning');
                $this->dispatch('jumping-process-alert', message: 'Anda Sedang Jumping Process');
                return;
            }
        }

        $this->sequenceNo = (int) $clickable['sequence_no'];
        $this->loadContext($service, $result);
        $this->flash('Konteks lot dan proses aktif berhasil dimuat.', 'success');
        $this->dispatch('stopwatch-sync');
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

    public function addCurrentOperator(GembaInputService $service): void
    {
        $sessionNik = $this->sessionNik();
        if ($sessionNik === '') {
            $message = 'NIK pengguna login tidak tersedia pada sesi.';
            $this->addError('operatorNik', $message);
            $this->flash('Tambahkan Saya ditolak. '.$message, 'warning');
            return;
        }

        $operatorCountBefore = count($this->operators);
        $this->operatorNik = $sessionNik;
        $this->addOperator($service);

        if (count($this->operators) === $operatorCountBefore) {
            $reason = (string) ($this->getErrorBag()->first('operatorNik') ?: 'Operator login belum memiliki skill untuk proses ini.');
            $this->flash('Tambahkan Saya ditolak. '.$reason, 'warning');
            return;
        }

        $this->flash('Operator '.$sessionNik.' berhasil ditambahkan.', 'success');
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
            && $this->isProcessStarted()
            && ! $this->isProcessFinished();
    }

    public function canInputPttn(): bool
    {
        return $this->context !== [] && $this->hasPttn;
    }

    public function openPttnInput(PttnOmesService $service): void
    {
        if (! $this->canInputPttn()) {
            $this->flash('PTTN belum terpasang pada proses aktif ini.', 'warning');
            return;
        }

        $sessionNik = $this->sessionNik();
        if (! $service->operatorIsCompetent($sessionNik, (string) ($this->process['id_seq'] ?? ''))) {
            $this->flash('Akses Input PTTN ditolak. Operator yang login tidak kompeten untuk proses ini.', 'warning');
            return;
        }

        $this->redirectRoute('pttn.omes.input', [
            'noLot' => $this->noLot,
            'sequenceNo' => $this->sequenceNo,
        ]);
    }

    private function isFilledTime(?string $time): bool
    {
        if ($time === null) {
            return false;
        }
        $t = trim($time);
        if ($t === '' || $t === '-' || $t === '0' || str_starts_with($t, '0000-00-00')) {
            return false;
        }
        return true;
    }

    public function stopwatchStartTime(): string
    {
        $startTime = trim((string) ($this->state['start_time'] ?? ''));
        if (! $this->isFilledTime($startTime)) {
            return '';
        }
        try {
            return \Illuminate\Support\Carbon::parse($startTime)->toIso8601String();
        } catch (\Throwable) {
            return $startTime;
        }
    }

    public function stopwatchFinishTime(): string
    {
        $finishTime = trim((string) ($this->state['finish_time'] ?? ''));
        if (! $this->isFilledTime($finishTime)) {
            return '';
        }
        try {
            return \Illuminate\Support\Carbon::parse($finishTime)->toIso8601String();
        } catch (\Throwable) {
            return $finishTime;
        }
    }

    public function stopwatchStartMs(): ?int
    {
        $startTime = trim((string) ($this->state['start_time'] ?? ''));
        if (! $this->isFilledTime($startTime)) {
            return null;
        }
        try {
            return (int) (\Illuminate\Support\Carbon::parse($startTime)->getTimestamp() * 1000);
        } catch (\Throwable) {
            $time = strtotime($startTime);
            return $time ? ($time * 1000) : null;
        }
    }

    public function stopwatchFinishMs(): ?int
    {
        $finishTime = trim((string) ($this->state['finish_time'] ?? ''));
        if (! $this->isFilledTime($finishTime)) {
            return null;
        }
        try {
            return (int) (\Illuminate\Support\Carbon::parse($finishTime)->getTimestamp() * 1000);
        } catch (\Throwable) {
            $time = strtotime($finishTime);
            return $time ? ($time * 1000) : null;
        }
    }

    public function isProcessStarted(): bool
    {
        return $this->isFilledTime($this->state['start_time'] ?? null);
    }

    public function isProcessFinished(): bool
    {
        $finishTime = $this->state['finish_time'] ?? null;
        $finish = strtolower(trim((string) ($this->state['finish'] ?? '')));
        return $this->isFilledTime($finishTime)
            || in_array($finish, ['yes', '1', 'true', 'finish', 'finished'], true);
    }

    public function isProcessHeld(): bool
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
        $this->dispatch('stopwatch-sync');
    }

    public function finishProcess(GembaInputService $service): void
    {
        $result = $service->finish($this->context);
        if (! ($result['ok'] ?? false)) {
            $this->addError('form', (string) ($result['message'] ?? 'Proses gagal di-finish.'));
            return;
        }

        $message = (string) ($result['message'] ?? 'Proses berhasil di-finish.');
        $this->dispatch('stopwatch-sync');
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
        $this->hasPttn = false;
        $this->hasWorkInstruction = false;
        $this->wiDocumentId = 0;
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

    private function loadContext(GembaInputService $service, ?array $verifyResult = null): void
    {
        $this->problemCategories = $service->problemCategories();
        $this->problemRanks = $service->problemRanks();
        $this->context = $service->processContext($this->noLot, $this->sequenceNo, $verifyResult) ?? [];
        if ($this->context === []) {
            $this->lotContext = [];
            $this->process = [];
            $this->requiresMachine = false;
            $this->hasPttn = false;
            $this->hasWorkInstruction = false;
            $this->wiDocumentId = 0;
            $this->state = [];
            $this->operators = [];
            return;
        }

        $this->lotContext = $service->lotContext($this->noLot) ?? [];
        $this->process = $this->context['process'] ?? [];
        $this->requiresMachine = $service->requiresMachine($this->process);
        $this->machines = $service->machinesForProcess($this->process);
        $this->resolvePttn();
        $this->resolveWorkInstruction();
        $this->state = $service->currentState($this->noLot, $this->sequenceNo);
        $this->operators = $service->operators($this->noLot, $this->sequenceNo);
        $this->trayNo = trim((string) ($this->state['tray_no'] ?? ''));
        $this->traySmall = trim((string) ($this->state['tray_nokecil'] ?? ''));
        $this->machineNo = trim((string) ($this->state['no_mesin'] ?? ''));

        if (($this->trayNo === '' || $this->traySmall === '') && $this->sequenceNo > 1) {
            $inheritedTrays = $service->resolveInitialTrayNumbers($this->noLot, $this->sequenceNo);
            if ($this->trayNo === '' && ($inheritedTrays['tray_no'] ?? '') !== '') {
                $this->trayNo = $inheritedTrays['tray_no'];
            }
            if ($this->traySmall === '' && ($inheritedTrays['tray_nokecil'] ?? '') !== '') {
                $this->traySmall = $inheritedTrays['tray_nokecil'];
            }
        }
    }

    private function resolvePttn(): void
    {
        try {
            $this->hasPttn = app(PttnOmesService::class)->mappingForLotSequence(
                $this->noLot,
                (string) ($this->lotContext['no_srp'] ?? ''),
                $this->sequenceNo
            ) !== null;
        } catch (\Throwable $exception) {
            report($exception);
            $this->hasPttn = false;
        }
    }

    private function resolveWorkInstruction(): void
    {
        $this->hasWorkInstruction = false;
        $this->wiDocumentId = 0;

        $noSrp = (string) ($this->lotContext['no_srp'] ?? '');
        $idRouting = (string) ($this->lotContext['id_routing'] ?? '');
        $sequence = (string) ($this->process['id_seq'] ?? '');
        if ($noSrp === '' || $idRouting === '' || $sequence === '') {
            return;
        }

        try {
            $documents = app(DocumentProcessService::class)->documents($noSrp, $idRouting, $sequence);
        } catch (\Throwable $exception) {
            report($exception);

            return;
        }

        $latest = $documents[0] ?? null;
        if ($latest !== null) {
            $this->hasWorkInstruction = true;
            $this->wiDocumentId = (int) ($latest['id'] ?? 0);
        }
    }

    private function refreshState(GembaInputService $service): void
    {
        $this->state = $service->currentState($this->noLot, $this->sequenceNo);
        $this->trayNo = $this->state['tray_no'] ?? $this->trayNo;
        $this->traySmall = $this->state['tray_nokecil'] ?? $this->traySmall;
        $this->machineNo = $this->state['no_mesin'] ?? $this->machineNo;
        $this->operators = $service->operators($this->noLot, $this->sequenceNo) ?: $this->operators;
    }

    private function sessionNik(): string
    {
        return trim((string) (session('nik') ?: session('id_user') ?: auth()->user()?->id_user ?: ''));
    }

    private function currentUsername(): string
    {
        return trim((string) (auth()->user()?->username ?: session('username') ?: ''));
    }

    private function currentIdUser(): string
    {
        return trim((string) (auth()->user()?->id_user ?: session('id_user') ?: session('nik') ?: ''));
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
