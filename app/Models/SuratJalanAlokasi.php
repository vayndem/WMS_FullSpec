<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratJalanAlokasi extends Model
{
    protected $table = 'wms_surat_jalan_alokasi';

    protected $fillable = [
        'surat_jalan_detail_id', 'inventory_layer_id', 'jumlah', 'harga_satuan', 'total_hpp',
    ];

    protected $casts = [
        'jumlah' => 'decimal:6',
        'harga_satuan' => 'decimal:4',
        'total_hpp' => 'decimal:2',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(SuratJalanDetail::class, 'surat_jalan_detail_id');
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(LayerPersediaan::class, 'inventory_layer_id');
    }
}
