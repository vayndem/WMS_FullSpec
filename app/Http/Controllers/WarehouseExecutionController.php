<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wms\InspectLpbRequest;
use App\Http\Requests\Wms\PutawayLpbRequest;
use App\Http\Requests\Wms\StoreInventoryReservationRequest;
use App\Http\Requests\Wms\WarehouseActionRequest;
use App\Models\InventoryReservation;
use App\Models\Lpb;
use App\Models\PickingOrder;
use App\Models\WarehouseLocation;
use App\Services\ReplenishmentService;
use App\Services\WarehouseExecutionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

class WarehouseExecutionController extends Controller
{
    public function inspect(InspectLpbRequest $request, Lpb $lpb, WarehouseExecutionService $service): RedirectResponse
    {
        $this->ensureWarehouseAccess($request, (int) $lpb->gudang_id);
        $service->inspect($lpb, $request->validated('decisions'));

        return back()->with('success', 'Pemeriksaan QC diselesaikan.');
    }

    public function putaway(PutawayLpbRequest $request, Lpb $lpb, WarehouseExecutionService $service): RedirectResponse
    {
        $this->ensureWarehouseAccess($request, (int) $lpb->gudang_id);
        $location = WarehouseLocation::findOrFail($request->integer('warehouse_location_id'));
        abort_unless((int) $location->gudang_id === (int) $lpb->gudang_id, 422, 'Lokasi putaway harus berada di gudang penerimaan.');
        $service->putaway($lpb, $location);

        return back()->with('success', 'Putaway LPB selesai.');
    }

    public function reserve(StoreInventoryReservationRequest $request, WarehouseExecutionService $service): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureWarehouseAccess($request, (int) $data['gudang_id']);
        $service->reserve((int) $data['gudang_id'], (int) $data['bahan_id'], (float) $data['quantity'], $data['reference_type'] ?? null, $data['reference_id'] ?? null);

        return back()->with('success', 'Stok berhasil direservasi.');
    }

    public function releaseReservation(WarehouseActionRequest $request, InventoryReservation $reservation, WarehouseExecutionService $service): RedirectResponse
    {
        $this->ensureWarehouseAccess($request, (int) $reservation->gudang_id);
        $service->release($reservation);

        return back()->with('success', 'Reservasi dilepas.');
    }

    public function createPick(WarehouseActionRequest $request, InventoryReservation $reservation, WarehouseExecutionService $service): RedirectResponse
    {
        $this->ensureWarehouseAccess($request, (int) $reservation->gudang_id);
        $service->createPick($reservation);

        return back()->with('success', 'Picking order FEFO/FIFO dibuat.');
    }

    public function completePick(WarehouseActionRequest $request, PickingOrder $pickingOrder, WarehouseExecutionService $service): RedirectResponse
    {
        $this->ensureWarehouseAccess($request, (int) $pickingOrder->gudang_id);
        $service->completePick($pickingOrder);

        return back()->with('success', 'Picking selesai dan siap diterbitkan melalui NPK.');
    }

    public function replenish(WarehouseActionRequest $request, ReplenishmentService $service): RedirectResponse
    {
        $gudangId = $request->integer('gudang_id') ?: null;
        if ($gudangId !== null) {
            $this->ensureWarehouseAccess($request, $gudangId);
        }
        $count = $service->calculate($gudangId);

        return back()->with('success', "Planning dihitung ulang untuk {$count} bahan-gudang.");
    }

    private function ensureWarehouseAccess(FormRequest $request, int $gudangId): void
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isWarehouse() || $request->user()->canAccessGudang($gudangId), 403);
    }
}
