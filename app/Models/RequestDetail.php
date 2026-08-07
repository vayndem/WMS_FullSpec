<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestDetail extends Model
{
    use HasFactory;

    protected $table = 'request_details';
    protected $guarded = ['id'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function kategoriBahan(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'kategori');
    }

    public function tipeBarang(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'tipe_barang');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'tipe_gudang');
    }

    public function pembelianDetails(): HasMany
    {
        return $this->hasMany(PembelianDetail::class, 'request_detail_id');
    }
}
