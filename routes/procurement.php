<?php

use App\Http\Controllers\BahanController;
use App\Http\Controllers\DebitController;
use App\Http\Controllers\KategoriBahanController;
use App\Http\Controllers\KreditController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PembelianDetailController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\RequestDetailController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TipePembebananController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('supplier/data-table', [SupplierController::class, 'dataTable'])->name('supplier.dataTable');
    Route::get('supplier-report/pdf', [SupplierController::class, 'reportPdf'])->name('supplier.report.pdf');
    Route::resource('supplier', SupplierController::class)->except(['show']);
    Route::resource('bahan', BahanController::class)->only(['index', 'show', 'edit', 'update']);

    Route::get('request-report/pdf', [MaterialRequestController::class, 'reportPdf'])->name('request.report.pdf');
    Route::resource('request', MaterialRequestController::class);
    Route::get('request/{request}/approve', [MaterialRequestController::class, 'approveForm'])->name('request.approveForm');
    Route::post('request/{request}/approve', [MaterialRequestController::class, 'processApprove'])->name('request.processApprove');
    Route::resource('requestdetail', RequestDetailController::class);

    Route::get('pembeliandetail/{no_po}', [PembelianDetailController::class, 'index'])->name('pembeliandetail.index');
    Route::post('pembeliandetail/{no_po}', [PembelianDetailController::class, 'store'])->name('pembeliandetail.store');
    Route::put('pembeliandetail/{pembeliandetail}', [PembelianDetailController::class, 'update'])->name('pembeliandetail.update');
    Route::delete('pembeliandetail/{pembeliandetail}', [PembelianDetailController::class, 'destroy'])->name('pembeliandetail.destroy');

    Route::get('pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
    Route::get('pembelian-report/pdf', [PembelianController::class, 'reportPdf'])->name('pembelian.report.pdf');
    Route::post('pembelian', [PembelianController::class, 'store'])->name('pembelian.store');
    Route::get('pembelian/{no_po}', [PembelianController::class, 'show'])->name('pembelian.show');
    Route::put('pembelian/{no_po}', [PembelianController::class, 'update'])->name('pembelian.update');
    Route::delete('pembelian/{no_po}', [PembelianController::class, 'destroy'])->name('pembelian.destroy');
    Route::patch('pembelian/{no_po}/so-term', [PembelianController::class, 'updateSoTerm'])->name('pembelian.update-so-term');
    Route::patch('pembelian/{no_po}/financials', [PembelianController::class, 'updateFinancials'])->name('pembelian.update-financials');
    Route::patch('pembelian/{no_po}/close', [PembelianController::class, 'close'])->name('pembelian.close');
    Route::post('pembelian/{no_po}/cetak', [PembelianController::class, 'cetak'])->name('pembelian.cetak');

    Route::resource('kredit', KreditController::class);
    Route::resource('debit', DebitController::class);
    Route::get('tipe-pembebanan-report/pdf', [TipePembebananController::class, 'reportPdf'])->name('tipe-pembebanan.report.pdf');
    Route::resource('tipe-pembebanan', TipePembebananController::class);
    Route::resource('kategori-bahan', KategoriBahanController::class);
});
