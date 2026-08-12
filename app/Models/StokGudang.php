<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['stok_tersedia' => 'decimal:6', 'stok_direservasi' => 'decimal:6', 'stok_dipesan' => 'decimal:6'];
    protected $appends = ['stok_bebas', 'stok_dapat_dipakai'];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
    public function getStokBebasAttribute(): float
    {
        return (float) $this->stok_tersedia - (float) $this->stok_direservasi;
    }
    public function getStokDapatDipakaiAttribute(): float
    {
        $usable = InventoryLayer::where('gudang_id', $this->gudang_id)->where('bahan_id', $this->bahan_id)
            ->where('stock_status', 'AVAILABLE')->where('remaining_quantity', '>', 0)
            ->where(function ($query) {
                $query->whereNull('inventory_lot_id')->orWhereHas('lot', fn ($lot) => $lot
                    ->where('blocked', false)
                    ->where(fn ($expiry) => $expiry->whereNull('expires_at')->orWhereDate('expires_at', '>=', today())));
            })->sum('remaining_quantity');
        return max(0, (float) $usable - (float) $this->stok_direservasi);
    }
}
