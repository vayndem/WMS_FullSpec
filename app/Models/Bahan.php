<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bahan extends Model
{
    use HasFactory;

    protected $table = 'bahans';

    protected $guarded = ['id'];

    public function kategoriBahan(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'kategori');
    }

    public function tipeBarang(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'tipe_barang');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'tipe_gudang');
    }

    public function inventoryLayers(): HasMany
    {
        return $this->hasMany(InventoryLayer::class, 'bahan_id');
    }

    public function stokGudangs(): HasMany
    {
        return $this->hasMany(StokGudang::class, 'bahan_id');
    }

    public function hasSmallUnit(): bool
    {
        return filled($this->satuan_kecil) && (float) $this->berat_kecil > 1;
    }

    public function toStockQuantity(float $transactionQuantity): float
    {
        return $this->hasSmallUnit()
            ? round($transactionQuantity / (float) $this->berat_kecil, 6)
            : round($transactionQuantity, 6);
    }

    public function smallUnitEquivalent(float $stockQuantity): ?float
    {
        return $this->hasSmallUnit()
            ? round($stockQuantity * (float) $this->berat_kecil, 6)
            : null;
    }
}
