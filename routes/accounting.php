<?php

use App\Http\Controllers\AccountingPeriodLockController;
use App\Http\Controllers\AccountingReconciliationController;
use App\Http\Controllers\BaganAkunController;
use App\Http\Controllers\ExecutiveDashboardController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\JurnalDetailController;
use App\Http\Controllers\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('executive-dashboard', [ExecutiveDashboardController::class, 'index'])->name('executive-dashboard.index');
    Route::get('bagan-akun/kas-bank', [BaganAkunController::class, 'getKasBank'])->name('bagan-akun.kas-bank');
    Route::put('bagan-akun/mapping', [BaganAkunController::class, 'updateMapping'])->name('bagan-akun.mapping.update');
    Route::get('bagan-akun-report/pdf', [BaganAkunController::class, 'reportPdf'])->name('bagan-akun.report.pdf');
    Route::get('bagan-akun-report/excel', [BaganAkunController::class, 'reportExcel'])->name('bagan-akun.report.excel');
    Route::resource('bagan-akun', BaganAkunController::class)->parameters(['bagan-akun' => 'chart_of_account']);

    Route::get('jurnal-report/pdf', [JurnalController::class, 'reportPdf'])->name('jurnal.report.pdf');
    Route::get('jurnal-report/excel', [JurnalController::class, 'reportExcel'])->name('jurnal.report.excel');
    Route::post('jurnal/{jurnal}/post', [JurnalController::class, 'post'])->name('jurnal.post');
    Route::post('jurnal/{jurnal}/reverse', [JurnalController::class, 'reverse'])->name('jurnal.reverse');
    Route::resource('jurnal', JurnalController::class);
    Route::post('jurnal-detail', [JurnalDetailController::class, 'store'])->name('jurnal-detail.store');
    Route::put('jurnal-detail/{id}', [JurnalDetailController::class, 'update'])->name('jurnal-detail.update');
    Route::delete('jurnal-detail/{id}', [JurnalDetailController::class, 'destroy'])->name('jurnal-detail.destroy');

    Route::get('reconciliation', [AccountingReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('reconciliation/{check}', [AccountingReconciliationController::class, 'show'])->name('reconciliation.show');
    Route::post('period-lock/{period_lock}/unlock', [AccountingPeriodLockController::class, 'unlock'])->name('period-lock.unlock');
    Route::resource('period-lock', AccountingPeriodLockController::class)->only(['index', 'store']);
    Route::resource('tax-rate', TaxRateController::class)->only(['index', 'store', 'update']);

    Route::get('financial-statements/neraca-saldo', [FinancialStatementController::class, 'neracaSaldo'])->name('financial-statements.neraca-saldo');
    Route::get('financial-statements/neraca-saldo/pdf', [FinancialStatementController::class, 'neracaSaldoPdf'])->name('financial-statements.neraca-saldo.pdf');
    Route::get('financial-statements/neraca-saldo/excel', [FinancialStatementController::class, 'neracaSaldoExcel'])->name('financial-statements.neraca-saldo.excel');
    Route::get('financial-statements/buku-besar', [FinancialStatementController::class, 'bukuBesar'])->name('financial-statements.buku-besar');
    Route::get('financial-statements/buku-besar/pdf', [FinancialStatementController::class, 'bukuBesarPdf'])->name('financial-statements.buku-besar.pdf');
    Route::get('financial-statements/buku-besar/excel', [FinancialStatementController::class, 'bukuBesarExcel'])->name('financial-statements.buku-besar.excel');
    Route::get('financial-statements/laba-rugi', [FinancialStatementController::class, 'labaRugi'])->name('financial-statements.laba-rugi');
    Route::get('financial-statements/laba-rugi/pdf', [FinancialStatementController::class, 'labaRugiPdf'])->name('financial-statements.laba-rugi.pdf');
    Route::get('financial-statements/laba-rugi/excel', [FinancialStatementController::class, 'labaRugiExcel'])->name('financial-statements.laba-rugi.excel');
    Route::get('financial-statements/neraca', [FinancialStatementController::class, 'neraca'])->name('financial-statements.neraca');
    Route::get('financial-statements/neraca/pdf', [FinancialStatementController::class, 'neracaPdf'])->name('financial-statements.neraca.pdf');
    Route::get('financial-statements/neraca/excel', [FinancialStatementController::class, 'neracaExcel'])->name('financial-statements.neraca.excel');
});
