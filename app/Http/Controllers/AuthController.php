<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MaterialRequest;
use App\Models\PesananPembelian;
use App\Models\PenerimaanBarang;
use App\Models\FakturPembelian;
use App\Models\PembayaranFaktur;
use App\Models\Bahan;
use App\Models\PemakaianBarang;
use App\Models\StockOpname;
use App\Models\PenerimaanJasa;
use App\Models\ReturPembelian;
use App\Models\Jurnal;
use App\Models\Aset;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function dashboard()
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return view('dashboard', compact('user'));
        }

        if ($user->isPurchasing()) {
            return view('purchasing.dashboard', array_merge(
                compact('user'),
                $this->purchasingDashboardData()
            ));
        } else if ($user->isFinance()) {
            return view('finance.payment-dashboard', array_merge(
                compact('user'),
                $this->paymentDashboardData()
            ));
        } else if ($user->isWarehouse()) {
            return view('gudang.dashboard', array_merge(
                compact('user'),
                $this->warehouseDashboardData()
            ));
        } else if ($user->isProduction()) {
            return view('produksi.dashboard', array_merge(
                compact('user'),
                $this->productionDashboardData($user)
            ));
        } else if ($user->isAccounting() || $user->isAccountingManager()) {
            return view('accounting.dashboard', array_merge(
                compact('user'),
                $this->accountingDashboardData($user)
            ));
        } else {
            return view('dashboard', compact('user'));
        }
    }

    private function warehouseDashboardData(): array
    {
        $stockAttention = Bahan::whereColumn('stok_onhand', '<=', 'planning')->count();
        $receiptsToday = PenerimaanBarang::whereDate('tanggal', today())->count();
        $issuesToday = PemakaianBarang::whereDate('tanggal', today())->count();
        $openOpnames = StockOpname::whereIn('status', [
            StockOpname::DRAFT,
            StockOpname::REJECTED,
            StockOpname::SUBMITTED,
            StockOpname::APPROVED,
        ])->count();
        $serviceBapsToday = PenerimaanJasa::whereDate('tanggal', today())->count();
        $transfersInProgress = DB::table('transfer_gudangs')->whereIn('status', ['DRAFT', 'DIAJUKAN', 'DIKIRIM'])->count();
        $myPendingRequests = MaterialRequest::where('requested_by', Auth::id())->where('status', MaterialRequest::PENDING)->count();

        $tasks = collect([
            ['label' => 'Bahan Perlu Perhatian Stok', 'count' => $stockAttention, 'url' => route('bahan.index'), 'icon' => 'fa-triangle-exclamation', 'tone' => 'warning'],
            ['label' => 'Penerimaan Hari Ini', 'count' => $receiptsToday, 'url' => route('penerimaan-barang.index'), 'icon' => 'fa-truck-ramp-box', 'tone' => 'info'],
            ['label' => 'Pemakaian Hari Ini', 'count' => $issuesToday, 'url' => route('pemakaian-barang.index'), 'icon' => 'fa-boxes-packing', 'tone' => 'info'],
            ['label' => 'Stock Opname Terbuka', 'count' => $openOpnames, 'url' => route('stock-opname.index'), 'icon' => 'fa-clipboard-list', 'tone' => 'warning'],
            ['label' => 'BAP Jasa Hari Ini', 'count' => $serviceBapsToday, 'url' => route('penerimaan-jasa.index'), 'icon' => 'fa-handshake', 'tone' => 'neutral'],
            ['label' => 'Transfer Gudang Berjalan', 'count' => $transfersInProgress, 'url' => route('transfer-gudangs.index'), 'icon' => 'fa-truck-fast', 'tone' => 'info'],
            ['label' => 'Request Saya Menunggu Approval', 'count' => $myPendingRequests, 'url' => route('request.index'), 'icon' => 'fa-file-circle-question', 'tone' => 'neutral'],
        ]);

        return [
            'tasks' => $tasks,
            'warehouseMetrics' => [
                'total_materials' => Bahan::count(),
                'stock_attention' => $stockAttention,
                'receipts_today' => $receiptsToday,
                'issues_today' => $issuesToday,
                'open_opnames' => $openOpnames,
                'service_baps_today' => $serviceBapsToday,
            ],
            'recentReceipts' => PenerimaanBarang::with('pembelian.supplier')
                ->latest('tanggal')->latest('id')->limit(5)->get(),
            'recentIssues' => PemakaianBarang::with('barang')
                ->latest('tanggal')->latest('id')->limit(5)->get(),
        ];
    }

    private function productionDashboardData($user): array
    {
        $warehouseIds = $user->accessibleGudangIds('npk');
        $issuesToday = PemakaianBarang::whereIn('id_gudang_asal', $warehouseIds)->whereDate('tanggal', today())->count();
        $transfersInProgress = DB::table('transfer_gudangs')
            ->where(function ($query) use ($warehouseIds) {
                $query->whereIn('gudang_asal_id', $warehouseIds)
                    ->orWhereIn('gudang_tujuan_id', $warehouseIds);
            })
            ->whereIn('status', ['DRAFT', 'DIAJUKAN'])
            ->count();
        $openOpnames = StockOpname::whereIn('warehouse_id', $user->accessibleGudangIds('opname'))
            ->whereIn('status', [
                StockOpname::DRAFT,
                StockOpname::REJECTED,
                StockOpname::SUBMITTED,
                StockOpname::APPROVED,
            ])->count();
        $myPendingRequests = MaterialRequest::where('requested_by', $user->id)->where('status', MaterialRequest::PENDING)->count();

        $tasks = collect([
            ['label' => 'Pemakaian Hari Ini', 'count' => $issuesToday, 'url' => route('pemakaian-barang.index'), 'icon' => 'fa-boxes-packing', 'tone' => 'info'],
            ['label' => 'Transfer Sedang Berjalan', 'count' => $transfersInProgress, 'url' => route('transfer-gudangs.index'), 'icon' => 'fa-truck-fast', 'tone' => 'info'],
            ['label' => 'Stock Opname Terbuka', 'count' => $openOpnames, 'url' => route('stock-opname.index'), 'icon' => 'fa-clipboard-list', 'tone' => 'warning'],
            ['label' => 'Request Saya Menunggu Approval', 'count' => $myPendingRequests, 'url' => route('request.index'), 'icon' => 'fa-file-circle-question', 'tone' => 'neutral'],
        ]);

        return [
            'tasks' => $tasks,
            'productionMetrics' => [
                'assigned_warehouses' => count($user->accessibleGudangIds()),
                'issues_today' => $issuesToday,
                'transfers_in_progress' => $transfersInProgress,
                'open_opnames' => $openOpnames,
            ],
            'recentIssues' => PemakaianBarang::with(['barang', 'gudangAsal'])
                ->whereIn('id_gudang_asal', $warehouseIds)
                ->latest('tanggal')->latest('id')->limit(5)->get(),
            'recentTransfers' => DB::table('transfer_gudangs as tg')
                ->leftJoin('gudangs as asal', 'asal.id', '=', 'tg.gudang_asal_id')
                ->leftJoin('gudangs as tujuan', 'tujuan.id', '=', 'tg.gudang_tujuan_id')
                ->where(function ($query) use ($warehouseIds) {
                    $query->whereIn('tg.gudang_asal_id', $warehouseIds)
                        ->orWhereIn('tg.gudang_tujuan_id', $warehouseIds);
                })
                ->orderByDesc('tg.tanggal')
                ->orderByDesc('tg.id')
                ->limit(5)
                ->get([
                    'tg.nomor_transfer',
                    'tg.tanggal',
                    'tg.status',
                    'asal.nama as asal_nama',
                    'tujuan.nama as tujuan_nama',
                ]),
        ];
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function purchasingDashboardData(): array
    {
        $metrics = [
            'pending_requests' => MaterialRequest::where('status', MaterialRequest::PENDING)->count(),
            'approved_unrealized' => DB::table('request_details')
                ->join('requests', 'requests.id', '=', 'request_details.request_id')
                ->where('requests.status', MaterialRequest::APPROVED)
                ->whereRaw('COALESCE(request_details.realisasi, 0) < COALESCE(request_details.jumlah_acc, request_details.jumlah_minta)')
                ->count(),
            'open_purchase_orders' => PesananPembelian::where('status', PesananPembelian::OPEN)->count(),
            'awaiting_receipt' => PesananPembelian::where('status', PesananPembelian::OPEN)
                ->whereHas('details', fn($query) => $query->whereColumn('diterima', '<', 'jumlah'))
                ->count(),
            'unbilled_receipts' => PenerimaanBarang::whereNull('no_invoice')->count(),
            'unpaid_invoices' => FakturPembelian::where('status', '!=', FakturPembelian::VOID)->where('sisa_tagihan', '>', 0)->count(),
            'overdue_invoices' => FakturPembelian::where('status', '!=', FakturPembelian::VOID)->where('sisa_tagihan', '>', 0)
                ->whereDate('tgl_deadline_pembayaran', '<', today())->count(),
            'stock_attention' => Bahan::whereColumn('stok_onhand', '<', 'planning')->count(),
        ];

        $pendingRequests = MaterialRequest::withCount('details')
            ->where('status', MaterialRequest::PENDING)->latest()->limit(5)->get();

        $openPurchaseOrders = PesananPembelian::with('supplier')
            ->withSum('details as ordered_quantity', 'jumlah')
            ->withSum('details as received_quantity', 'diterima')
            ->where('status', PesananPembelian::OPEN)->latest('tanggal')->limit(5)->get();

        $unbilledReceipts = PenerimaanBarang::with('pembelian.supplier')
            ->whereNull('no_invoice')->latest('tanggal')->limit(5)->get();

        $dueInvoices = FakturPembelian::with('supplier')->where('status', '!=', FakturPembelian::VOID)
            ->where('sisa_tagihan', '>', 0)
            ->orderByRaw('tgl_deadline_pembayaran IS NULL')
            ->orderBy('tgl_deadline_pembayaran')->limit(5)->get();

        $pendingApprovalInvoices = FakturPembelian::where('status', FakturPembelian::PENDING_APPROVAL)->count();
        $openServiceOrders = PesananPembelian::where('document_type', 'SERVICE')->where('status', PesananPembelian::OPEN)->count();
        $unbilledServiceReceipts = PenerimaanJasa::whereNull('no_invoice')->count();
        $activeReturs = ReturPembelian::where('status', ReturPembelian::POSTED)->count();

        $tasks = collect([
            ['label' => 'Request Menunggu Approval', 'count' => $metrics['pending_requests'], 'url' => route('request.index'), 'icon' => 'fa-file-circle-question', 'tone' => 'warning'],
            ['label' => 'Request Disetujui Belum Di-PO-kan', 'count' => $metrics['approved_unrealized'], 'url' => route('request.index'), 'icon' => 'fa-cart-plus', 'tone' => 'info'],
            ['label' => 'PO Terbuka', 'count' => $metrics['open_purchase_orders'], 'url' => route('pembelian.index'), 'icon' => 'fa-file-invoice', 'tone' => 'info'],
            ['label' => 'PO Menunggu Penerimaan', 'count' => $metrics['awaiting_receipt'], 'url' => route('pembelian.index'), 'icon' => 'fa-truck', 'tone' => 'warning'],
            ['label' => 'Penerimaan Barang Belum Ditagih', 'count' => $metrics['unbilled_receipts'], 'url' => route('penerimaan-barang.index'), 'icon' => 'fa-receipt', 'tone' => 'neutral'],
            ['label' => 'Invoice Menunggu Persetujuan Manager', 'count' => $pendingApprovalInvoices, 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-hourglass-half', 'tone' => 'info'],
            ['label' => 'Invoice Belum Lunas', 'count' => $metrics['unpaid_invoices'], 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-file-invoice-dollar', 'tone' => 'neutral'],
            ['label' => 'Invoice Jatuh Tempo', 'count' => $metrics['overdue_invoices'], 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-clock', 'tone' => 'error'],
            ['label' => 'Bahan Di Bawah Planning', 'count' => $metrics['stock_attention'], 'url' => route('bahan.index'), 'icon' => 'fa-triangle-exclamation', 'tone' => 'warning'],
            ['label' => 'PO Jasa Terbuka', 'count' => $openServiceOrders, 'url' => route('pesanan-jasa.index'), 'icon' => 'fa-handshake', 'tone' => 'info'],
            ['label' => 'Penerimaan Jasa Belum Ditagih', 'count' => $unbilledServiceReceipts, 'url' => route('penerimaan-jasa.index'), 'icon' => 'fa-receipt', 'tone' => 'neutral'],
            ['label' => 'Retur Pembelian Aktif', 'count' => $activeReturs, 'url' => route('retur-pembelian.index'), 'icon' => 'fa-rotate-left', 'tone' => 'neutral'],
        ]);

        return compact('metrics', 'pendingRequests', 'openPurchaseOrders', 'unbilledReceipts', 'dueInvoices', 'tasks');
    }

    private function paymentDashboardData(): array
    {
        $activeInvoices = FakturPembelian::query()
            ->whereNotIn('status', [FakturPembelian::VOID, FakturPembelian::PENDING_APPROVAL])
            ->where('sisa_tagihan', '>', 0);
        $metrics = [
            'unpaid_count' => (clone $activeInvoices)->count(),
            'outstanding_value' => (float) (clone $activeInvoices)->sum('sisa_tagihan'),
            'overdue_count' => (clone $activeInvoices)->whereDate('tgl_deadline_pembayaran', '<', today())->count(),
            'due_soon_count' => (clone $activeInvoices)
                ->whereBetween('tgl_deadline_pembayaran', [today(), today()->addDays(7)])->count(),
            'paid_this_month' => (float) PembayaranFaktur::query()->where('status', PembayaranFaktur::POSTED)
                ->whereBetween('tanggal_pembayaran', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('jumlah_pembayaran'),
            'payments_this_month' => PembayaranFaktur::query()->where('status', PembayaranFaktur::POSTED)
                ->whereBetween('tanggal_pembayaran', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count(),
        ];

        $priorityInvoices = FakturPembelian::with('supplier')
            ->whereNotIn('status', [FakturPembelian::VOID, FakturPembelian::PENDING_APPROVAL])
            ->where('sisa_tagihan', '>', 0)
            ->orderByRaw('tgl_deadline_pembayaran IS NULL')
            ->orderBy('tgl_deadline_pembayaran')
            ->limit(8)->get();

        $recentPayments = PembayaranFaktur::with(['invoice.supplier', 'coaKasBank'])
            ->where('status', PembayaranFaktur::POSTED)->latest('tanggal_pembayaran')->latest('id')->limit(8)->get();

        $pendingApprovalInvoices = FakturPembelian::where('status', FakturPembelian::PENDING_APPROVAL)->count();
        $availableAdvances = PembayaranFaktur::where('status', PembayaranFaktur::POSTED)
            ->where('jenis_selisih', 'UANG_MUKA_SUPPLIER')
            ->get()
            ->filter(fn($payment) => $payment->sisaUangMuka() > 0.01)
            ->count();

        $tasks = collect([
            ['label' => 'Invoice Siap Dibayar', 'count' => $metrics['unpaid_count'], 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-file-invoice-dollar', 'tone' => 'neutral'],
            ['label' => 'Invoice Jatuh Tempo', 'count' => $metrics['overdue_count'], 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-clock', 'tone' => 'error'],
            ['label' => 'Jatuh Tempo 7 Hari Ke Depan', 'count' => $metrics['due_soon_count'], 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-calendar-day', 'tone' => 'warning'],
            ['label' => 'Invoice Menunggu Persetujuan Manager', 'count' => $pendingApprovalInvoices, 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-hourglass-half', 'tone' => 'info'],
            ['label' => 'Uang Muka Supplier Tersedia', 'count' => $availableAdvances, 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-piggy-bank', 'tone' => 'info'],
            ['label' => 'Pembayaran Bulan Ini', 'count' => $metrics['payments_this_month'], 'url' => route('faktur-pembelian.index'), 'icon' => 'fa-money-bill-transfer', 'tone' => 'success'],
        ]);

        return compact('metrics', 'priorityInvoices', 'recentPayments', 'tasks');
    }

    private function accountingDashboardData($user): array
    {
        $pendingApprovalInvoices = FakturPembelian::where('status', FakturPembelian::PENDING_APPROVAL)->count();
        $draftJurnals = Jurnal::where('status', 'DRAFT')->count();
        $reconciliationIssues = app(\App\Services\AccountingReconciliationService::class)->checks()->sum('invalid');
        $opnameAwaitingAccounting = StockOpname::where('status', StockOpname::SUBMITTED)->count();
        $activeReturs = ReturPembelian::where('status', ReturPembelian::POSTED)->count();
        $pendingRequests = MaterialRequest::where('status', MaterialRequest::PENDING)->count();
        $assetsDueDepreciation = Aset::where('status', 'ACTIVE')
            ->where('depreciation_method', 'STRAIGHT_LINE')
            ->whereRaw('book_value > residual_value')
            ->count();

        $tasks = collect(array_filter([
            $user->isAccountingManager() ? [
                'label' => 'Invoice Menunggu Persetujuan Saya',
                'count' => $pendingApprovalInvoices,
                'url' => route('faktur-pembelian.index'),
                'icon' => 'fa-hourglass-half',
                'tone' => 'warning',
            ] : [
                'label' => 'Invoice Menunggu Persetujuan Manager',
                'count' => $pendingApprovalInvoices,
                'url' => route('faktur-pembelian.index'),
                'icon' => 'fa-hourglass-half',
                'tone' => 'info',
            ],
            ['label' => 'Jurnal Draft Belum Diposting', 'count' => $draftJurnals, 'url' => route('jurnal.index'), 'icon' => 'fa-book', 'tone' => 'warning'],
            ['label' => 'Masalah Rekonsiliasi', 'count' => $reconciliationIssues, 'url' => route('reconciliation.index'), 'icon' => 'fa-scale-unbalanced', 'tone' => $reconciliationIssues > 0 ? 'error' : 'success'],
            ['label' => 'Stock Opname Menunggu Konfirmasi', 'count' => $opnameAwaitingAccounting, 'url' => route('stock-opname.index'), 'icon' => 'fa-clipboard-check', 'tone' => 'warning'],
            ['label' => 'Retur Pembelian Aktif', 'count' => $activeReturs, 'url' => route('retur-pembelian.index'), 'icon' => 'fa-rotate-left', 'tone' => 'neutral'],
            ['label' => 'Request Menunggu Approval', 'count' => $pendingRequests, 'url' => route('request.index'), 'icon' => 'fa-file-circle-question', 'tone' => 'neutral'],
            ['label' => 'Aset Belum Disusutkan Periode Ini', 'count' => $assetsDueDepreciation, 'url' => route('aset.index'), 'icon' => 'fa-building-shield', 'tone' => 'neutral'],
        ]));

        return ['tasks' => $tasks];
    }
}
