<?php

use App\Http\Controllers\AccountingPeriodLockController;
use App\Http\Controllers\AccountingReconciliationController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\DebitController;
use App\Http\Controllers\InvoicelpbController;
use App\Http\Controllers\InvoicelpbdetailController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\JurnalDetailController;
use App\Http\Controllers\KategoriBahanController;
use App\Http\Controllers\KreditController;
use App\Http\Controllers\LpbController;
use App\Http\Controllers\NpkController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PembeliandetailController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestdetailController;
use App\Http\Controllers\ServiceBapController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServicePurchaseController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TipePembebananController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('supplier/data-table', [SupplierController::class, 'dataTable'])->name('supplier.dataTable');
    Route::get('supplier-report/pdf', [SupplierController::class, 'reportPdf'])->name('supplier.report.pdf');
    Route::resource('supplier', SupplierController::class)->except(['show']);
    Route::resource('bahan', BahanController::class)->only(['index', 'show', 'edit', 'update']);

    Route::get('request-report/pdf', [RequestController::class, 'reportPdf'])->name('request.report.pdf');
    Route::resource('request', RequestController::class);
    Route::get('request/{request}/approve', [RequestController::class, 'approveForm'])->name('request.approveForm');
    Route::post('request/{request}/approve', [RequestController::class, 'processApprove'])->name('request.processApprove');
    Route::resource('requestdetail', RequestdetailController::class);

    Route::get('pembeliandetail/{no_po}', [PembeliandetailController::class, 'index'])->name('pembeliandetail.index');
    Route::post('pembeliandetail/{no_po}', [PembeliandetailController::class, 'store'])->name('pembeliandetail.store');
    Route::put('pembeliandetail/{pembeliandetail}', [PembeliandetailController::class, 'update'])->name('pembeliandetail.update');
    Route::delete('pembeliandetail/{pembeliandetail}', [PembeliandetailController::class, 'destroy'])->name('pembeliandetail.destroy');

    Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
    Route::get('/pembelian-report/pdf', [PembelianController::class, 'reportPdf'])->name('pembelian.report.pdf');
    Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store');
    Route::get('/pembelian/{no_po}', [PembelianController::class, 'show'])->name('pembelian.show');
    Route::put('/pembelian/{no_po}', [PembelianController::class, 'update'])->name('pembelian.update');
    Route::delete('/pembelian/{no_po}', [PembelianController::class, 'destroy'])->name('pembelian.destroy');
    Route::patch('/pembelian/{no_po}/so-term', [PembelianController::class, 'updateSoTerm'])->name('pembelian.update-so-term');
    Route::patch('/pembelian/{no_po}/financials', [PembelianController::class, 'updateFinancials'])->name('pembelian.update-financials');
    Route::patch('/pembelian/{no_po}/close', [PembelianController::class, 'close'])->name('pembelian.close');
    Route::post('/pembelian/{no_po}/cetak', [PembelianController::class, 'cetak'])->name('pembelian.cetak');

    Route::resource('kredit', KreditController::class);
    Route::resource('debit', DebitController::class);

    Route::get('lpb/po/{no_po}', [LpbController::class, 'getPoDetail'])->name('lpb.get-po-detail');
    Route::get('lpb-report/pdf', [LpbController::class, 'reportPdf'])->name('lpb.report.pdf');
    Route::post('lpb/{lpb}/details', [LpbController::class, 'storeDetail'])->name('lpb.details.store');
    Route::put('lpb/{lpb}/details/{detail}', [LpbController::class, 'updateDetail'])->name('lpb.details.update');
    Route::delete('lpb/{lpb}/details/{detail}', [LpbController::class, 'destroyDetail'])->name('lpb.details.destroy');
    Route::resource('lpb', LpbController::class)->except(['edit']);

    Route::get('invoice-lpb/lpb-detail/{id_lpb}', [InvoicelpbController::class, 'getLpbDetail'])->name('invoice-lpb.get-lpb-detail');
    Route::get('invoice-lpb-report/pdf', [InvoicelpbController::class, 'reportPdf'])->name('invoice-lpb.report.pdf');
    Route::resource('invoice-lpb', InvoicelpbController::class);
    Route::post('invoice-lpb-detail', [InvoicelpbdetailController::class, 'store'])->name('invoice-lpb-detail.store');
    Route::delete('invoice-lpb-detail/{id}', [InvoicelpbdetailController::class, 'destroy'])->name('invoice-lpb-detail.destroy');

    Route::get('tipe-pembebanan-report/pdf', [TipePembebananController::class, 'reportPdf'])->name('tipe-pembebanan.report.pdf');
    Route::resource('tipe-pembebanan', TipePembebananController::class);
    Route::resource('kategori-bahan', KategoriBahanController::class);

    Route::get('chart-of-accounts/kas-bank', [ChartOfAccountController::class, 'getKasBank'])->name('chart-of-accounts.kas-bank');
    Route::put('chart-of-accounts/mapping', [ChartOfAccountController::class, 'updateMapping'])->name('chart-of-accounts.mapping.update');
    Route::get('chart-of-accounts-report/pdf', [ChartOfAccountController::class, 'reportPdf'])->name('chart-of-accounts.report.pdf');
    Route::resource('chart-of-accounts', ChartOfAccountController::class);

    Route::get('jurnal-report/pdf', [JurnalController::class, 'reportPdf'])->name('jurnal.report.pdf');
    Route::post('jurnal/{jurnal}/post', [JurnalController::class, 'post'])->name('jurnal.post');
    Route::post('jurnal/{jurnal}/reverse', [JurnalController::class, 'reverse'])->name('jurnal.reverse');
    Route::resource('jurnal', JurnalController::class);
    Route::post('jurnal-detail', [JurnalDetailController::class, 'store'])->name('jurnal-detail.store');
    Route::put('jurnal-detail/{id}', [JurnalDetailController::class, 'update'])->name('jurnal-detail.update');
    Route::delete('jurnal-detail/{id}', [JurnalDetailController::class, 'destroy'])->name('jurnal-detail.destroy');

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

    Route::get('reconciliation', [AccountingReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('reconciliation/{check}', [AccountingReconciliationController::class, 'show'])->name('reconciliation.show');
    Route::post('period-lock/{period_lock}/unlock', [AccountingPeriodLockController::class, 'unlock'])->name('period-lock.unlock');
    Route::resource('period-lock', AccountingPeriodLockController::class)->only(['index', 'store']);
    Route::resource('tax-rate', TaxRateController::class)->only(['index', 'store', 'update']);

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
