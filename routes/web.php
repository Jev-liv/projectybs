<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OilController;
// use App\Http\Controllers\TimbanganController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\KernelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProcessController;
// use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;


// ─── Guest routes ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    // Route::post('/register', [AuthController::class, 'register']);
});

// ─── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return view('dashboard');
    });

    // ── Dashboard ──────────────────────────────────────────────────────────────
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard')->middleware('permission:view dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ══════════════════════════════════════════════════════════════════════════
    // ── OIL LOSSES ──────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('oil')->name('oil.')->group(function () {
        Route::get('/', [OilController::class, 'index'])
            ->name('index')
            ->middleware('permission:view oil losses');

        Route::get('/olwb', [OilController::class, 'olwbIndex'])
            ->name('olwb')
            ->middleware('permission:view olwb');

        Route::post('/olwb/export', [OilController::class, 'exportOlwb'])
            ->name('olwb.export')
            ->middleware('permission:export olwb reports');

        Route::get('/report', [OilController::class, 'reportIndex'])
            ->name('report')
            ->middleware('permission:view performance oil losses');

        Route::post('/report/export', [OilController::class, 'exportPerformance'])
            ->name('report.export')
            ->middleware('permission:export performance reports oil losses');

        Route::get('/create', [OilController::class, 'create'])
            ->name('create')
            ->middleware('permission:create oil losses');

        Route::post('/', [OilController::class, 'store'])
            ->name('store')
            ->middleware('permission:create oil losses');

        Route::get('/{oilCalculation}', [OilController::class, 'show'])
            ->name('show')
            ->middleware('permission:view oil losses');

        Route::get('/{oilCalculation}/edit', [OilController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:edit oil losses');

        Route::put('/{oilCalculation}', [OilController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit oil losses');

        Route::delete('/{oilCalculation}', [OilController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:delete oil losses');

        Route::get('/records/{oilRecord}/edit', [OilController::class, 'editRecord'])
            ->name('records.edit')
            ->middleware('permission:edit oil losses');

        Route::put('/records/{oilRecord}', [OilController::class, 'updateRecord'])
            ->name('records.update')
            ->middleware('permission:edit oil losses');

        Route::delete('/records/{oilRecord}', [OilController::class, 'destroyRecord'])
            ->name('records.destroy')
            ->middleware('permission:delete oil losses');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── KERNEL LOSSES ────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('kernel')->name('kernel.')->group(function () {
        Route::get('/', [KernelController::class, 'index'])
            ->name('index')
            ->middleware('permission:view kernel losses');

        Route::get('/create', [KernelController::class, 'create'])
            ->name('create')
            ->middleware('permission:create kernel losses');

        Route::post('/', [KernelController::class, 'store'])
            ->name('store')
            ->middleware('permission:create kernel losses');

        Route::get('/{kernelCalculation}/edit', [KernelController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:edit kernel losses');

        Route::put('/{kernelCalculation}', [KernelController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit kernel losses');

        Route::get('/dirt-moist', [KernelController::class, 'dirtMoistIndex'])
            ->name('dirt-moist.index')
            ->middleware('permission:view kernel losses');

        Route::get('/dirt-moist/create', [KernelController::class, 'dirtMoistCreate'])
            ->name('dirt-moist.create')
            ->middleware('permission:create kernel losses');

        Route::post('/dirt-moist', [KernelController::class, 'dirtMoistStore'])
            ->name('dirt-moist.store')
            ->middleware('permission:create kernel losses');

        Route::get('/boiler-softener', function () {
            return redirect()->route('kernel.boiler-softener.boiler.index');
        })
            ->name('boiler-softener.index')
            ->middleware('permission:view kernel losses');

        Route::get('/boiler-softener/boiler', [KernelController::class, 'boilerSoftenerBoilerIndex'])
            ->name('boiler-softener.boiler.index')
            ->middleware('permission:view kernel losses');

        Route::get('/boiler-softener/softener', [KernelController::class, 'boilerSoftenerSoftenerIndex'])
            ->name('boiler-softener.softener.index')
            ->middleware('permission:view kernel losses');

        Route::get('/boiler-softener/create', [KernelController::class, 'boilerSoftenerCreate'])
            ->name('boiler-softener.create')
            ->middleware('permission:create kernel losses');

        Route::post('/boiler-softener', [KernelController::class, 'boilerSoftenerStore'])
            ->name('boiler-softener.store')
            ->middleware('permission:create kernel losses');

        Route::delete('/boiler-softener/{boilerSoftenerCalculation}', [KernelController::class, 'boilerSoftenerDestroy'])
            ->name('boiler-softener.destroy')
            ->middleware('permission:delete kernel losses');

        Route::get('/boiler-softener/rekap', [KernelController::class, 'boilerSoftenerRekap'])
            ->name('boiler-softener.rekap')
            ->middleware('permission:view rekap kernel losses');

        Route::get('/dirt-moist/{dirtMoistCalculation}/edit', [KernelController::class, 'dirtMoistEdit'])
            ->name('dirt-moist.edit')
            ->middleware('permission:edit kernel losses');

        Route::put('/dirt-moist/{dirtMoistCalculation}', [KernelController::class, 'dirtMoistUpdate'])
            ->name('dirt-moist.update')
            ->middleware('permission:edit kernel losses');

        Route::delete('/dirt-moist/{dirtMoistCalculation}', [KernelController::class, 'dirtMoistDestroy'])
            ->name('dirt-moist.destroy')
            ->middleware('permission:delete kernel losses');

        Route::get('/qwt-fibre-press', [KernelController::class, 'qwtIndex'])
            ->name('qwt.index')
            ->middleware('permission:view kernel losses');

        Route::get('/qwt-fibre-press/create', [KernelController::class, 'qwtCreate'])
            ->name('qwt.create')
            ->middleware('permission:create kernel losses');

        Route::post('/qwt-fibre-press', [KernelController::class, 'qwtStore'])
            ->name('qwt.store')
            ->middleware('permission:create kernel losses');

        Route::get('/qwt-fibre-press/{kernelQwt}/edit', [KernelController::class, 'qwtEdit'])
            ->name('qwt.edit')
            ->middleware('permission:edit kernel losses');

        Route::put('/qwt-fibre-press/{kernelQwt}', [KernelController::class, 'qwtUpdate'])
            ->name('qwt.update')
            ->middleware('permission:edit kernel losses');

        Route::delete('/qwt-fibre-press/{kernelQwt}', [KernelController::class, 'qwtDestroy'])
            ->name('qwt.destroy')
            ->middleware('permission:delete kernel losses');

        Route::get('/ripple-mill', [KernelController::class, 'rippleMillIndex'])
            ->name('ripple-mill.index')
            ->middleware('permission:view kernel losses');

        Route::get('/ripple-mill/create', [KernelController::class, 'rippleMillCreate'])
            ->name('ripple-mill.create')
            ->middleware('permission:create kernel losses');

        Route::post('/ripple-mill', [KernelController::class, 'rippleMillStore'])
            ->name('ripple-mill.store')
            ->middleware('permission:create kernel losses');

        Route::get('/ripple-mill/{kernelRippleMill}/edit', [KernelController::class, 'rippleMillEdit'])
            ->name('ripple-mill.edit')
            ->middleware('permission:edit kernel losses');

        Route::put('/ripple-mill/{kernelRippleMill}', [KernelController::class, 'rippleMillUpdate'])
            ->name('ripple-mill.update')
            ->middleware('permission:edit kernel losses');

        Route::delete('/ripple-mill/{kernelRippleMill}', [KernelController::class, 'rippleMillDestroy'])
            ->name('ripple-mill.destroy')
            ->middleware('permission:delete kernel losses');

        Route::get('/destoner', [KernelController::class, 'destonerIndex'])
            ->name('destoner.index')
            ->middleware('permission:view kernel losses');

        Route::get('/destoner/create', [KernelController::class, 'destonerCreate'])
            ->name('destoner.create')
            ->middleware('permission:create kernel losses');

        Route::post('/destoner', [KernelController::class, 'destonerStore'])
            ->name('destoner.store')
            ->middleware('permission:create kernel losses');

        Route::get('/destoner/{kernelDestoner}/edit', [KernelController::class, 'destonerEdit'])
            ->name('destoner.edit')
            ->middleware('permission:edit kernel losses');

        Route::put('/destoner/{kernelDestoner}', [KernelController::class, 'destonerUpdate'])
            ->name('destoner.update')
            ->middleware('permission:edit kernel losses');

        Route::delete('/destoner/{kernelDestoner}', [KernelController::class, 'destonerDestroy'])
            ->name('destoner.destroy')
            ->middleware('permission:delete kernel losses');

        Route::delete('/records/{kernelRecord}', [KernelController::class, 'destroyRecord'])
            ->name('records.destroy')
            ->middleware('permission:delete kernel losses');

        Route::delete('/calculations/{kernelCalculation}', [KernelController::class, 'destroyCalculation'])
            ->name('calculations.destroy')
            ->middleware('permission:delete kernel losses');

        Route::get('/rekap', [KernelController::class, 'rekap'])
            ->name('rekap')
            ->middleware('permission:view rekap kernel losses');

        Route::post('/rekap/export', [KernelController::class, 'exportRekap'])
            ->name('rekap.export')
            ->middleware('permission:view rekap kernel losses');

        Route::get('/performance', [KernelController::class, 'performance'])
            ->name('performance')
            ->middleware('permission:view performance kernel losses');

        Route::post('/performance/export', [KernelController::class, 'exportPerformance'])
            ->name('performance.export')
            ->middleware('permission:view performance kernel losses');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── LAPORAN (REPORTS) ─────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])
            ->name('index')
            ->middleware('permission:view laporan oil losses');

        Route::post('/export', [ReportController::class, 'export'])
            ->name('export')
            ->middleware('permission:export laporan oil losses');

        Route::get('/kernel', [KernelController::class, 'laporan'])
            ->name('kernel')
            ->middleware('permission:view laporan kernel losses');

        Route::post('/kernel/export', [KernelController::class, 'exportLaporan'])
            ->name('kernel.export')
            ->middleware('permission:view laporan kernel losses');

        Route::get('/kernel/dirt-moist', [KernelController::class, 'laporanDirtMoist'])
            ->name('kernel.dirt-moist')
            ->middleware('permission:view laporan kernel losses');

        Route::post('/kernel/dirt-moist/export', [KernelController::class, 'exportLaporanDirtMoist'])
            ->name('kernel.dirt-moist.export')
            ->middleware('permission:view laporan kernel losses');

        Route::get('/kernel/qwt-fibre-press', [KernelController::class, 'laporanQwt'])
            ->name('kernel.qwt')
            ->middleware('permission:view laporan kernel losses');

        Route::post('/kernel/qwt-fibre-press/export', [KernelController::class, 'exportLaporanQwt'])
            ->name('kernel.qwt.export')
            ->middleware('permission:view laporan kernel losses');

        Route::get('/kernel/ripple-mill', [KernelController::class, 'laporanRippleMill'])
            ->name('kernel.ripple-mill')
            ->middleware('permission:view laporan kernel losses');

        Route::post('/kernel/ripple-mill/export', [KernelController::class, 'exportLaporanRippleMill'])
            ->name('kernel.ripple-mill.export')
            ->middleware('permission:view laporan kernel losses');

        Route::get('/kernel/destoner', [KernelController::class, 'laporanDestoner'])
            ->name('kernel.destoner')
            ->middleware('permission:view laporan kernel losses');

        Route::post('/kernel/destoner/export', [KernelController::class, 'exportLaporanDestoner'])
            ->name('kernel.destoner.export')
            ->middleware('permission:view laporan kernel losses');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── PROCESS INFO ──────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('process')->name('process.')->group(function () {
        Route::get('/', [ProcessController::class, 'index'])
            ->name('index');

        Route::get('/spintest', [ProcessController::class, 'spintestIndex'])
            ->name('spintest.index');

        Route::post('/spintest', [ProcessController::class, 'spintestStore'])
            ->name('spintest.store');

        Route::get('/spintest/{spintestProsses}/detail', [ProcessController::class, 'spintestShow'])
            ->name('spintest.show');

        Route::get('/spintest/{spintestProsses}/edit', [ProcessController::class, 'spintestEdit'])
            ->name('spintest.edit');

        Route::put('/spintest/{spintestProsses}', [ProcessController::class, 'spintestUpdate'])
            ->name('spintest.update');

        Route::get('/spintest/{spintestProsses}/machines', [ProcessController::class, 'spintestMachineDetail'])
            ->name('spintest.machines.show');

        Route::get('/spintest/{spintestProsses}/machines/edit', [ProcessController::class, 'spintestEditMachines'])
            ->name('spintest.machines.edit');

        Route::put('/spintest/{spintestProsses}/machines', [ProcessController::class, 'spintestUpdateMachines'])
            ->name('spintest.machines.update');

        Route::get('/performance-sampel-boy', [ProcessController::class, 'performanceSampelBoy'])
            ->name('performance-sampel-boy');

        Route::post('/performance-sampel-boy/export', [ProcessController::class, 'exportPerformanceSampelBoy'])
            ->name('performance-sampel-boy.export');

        Route::get('/rekap-breakdown', [ProcessController::class, 'lapJangkosRekapBreakdown'])
            ->name('rekap-breakdown');

        Route::post('/rekap-breakdown/export', [ProcessController::class, 'lapJangkosRekapBreakdownExport'])
            ->name('rekap-breakdown.export');

        Route::post('/', [ProcessController::class, 'store'])
            ->name('store');

        Route::get('/{kernelProsses}/detail', [ProcessController::class, 'show'])
            ->name('show');

        Route::get('/{kernelProsses}/edit', [ProcessController::class, 'edit'])
            ->name('edit');

        Route::put('/{kernelProsses}', [ProcessController::class, 'updateBoth'])
            ->name('update');

        Route::get('/{kernelProsses}/machines', [ProcessController::class, 'machineDetail'])
            ->name('machines.show');

        Route::get('/{kernelProsses}/machines/edit', [ProcessController::class, 'editMachines'])
            ->name('machines.edit');

        Route::put('/{kernelProsses}/machines', [ProcessController::class, 'updateMachines'])
            ->name('machines.update');

        Route::delete('/{kernelProsses}/machines', [ProcessController::class, 'destroyMachines'])
            ->name('machines.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── ANALISA MOISTURE & SPINTES ────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('analisa-moisture')->name('analisa-moisture.')->group(function () {
        // Halaman inputan Analisa moisture & spintes
        Route::get('/input', [ProcessController::class, 'analisisaMoistureInput'])
            ->name('input');

        Route::post('/input', [ProcessController::class, 'analisisaMoistureStore'])
            ->name('store');

        // Data analisa FFA dan Moisture
        Route::get('/ffa-moisture', [ProcessController::class, 'analisaFfaMoisture'])
            ->name('ffa-moisture');

        Route::get('/ffa-moisture/{ffaMoisture}/edit', [ProcessController::class, 'editAnalisaFfaMoisture'])
            ->name('ffa-moisture.edit');

        Route::put('/ffa-moisture/{ffaMoisture}', [ProcessController::class, 'updateAnalisaFfaMoisture'])
            ->name('ffa-moisture.update')
            ->middleware('role:Super Admin');

        Route::delete('/ffa-moisture/{ffaMoisture}', [ProcessController::class, 'destroyAnalisaFfaMoisture'])
            ->name('ffa-moisture.destroy')
            ->middleware('role:Super Admin');

        Route::post('/ffa-moisture/export', [ProcessController::class, 'exportAnalisaFfaMoisture'])
            ->name('ffa-moisture.export');

        // Data analisa spintest COT
        Route::get('/spintest-cot', [ProcessController::class, 'analisaSpintestCot'])
            ->name('spintest-cot');

        Route::get('/spintest-cot/{spintestCot}/edit', [ProcessController::class, 'editAnalisaSpintestCot'])
            ->name('spintest-cot.edit')
            ->middleware('role:Super Admin');

        Route::put('/spintest-cot/{spintestCot}', [ProcessController::class, 'updateAnalisaSpintestCot'])
            ->name('spintest-cot.update')
            ->middleware('role:Super Admin');

        Route::delete('/spintest-cot/{spintestCot}', [ProcessController::class, 'destroyAnalisaSpintestCot'])
            ->name('spintest-cot.destroy')
            ->middleware('role:Super Admin');

        Route::post('/spintest-cot/export', [ProcessController::class, 'exportAnalisaSpintestCot'])
            ->name('spintest-cot.export');

        // Data analisa spintest Underflow CST
        Route::get('/spintest-underflow-cst', [ProcessController::class, 'analisaSpintestUnderflowCst'])
            ->name('spintest-underflow-cst');

        Route::get('/spintest-underflow-cst/{spintestCst}/edit', [ProcessController::class, 'editAnalisaSpintestUnderflowCst'])
            ->name('spintest-underflow-cst.edit')
            ->middleware('role:Super Admin');

        Route::put('/spintest-underflow-cst/{spintestCst}', [ProcessController::class, 'updateAnalisaSpintestUnderflowCst'])
            ->name('spintest-underflow-cst.update')
            ->middleware('role:Super Admin');

        Route::delete('/spintest-underflow-cst/{spintestCst}', [ProcessController::class, 'destroyAnalisaSpintestUnderflowCst'])
            ->name('spintest-underflow-cst.destroy')
            ->middleware('role:Super Admin');

        Route::post('/spintest-underflow-cst/export', [ProcessController::class, 'exportAnalisaSpintestUnderflowCst'])
            ->name('spintest-underflow-cst.export');

        // Data analisa spintest Feed Decanter
        Route::get('/spintest-feed-decanter', [ProcessController::class, 'analisaSpintestFeedDecanter'])
            ->name('spintest-feed-decanter');

        Route::get('/spintest-feed-decanter/{spintestFeedDecanter}/edit', [ProcessController::class, 'editAnalisaSpintestFeedDecanter'])
            ->name('spintest-feed-decanter.edit')
            ->middleware('role:Super Admin');

        Route::put('/spintest-feed-decanter/{spintestFeedDecanter}', [ProcessController::class, 'updateAnalisaSpintestFeedDecanter'])
            ->name('spintest-feed-decanter.update')
            ->middleware('role:Super Admin');

        Route::delete('/spintest-feed-decanter/{spintestFeedDecanter}', [ProcessController::class, 'destroyAnalisaSpintestFeedDecanter'])
            ->name('spintest-feed-decanter.destroy')
            ->middleware('role:Super Admin');

        Route::post('/spintest-feed-decanter/export', [ProcessController::class, 'exportAnalisaSpintestFeedDecanter'])
            ->name('spintest-feed-decanter.export');

        // Data analisa spintest Light Phase
        Route::get('/spintest-light-phase', [ProcessController::class, 'analisaSpintestLightPhase'])
            ->name('spintest-light-phase');

        Route::get('/spintest-light-phase/{spintestLightPhase}/edit', [ProcessController::class, 'editAnalisaSpintestLightPhase'])
            ->name('spintest-light-phase.edit')
            ->middleware('role:Super Admin');

        Route::put('/spintest-light-phase/{spintestLightPhase}', [ProcessController::class, 'updateAnalisaSpintestLightPhase'])
            ->name('spintest-light-phase.update')
            ->middleware('role:Super Admin');

        Route::delete('/spintest-light-phase/{spintestLightPhase}', [ProcessController::class, 'destroyAnalisaSpintestLightPhase'])
            ->name('spintest-light-phase.destroy')
            ->middleware('role:Super Admin');

        Route::post('/spintest-light-phase/export', [ProcessController::class, 'exportAnalisaSpintestLightPhase'])
            ->name('spintest-light-phase.export');

        // Rekap analisa moisture dan spintest (average per hari)
        Route::get('/rekap', [ProcessController::class, 'rekapAnalisaMoistureSpintest'])
            ->name('rekap');

        Route::post('/rekap/export', [ProcessController::class, 'exportRekapAnalisaMoistureSpintest'])
            ->name('rekap.export');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── LAP JANGKOS ───────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('lap-jangkos')->name('lap-jangkos.')->group(function () {
        // Inputan USB dengan data USB
        Route::get('/input-usb', [ProcessController::class, 'lapJangkosInputUsb'])
            ->name('input-usb');

        Route::post('/input-usb', [ProcessController::class, 'lapJangkosStoreUsb'])
            ->name('store-usb');

        // Data USB
        Route::get('/data-usb', [ProcessController::class, 'lapJangkosDataUsb'])
            ->name('data-usb');

        Route::get('/data-usb/{analisaUsb}/edit', [ProcessController::class, 'lapJangkosEditUsb'])
            ->name('edit-usb')
            ->middleware('role:Super Admin');

        Route::put('/data-usb/{analisaUsb}', [ProcessController::class, 'lapJangkosUpdateUsb'])
            ->name('update-usb')
            ->middleware('role:Super Admin');

        Route::delete('/data-usb/{analisaUsb}', [ProcessController::class, 'lapJangkosDestroyUsb'])
            ->name('destroy-usb')
            ->middleware('role:Super Admin');

        // Rekap USB
        Route::get('/rekap-usb', [ProcessController::class, 'lapJangkosRekapUsb'])
            ->name('rekap-usb');

        Route::get('/rekap-usb/export', [ProcessController::class, 'lapJangkosRekapUsbExport'])
            ->name('rekap-usb.export');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── OIL LOSS FOSS ────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('oil-loss-foss')->name('oil-loss-foss.')->group(function () {
        Route::get('/input', [ProcessController::class, 'oilLossFossInput'])
            ->name('input');

        Route::post('/input', [ProcessController::class, 'oilLossFossStore'])
            ->name('store');

        Route::get('/data', [ProcessController::class, 'oilLossFossData'])
            ->name('data');

        Route::get('/data/{oilFoss}/edit', [ProcessController::class, 'oilLossFossEdit'])
            ->name('edit')
            ->middleware('role:Super Admin');

        Route::put('/data/{oilFoss}', [ProcessController::class, 'oilLossFossUpdate'])
            ->name('update')
            ->middleware('role:Super Admin');

        Route::delete('/data/{oilFoss}', [ProcessController::class, 'oilLossFossDestroy'])
            ->name('destroy')
            ->middleware('role:Super Admin');

        Route::get('/data/export', [ProcessController::class, 'oilLossFossDataExport'])
            ->name('data.export');

        Route::get('/rekap', [ProcessController::class, 'oilLossFossRekap'])
            ->name('rekap');

        Route::get('/rekap/export', [ProcessController::class, 'oilLossFossRekapExport'])
            ->name('rekap.export');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── MANAJEMEN PENGGUNA ────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('index')
            ->middleware('permission:view users');

        Route::get('/create', [UserController::class, 'create'])
            ->name('create')
            ->middleware('permission:create users');

        Route::post('/', [UserController::class, 'store'])
            ->name('store')
            ->middleware('permission:create users');

        Route::get('/{id}/edit', [UserController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:edit users');

        Route::put('/{id}', [UserController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit users');

        Route::delete('/{id}', [UserController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:delete users');

        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])
            ->name('reset-password')
            ->middleware('permission:reset user password');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── ACTIVITY LOGS ─────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])
            ->name('index')
            ->middleware('permission:view user activity log');

        Route::get('/{id}', [ActivityLogController::class, 'show'])
            ->name('show')
            ->middleware('permission:view user activity log');

        Route::post('/cleanup', [ActivityLogController::class, 'cleanup'])
            ->name('cleanup')
            ->middleware('permission:view user activity log');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // ── PERMISSION CONTROL ────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════════
    Route::prefix('permissions')->name('permissions.')->middleware('role:Super Admin')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])
            ->name('index');

        Route::get('/{role}/edit', [PermissionController::class, 'edit'])
            ->name('edit');

        Route::put('/{role}', [PermissionController::class, 'update'])
            ->name('update');
    });

});
