<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Services\SlottingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SlottingController extends Controller
{
    public function __construct(private SlottingService $slotting) {}

    public function index(Request $request)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);

        $gudangIds = $request->user()->accessibleGudangIds();
        $gudangs = Gudang::whereIn('id', $gudangIds)->where('aktif', true)->orderBy('nama')->get();
        $dipilih = (int) ($request->input('gudang_id') ?: $gudangs->first()->id ?? 0);

        abort_unless($dipilih === 0 || in_array($dipilih, $gudangIds, true), 403);

        return view('wms_control.slotting', [
            'gudangs' => $gudangs,
            'gudangDipilih' => $dipilih,
            'saran' => $dipilih ? $this->slotting->saran($dipilih) : collect(),
        ]);
    }
}
