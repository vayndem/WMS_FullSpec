<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BahanProduksiDetail extends Model
{
    protected $table = 'bahan_produksi_detail';

    protected $fillable = [
        'id_produksi',
        'dipakai',
        'data_pesanan',
        'keterangan'
    ];

    protected $casts = [
        'dipakai' => 'float',
    ];

    public $timestamps = true;

    public function bahanProduksi(): BelongsTo
    {
        return $this->belongsTo(BahanProduksi::class, 'id_produksi', 'id');
    }
}
