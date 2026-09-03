<?php

use App\Http\Controllers\KategoriAsetController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\ServiceBapController;
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
    Route::get('service-baps-report/pdf', [ServiceBapController::class, 'reportPdf'])->name('service-baps.report.pdf');
    Route::post('service-baps/{service_bap}/cancel', [ServiceBapController::class, 'cancel'])->name('service-baps.cancel');
    Route::resource('service-baps', ServiceBapController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('service-categories', ServiceCategoryController::class)->only(['index', 'update']);
});
