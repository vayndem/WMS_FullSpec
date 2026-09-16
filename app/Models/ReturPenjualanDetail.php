<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturPenjualanDetail extends Model
{
    protected $table = 'wms_retur_penjualan_detail';

    protected $fillable = [
        'retur_penjualan_id', 'surat_jalan_detail_id', 'bahan_id',
        'jumlah', 'harga_satuan', 'total_harga', 'hpp',
    ];

    protected $casts = [
        'jumlah' => 'decimal:6',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'hpp' => 'decimal:2',
    ];

    public function retur(): BelongsTo
    {
        return $this->belongsTo(ReturPenjualan::class, 'retur_penjualan_id');
    }

    public function suratJalanDetail(): BelongsTo
    {
        return $this->belongsTo(SuratJalanDetail::class, 'surat_jalan_detail_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
