<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kit extends Model
{
    use Auditable;

    protected $table = 'wms_kit';
    protected $guarded = ['id'];

    protected $casts = [
        'aktif' => 'boolean',
        'jumlah_hasil' => 'decimal:6',
    ];

    public function bahanHasil(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_hasil_id');
    }

    public function komponen(): HasMany
    {
        return $this->hasMany(KitKomponen::class, 'kit_id');
    }

    public function perakitan(): HasMany
    {
        return $this->hasMany(PerakitanKit::class, 'kit_id');
    }
}
