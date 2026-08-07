<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BahanProduksi extends Model
{
    protected $table = 'bahan_produksi';

    protected $fillable = [
        'kategori',
        'id_gudang',
        'nama',
        'untuk_npk',
        'kode',
        'jumlah',
        'dipakai',
        'satuan'
    ];

    protected $casts = [
        'untuk_npk' => 'array',
        'jumlah'    => 'float',
        'dipakai'   => 'float',
    ];

    public $timestamps = true;

    public function detailBahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'nama', 'id');
    }

    public function detailPemakaian(): HasMany
    {
        return $this->hasMany(BahanProduksiDetail::class, 'id_produksi', 'id')->orderBy('created_at', 'desc');
    }
}
