<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permintaan extends Model
{
    use HasFactory;

    protected $table = 'permintaan';

    protected $fillable = [
        'id_bahan',
        'jumlah_order',
        'realisasi',
        'finish',
        'created_at',
        'updated_at'
    ];

    // Relasi dengan model Bahan
    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'id_bahan', 'id');
    }
}
