<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperasiBomRequest;
use App\Http\Requests\StorePusatKerjaRequest;
use App\Models\Bom;
use App\Models\DataPesanan;
use App\Models\Gudang;
use App\Models\PusatKerja;
use App\Services\RoutingProduksiService;
use Illuminate\Http\Request;
use RuntimeException;

class RoutingProduksiController extends Controller
{
    public function __construct(private RoutingProduksiService $routing) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PusatKerja::class);

        $bomId = $request->integer('bom') ?: null;
        $bom = $bomId ? Bom::with('operasi.pusatKerja', 'bahan')->find($bomId) : null;
        $pesananId = $request->integer('pesanan') ?: null;
        $pesanan = $pesananId ? DataPesanan::with('bahanHasil')->find($pesananId) : null;

        return view('routing_produksi.index', [
            'pusatKerja' => PusatKerja::with('gudang')->orderBy('kode')->get(),
            'gudang' => Gudang::where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
            'bomPilihan' => Bom::with('bahan')->orderBy('kode')->get(),
            'bom' => $bom,
            'pesananPilihan' => DataPesanan::with('bahanHasil')
                ->whereNotIn('status', [DataPesanan::DIBATALKAN])
                ->orderByDesc('id')->limit(100)->get(),
            'pesanan' => $pesanan,
            'routing' => $pesanan ? $this->routing->routing($pesanan) : null,
            'beban' => $this->routing->bebanPusatKerja(),
        ]);
    }

    public function storePusatKerja(StorePusatKerjaRequest $request)
    {
        try {
            $this->routing->simpanPusatKerja($request->validated(), $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['pusat_kerja' => $e->getMessage()]);
        }

        return back()->with('success', 'Pusat kerja ditambahkan.');
    }

    public function statusPusatKerja(Request $request, PusatKerja $pusatKerja)
    {
        $this->authorize('update', $pusatKerja);

        try {
            $this->routing->ubahStatusPusatKerja($pusatKerja, (string) $request->input('status', PusatKerja::NONAKTIF));
        } catch (RuntimeException $e) {
            return back()->withErrors(['pusat_kerja' => $e->getMessage()]);
        }

        return back()->with('success', "Status pusat kerja {$pusatKerja->kode} diperbarui.");
    }

    public function storeOperasi(StoreOperasiBomRequest $request, Bom $bom)
    {
        try {
            $this->routing->simpanOperasi($bom, $request->validated()['operasi']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['operasi' => $e->getMessage()]);
        }

        return back()->with('success', "Routing BOM {$bom->kode} disimpan.");
    }
}
