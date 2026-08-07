<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemeriksaanConsider extends Model
{
    public const DRAFT = 'DRAFT';
    public const DIKONFIRMASI = 'DIKONFIRMASI';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date', 'dikonfirmasi_pada' => 'datetime'];
    public function gudangConsider()
    {
        return $this->belongsTo(Gudang::class, 'gudang_consider_id');
    }
    public function gudangBaik()
    {
        return $this->belongsTo(Gudang::class, 'gudang_baik_id');
    }
    public function gudangRusak()
    {
        return $this->belongsTo(Gudang::class, 'gudang_rusak_id');
    }
    public function details()
    {
        return $this->hasMany(DetailPemeriksaanConsider::class);
    }
}
