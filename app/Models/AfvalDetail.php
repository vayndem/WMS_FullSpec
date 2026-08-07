<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfvalDetail extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'afval_detail';

    // Kolom yang bisa diisi secara massal
    protected $fillable = [
        'kode_afval',
        'tipe',
        'berat',
        'harga_satuan',
    ];
    protected $casts = [
        'harga_satuan' => 'decimal:4',
    ];
}
