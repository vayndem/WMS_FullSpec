<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerakitanKit extends Model
{
    use Auditable;

    protected $table = 'wms_perakitan_kit';
    protected $guarded = ['id'];

    public const DRAFT = 'DRAFT';
    public const POSTED = 'POSTED';
    public const DIBATALKAN = 'DIBATALKAN';

    public const RAKIT = 'RAKIT';
    public const URAI = 'URAI';

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_kit' => 'decimal:6',
        'nilai_total' => 'decimal:2',
        'diposting_pada' => 'datetime',
    ];

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class, 'kit_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PerakitanKitDetail::class, 'perakitan_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class, 'journal_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
