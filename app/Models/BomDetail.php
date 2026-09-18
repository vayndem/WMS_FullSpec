<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomDetail extends Model
{
    protected $table = 'wms_bom_detail';

    protected $fillable = ['bom_id', 'bahan_id', 'jumlah', 'satuan', 'catatan'];

    protected $casts = ['jumlah' => 'decimal:6'];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
