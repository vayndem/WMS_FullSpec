<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPOModel extends Model
{
    use HasFactory;

    protected $table = 'inv_podetail';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'double';
    public $timestamps = true;

    protected $fillable = [
        'no_po',
        'id_bahan',
        'jumlah',
        'harga',
        'exclude',
        'ppn',
        'include',
        'diterima',
        'created_at',
        'updated_at',
        'id_permintaan',
        'jenis'
    ];
    public static function detail($nomorpo)
    {
        return self::where('no_po', $nomorpo)
            ->join('bahan', 'inv_podetail.id_bahan', '=', 'bahan.id')
            ->select(
                'inv_podetail.*',
                'bahan.nama',
                'bahan.keterangan_bahan',
                'bahan.satuan'
            )
            ->get();
    }
    public function bahan()
    {
        // 'id_bahan' di tabel ini terhubung dengan 'id' di tabel bahan.
        return $this->belongsTo(Bahan::class, 'id_bahan', 'id');
    }
}
