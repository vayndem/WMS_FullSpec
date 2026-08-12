<?php

use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\ServiceBapController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServicePurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('asetperusahaan-report/pdf', [AssetController::class, 'reportPdf'])->name('assets.report.pdf');
    Route::post('asetperusahaan/{asset}/depreciate', [AssetController::class, 'depreciate'])->name('assets.depreciate');
    Route::post('asetperusahaan/{asset}/dispose', [AssetController::class, 'dispose'])->name('assets.dispose');
    Route::resource('asetperusahaan', AssetController::class)
        ->parameters(['asetperusahaan' => 'asset'])
        ->names('assets')
        ->except(['destroy']);
    Route::resource('asset-categories', AssetCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('service-purchases-report/pdf', [ServicePurchaseController::class, 'reportPdf'])->name('service-purchases.report.pdf');
    Route::resource('service-purchases', ServicePurchaseController::class);
    Route::get('service-baps-report/pdf', [ServiceBapController::class, 'reportPdf'])->name('service-baps.report.pdf');
    Route::post('service-baps/{service_bap}/cancel', [ServiceBapController::class, 'cancel'])->name('service-baps.cancel');
    Route::resource('service-baps', ServiceBapController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('service-categories', ServiceCategoryController::class)->only(['index', 'update']);
});
