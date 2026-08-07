<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminKategoribahan extends Model
{
    use HasFactory;

    protected $table = 'kategoribahan';
    protected $guarded = [];

    public function bahans(): HasMany
    {
        return $this->hasMany(Bahan::class, 'tipe_barang');
    }

    public function lpbDetails(): HasMany
    {
        return $this->hasMany(LpbDetail::class, 'id_kategori', 'id');
    }
}
