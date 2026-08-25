<?php

use App\Livewire\AkakamiApproval;
use App\Livewire\AkakamiApprovalReview;
use App\Livewire\AkakamiDashboard;
use App\Livewire\Dashboard;
use App\Http\Controllers\AkakamiExportController;
use App\Http\Controllers\AkakamiPrintController;
use App\Http\Controllers\AbnormalReportPrintController;
use App\Http\Controllers\ExportExcelController;
use App\Http\Controllers\LastSequenceExportController;
use App\Http\Controllers\IncomingTraceExportController;
use App\Http\Controllers\RealtimeProductionExportController;
use App\Http\Controllers\ProductivityProcessExportController;
use App\Http\Controllers\ProcessProductivityExportController;
use App\Http\Controllers\ManpowerCertificateController;
use App\Http\Controllers\RankAImageController;
use App\Http\Controllers\RankAInstructionImageController;
use App\Http\Controllers\RankAInstructionPrintController;
use App\Livewire\MonthlyDashboard;
use App\Livewire\LastSequence;
use App\Livewire\IncomingTrace;
use App\Livewire\RealtimeProduction;
use App\Livewire\ManpowerProductivity;
use App\Livewire\ManPowerControl;
use App\Livewire\SusunRoutingProses;
use App\Livewire\PairingRoutingSrp;
use App\Livewire\AdjustCycleTime;
use App\Livewire\UploadWiProses;
use App\Livewire\RoleAkses;
use App\Livewire\UserControl;
use App\Livewire\UserQrCode;
use App\Livewire\AddItemSatuan;
use App\Livewire\PttnMaker;
use App\Livewire\PairingPttnToSrp;
use App\Livewire\InputPttnOmes;
use App\Http\Controllers\DocumentProcessPdfController;
use App\Http\Controllers\UserQrCodePrintController;
use App\Http\Controllers\PttnImageController;
use App\Livewire\ProcessProductivity;
use App\Livewire\RankAApproval;
use App\Livewire\RankAInstructionSheet;
use App\Livewire\AbnormalMtcApproval;
use App\Livewire\AbnormalReport;
use App\Livewire\CarbTemperMonitoring;
use App\Livewire\Login;
use App\Livewire\ChangePassword;
use App\Livewire\MenuAuthorization;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureMenuAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware(['auth', EnsurePasswordChanged::class, EnsureMenuAccess::class])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/akakami-qaqc', AkakamiApproval::class)->name('akakami.qaqc');
    Route::get('/prv-akakami', AkakamiApprovalReview::class)->name('akakami.approval');
    Route::get('/prv-rank-a', RankAApproval::class)->name('ranka.approval');
    Route::get('/prv-abnormal-mtc', AbnormalMtcApproval::class)->name('abnormal.mtc.approval');
    Route::get('/abnormal-report', AbnormalReport::class)->name('abnormal.report');
    Route::get('/prv-rank-a/{noRank}/image', RankAImageController::class)->name('rank.a.image');
    Route::get('/improvement-instruction/rank-a', RankAInstructionSheet::class)->name('instruction.rank.a');
    Route::get('/improvement-instruction/rank-a/{noRank}/image', RankAInstructionImageController::class)->name('rank.a.instruction.image');
    Route::get('/improvement-instruction/rank-a/{noRank}/print', RankAInstructionPrintController::class)->name('rank.a.instruction.print');
    Route::get('/akakami-qaqc/export', AkakamiExportController::class)->name('akakami.export');
    Route::get('/akakami-qaqc/{noflpp}/print', AkakamiPrintController::class)->name('akakami.print');
    Route::get('/abnormal-report/{noAbn}/print', AbnormalReportPrintController::class)->name('abnormal.report.print');
    Route::get('/monthly-report', MonthlyDashboard::class)->name('monthly.report');
    Route::get('/last-sequence', LastSequence::class)->name('last.sequence');
    Route::get('/last-sequence/export', LastSequenceExportController::class)->name('last.sequence.export');
    Route::get('/incoming-trace', IncomingTrace::class)->name('incoming.trace');
    Route::get('/incoming-trace/export', IncomingTraceExportController::class)->name('incoming.trace.export');
    Route::get('/realtime-production', RealtimeProduction::class)->name('realtime.production');
    Route::get('/realtime-production/export', RealtimeProductionExportController::class)->name('realtime.production.export');
    Route::get('/productivity-process', ProcessProductivity::class)->name('productivity.process');
    Route::get('/manpower-productivity', ManpowerProductivity::class)->name('manpower.productivity');
    Route::get('/man-power/control', ManPowerControl::class)->name('manpower.control');
    Route::get('/routing/susun-routing-proses', SusunRoutingProses::class)->name('routing.process');
    Route::get('/routing/pairing-routing-srp', PairingRoutingSrp::class)->name('routing.pairing.srp');
    Route::get('/cycle-time/adjust', AdjustCycleTime::class)->name('cycle-time.adjust');
    Route::get('/document-process/upload-wi', UploadWiProses::class)->name('document.process.upload');
    Route::get('/user-management/role-akses', RoleAkses::class)->name('user-management.role-akses');
    Route::get('/user-management/user-control', UserControl::class)->name('user-management.user-control');
    Route::get('/user-management/qr-code', UserQrCode::class)->name('user-management.qr-code');
    Route::get('/user-management/menu-authorization', MenuAuthorization::class)->name('user-management.menu-authorization');
    Route::get('/user-management/qr-code/print', UserQrCodePrintController::class)->name('user-management.qr-code.print');
    Route::get('/pttn-engineering/add-item-satuan', AddItemSatuan::class)->name('pttn.add-item-satuan');
    Route::get('/pttn-engineering/pttn-maker', PttnMaker::class)->name('pttn.maker');
    Route::get('/pttn-engineering/pttn-maker/{id}/image', PttnImageController::class)->name('pttn.image');
    Route::get('/pttn-engineering/pairing-pttn-to-srp', PairingPttnToSrp::class)->name('pttn.pairing.srp');
    Route::get('/pttn-operator-mesin/input-pttn-omes', InputPttnOmes::class)->name('pttn.omes.input');
    Route::get('/document-process/pdf/{id}', DocumentProcessPdfController::class)->name('document.process.pdf');
    Route::get('/productivity-process/export', ProcessProductivityExportController::class)->name('productivity.process.export');
    Route::get('/manpower-productivity/export', ProductivityProcessExportController::class)->name('manpower.productivity.export');
    Route::get('/manpower-productivity/certificate', ManpowerCertificateController::class)->name('manpower.productivity.certificate');
    Route::get('/carb-temper', CarbTemperMonitoring::class)->name('carb.temper');
    Route::get('/akakami-dashboard', AkakamiDashboard::class)->name('akakami.dashboard');
    Route::get('/export-excel', ExportExcelController::class)->name('export.excel');
});

Route::middleware('auth')->group(function () {
    Route::get('/change-password', ChangePassword::class)->name('password.change');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
