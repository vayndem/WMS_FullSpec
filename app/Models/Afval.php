<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Afval extends Model
{
    use HasFactory;

    protected $table = 'afval';

    protected $fillable = [
        'kode_afval',
        'nama',
        'alamat',
        'tanggal',
        'notes',
        'status_faktur',
    ];

    /**
     * Mendefinisikan relasi one-to-many ke AfvalDetail.
     * Satu transaksi afval memiliki banyak item detail.
     */
    public function details()
    {
        return $this->hasMany(AfvalDetail::class, 'kode_afval', 'kode_afval');
    }
}
