<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferGudang extends Model
{
    public const DRAFT = 'DRAFT';
    public const DIAJUKAN = 'DIAJUKAN';
    public const DIKONFIRMASI = 'DIKONFIRMASI';
    public const DIBATALKAN = 'DIBATALKAN';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date', 'diajukan_pada' => 'datetime', 'dikonfirmasi_pada' => 'datetime'];
    public function gudangAsal()
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }
    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }
    public function details()
    {
        return $this->hasMany(DetailTransferGudang::class);
    }
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
