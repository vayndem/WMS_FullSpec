<?php

use App\Http\Controllers\FakturPembelianController;
use App\Http\Controllers\PembayaranFakturController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('faktur-pembelian/lpb-detail/{id_lpb}', [FakturPembelianController::class, 'getLpbDetail'])->name('faktur-pembelian.get-lpb-detail');
    Route::get('faktur-pembelian-report/pdf', [FakturPembelianController::class, 'reportPdf'])->name('faktur-pembelian.report.pdf');
    Route::post('faktur-pembelian/{id}/approve', [FakturPembelianController::class, 'approve'])->name('faktur-pembelian.approve');
    Route::resource('faktur-pembelian', FakturPembelianController::class);
    Route::get('pembayaran-faktur/available-advances/{supplier}', [PembayaranFakturController::class, 'availableAdvances'])->name('pembayaran-faktur.available-advances');
    Route::post('pembayaran-faktur', [PembayaranFakturController::class, 'store'])->name('pembayaran-faktur.store');
    Route::delete('pembayaran-faktur/{payment}', [PembayaranFakturController::class, 'destroy'])->name('pembayaran-faktur.destroy');
});
