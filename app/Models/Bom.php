<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    use Auditable;

    public const AKTIF = 'AKTIF';
    public const NONAKTIF = 'NONAKTIF';

    protected $table = 'wms_bom';

    protected $fillable = [
        'kode', 'nama', 'bahan_id', 'versi', 'jumlah_hasil', 'status', 'catatan', 'dibuat_oleh',
    ];

    protected $casts = [
        'jumlah_hasil' => 'decimal:6',
    ];

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(BomDetail::class, 'bom_id');
    }

    public function isAktif(): bool
    {
        return $this->status === self::AKTIF;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', self::AKTIF);
    }
}
