<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatatanCustomer extends Model
{
    use HasFactory;

    protected $table = 'catatan_customer';

    protected $fillable = [
        'id_bahan',
        'no_po',
        'id_lpb',
        'salah_spesifikasi',
        'jumlah_kurang',
        'rusak',
        'tidak_layak',
        'cover_rusak',
        'kemasan_bocor',
        'notes'
    ];
}
