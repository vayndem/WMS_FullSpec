<?php

namespace App\Services;

use App\Models\MutasiStok;
use App\Models\PengaturanBahanGudang;
use App\Models\ReplenishmentSuggestion;
use App\Models\StokGudang;
use App\Models\InventoryLayer;

class ReplenishmentService
{
    public function calculate(?int $warehouseId = null): int
    {
        $settings = PengaturanBahanGudang::where('aktif', true)->when($warehouseId, fn ($query) => $query->where('gudang_id', $warehouseId))->get();
        foreach ($settings as $setting) {
            $usage = (float) MutasiStok::where('gudang_id', $setting->gudang_id)->where('bahan_id', $setting->bahan_id)->where('tanggal', '>=', now()->subDays(30))->sum('jumlah_keluar');
            $daily = $usage / 30;
            $available = (float) InventoryLayer::where('gudang_id', $setting->gudang_id)->where('bahan_id', $setting->bahan_id)->where('stock_status', 'AVAILABLE')->where('remaining_quantity', '>', 0)->where(function ($query) {
                $query->whereNull('inventory_lot_id')->orWhereHas('lot', fn ($lot) => $lot->where('blocked', false)->where(fn ($expiry) => $expiry->whereNull('expires_at')->orWhereDate('expires_at', '>=', today())));
            })->sum('remaining_quantity');
            $reorderPoint = max((float) $setting->titik_pemesanan, (float) $setting->stok_pengaman);
            $target = max((float) $setting->stok_maksimum, $reorderPoint);
            $suggested = $available <= $reorderPoint ? max(0, $target - $available) : 0;
            ReplenishmentSuggestion::updateOrCreate(['gudang_id' => $setting->gudang_id, 'bahan_id' => $setting->bahan_id, 'calculated_at' => today()], ['average_daily_usage' => $daily, 'lead_time_days' => $daily > 0 ? (int) ceil($reorderPoint / $daily) : 0, 'available_quantity' => $available, 'suggested_quantity' => $suggested, 'priority' => $available <= (float) $setting->stok_pengaman ? 'CRITICAL' : ($suggested > 0 ? 'HIGH' : 'NORMAL'), 'status' => $suggested > 0 ? 'OPEN' : 'NO_ACTION']);
        }
        return $settings->count();
    }
}
