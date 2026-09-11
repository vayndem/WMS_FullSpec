<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerhitunganPajakPenghasilan extends Model
{
    protected $table = 'wms_perhitungan_pajak_penghasilan';
    protected $guarded = ['id'];
    protected $casts = ['period_start' => 'date', 'period_end' => 'date'];

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'journal_id');
    }

    public function kompensasi()
    {
        return $this->hasMany(KompensasiKerugianFiskal::class, 'perhitungan_id');
    }
}
