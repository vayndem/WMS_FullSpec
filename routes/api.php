<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvPoController;
use App\Http\Controllers\AfvalController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\BahanController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\PengambilanController;

// Ubah baris middleware menjadi seperti ini
Route::middleware('auth:api_token')->group(function () {
    // Route untuk mendapatkan informasi user API yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Route untuk DataTables Purchase Order
    Route::get('purchase-orders', [InvPoController::class, 'index']);
    //lock unluck status
    Route::post('purchase-orders/toggle-lock', [InvPoController::class, 'toggleLockStatus']);
    //tampil view
    Route::get('purchase-orders/{nomorpo}', [InvPoController::class, 'show']);
    Route::get('/reports/monthly-purchases', [InvPoController::class, 'monthlyPurchases']);

    Route::get('/detailAfvalid/{kode_afval}', [AfvalController::class, 'detailAfvalid'])->name('afval.detail');
    Route::get('/getafvalid', [AfvalController::class, 'getafvalid']);
    Route::put('/updateatatusafval/{kode_afval}', [AfvalController::class, 'updateStatusAfval']);

    Route::get('/gudang/lpbDatano', [GudangController::class, 'getLpbDataapi']);
    Route::put('/bukalpbgudang/{id_data}', [GudangController::class, 'bukalpbdata']);

    Route::post('/setujuiStokAdjust', [GudangController::class, 'setujuiStokAdjust']);
    Route::get('/ambilstokadjust', [GudangController::class, 'ambilstokadjust']);

    Route::get('/read', [PengajuanController::class, 'read']);
    Route::post('/updatestatus', [PengajuanController::class, 'updatestatus']);

    Route::post('/gudang/finalizeStockOpname/{id}', [GudangController::class, 'finalizeStockOpname']);
    Route::get('/gudang/detail-opname-non-kertas', [GudangController::class, 'getDetailOpnameNonKertas']);
    Route::get('/gudang/ambilPengajuanOpname', [GudangController::class, 'ambilPengajuanOpname']);

    Route::get('/apigetbahan', [BahanController::class, 'apigetbahan']);
    Route::get('/planning/packing/next-number', [PengambilanController::class, 'apiNextPackingNumber']);
    Route::post('/planning/packing', [PengambilanController::class, 'apiStorePlanningPacking']);
    Route::delete('/planning/packing/detail/{id}', [PengambilanController::class, 'apiDeletePlanningPackingDetail']);
    Route::get('planning/packing/by-dp/{kode_datapesanan}', [PengambilanController::class, 'apiGetPlanningPackingByDp'])->name('packing-planning.by-dp');

    Route::post('/pengajuan/external', [PengajuanController::class, 'apiStoreExternal']);
    Route::get('/pengajuan/check-status/{id}', [PengajuanController::class, 'apiCheckStatus']);
    Route::get('/pengajuan/list', [PengajuanController::class, 'apiListExternal']);
    Route::get('/pengajuan/detail/{id}', [PengajuanController::class, 'apiShowDetail']);
    Route::get('/bahan/list', [BahanController::class, 'apiGetBahanList']);

    
});
