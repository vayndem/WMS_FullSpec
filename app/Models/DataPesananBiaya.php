<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataPesananBiaya extends Model
{
    public const NPK = 'NPK';
    public const JASA = 'JASA';

    protected $table = 'wms_data_pesanan_biaya';

    protected $fillable = [
        'data_pesanan_id', 'sumber', 'referensi_id', 'tanggal', 'nilai', 'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nilai' => 'decimal:2',
    ];

    public function dataPesanan(): BelongsTo
    {
        return $this->belongsTo(DataPesanan::class, 'data_pesanan_id');
    }
}
