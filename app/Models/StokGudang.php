<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['stok_tersedia' => 'decimal:6', 'stok_direservasi' => 'decimal:6', 'stok_dipesan' => 'decimal:6'];
    protected $appends = ['stok_bebas'];

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
}
