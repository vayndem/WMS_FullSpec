<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrossDock extends Model
{
    use Auditable;

    public const DIRESERVASI = 'DIRESERVASI';
    public const DIKIRIM = 'DIKIRIM';
    public const DIBATALKAN = 'DIBATALKAN';

    protected $table = 'wms_cross_dock';

    protected $fillable = [
        'nomor', 'tanggal', 'penerimaan_barang_detail_id', 'pesanan_penjualan_detail_id',
        'gudang_id', 'bahan_id', 'jumlah', 'reservasi_id', 'status', 'keterangan',
        'dibuat_oleh', 'dikirim_pada',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:6',
        'dikirim_pada' => 'datetime',
    ];

    public function penerimaanDetail(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarangDetail::class, 'penerimaan_barang_detail_id');
    }

    public function pesananDetail(): BelongsTo
    {
        return $this->belongsTo(PesananPenjualanDetail::class, 'pesanan_penjualan_detail_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function reservasi(): BelongsTo
    {
        return $this->belongsTo(ReservasiPersediaan::class, 'reservasi_id');
    }

    public function isAktif(): bool
    {
        return $this->status === self::DIRESERVASI;
    }
}
