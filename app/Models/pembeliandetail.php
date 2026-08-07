<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembeliandetail extends Model
{
    use HasFactory;

    protected $table = 'pembelian_details';
    protected $guarded = ['id'];

    protected $appends = ['id_bahan', 'id_permintaan'];

    public function getIdBahanAttribute(): ?int
    {
        return $this->attributes['bahan_id'] ?? null;
    }

    public function getIdPermintaanAttribute(): ?int
    {
        return $this->attributes['request_detail_id'] ?? null;
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'no_po', 'no_po');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function requestDetail(): BelongsTo
    {
        return $this->belongsTo(RequestDetail::class, 'request_detail_id');
    }
}
