<?php

use App\Http\Controllers\BahanController;
use App\Http\Controllers\DebitController;
use App\Http\Controllers\KategoriBahanController;
use App\Http\Controllers\KreditController;
use App\Http\Controllers\PesananPembelianController;
use App\Http\Controllers\PesananPembelianDetailController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\RequestDetailController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TipePembebananController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('supplier/data-table', [SupplierController::class, 'dataTable'])->name('supplier.dataTable');
    Route::get('supplier-report/pdf', [SupplierController::class, 'reportPdf'])->name('supplier.report.pdf');
    Route::get('supplier-report/excel', [SupplierController::class, 'reportExcel'])->name('supplier.report.excel');
    Route::resource('supplier', SupplierController::class)->except(['show']);
    Route::resource('bahan', BahanController::class)->only(['index', 'show', 'edit', 'update']);

    Route::get('request-report/pdf', [MaterialRequestController::class, 'reportPdf'])->name('request.report.pdf');
    Route::get('request-report/excel', [MaterialRequestController::class, 'reportExcel'])->name('request.report.excel');
    Route::resource('request', MaterialRequestController::class);
    Route::get('request/{request}/approve', [MaterialRequestController::class, 'approveForm'])->name('request.approveForm');
    Route::post('request/{request}/approve', [MaterialRequestController::class, 'processApprove'])->name('request.processApprove');
    Route::resource('requestdetail', RequestDetailController::class)->except(['create', 'edit']);

    Route::get('pembeliandetail/{no_po}', [PesananPembelianDetailController::class, 'index'])->name('pembeliandetail.index');
    Route::post('pembeliandetail/{no_po}', [PesananPembelianDetailController::class, 'store'])->name('pembeliandetail.store');
    Route::put('pembeliandetail/{pembeliandetail}', [PesananPembelianDetailController::class, 'update'])->name('pembeliandetail.update');
    Route::delete('pembeliandetail/{pembeliandetail}', [PesananPembelianDetailController::class, 'destroy'])->name('pembeliandetail.destroy');

    Route::get('pembelian', [PesananPembelianController::class, 'index'])->name('pembelian.index');
    Route::get('pembelian-report/pdf', [PesananPembelianController::class, 'reportPdf'])->name('pembelian.report.pdf');
    Route::get('pembelian-report/excel', [PesananPembelianController::class, 'reportExcel'])->name('pembelian.report.excel');
    Route::post('pembelian', [PesananPembelianController::class, 'store'])->name('pembelian.store');
    Route::get('pembelian/{no_po}', [PesananPembelianController::class, 'show'])->name('pembelian.show');
    Route::put('pembelian/{no_po}', [PesananPembelianController::class, 'update'])->name('pembelian.update');
    Route::delete('pembelian/{no_po}', [PesananPembelianController::class, 'destroy'])->name('pembelian.destroy');
    Route::patch('pembelian/{no_po}/so-term', [PesananPembelianController::class, 'updateSoTerm'])->name('pembelian.update-so-term');
    Route::patch('pembelian/{no_po}/financials', [PesananPembelianController::class, 'updateFinancials'])->name('pembelian.update-financials');
    Route::patch('pembelian/{no_po}/close', [PesananPembelianController::class, 'close'])->name('pembelian.close');
    Route::post('pembelian/{no_po}/cetak', [PesananPembelianController::class, 'cetak'])->name('pembelian.cetak');

    Route::resource('kredit', KreditController::class)->except(['create', 'edit']);
    Route::resource('debit', DebitController::class)->except(['create', 'edit']);
    Route::get('tipe-pembebanan-report/pdf', [TipePembebananController::class, 'reportPdf'])->name('tipe-pembebanan.report.pdf');
    Route::get('tipe-pembebanan-report/excel', [TipePembebananController::class, 'reportExcel'])->name('tipe-pembebanan.report.excel');
    Route::resource('tipe-pembebanan', TipePembebananController::class);
    Route::resource('kategori-bahan', KategoriBahanController::class);
});
