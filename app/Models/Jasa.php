<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jasa extends Model
{
    use HasFactory;

    protected $table = 'inv_jasa';
    protected $primaryKey = 'id';

    protected $fillable = [
        'no_jasa',
        'nama',
        'tanggal',
        'no_order',
        'untukperhatian',
        'term',
        'notes',
        'ppn',
        'totalexclude',
        'totalppn',
        'totalinclude',
        'diskon',
        'ongkir',
        'GrandTotalPembelian',
        'status',
        'user_id',
        'term_pengiriman',
        'Jenis',
        'inputlabel',
        'cetak',
        'kunci',
        'counter_asli',
        'cetak_ulang'
    ];
}
