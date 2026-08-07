<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengambilan extends Model
{
    use HasFactory;

    protected $table = 'npk_planning';

    protected $primaryKey = 'id';

    protected $fillable = [
        'kode',
        'kode_datapesanan',
        'tanggal',
        'id_barang',
        'id_gudang_asal',
        'id_gudang_tujuan',
        'jumlah',
        'jumlah_terkirim',
        'tgl_terkirim',
        'close',
        'keterangan',
        'id_user',
        'operator',
    ];

    public $timestamps = true;
}
