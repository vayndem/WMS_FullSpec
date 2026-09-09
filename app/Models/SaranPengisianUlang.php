<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaranPengisianUlang extends Model
{
    public const OPEN = 'OPEN';
    public const REQUESTED = 'REQUESTED';
    public const NO_ACTION = 'NO_ACTION';

    protected $table = 'wms_saran_pengisian_ulang';
    protected $guarded = ['id'];
    protected $casts = ['calculated_at' => 'date'];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }
}
