<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelianDetail extends Model
{
    use HasFactory;

    protected $table = 'retur_pembelian_details';

    protected $fillable = [
        'retur_pembelian_id',
        'lpb_detail_id',
        'jumlah_retur',
        'harga',
        'total_harga',
    ];

    protected $casts = [
        'jumlah_retur' => 'decimal:6',
        'harga' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    public function returPembelian()
    {
        return $this->belongsTo(ReturPembelian::class);
    }

    public function lpbDetail()
    {
        return $this->belongsTo(PenerimaanBarangDetail::class, 'lpb_detail_id');
    }
}
