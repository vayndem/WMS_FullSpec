<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLpbReceipt extends Model
{
    protected $fillable = ['invoice_lpb_id', 'lpb_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceLpb::class, 'invoice_lpb_id');
    }

    public function lpb(): BelongsTo
    {
        return $this->belongsTo(Lpb::class, 'lpb_id');
    }
}
