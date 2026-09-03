<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenerimaanJasaDetail extends Model
{
    protected $table = 'wms_penerimaan_jasa_detail';
    protected $guarded = [];
    protected $casts = ['progress_percent' => 'decimal:4', 'amount' => 'decimal:2'];
    public function lpb(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarang::class, 'lpb_id');
    }
    public function servicePoDetail(): BelongsTo
    {
        return $this->belongsTo(ServicePoDetail::class);
    }
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'id_kategori');
    }
    public function allocations(): HasMany
    {
        return $this->hasMany(PenerimaanJasaAlokasi::class, 'service_bap_detail_id');
    }
}
