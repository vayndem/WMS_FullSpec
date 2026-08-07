<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNamagudang extends Model
{
    use HasFactory;

    protected $table = 'admin_namagudang';
    protected $guarded = [];

    public function bahans()
    {
        return $this->hasMany(Bahan::class, 'tipe_gudang');
    }
}
