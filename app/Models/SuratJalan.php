<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLampiran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratJalan extends Model
{
    use Auditable, HasLampiran;

    public const DRAFT = 'DRAFT';
    public const POSTED = 'POSTED';
    public const REVERSED = 'REVERSED';

    protected $table = 'wms_surat_jalan';

    protected $fillable = [
        'nomor', 'tanggal', 'pesanan_penjualan_id', 'pelanggan_id', 'gudang_id',
        'nomor_kendaraan', 'pengirim', 'total_hpp', 'status', 'keterangan',
        'journal_id', 'dibuat_oleh', 'diposting_oleh', 'diposting_pada',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_hpp' => 'decimal:2',
        'diposting_pada' => 'datetime',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(PesananPenjualan::class, 'pesanan_penjualan_id');
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(SuratJalanDetail::class, 'surat_jalan_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::POSTED;
    }
}
