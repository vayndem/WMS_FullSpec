<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanDetail extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_detail';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    // Relasi ke Header Pengajuan
    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id', 'id');
    }

    public function bahan()
    {
        // Pastikan Anda punya model Bahan.php
        return $this->belongsTo(Bahan::class, 'id_bahan', 'id');
    }
}