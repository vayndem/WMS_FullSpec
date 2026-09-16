<?php

use App\Http\Controllers\DataPesananController;
use App\Http\Controllers\FakturPenjualanController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PesananPenjualanController;
use App\Http\Controllers\SuratJalanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('pelanggan', PelangganController::class)->except(['show']);

    Route::get('pesanan-penjualan', [PesananPenjualanController::class, 'index'])->name('pesanan-penjualan.index');
    Route::get('pesanan-penjualan/create', [PesananPenjualanController::class, 'create'])->name('pesanan-penjualan.create');
    Route::post('pesanan-penjualan', [PesananPenjualanController::class, 'store'])->name('pesanan-penjualan.store');
    Route::get('pesanan-penjualan/{pesanan}', [PesananPenjualanController::class, 'show'])->name('pesanan-penjualan.show');
    Route::post('pesanan-penjualan/{pesanan}/kirim', [PesananPenjualanController::class, 'kirim'])->name('pesanan-penjualan.kirim');

    Route::get('surat-jalan', [SuratJalanController::class, 'index'])->name('surat-jalan.index');
    Route::get('surat-jalan/{suratJalan}', [SuratJalanController::class, 'show'])->name('surat-jalan.show');
    Route::post('surat-jalan/{suratJalan}/post', [SuratJalanController::class, 'post'])->name('surat-jalan.post');
    Route::post('surat-jalan/{suratJalan}/faktur', [SuratJalanController::class, 'faktur'])->name('surat-jalan.faktur');
    Route::post('surat-jalan/{suratJalan}/retur', [SuratJalanController::class, 'retur'])->name('surat-jalan.retur');

    Route::get('data-pesanan', [DataPesananController::class, 'index'])->name('data-pesanan.index');
    Route::get('data-pesanan/create', [DataPesananController::class, 'create'])->name('data-pesanan.create');
    Route::post('data-pesanan', [DataPesananController::class, 'store'])->name('data-pesanan.store');
    Route::get('data-pesanan/{pesanan}', [DataPesananController::class, 'show'])->name('data-pesanan.show');
    Route::post('data-pesanan/{pesanan}/rilis', [DataPesananController::class, 'rilis'])->name('data-pesanan.rilis');
    Route::post('data-pesanan/{pesanan}/selesaikan', [DataPesananController::class, 'selesaikan'])->name('data-pesanan.selesaikan');
    Route::post('data-pesanan/{pesanan}/batalkan', [DataPesananController::class, 'batalkan'])->name('data-pesanan.batalkan');

    Route::get('faktur-penjualan', [FakturPenjualanController::class, 'index'])->name('faktur-penjualan.index');
    Route::get('faktur-penjualan/{faktur}', [FakturPenjualanController::class, 'show'])->name('faktur-penjualan.show');
    Route::post('faktur-penjualan/{faktur}/post', [FakturPenjualanController::class, 'post'])->name('faktur-penjualan.post');
    Route::post('faktur-penjualan/{faktur}/bayar', [FakturPenjualanController::class, 'bayar'])->name('faktur-penjualan.bayar');
});
