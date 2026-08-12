<?php

namespace App\Services;

use App\Models\InventoryLayer;
use App\Models\InventoryReservation;
use App\Models\Gudang;
use App\Models\Lpb;
use App\Models\PickingOrder;
use App\Models\QualityInspection;
use App\Models\StokGudang;
use App\Models\WarehouseLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseExecutionService
{
    public function __construct(private StokGudangService $stock, private DocumentNumberService $numbers) {}

    public function reserve(int $warehouseId, int $materialId, float $quantity, ?string $referenceType = null, ?int $referenceId = null): InventoryReservation
    {
        return DB::transaction(function () use ($warehouseId, $materialId, $quantity, $referenceType, $referenceId) {
            if ($quantity <= 0) throw new RuntimeException('Jumlah reservasi harus lebih besar dari nol.');
            $balance = $this->stock->saldo($warehouseId, $materialId);
            if ((float) $balance->stok_dapat_dipakai + .000001 < $quantity) throw new RuntimeException('Stok available, tidak expired, dan tidak diblokir tidak mencukupi untuk reservasi.');
            $balance->increment('stok_direservasi', $quantity);
            return InventoryReservation::create(['number' => $this->numbers->internal('RSV', 'STK'), 'gudang_id' => $warehouseId, 'bahan_id' => $materialId, 'quantity' => $quantity, 'reference_type' => $referenceType, 'reference_id' => $referenceId, 'created_by' => Auth::id()]);
        });
    }

    public function release(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $reservation = InventoryReservation::lockForUpdate()->findOrFail($reservation->id);
            if (!in_array($reservation->status, ['ACTIVE', 'PICKING', 'PICKED'], true)) throw new RuntimeException('Reservasi sudah tidak aktif.');
            $remaining = (float) $reservation->quantity;
            $balance = StokGudang::where('gudang_id', $reservation->gudang_id)->where('bahan_id', $reservation->bahan_id)->lockForUpdate()->firstOrFail();
            $balance->update(['stok_direservasi' => max(0, (float) $balance->stok_direservasi - $remaining)]);
            $reservation->update(['status' => 'RELEASED']);
        });
    }

    public function createPick(InventoryReservation $reservation): PickingOrder
    {
        return DB::transaction(function () use ($reservation) {
            $reservation = InventoryReservation::lockForUpdate()->findOrFail($reservation->id);
            if ($reservation->status !== 'ACTIVE') throw new RuntimeException('Reservasi tidak aktif.');
            $quantity = (float) $reservation->quantity - (float) $reservation->picked_quantity;
            $layers = InventoryLayer::with('lot')->where('gudang_id', $reservation->gudang_id)->where('bahan_id', $reservation->bahan_id)->where('stock_status', 'AVAILABLE')->where('remaining_quantity', '>', 0)
                ->orderByRaw('CASE WHEN inventory_lot_id IS NULL THEN 1 ELSE 0 END')->orderByRaw('(SELECT expires_at FROM inventory_lots WHERE inventory_lots.id = inventory_layers.inventory_lot_id)')->orderBy('transaction_date')->lockForUpdate()->get();
            if ((float) $layers->sum('remaining_quantity') + .000001 < $quantity) throw new RuntimeException('Layer tersedia tidak mencukupi untuk picking.');
            $pick = PickingOrder::create(['number' => $this->numbers->internal('PCK', 'STK'), 'gudang_id' => $reservation->gudang_id, 'status' => 'RELEASED', 'created_by' => Auth::id()]);
            $remaining = $quantity;
            foreach ($layers as $layer) {
                if ($remaining <= 0) break;
                $take = min($remaining, (float) $layer->remaining_quantity);
                $pick->lines()->create(['inventory_reservation_id' => $reservation->id, 'inventory_layer_id' => $layer->id, 'warehouse_location_id' => $layer->warehouse_location_id, 'quantity_requested' => $take]);
                $remaining -= $take;
            }
            $reservation->update(['status' => 'PICKING']);
            return $pick;
        });
    }

    public function completePick(PickingOrder $pick): void
    {
        DB::transaction(function () use ($pick) {
            $pick = PickingOrder::with('lines')->lockForUpdate()->findOrFail($pick->id);
            if ($pick->status !== 'RELEASED') throw new RuntimeException('Picking order tidak dapat diselesaikan.');
            foreach ($pick->lines as $line) {
                $line->update(['quantity_picked' => $line->quantity_requested]);
                $reservation = InventoryReservation::lockForUpdate()->findOrFail($line->inventory_reservation_id);
                $reservation->increment('picked_quantity', $line->quantity_requested);
                if ((float) $reservation->fresh()->picked_quantity + .000001 >= (float) $reservation->quantity) $reservation->update(['status' => 'PICKED']);
            }
            $pick->update(['status' => 'COMPLETED', 'picked_by' => Auth::id(), 'picked_at' => now()]);
        });
    }

    public function consumeReservation(InventoryReservation $reservation, int $warehouseId, int $materialId, float $quantity): void
    {
        $reservation = InventoryReservation::lockForUpdate()->findOrFail($reservation->id);
        if (!in_array($reservation->status, ['ACTIVE', 'PICKED'], true)) throw new RuntimeException('Reservasi tidak siap digunakan.');
        if ((int) $reservation->gudang_id !== $warehouseId || (int) $reservation->bahan_id !== $materialId || abs((float) $reservation->quantity - $quantity) > .000001) throw new RuntimeException('Gudang, bahan, atau jumlah NPK tidak sama dengan reservasi.');
        $balance = StokGudang::where('gudang_id', $warehouseId)->where('bahan_id', $materialId)->lockForUpdate()->firstOrFail();
        $balance->update(['stok_direservasi' => max(0, (float) $balance->stok_direservasi - $quantity)]);
        $reservation->update(['status' => 'CONSUMED']);
    }

    public function inspect(Lpb $lpb, array $decisions): QualityInspection
    {
        return DB::transaction(function () use ($lpb, $decisions) {
            $lpb = Lpb::with('details')->lockForUpdate()->findOrFail($lpb->id);
            if ($lpb->receiving_status !== 'RECEIVED') throw new RuntimeException('LPB hanya dapat diperiksa QC satu kali sebelum putaway.');
            $inspection = QualityInspection::create(['number' => $this->numbers->internal('QCI', 'WH'), 'lpb_id' => $lpb->id, 'status' => 'COMPLETED', 'inspected_by' => Auth::id(), 'inspected_at' => now()]);
            $hasRejected = false;
            foreach ($lpb->details as $detail) {
                $accepted = (float) data_get($decisions, $detail->id . '.accepted', $detail->jumlah_barang_diterima);
                $rejected = (float) $detail->jumlah_barang_diterima - $accepted;
                $hasRejected = $hasRejected || $rejected > 0;
                if ($accepted < 0 || $rejected < 0) throw new RuntimeException('Keputusan QC melebihi jumlah diterima.');
                $inspection->lines()->create(['lpb_detail_id' => $detail->id, 'quantity_received' => $detail->jumlah_barang_diterima, 'quantity_accepted' => $accepted, 'quantity_rejected' => $rejected, 'decision' => $rejected > 0 ? ($accepted > 0 ? 'PARTIAL' : 'REJECTED') : 'ACCEPTED', 'reason' => data_get($decisions, $detail->id . '.reason')]);
                $layer = InventoryLayer::where('source_type', 'LPB_DETAIL')->where('source_id', $detail->id)->lockForUpdate()->firstOrFail();
                if (abs((float) $layer->initial_quantity - (float) $layer->remaining_quantity) > .000001) throw new RuntimeException('Barang yang sudah digunakan tidak dapat masuk pemeriksaan QC penerimaan.');
                $consider = null;
                if ($rejected > 0) {
                    $consider = Gudang::where('jenis', Gudang::CONSIDER)->where('aktif', true)->first();
                    if (!$consider) throw new RuntimeException('Gudang Consider aktif wajib tersedia untuk barang QC reject.');
                    $this->stock->keluar((int) $lpb->gudang_id, (int) $detail->id_bahan, $rejected, (float) $layer->unit_cost, 'QC_REJECT_KELUAR', 'QUALITY_INSPECTION', $inspection->id, 'QC penerimaan', false);
                    $this->stock->masuk((int) $consider->id, (int) $detail->id_bahan, $rejected, (float) $layer->unit_cost, 'QC_HOLD_MASUK', 'QUALITY_INSPECTION', $inspection->id, 'Menunggu pemeriksaan Consider', false);
                }
                if ($accepted > 0 && $rejected > 0) {
                    $layer->update(['initial_quantity' => $accepted, 'remaining_quantity' => $accepted, 'stock_status' => 'AVAILABLE']);
                    InventoryLayer::create(['bahan_id' => $layer->bahan_id, 'gudang_id' => $consider->id, 'warehouse_location_id' => null, 'inventory_lot_id' => $layer->inventory_lot_id, 'stock_status' => 'QC_HOLD', 'source_type' => 'QC_REJECT_' . $inspection->id, 'source_id' => $detail->id, 'transaction_date' => $layer->transaction_date, 'initial_quantity' => $rejected, 'remaining_quantity' => $rejected, 'unit_cost' => $layer->unit_cost]);
                } else {
                    $layer->update(['gudang_id' => $rejected > 0 ? $consider->id : $layer->gudang_id, 'warehouse_location_id' => $rejected > 0 ? null : $layer->warehouse_location_id, 'stock_status' => $rejected > 0 ? 'QC_HOLD' : 'AVAILABLE']);
                }
            }
            $lpb->update(['receiving_status' => $hasRejected ? 'QC_COMPLETED_WITH_HOLD' : 'QC_COMPLETED']);
            return $inspection;
        });
    }

    public function putaway(Lpb $lpb, WarehouseLocation $location): void
    {
        DB::transaction(function () use ($lpb, $location) {
            $lpb = Lpb::with('details')->lockForUpdate()->findOrFail($lpb->id);
            $location = WarehouseLocation::findOrFail($location->id);
            if ((int) $location->gudang_id !== (int) $lpb->gudang_id || !$location->active) throw new RuntimeException('Lokasi putaway tidak valid untuk gudang LPB.');
            $detailIds = $lpb->details->pluck('id');
            if (InventoryLayer::where('source_type', 'LPB_DETAIL')->whereIn('source_id', $detailIds)->where('stock_status', 'QC_HOLD')->where('remaining_quantity', '>', 0)->exists()) throw new RuntimeException('Selesaikan keputusan barang QC hold sebelum putaway.');
            InventoryLayer::where('source_type', 'LPB_DETAIL')->whereIn('source_id', $detailIds)->update(['warehouse_location_id' => $location->id]);
            $lpb->update(['receiving_status' => 'PUTAWAY', 'putaway_by' => Auth::id(), 'putaway_at' => now()]);
        });
    }
}
