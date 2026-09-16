<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFakturPenjualanRequest;
use App\Http\Requests\StoreReturPenjualanRequest;
use App\Models\SuratJalan;
use App\Services\FakturPenjualanService;
use App\Services\PenjualanService;
use App\Services\ReturPenjualanService;
use Illuminate\Http\Request;
use RuntimeException;

class SuratJalanController extends Controller
{
    public function __construct(
        private PenjualanService $penjualan,
        private FakturPenjualanService $faktur,
        private ReturPenjualanService $retur,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', SuratJalan::class);

        $status = $request->input('status');

        $suratJalan = SuratJalan::with(['pelanggan', 'gudang', 'pesanan'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('surat_jalan.index', compact('suratJalan', 'status'));
    }

    public function show(SuratJalan $suratJalan)
    {
        $this->authorize('view', $suratJalan);

        $suratJalan->load(['pelanggan', 'gudang', 'pesanan', 'details.bahan', 'details.alokasi.layer']);

        return view('surat_jalan.show', [
            'suratJalan' => $suratJalan,
            'belumTerfaktur' => $this->faktur->sisaBelumTerfaktur($suratJalan),
        ]);
    }

    public function post(Request $request, SuratJalan $suratJalan)
    {
        $this->authorize('post', $suratJalan);

        try {
            $this->penjualan->postingSuratJalan($suratJalan, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['surat_jalan' => $e->getMessage()]);
        }

        return back()->with('success', 'Surat jalan diposting: stok berkurang dan jurnal harga pokok terbentuk.');
    }

    public function faktur(StoreFakturPenjualanRequest $request, SuratJalan $suratJalan)
    {
        try {
            $faktur = $this->faktur->buatDariSuratJalan($suratJalan, $request->validated(), $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['surat_jalan' => $e->getMessage()]);
        }

        return redirect()->route('faktur-penjualan.show', $faktur)
            ->with('success', "Faktur penjualan {$faktur->nomor} dibuat sebagai draft.");
    }

    public function retur(StoreReturPenjualanRequest $request, SuratJalan $suratJalan)
    {
        try {
            $retur = $this->retur->buat($suratJalan, $request->validated(), $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['surat_jalan' => $e->getMessage()]);
        }

        return back()->with('success', "Retur penjualan {$retur->nomor} tercatat dan stok dikembalikan.");
    }
}
