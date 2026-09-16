<?php

use App\Http\Controllers\KategoriAsetController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\PenerimaanJasaController;
use App\Http\Controllers\KategoriJasaController;
use App\Http\Controllers\PesananJasaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('asetperusahaan-report/pdf', [AsetController::class, 'reportPdf'])->name('aset.report.pdf');
    Route::get('asetperusahaan-report/excel', [AsetController::class, 'reportExcel'])->name('aset.report.excel');
    Route::post('asetperusahaan/{aset}/depreciate', [AsetController::class, 'depreciate'])->name('aset.depreciate');
    Route::post('asetperusahaan-penyusutan-otomatis', [AsetController::class, 'runAutomaticDepreciation'])->name('aset.depreciate-all');
    Route::post('asetperusahaan/{aset}/dispose', [AsetController::class, 'dispose'])->name('aset.dispose');
    Route::resource('asetperusahaan', AsetController::class)
        ->parameters(['asetperusahaan' => 'aset'])
        ->names('aset')
        ->except(['destroy']);
    Route::resource('kategori-aset', KategoriAsetController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('pesanan-jasa-report/pdf', [PesananJasaController::class, 'reportPdf'])->name('pesanan-jasa.report.pdf');
    Route::get('pesanan-jasa-report/excel', [PesananJasaController::class, 'reportExcel'])->name('pesanan-jasa.report.excel');
    Route::resource('pesanan-jasa', PesananJasaController::class)->parameters(['pesanan-jasa' => 'service_purchase']);
    Route::get('penerimaan-jasa-report/pdf', [PenerimaanJasaController::class, 'reportPdf'])->name('penerimaan-jasa.report.pdf');
    Route::get('penerimaan-jasa-report/excel', [PenerimaanJasaController::class, 'reportExcel'])->name('penerimaan-jasa.report.excel');
    Route::post('penerimaan-jasa/{service_bap}/cancel', [PenerimaanJasaController::class, 'cancel'])->name('penerimaan-jasa.cancel');
    Route::resource('penerimaan-jasa', PenerimaanJasaController::class)->parameters(['penerimaan-jasa' => 'service_bap'])->only(['index', 'create', 'store', 'show']);
    Route::resource('kategori-jasa', KategoriJasaController::class)->parameters(['kategori-jasa' => 'service_category'])->only(['index', 'update']);
});

Route::middleware('auth')->group(function () {
    Route::get('pengeluaran-barang', [App\Http\Controllers\PengeluaranBarangController::class, 'index'])->name('pengeluaran-barang.index');
    Route::post('pengeluaran-barang', [App\Http\Controllers\PengeluaranBarangController::class, 'store'])->name('pengeluaran-barang.store');
    Route::post('pengeluaran-barang/{pengeluaran}/kirim', [App\Http\Controllers\PengeluaranBarangController::class, 'kirim'])->name('pengeluaran-barang.kirim');
    Route::post('pengeluaran-barang/{pengeluaran}/terima', [App\Http\Controllers\PengeluaranBarangController::class, 'terima'])->name('pengeluaran-barang.terima');
    Route::delete('pengeluaran-barang/{pengeluaran}', [App\Http\Controllers\PengeluaranBarangController::class, 'destroy'])->name('pengeluaran-barang.destroy');

    Route::post('barang-titipan', [App\Http\Controllers\PengeluaranBarangController::class, 'storeTitipan'])->name('barang-titipan.store');
    Route::post('barang-titipan/{titipan}/selesai', [App\Http\Controllers\PengeluaranBarangController::class, 'selesaikanTitipan'])->name('barang-titipan.selesai');
});
