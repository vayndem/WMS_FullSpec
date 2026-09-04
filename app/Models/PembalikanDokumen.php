<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembalikanDokumen extends Model
{
    protected $table = 'wms_pembalikan_dokumen';
    protected $guarded = ['id'];
    protected $casts = ['posted_at' => 'datetime'];
}
