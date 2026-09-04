<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FakturPembelianPenerimaan extends Model
{
    protected $table = 'wms_faktur_pembelian_penerimaan';
    protected $fillable = ['invoice_lpb_id', 'lpb_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FakturPembelian::class, 'invoice_lpb_id');
    }

    public function lpb(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarang::class, 'lpb_id');
    }
}
