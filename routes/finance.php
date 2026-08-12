<?php

use App\Http\Controllers\InvoiceLpbController;
use App\Http\Controllers\InvoicePaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('invoice-lpb/lpb-detail/{id_lpb}', [InvoiceLpbController::class, 'getLpbDetail'])->name('invoice-lpb.get-lpb-detail');
    Route::get('invoice-lpb-report/pdf', [InvoiceLpbController::class, 'reportPdf'])->name('invoice-lpb.report.pdf');
    Route::resource('invoice-lpb', InvoiceLpbController::class);
    Route::post('invoice-payments', [InvoicePaymentController::class, 'store'])->name('invoice-payments.store');
    Route::delete('invoice-payments/{payment}', [InvoicePaymentController::class, 'destroy'])->name('invoice-payments.destroy');
});
