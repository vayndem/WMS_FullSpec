<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KapitalisasiAset extends Model
{
    protected $table = 'wms_kapitalisasi_aset';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'nilai' => 'decimal:2',
        'nilai_sebelum' => 'decimal:2',
        'nilai_sesudah' => 'decimal:2',
    ];

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class, 'journal_id');
    }
}
