<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipePembebanan extends Model
{
    use HasFactory;

    protected $table = 'tipe_pembebanans';

    protected $fillable = [
        'nama_tipe',
        'keterangan',
    ];

    public function kategoriBahan(): HasMany
    {
        return $this->hasMany(KategoriBahan::class, 'tipe_pembebanan_id');
    }
}
