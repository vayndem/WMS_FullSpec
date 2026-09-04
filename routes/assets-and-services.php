<?php

use App\Http\Controllers\KategoriAsetController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\PenerimaanJasaController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServicePurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('asetperusahaan-report/pdf', [AsetController::class, 'reportPdf'])->name('aset.report.pdf');
    Route::post('asetperusahaan/{aset}/depreciate', [AsetController::class, 'depreciate'])->name('aset.depreciate');
    Route::post('asetperusahaan/{aset}/dispose', [AsetController::class, 'dispose'])->name('aset.dispose');
    Route::resource('asetperusahaan', AsetController::class)
        ->parameters(['asetperusahaan' => 'aset'])
        ->names('aset')
        ->except(['destroy']);
    Route::resource('kategori-aset', KategoriAsetController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('service-purchases-report/pdf', [ServicePurchaseController::class, 'reportPdf'])->name('service-purchases.report.pdf');
    Route::resource('service-purchases', ServicePurchaseController::class);
    Route::get('penerimaan-jasa-report/pdf', [PenerimaanJasaController::class, 'reportPdf'])->name('penerimaan-jasa.report.pdf');
    Route::post('penerimaan-jasa/{service_bap}/cancel', [PenerimaanJasaController::class, 'cancel'])->name('penerimaan-jasa.cancel');
    Route::resource('penerimaan-jasa', PenerimaanJasaController::class)->parameters(['penerimaan-jasa' => 'service_bap'])->only(['index', 'create', 'store', 'show']);
    Route::resource('service-categories', ServiceCategoryController::class)->only(['index', 'update']);
});
