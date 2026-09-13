<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLampiran;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanKualitas extends Model
{
    use Auditable, HasLampiran;
    protected $table = 'wms_pemeriksaan_kualitas';
    protected $guarded = ['id'];
    protected $casts = ['inspected_at' => 'datetime'];
    public function lpb()
    {
        return $this->belongsTo(PenerimaanBarang::class);
    }
    public function lines()
    {
        return $this->hasMany(PemeriksaanKualitasDetail::class, 'quality_inspection_id');
    }
}
