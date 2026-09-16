<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengeluaranBarangDetail extends Model
{
    protected $table = 'wms_pengeluaran_barang_detail';
    protected $guarded = ['id'];

    public const DI_VENDOR = 'DI_VENDOR';
    public const KEMBALI = 'KEMBALI';
    public const TIDAK_KEMBALI = 'TIDAK_KEMBALI';

    protected $casts = ['jumlah' => 'decimal:6', 'tanggal_kembali' => 'date'];

    public function pengeluaran(): BelongsTo
    {
        return $this->belongsTo(PengeluaranBarang::class, 'pengeluaran_id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }
}
