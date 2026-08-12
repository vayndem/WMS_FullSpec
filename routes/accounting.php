<?php

use App\Http\Controllers\AccountingPeriodLockController;
use App\Http\Controllers\AccountingReconciliationController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\JurnalDetailController;
use App\Http\Controllers\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('chart-of-accounts/kas-bank', [ChartOfAccountController::class, 'getKasBank'])->name('chart-of-accounts.kas-bank');
    Route::put('chart-of-accounts/mapping', [ChartOfAccountController::class, 'updateMapping'])->name('chart-of-accounts.mapping.update');
    Route::get('chart-of-accounts-report/pdf', [ChartOfAccountController::class, 'reportPdf'])->name('chart-of-accounts.report.pdf');
    Route::resource('chart-of-accounts', ChartOfAccountController::class);

    Route::get('jurnal-report/pdf', [JurnalController::class, 'reportPdf'])->name('jurnal.report.pdf');
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
});
