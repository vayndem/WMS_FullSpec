<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;

    public const NORMAL = 'NORMAL';
    public const CONSIDER = 'CONSIDER';
    public const RUSAK = 'RUSAK';

    protected $table = 'gudangs';
    protected $guarded = ['id'];
    protected $casts = [
        'aktif' => 'boolean',
        'boleh_penerimaan' => 'boolean',
        'boleh_npk' => 'boolean',
        'boleh_transfer' => 'boolean',
        'boleh_opname' => 'boolean',
    ];

    public function stok() { return $this->hasMany(StokGudang::class); }
    public function pembagian() { return $this->hasMany(PembagianGudang::class); }
    public function pengaturanBahan() { return $this->hasMany(PengaturanBahanGudang::class); }
    public function pembelians() { return $this->hasMany(Pembelian::class); }
    public function lpbs() { return $this->hasMany(Lpb::class); }
}
