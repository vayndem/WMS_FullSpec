<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BahanController;
use App\Http\Controllers\PermintaanController;
use App\Http\Controllers\POController;
use App\Http\Controllers\PurchasingController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\Pengambilancontroller;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\AfvalController;
use App\Exports\LpbExport;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\CatatanCustomerController;
use App\Http\Controllers\JasaController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\SsoProviderController;
use App\Http\Controllers\BahanProduksiController;
use App\Http\Controllers\AdminLpbTemporaryController;
use App\Http\Controllers\AdminLpbDetailTemporaryController;

use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestdetailController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PembelianDetailController;
use App\Http\Controllers\LpbController;
use App\Http\Controllers\KreditController;
use App\Http\Controllers\DebitController;
use App\Http\Controllers\InvoicelpbController;
use App\Http\Controllers\InvoicelpbdetailController;
use App\Http\Controllers\TipePembebananController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\JurnalDetailController;
use App\Http\Controllers\NpkController;
use App\Http\Controllers\KategoriBahanController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\AccountingPeriodLockController;
use App\Http\Controllers\AccountingReconciliationController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\ServicePurchaseController;
use App\Http\Controllers\ServiceBapController;
use App\Http\Controllers\ServiceCategoryController;
use Illuminate\Http\Request;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth.custom'])->group(function () {
    Route::get('/akses-arsip', [SsoProviderController::class, 'goToArsip'])->name('sso.arsip');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
     * WMS LEGACY
     * Dinonaktifkan selama pengembangan WMS baru. Dipertahankan sementara
     * sebagai referensi sampai seluruh alur WMS baru telah diverifikasi.
     */
    /*
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori');
    Route::get('/kategori/fetch', [KategoriController::class, 'fetchKategori']);
    Route::post('/kategori/store', [KategoriController::class, 'storeKategori']);
    Route::put('/kategori/update/{id}', [KategoriController::class, 'updateKategori']);
    Route::delete('/kategori/delete/{id}', [KategoriController::class, 'deleteKategori']);

    Route::get('/report/export', [ReportController::class, 'exportExcel'])->name('report.export');
    Route::resource('report', ReportController::class);

    Route::resource('bahan', BahanController::class);
    Route::post('listbahan', [BahanController::class, 'listbahan'])->name('listbahan');
    Route::get('/showmodalpencarianbahan', [BahanController::class, 'showmodalpencarianbahan'])->name('showmodalpencarianbahan');
    Route::get('penunjang', [BahanController::class, 'index'])->name('penunjang')->defaults('jenis', 0);
    Route::get('penolong', [BahanController::class, 'index'])->name('penolong')->defaults('jenis', 1);
    Route::get('masternonpopp', [BahanController::class, 'index'])->name('masternonpopp')->defaults('jenis', 2);
    Route::get('/bahan/fetch', [BahanController::class, 'fetchbahan']);

    Route::get('/bahan_produksi/get-data', [BahanProduksiController::class, 'getdatapesanan'])->name('bahan_produksi.get_data');
    Route::get('/bahan-produksi/dashboard', [BahanProduksiController::class, 'index2'])->name('bahan_produksi.dashboard');
    Route::post('/bahan-produksi/return', [BahanProduksiController::class, 'returnBahan'])->name('bahan_produksi.return');
    Route::post('/bahan-produksi/kembali', [BahanProduksiController::class, 'kembaliBahan'])->name('bahan_produksi.kembali');
    Route::get('/bahan_produksi/generate/{id}', [BahanProduksiController::class, 'generate'])->name('bahan_produksi.generate');
    Route::post('/bahan_produksi/verify-scan', [BahanProduksiController::class, 'verifyScan'])->name('bahan_produksi.verify_scan');
    Route::get('/bahan_produksi/gudang/{id_gudang}', [BahanProduksiController::class, 'indexGudang'])->name('bahan_produksi.gudang');
    Route::resource('bahan_produksi', BahanProduksiController::class);

    Route::resource('createpo', POController::class);
    Route::get('createpp', [POController::class, 'createpp'])->name('createpp');
    Route::get('createnon', [POController::class, 'createnon'])->name('createnon');
    Route::post('/cetakpembelian', [PurchasingController::class, 'cetakpembelian'])->name('cetakpembelian');
    Route::post('/lihatcetak', [POController::class, 'lihatcetak'])->name('lihatcetak');
    Route::get('/cetak-revisi', [POController::class, 'cetakRevisi'])->name('cetak.revisi');

    Route::get('/getDataJasa', [JasaController::class, 'getDataJasa'])->name('getDataJasa');
    Route::post('/jasa/lihatcetak', [JasaController::class, 'lihatcetak']);
    Route::get('/jasa/cetakdirect', [JasaController::class, 'cetakDirect']);
    Route::resource('jasa', JasaController::class)->except(['destroy']);

    Route::resource('afval', AfvalController::class);
    Route::get('/readafval', [AfvalController::class, 'readafval'])->name('readafval');
    Route::post('/createafval', [AfvalController::class, 'createafval'])->name('createafval');
    Route::get('/afval/details/{kode_afval}', [AfvalController::class, 'getDetailsafval'])->name('afval.details');

    Route::resource('purchasing', PurchasingController::class);
    Route::get('getDataPO', [PurchasingController::class, 'getData'])->name('getDataPO');
    Route::get('getdetailPO', [PurchasingController::class, 'getDatadetail'])->name('getDataPOdetail');
    Route::get('showDetail', [PurchasingController::class, 'showDetail'])->name('showDetail');
    Route::get('/invoicelpb', [PurchasingController::class, 'invoiceLpb'])->name('invoicelpb.index');
    Route::get('/lpbreturn', [PurchasingController::class, 'lpbreturn'])->name('invoicelpb.lpbreturn');
    Route::post('/invoice-lpb/store', [PurchasingController::class, 'storeInvoice'])->name('invoice.lpb.store');
    Route::get('/purchasing/invoice/data', [PurchasingController::class, 'getInvoiceData'])->name('purchasing.invoice.data');
    Route::get('/purchasing/invoice/available-pos', [PurchasingController::class, 'getAvailablePurchaseOrders'])->name('purchasing.invoice.available_pos');
    Route::get('/purchasing/lpb/by-po/{no_po}', [PurchasingController::class, 'getLpbByPo'])->name('purchasing.lpb.by_po');
    Route::get('/invoice/detail/{id}', [PurchasingController::class, 'getInvoiceDetail'])->name('invoice.detail');
    Route::get('/purchasing/getInvoiceItems/{invoiceId}', [PurchasingController::class, 'getInvoiceItems'])->name('invoice.getInvoiceItems');
    Route::post('/purchasing/updateInvoiceItems', [PurchasingController::class, 'updateInvoiceItems'])->name('invoice.updateInvoiceItems');
    Route::delete('/purchasing/invoice/{id}', [PurchasingController::class, 'destroyInvoice'])->name('invoice.lpb.destroy');
    Route::get('/purchasing/jasa/available', [PurchasingController::class, 'getAvailableJasa'])->name('purchasing.invoice.available_jasa');
    Route::post('/invoice-jasa/store', [PurchasingController::class, 'storeInvoiceJasa'])->name('invoice.jasa.store');
    Route::get('/export-po', [PurchasingController::class, 'exportPO'])->name('export.po');
    Route::get('/laporan-opname/export', [PurchasingController::class, 'exportExcel'])->name('laporan.opname');
    Route::get('/laporan-barang/export', [PurchasingController::class, 'exportLaporan'])->name('laporan.barang');

    Route::resource('permintaan', PermintaanController::class);
    Route::get('homepermintaan/{jenis}', [PermintaanController::class, 'home'])->name('homepermintaan.home');
    Route::get('/permintaan/export/{jenis}', [PermintaanController::class, 'exportExcel'])->name('permintaan.export');

    Route::get('/gudang/lpb', [GudangController::class, 'lpb'])->name('gudang.lpb');
    Route::get('/gudang/lpbData', [GudangController::class, 'getLpbData'])->name('gudang.lpbData');
    Route::post('/gudang/updateLpbData', [GudangController::class, 'updateLpbData'])->name('updateLpbData');
    Route::get('/lpb/detail', [GudangController::class, 'getDetailLpb'])->name('getDetailLpb');
    Route::get('/getSuppliers', [GudangController::class, 'getSuppliers'])->name('getSuppliers');
    Route::get('/gudang/no-po', [GudangController::class, 'getNoPoBySupplier'])->name('gudang.getNoPoBySupplier');
    Route::get('/getDetailByNoPo', [GudangController::class, 'getDetailByNoPo'])->name('gudang.getDetailByNoPo');
    Route::post('/gudang/save-lpb', [GudangController::class, 'saveLpb'])->name('gudang.saveLpb');
    Route::get('historibahan', [GudangController::class, 'historibahan'])->name('historibahan');
    Route::post('/lpb/detail/update', [GudangController::class, 'updateLpbDetail'])->name('updateDetailLpb');
    Route::delete('/lpb/detail/{id}', [GudangController::class, 'deleteLpbDetail'])->name('deleteDetailLpb');
    Route::post('/cetak-lpb', [GudangController::class, 'cetakLpb']);
    Route::get('/gudang/lpb-export', function (Request $request) {
        return Excel::download(new LpbExport($request->all()), 'lpb_data.xlsx');
    });
    Route::post('/gudang/check-surat-jalan', [GudangController::class, 'checkSuratJalan']);

    Route::get('lpb-temporary', [AdminLpbTemporaryController::class, 'index']);
    Route::get('get-lpb-temporary-data', [AdminLpbTemporaryController::class, 'getLpbTemporaryData']);
    Route::post('save-lpb-temporary', [AdminLpbTemporaryController::class, 'store']);
    Route::post('approve-lpb-temporary', [AdminLpbTemporaryController::class, 'approve']);
    Route::get('get-detail-lpb-temporary', [AdminLpbDetailTemporaryController::class, 'show']);
    Route::post('update-lpb-detail-temporary', [AdminLpbDetailTemporaryController::class, 'update']);
    Route::delete('delete-lpb-detail-temporary/{id}', [AdminLpbDetailTemporaryController::class, 'destroy']);

    Route::get('/gudang/stokawal', [GudangController::class, 'stokAwal'])->name('gudang.stokawal');
    Route::get('/stock-awal-data', [GudangController::class, 'getStockAwalData'])->name('getStockAwalData');
    Route::post('/store-lpb-detail', [GudangController::class, 'storeLpbDetail'])->name('storeLpbDetail');
    Route::get('/get-bahan-dan-kategori', [GudangController::class, 'getBahanDanKategori']);
    Route::get('/check-stok-awal', [GudangController::class, 'checkStokAwal'])->name('checkStokAwal');
    Route::post('/store-stok-awal', [GudangController::class, 'storeStokAwal'])->name('storeStokAwal');
    Route::get('/get-detail-stok-awal', [GudangController::class, 'getDetailStokAwal'])->name('getDetailStokAwal');
    Route::post('/updateDetailStokAwal', [GudangController::class, 'updateDetailStokAwal'])->name('updateDetailStokAwal');

    Route::get('/stokopname', [GudangController::class, 'stokOpname'])->name('gudang.stokopname');
    Route::get('/gudang/generateStockOpnameCode', [GudangController::class, 'generateStockOpnameCode'])->name('gudang.generateStockOpnameCode');
    Route::get('/gudang/getStockOpnameData', [GudangController::class, 'getStockOpnameData'])->name('gudang.getStockOpnameData');
    Route::post('/gudang/storeStockOpname', [GudangController::class, 'storeStockOpname'])->name('gudang.storeStockOpname');
    Route::get('/gudang/getDetailStockOpname', [GudangController::class, 'getDetailStockOpname'])->name('gudang.getDetailStockOpname');
    Route::get('/gudang/getStockOpname/{id}', [GudangController::class, 'getStockOpnameById'])->name('gudang.getStockOpname');
    Route::post('/gudang/updateStockOpname/{id}', [GudangController::class, 'updateStockOpname'])->name('gudang.updateStockOpname');
    Route::delete('/gudang/deleteStockOpname/{id}', [GudangController::class, 'deleteStockOpname'])->name('gudang.deleteStockOpname');
    Route::post('/gudang/completeStockOpname/{id}', [GudangController::class, 'completeStockOpname'])->name('gudang.completeStockOpname');
    Route::post('/gudang/updateDetailStockOpname', [GudangController::class, 'updateDetailStockOpname'])->name('gudang.updateDetailStockOpname');
    Route::get('/gudang/export-excel/{id}', [GudangController::class, 'exportStockOpnameDetail'])->name('gudang.exportStockOpnameDetail');
    Route::post('/gudang/finalizeStockOpname/{id}', [GudangController::class, 'finalizeStockOpname'])->name('gudang.finalizeStockOpname');

    Route::get('/adjustment', [GudangController::class, 'adjustment'])->name('gudang.adjustment');
    Route::post('/stokadjust', [GudangController::class, 'stokadjust'])->name('gudang.stokadjust');
    Route::get('/gudang/history-data', [GudangController::class, 'getAdjustmentHistory'])->name('gudang.history.data');
    Route::get('/searchadjustment', [GudangController::class, 'searchadjustment'])->name('searchadjustment');
    Route::get('/gudang/adjustment-details', [GudangController::class, 'getAdjustmentDetails'])->name('gudang.adjustment.details');

    Route::get('homepengambilan/{jenis}', [Pengambilancontroller::class, 'home'])->name('homepengambilan.home');
    Route::get('reloadbarang', [Pengambilancontroller::class, 'getbarang'])->name('reloadbarang');
    Route::post('addpengambilan', [Pengambilancontroller::class, 'adddata'])->name('addpengambilan');
    Route::get('listnpkplanning', [Pengambilancontroller::class, 'listdata'])->name('listnpkplanning');
    Route::delete('/deletenpkplanning/{id}', [Pengambilancontroller::class, 'destroy'])->name('deletenpkplanning');
    Route::post('/npkkirim', [Pengambilancontroller::class, 'updateTanggalKirim'])->name('npkkirim');
    Route::get('/exportnpk', [Pengambilancontroller::class, 'exportNpk'])->name('exportnpk');

    Route::resource('pengajuan', PengajuanController::class);
    Route::post('/pengajuan/cetak', [PengajuanController::class, 'lihatCetak'])->name('pengajuan.cetak');

    Route::resource('catatan-customer', CatatanCustomerController::class);
    Route::get('cetak-lpb-temporary/{id_lpb}', [CatatanCustomerController::class, 'cetakLpbQc'])->name('lpb.temporary.cetak');

    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran-nonkertas/data', [PembayaranController::class, 'data'])->name('pembayaran-nonkertas.data');
    Route::get('/pembayaran-nonkertas/{no_invoice}/full-lpb-detail', [PembayaranController::class, 'getInvoiceCompositionJson'])
        ->name('pembayaran-nonkertas.fullLpbDetailJson')
        ->where('no_invoice', '.*');
    Route::get('/pembayaran-nonkertas/{no_invoice}/detail', [PembayaranController::class, 'getDetailJson'])
        ->name('pembayaran-nonkertas.detailJson')
        ->where('no_invoice', '.*');
    Route::post('/pembayaran-nonkertas/store', [PembayaranController::class, 'storePembayaran'])->name('pembayaran-nonkertas.store');
    Route::get('/pembayaran/export-mutu', [PembayaranController::class, 'exportmutu'])->name('pembayaran.export.mutu');
    Route::delete('/pembayaran-nonkertas/detail/{id}', [PembayaranController::class, 'destroyDetail'])->name('pembayaran-nonkertas.destroyDetail');
    /* END WMS LEGACY */

    // WMS BARU - arsitektur Laravel model, migration, request, policy, controller, dan view.
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
