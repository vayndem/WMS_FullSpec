<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DetailTransferGudang extends Model
{
    protected $guarded=['id']; protected $casts=['jumlah'=>'decimal:6'];
    public function transfer(){ return $this->belongsTo(TransferGudang::class,'transfer_gudang_id'); }
    public function bahan(){ return $this->belongsTo(Bahan::class); }
    public function alokasi(){ return $this->hasMany(AlokasiTransferGudang::class); }
}
