<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GelombangPengambilan extends Model
{
    use Auditable;

    protected $table = 'wms_gelombang_pengambilan';
    protected $guarded = ['id'];

    public const DIRENCANAKAN = 'DIRENCANAKAN';
    public const DIRILIS = 'DIRILIS';
    public const SELESAI = 'SELESAI';
    public const DIBATALKAN = 'DIBATALKAN';

    public const STRATEGI = [
        'LOKASI' => 'Urut lokasi bin (jalur terpendek)',
        'BAHAN' => 'Kelompokkan per bahan',
    ];

    protected $casts = [
        'dirilis_pada' => 'datetime',
        'selesai_pada' => 'datetime',
    ];

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(PesananPengambilan::class, 'gelombang_id')->orderBy('urutan_dalam_gelombang');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_ke');
    }
}
