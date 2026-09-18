<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperasiBom extends Model
{
    protected $table = 'wms_bom_operasi';

    protected $fillable = ['bom_id', 'pusat_kerja_id', 'urutan', 'nama_operasi', 'waktu_standar_menit', 'catatan'];

    protected $casts = ['waktu_standar_menit' => 'decimal:2'];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function pusatKerja(): BelongsTo
    {
        return $this->belongsTo(PusatKerja::class, 'pusat_kerja_id');
    }
}
