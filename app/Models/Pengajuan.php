<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    // Relasi ke Detail (Barang-barang yang dibeli)
    public function details()
    {
        return $this->hasMany(PengajuanDetail::class, 'pengajuan_id', 'id');
    }

    public function suplier()
    {
        // Pastikan Anda punya model Supplier.php
        return $this->belongsTo(Supplier::class, 'id_suplier', 'id');
    }
}