<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LpbDetail extends Model
{
    use HasFactory;

    protected $table = 'lpb_details';

    protected $fillable = [
        'id_lpb',
        'id_bahan',
        'id_kategori',
        'jumlah_barang_diterima',
        'lot_number',
        'harga',
        'nilai_awal',
        'jumlah_dipakai',
        'jumlah_tersisa',
        'flag_dipakai',
    ];

    protected $casts = [
        'jumlah_barang_diterima' => 'decimal:6',
        'jumlah_dipakai' => 'decimal:6',
        'jumlah_tersisa' => 'decimal:6',
        'harga' => 'decimal:2',
        'nilai_awal' => 'decimal:2',
    ];

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'id_bahan', 'id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'id_kategori', 'id');
    }

    public function lpb(): BelongsTo
    {
        return $this->belongsTo(Lpb::class, 'id_lpb', 'id_lpb');
    }
}
