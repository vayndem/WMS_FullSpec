<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Request as RequestModel;
use App\Models\Pembelian;
use App\Models\Lpb;
use App\Models\Invoicelpb;
use App\Models\Invoicelpbdetail;
use App\Models\Bahan;
use App\Models\Npk;
use App\Models\StockOpname;
use App\Models\ServiceBap;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $response = Http::post('http://192.168.0.2/api/login', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['token'])) {
                session(['jwt_token' => $data['token']]);
                session(['user_data' => $data['user']]);

                return redirect()->route('dashboard');
            }
            return back()->withErrors('Token tidak ditemukan.');
        }

        return back()->withErrors('Login gagal: ' . $response->body());
    }

    public function dashboard()
    {
        $user = session('user_data');

        if ($user['type'] == 5) {
            return view('purchasing.dashboard', array_merge(
                compact('user'),
                $this->purchasingDashboardData()
            ));
        } else if ($user['type'] == 13) {
            return view('finance.payment-dashboard', array_merge(
                compact('user'),
                $this->paymentDashboardData()
            ));
        } else if ($user['type'] == 15) {
            return redirect()->route('bahan_produksi.dashboard');
        } else if (in_array($user['type'], [11, 14, 29])) {
            return view('gudang.dashboard', array_merge(
                compact('user'),
                $this->warehouseDashboardData()
            ));
        } else if (in_array($user['type'], [15, 33])) {
            return view('accouting.dashboard', compact('user'));
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
                'receipts_today' => Lpb::whereDate('tanggal', today())->count(),
                'issues_today' => Npk::whereDate('tanggal', today())->count(),
                'open_opnames' => StockOpname::whereIn('status', [
                    StockOpname::DRAFT,
                    StockOpname::REJECTED,
                    StockOpname::SUBMITTED,
                    StockOpname::APPROVED,
                ])->count(),
                'service_baps_today' => ServiceBap::whereDate('tanggal', today())->count(),
            ],
            'recentReceipts' => Lpb::with('pembelian.supplier')
                ->latest('tanggal')->latest('id')->limit(5)->get(),
            'recentIssues' => Npk::with('barang')
                ->latest('tanggal')->latest('id')->limit(5)->get(),
        ];
    }

    public function logout(Request $request)
    {
        $request->session()->forget('jwt_token');
        $request->session()->forget('user_data');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function purchasingDashboardData(): array
    {
        $metrics = [
            'pending_requests' => RequestModel::where('status', 'pending')->count(),
            'approved_unrealized' => DB::table('request_details')
                ->join('requests', 'requests.id', '=', 'request_details.request_id')
                ->where('requests.status', 'approved')
                ->whereRaw('COALESCE(request_details.realisasi, 0) < COALESCE(request_details.jumlah_acc, request_details.jumlah_minta)')
                ->count(),
            'open_purchase_orders' => Pembelian::where('status', '!=', 2)->count(),
            'awaiting_receipt' => Pembelian::where('status', '!=', 2)
                ->whereHas('details', fn($query) => $query->whereColumn('diterima', '<', 'jumlah'))
                ->count(),
            'unbilled_receipts' => Lpb::whereNull('no_invoice')->count(),
            'unpaid_invoices' => Invoicelpb::where('is_void', false)->where('sisa_tagihan', '>', 0)->count(),
            'overdue_invoices' => Invoicelpb::where('is_void', false)->where('sisa_tagihan', '>', 0)
                ->whereDate('tgl_deadline_pembayaran', '<', today())->count(),
            'stock_attention' => Bahan::whereColumn('stok_onhand', '<', 'planning')->count(),
        ];

        $pendingRequests = RequestModel::withCount('details')
            ->where('status', 'pending')->latest()->limit(5)->get();

        $openPurchaseOrders = Pembelian::with('supplier')
            ->withSum('details as ordered_quantity', 'jumlah')
            ->withSum('details as received_quantity', 'diterima')
            ->where('status', '!=', 2)->latest('tanggal')->limit(5)->get();

        $unbilledReceipts = Lpb::with('pembelian.supplier')
            ->whereNull('no_invoice')->latest('tanggal')->limit(5)->get();

        $dueInvoices = Invoicelpb::with('supplier')->where('is_void', false)
            ->where('sisa_tagihan', '>', 0)
            ->orderByRaw('tgl_deadline_pembayaran IS NULL')
            ->orderBy('tgl_deadline_pembayaran')->limit(5)->get();

        return compact('metrics', 'pendingRequests', 'openPurchaseOrders', 'unbilledReceipts', 'dueInvoices');
    }

    private function paymentDashboardData(): array
    {
        $activeInvoices = Invoicelpb::query()->where('is_void', false)->where('sisa_tagihan', '>', 0);
        $metrics = [
            'unpaid_count' => (clone $activeInvoices)->count(),
            'outstanding_value' => (float) (clone $activeInvoices)->sum('sisa_tagihan'),
            'overdue_count' => (clone $activeInvoices)->whereDate('tgl_deadline_pembayaran', '<', today())->count(),
            'due_soon_count' => (clone $activeInvoices)
                ->whereBetween('tgl_deadline_pembayaran', [today(), today()->addDays(7)])->count(),
            'paid_this_month' => (float) Invoicelpbdetail::query()->where('is_void', false)
                ->whereBetween('tanggal_pembayaran', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('jumlah_pembayaran'),
            'payments_this_month' => Invoicelpbdetail::query()->where('is_void', false)
                ->whereBetween('tanggal_pembayaran', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count(),
        ];

        $priorityInvoices = Invoicelpb::with('supplier')->where('is_void', false)
            ->where('sisa_tagihan', '>', 0)
            ->orderByRaw('tgl_deadline_pembayaran IS NULL')
            ->orderBy('tgl_deadline_pembayaran')
            ->limit(8)->get();

        $recentPayments = Invoicelpbdetail::with(['invoice.supplier', 'coaKasBank'])
            ->where('is_void', false)->latest('tanggal_pembayaran')->latest('id')->limit(8)->get();

        return compact('metrics', 'priorityInvoices', 'recentPayments');
    }
}
