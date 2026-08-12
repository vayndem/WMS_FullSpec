<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wms\StoreInventoryLotRequest;
use App\Http\Requests\Wms\StoreInventorySerialRequest;
use App\Http\Requests\Wms\StoreWarehouseLocationRequest;
use App\Http\Requests\Wms\UpdateInventoryLotBlockRequest;
use App\Models\InventoryLot;
use App\Models\InventorySerial;
use App\Models\WarehouseLocation;
use Illuminate\Http\RedirectResponse;

class WmsTraceabilityController extends Controller
{
    public function storeLocation(StoreWarehouseLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isWarehouse() || $request->user()->canAccessGudang((int) $data['gudang_id']), 403);

        WarehouseLocation::create($data);

        return back()->with('success', 'Lokasi gudang dibuat.');
    }

    public function storeLot(StoreInventoryLotRequest $request): RedirectResponse
    {
        InventoryLot::create($request->validated());

        return back()->with('success', 'Lot inventory dibuat.');
    }

    public function storeSerial(StoreInventorySerialRequest $request): RedirectResponse
    {
        InventorySerial::create($request->validated());

        return back()->with('success', 'Serial number ditambahkan ke lot.');
    }

    public function updateLotBlock(UpdateInventoryLotBlockRequest $request, InventoryLot $lot): RedirectResponse
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
