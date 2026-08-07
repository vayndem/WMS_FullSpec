<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DetailPemeriksaanConsider extends Model
{
    protected $guarded=['id']; protected $casts=['jumlah_diperiksa'=>'decimal:6','jumlah_baik'=>'decimal:6','jumlah_rusak'=>'decimal:6'];
    public function pemeriksaan(){ return $this->belongsTo(PemeriksaanConsider::class,'pemeriksaan_consider_id'); }
    public function bahan(){ return $this->belongsTo(Bahan::class); }
}
