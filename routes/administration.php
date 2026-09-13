<?php

use App\Http\Controllers\LampiranDokumenController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('pengguna', [UserController::class, 'index'])->name('user.index');
    Route::get('pengguna/create', [UserController::class, 'create'])->name('user.create');
    Route::post('pengguna', [UserController::class, 'store'])->name('user.store');
    Route::get('pengguna/audit-login', [UserController::class, 'loginAudit'])->name('user.login-audit');
    Route::get('pengguna/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('pengguna/{user}', [UserController::class, 'update'])->name('user.update');
    Route::post('pengguna/{user}/nonaktifkan', [UserController::class, 'deactivate'])->name('user.deactivate');
    Route::post('pengguna/{user}/aktifkan', [UserController::class, 'reactivate'])->name('user.reactivate');

    Route::get('profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');

    Route::get('notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('notifikasi/lonceng', [NotifikasiController::class, 'lonceng'])->name('notifikasi.lonceng');
    Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');

    Route::post('lampiran', [LampiranDokumenController::class, 'store'])->name('lampiran.store');
    Route::get('lampiran/{lampiran}/unduh', [LampiranDokumenController::class, 'download'])->name('lampiran.download');
    Route::delete('lampiran/{lampiran}', [LampiranDokumenController::class, 'destroy'])->name('lampiran.destroy');
});
