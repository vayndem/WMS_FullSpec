<?php

use App\Http\Controllers\GudangController;
use App\Http\Controllers\InventoryFinancialControlController;
use App\Http\Controllers\LpbController;
use App\Http\Controllers\MutasiStokController;
use App\Http\Controllers\NpkController;
use App\Http\Controllers\PembagianGudangController;
use App\Http\Controllers\PemeriksaanConsiderController;
use App\Http\Controllers\PengaturanBahanGudangController;
use App\Http\Controllers\RekonsiliasiGudangController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StokGudangController;
use App\Http\Controllers\TransferGudangController;
use App\Http\Controllers\WarehouseExecutionController;
use App\Http\Controllers\WmsControlController;
use App\Http\Controllers\WmsTraceabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('gudangs', GudangController::class)->except('destroy');
    Route::resource('stok-gudangs', StokGudangController::class)->only(['index', 'show']);
    Route::resource('pembagian-gudangs', PembagianGudangController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('pengaturan-bahan-gudangs', PengaturanBahanGudangController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('transfer-gudangs/{transfer_gudang}/submit', [TransferGudangController::class, 'submit'])->name('transfer-gudangs.submit');
    Route::post('transfer-gudangs/{transfer_gudang}/confirm', [TransferGudangController::class, 'confirm'])->name('transfer-gudangs.confirm');
    Route::post('transfer-gudangs/{transfer_gudang}/receive', [TransferGudangController::class, 'receive'])->name('transfer-gudangs.receive');
    Route::resource('transfer-gudangs', TransferGudangController::class);

    Route::post('pemeriksaan-considers/{pemeriksaan_consider}/confirm', [PemeriksaanConsiderController::class, 'confirm'])->name('pemeriksaan-considers.confirm');
    Route::resource('pemeriksaan-considers', PemeriksaanConsiderController::class);
    Route::resource('mutasi-stoks', MutasiStokController::class)->only('index');
    Route::get('rekonsiliasi-gudangs', [RekonsiliasiGudangController::class, 'index'])->name('rekonsiliasi-gudangs.index');

    Route::get('lpb/po/{no_po}', [LpbController::class, 'getPoDetail'])->name('lpb.get-po-detail');
    Route::get('lpb-report/pdf', [LpbController::class, 'reportPdf'])->name('lpb.report.pdf');
    Route::post('lpb/{lpb}/details', [LpbController::class, 'storeDetail'])->name('lpb.details.store');
    Route::put('lpb/{lpb}/details/{detail}', [LpbController::class, 'updateDetail'])->name('lpb.details.update');
    Route::delete('lpb/{lpb}/details/{detail}', [LpbController::class, 'destroyDetail'])->name('lpb.details.destroy');
    Route::resource('lpb', LpbController::class)->except(['edit']);

    Route::get('npk-report/pdf', [NpkController::class, 'reportPdf'])->name('npk.report.pdf');
    Route::resource('npk', NpkController::class);

    Route::get('stock-opname-report/pdf', [StockOpnameController::class, 'reportListPdf'])->name('stock-opname.report.pdf');
    Route::get('stock-opname-export/excel', [StockOpnameController::class, 'exportInventory'])->name('stock-opname.export.excel');
    Route::get('stock-opname/{stock_opname}/detail-data', [StockOpnameController::class, 'detailData'])->name('stock-opname.detail-data');
    Route::get('stock-opname/{stock_opname}/pdf', [StockOpnameController::class, 'reportPdf'])->name('stock-opname.pdf');
    Route::post('stock-opname/{stock_opname}/submit', [StockOpnameController::class, 'submit'])->name('stock-opname.submit');
    Route::post('stock-opname/{stock_opname}/approve', [StockOpnameController::class, 'approve'])->name('stock-opname.approve');
    Route::post('stock-opname/{stock_opname}/reject', [StockOpnameController::class, 'reject'])->name('stock-opname.reject');
    Route::post('stock-opname/{stock_opname}/post', [StockOpnameController::class, 'post'])->name('stock-opname.post');
    Route::resource('stock-opname', StockOpnameController::class);

    Route::prefix('wms-control')->name('wms-control.')->group(function () {
        Route::get('/', [WmsControlController::class, 'index'])->name('index');
        Route::post('locations', [WmsTraceabilityController::class, 'storeLocation'])->name('locations.store');
        Route::post('lots', [WmsTraceabilityController::class, 'storeLot'])->name('lots.store');
        Route::post('serials', [WmsTraceabilityController::class, 'storeSerial'])->name('serials.store');
        Route::patch('lots/{lot}/block', [WmsTraceabilityController::class, 'updateLotBlock'])->name('lots.block');
        Route::post('lpb/{lpb}/inspect', [WarehouseExecutionController::class, 'inspect'])->name('lpb.inspect');
        Route::post('lpb/{lpb}/putaway', [WarehouseExecutionController::class, 'putaway'])->name('lpb.putaway');
        Route::post('reservations', [WarehouseExecutionController::class, 'reserve'])->name('reservations.store');
        Route::post('reservations/{reservation}/release', [WarehouseExecutionController::class, 'releaseReservation'])->name('reservations.release');
        Route::post('reservations/{reservation}/pick', [WarehouseExecutionController::class, 'createPick'])->name('reservations.pick');
        Route::post('picking-orders/{pickingOrder}/complete', [WarehouseExecutionController::class, 'completePick'])->name('picking-orders.complete');
        Route::post('replenishment', [WarehouseExecutionController::class, 'replenish'])->name('replenishment');
        Route::post('invoices/{invoice}/match', [InventoryFinancialControlController::class, 'matchInvoice'])->name('invoices.match');
        Route::post('landed-costs', [InventoryFinancialControlController::class, 'storeLandedCost'])->name('landed-costs.store');
        Route::post('landed-costs/{landedCost}/post', [InventoryFinancialControlController::class, 'postLandedCost'])->name('landed-costs.post');
        Route::post('lpb/{lpb}/reverse', [InventoryFinancialControlController::class, 'reverseLpb'])->name('lpb.reverse');
        Route::post('npk/{npk}/reverse', [InventoryFinancialControlController::class, 'reverseNpk'])->name('npk.reverse');
    });
});
