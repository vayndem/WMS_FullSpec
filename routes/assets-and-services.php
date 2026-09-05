<?php

use App\Http\Controllers\KategoriAsetController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\PenerimaanJasaController;
use App\Http\Controllers\KategoriJasaController;
use App\Http\Controllers\PesananJasaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('asetperusahaan-report/pdf', [AsetController::class, 'reportPdf'])->name('aset.report.pdf');
    Route::post('asetperusahaan/{aset}/depreciate', [AsetController::class, 'depreciate'])->name('aset.depreciate');
    Route::post('asetperusahaan-penyusutan-otomatis', [AsetController::class, 'runAutomaticDepreciation'])->name('aset.depreciate-all');
    Route::post('asetperusahaan/{aset}/dispose', [AsetController::class, 'dispose'])->name('aset.dispose');
    Route::resource('asetperusahaan', AsetController::class)
        ->parameters(['asetperusahaan' => 'aset'])
        ->names('aset')
        ->except(['destroy']);
    Route::resource('kategori-aset', KategoriAsetController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('pesanan-jasa-report/pdf', [PesananJasaController::class, 'reportPdf'])->name('pesanan-jasa.report.pdf');
    Route::resource('pesanan-jasa', PesananJasaController::class)->parameters(['pesanan-jasa' => 'service_purchase']);
    Route::get('penerimaan-jasa-report/pdf', [PenerimaanJasaController::class, 'reportPdf'])->name('penerimaan-jasa.report.pdf');
    Route::post('penerimaan-jasa/{service_bap}/cancel', [PenerimaanJasaController::class, 'cancel'])->name('penerimaan-jasa.cancel');
    Route::resource('penerimaan-jasa', PenerimaanJasaController::class)->parameters(['penerimaan-jasa' => 'service_bap'])->only(['index', 'create', 'store', 'show']);
    Route::resource('kategori-jasa', KategoriJasaController::class)->parameters(['kategori-jasa' => 'service_category'])->only(['index', 'update']);
});
