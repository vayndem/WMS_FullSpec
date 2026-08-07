<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePoDetail extends Model
{
    public const OPERATIONAL = 'SERVICE_OPERATIONAL';
    public const PRODUCTION = 'SERVICE_PRODUCTION';
    protected $guarded = [];
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'accepted_amount' => 'decimal:2',
    ];
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'id_kategori');
    }
    public function bapDetails(): HasMany
    {
        return $this->hasMany(ServiceBapDetail::class);
    }
}
