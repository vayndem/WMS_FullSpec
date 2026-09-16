<?php

namespace App\Http\Controllers;

use App\Models\StokGudang;
use App\Services\RekonsiliasiGudangService;

class RekonsiliasiGudangController extends Controller
{
    public function __construct(private RekonsiliasiGudangService $service) {}
    public const BARIS_MAKSIMAL = 200;

    public function index()
    {
        $this->authorize('reconcile', StokGudang::class);

        $ringkasan = $this->service->summary();
        $semua = $ringkasan['rows'];

        $hanyaSelisih = request()->boolean('hanya_selisih', $ringkasan['quantity_exceptions'] > 0 || $ringkasan['reservation_exceptions'] > 0);

        $ditampilkan = $hanyaSelisih
            ? $semua->filter(fn ($row) => abs((float) $row->selisih) > .000001 || abs((float) $row->selisih_reservasi) > .000001)->values()
            : $semua;

        $totalDitampilkan = $ditampilkan->count();

        return view('rekonsiliasi_gudangs.index', $ringkasan + [
            'financial' => request()->user()->isAccounting() || request()->user()->isSuperAdmin(),
            'rows' => $ditampilkan->take(self::BARIS_MAKSIMAL),
            'hanya_selisih' => $hanyaSelisih,
            'total_baris' => $semua->count(),
            'total_ditampilkan' => $totalDitampilkan,
            'batas_baris' => self::BARIS_MAKSIMAL,
        ]);
    }
}
