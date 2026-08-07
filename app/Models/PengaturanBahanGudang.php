<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanBahanGudang extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['aktif' => 'boolean', 'stok_minimum' => 'decimal:6', 'stok_maksimum' => 'decimal:6', 'stok_pengaman' => 'decimal:6', 'titik_pemesanan' => 'decimal:6'];
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
}
