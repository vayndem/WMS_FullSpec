<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerakitanKitDetail extends Model
{
    protected $table = 'wms_perakitan_kit_detail';
    protected $guarded = ['id'];
    protected $casts = ['jumlah' => 'decimal:6', 'nilai' => 'decimal:2'];

    public function perakitan(): BelongsTo
    {
        return $this->belongsTo(PerakitanKit::class, 'perakitan_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
