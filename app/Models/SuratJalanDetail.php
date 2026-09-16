<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratJalanDetail extends Model
{
    protected $table = 'wms_surat_jalan_detail';

    protected $fillable = [
        'surat_jalan_id', 'pesanan_penjualan_detail_id', 'bahan_id', 'data_pesanan_id',
        'jumlah', 'jumlah_terfaktur', 'harga_satuan', 'hpp',
    ];

    protected $casts = [
        'jumlah' => 'decimal:6',
        'jumlah_terfaktur' => 'decimal:6',
        'harga_satuan' => 'decimal:2',
        'hpp' => 'decimal:2',
    ];

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class, 'surat_jalan_id');
    }

    public function pesananDetail(): BelongsTo
    {
        return $this->belongsTo(PesananPenjualanDetail::class, 'pesanan_penjualan_detail_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataPesanan(): BelongsTo
    {
        return $this->belongsTo(DataPesanan::class, 'data_pesanan_id');
    }

    public function alokasi(): HasMany
    {
        return $this->hasMany(SuratJalanAlokasi::class, 'surat_jalan_detail_id');
    }

    public function sisaFaktur(): float
    {
        return round((float) $this->jumlah - (float) $this->jumlah_terfaktur, 6);
    }
}
