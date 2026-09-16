<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanLaporanKeuangan extends Model
{
    protected $table = 'wms_catatan_laporan_keuangan';

    protected $fillable = [
        'periode_dari',
        'periode_sampai',
        'gambaran_umum',
        'kebijakan_akuntansi',
        'peristiwa_setelah_periode',
        'komitmen_kontinjensi',
        'disusun_oleh',
    ];

    protected $casts = [
        'periode_dari' => 'date',
        'periode_sampai' => 'date',
    ];

    public const BAGIAN_NARATIF = [
        'gambaran_umum' => 'Gambaran Umum Entitas',
        'kebijakan_akuntansi' => 'Ikhtisar Kebijakan Akuntansi',
        'peristiwa_setelah_periode' => 'Peristiwa Setelah Periode Pelaporan',
        'komitmen_kontinjensi' => 'Komitmen dan Kontinjensi',
    ];

    public function penyusun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disusun_oleh');
    }
}
