<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlokasiTransferGudang extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['jumlah' => 'decimal:6', 'harga_satuan' => 'decimal:4', 'total_nilai' => 'decimal:2'];
    public function detail()
    {
        return $this->belongsTo(DetailTransferGudang::class, 'detail_transfer_gudang_id');
    }
    public function layerAsal()
    {
        return $this->belongsTo(InventoryLayer::class, 'inventory_layer_asal_id');
    }
    public function layerTujuan()
    {
        return $this->belongsTo(InventoryLayer::class, 'inventory_layer_tujuan_id');
    }
}
