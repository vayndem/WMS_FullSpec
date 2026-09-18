<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKursPenutupRequest;
use App\Models\RevaluasiKurs;
use App\Services\RevaluasiKursService;
use Illuminate\Http\Request;
use RuntimeException;

class RevaluasiKursController extends Controller
{
    public function __construct(private RevaluasiKursService $revaluasi) {}

    public function index(Request $request)
    {
        $this->authorize('controlInventoryFinance');

        $periode = $this->periode($request);

        return view('revaluasi_kurs.index', [
            'periode' => $periode,
            'hasil' => $this->revaluasi->hitung($periode),
            'kurs' => $this->revaluasi->kursPenutup($periode),
            'riwayat' => RevaluasiKurs::with('faktur')->orderByDesc('periode')->orderByDesc('id')->limit(50)->get(),
        ]);
    }

    public function simpanKurs(StoreKursPenutupRequest $request)
    {
        $this->revaluasi->simpanKurs($request->validated(), $request->user());

        return back()->with('success', 'Kurs penutup disimpan.');
    }

    public function posting(Request $request)
    {
        $this->authorize('controlInventoryFinance');

        $periode = $this->periode($request);

        try {
            $hasil = $this->revaluasi->posting($periode, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['revaluasi' => $e->getMessage()]);
        }

        return back()->with('success', "Revaluasi kurs periode {$periode} diposting, selisih Rp " . number_format($hasil['total_selisih'], 2, ',', '.') . '.');
    }

    private function periode(Request $request): string
    {
        $periode = (string) $request->input('periode', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $periode) ? $periode : now()->format('Y-m');
    }
}
