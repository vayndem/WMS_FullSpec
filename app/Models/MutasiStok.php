<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'datetime', 'jumlah_masuk' => 'decimal:6', 'jumlah_keluar' => 'decimal:6', 'saldo_sebelum' => 'decimal:6', 'saldo_setelah' => 'decimal:6', 'harga_satuan' => 'decimal:4', 'total_nilai' => 'decimal:2'];
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
