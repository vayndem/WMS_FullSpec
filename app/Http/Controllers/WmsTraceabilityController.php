<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wms\StoreLotPersediaanRequest;
use App\Http\Requests\Wms\StoreSerialPersediaanRequest;
use App\Http\Requests\Wms\StoreLokasiGudangRequest;
use App\Http\Requests\Wms\UpdateLotPersediaanBlockRequest;
use App\Models\LotPersediaan;
use App\Models\SerialPersediaan;
use App\Models\LokasiGudang;
use Illuminate\Http\RedirectResponse;

class WmsTraceabilityController extends Controller
{
    public function storeLocation(StoreLokasiGudangRequest $request): RedirectResponse
    {
        $data = $request->validated();
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isWarehouse() || $request->user()->canAccessGudang((int) $data['gudang_id']), 403);

        LokasiGudang::create($data);

        return back()->with('success', 'Lokasi gudang dibuat.');
    }

    public function storeLot(StoreLotPersediaanRequest $request): RedirectResponse
    {
        LotPersediaan::create($request->validated());

        return back()->with('success', 'Lot inventory dibuat.');
    }

    public function storeSerial(StoreSerialPersediaanRequest $request): RedirectResponse
    {
        SerialPersediaan::create($request->validated());

        return back()->with('success', 'Serial number ditambahkan ke lot.');
    }

    public function updateLotBlock(UpdateLotPersediaanBlockRequest $request, LotPersediaan $lot): RedirectResponse
    {
        $data = $request->validated();
        $lot->update([
            'blocked' => $data['blocked'],
            'block_reason' => $data['blocked'] ? $data['block_reason'] : null,
            'quality_status' => $data['blocked'] ? 'BLOCKED' : 'RELEASED',
        ]);

        return back()->with('success', $data['blocked'] ? 'Lot diblokir dari pemakaian.' : 'Lot dirilis kembali.');
    }
}
