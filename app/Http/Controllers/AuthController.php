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
            return view('accounting.dashboard', compact('user'));
        } else {
            return view('dashboard', compact('user'));
        }
    }

    private function warehouseDashboardData(): array
    {
        return [
            'warehouseMetrics' => [
                'total_materials' => Bahan::count(),
                'stock_attention' => Bahan::whereColumn('stok_onhand', '<=', 'planning')->count(),
                'receipts_today' => PenerimaanBarang::whereDate('tanggal', today())->count(),
                'issues_today' => PemakaianBarang::whereDate('tanggal', today())->count(),
                'open_opnames' => StockOpname::whereIn('status', [
                    StockOpname::DRAFT,
                    StockOpname::REJECTED,
                    StockOpname::SUBMITTED,
                    StockOpname::APPROVED,
                ])->count(),
                'service_baps_today' => PenerimaanJasa::whereDate('tanggal', today())->count(),
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

        return [
            'productionMetrics' => [
                'assigned_warehouses' => count($user->accessibleGudangIds()),
                'issues_today' => PemakaianBarang::whereIn('id_gudang_asal', $warehouseIds)->whereDate('tanggal', today())->count(),
                'transfers_in_progress' => DB::table('transfer_gudangs')
                    ->where(function ($query) use ($warehouseIds) {
                        $query->whereIn('gudang_asal_id', $warehouseIds)
                            ->orWhereIn('gudang_tujuan_id', $warehouseIds);
                    })
                    ->whereIn('status', ['DRAFT', 'DIAJUKAN'])
                    ->count(),
                'open_opnames' => StockOpname::whereIn('warehouse_id', $user->accessibleGudangIds('opname'))
                    ->whereIn('status', [
                        StockOpname::DRAFT,
                        StockOpname::REJECTED,
                        StockOpname::SUBMITTED,
                        StockOpname::APPROVED,
                    ])->count(),
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

        return compact('metrics', 'pendingRequests', 'openPurchaseOrders', 'unbilledReceipts', 'dueInvoices');
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

        return compact('metrics', 'priorityInvoices', 'recentPayments');
    }
}
