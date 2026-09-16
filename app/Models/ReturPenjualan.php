<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturPenjualan extends Model
{
    use Auditable;

    public const POSTED = 'POSTED';
    public const REVERSED = 'REVERSED';

    protected $table = 'wms_retur_penjualan';

    protected $fillable = [
        'nomor', 'tanggal', 'surat_jalan_id', 'pelanggan_id', 'faktur_penjualan_id',
        'total_dpp', 'total_ppn', 'total_hpp', 'status', 'alasan', 'journal_id', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_dpp' => 'decimal:2',
        'total_ppn' => 'decimal:2',
        'total_hpp' => 'decimal:2',
    ];

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class, 'surat_jalan_id');
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function faktur(): BelongsTo
    {
        return $this->belongsTo(FakturPenjualan::class, 'faktur_penjualan_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ReturPenjualanDetail::class, 'retur_penjualan_id');
    }
}
