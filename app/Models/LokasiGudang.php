<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LokasiGudang extends Model
{
    use Auditable;
    protected $table = 'wms_lokasi_gudang';
    protected $guarded = ['id'];
    protected $attributes = ['type' => 'STORAGE', 'active' => true];
    protected $casts = ['active' => 'boolean', 'capacity' => 'decimal:6'];
    public function gudang() { return $this->belongsTo(Gudang::class); }
}
