<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaranPengisianUlang extends Model
{
    protected $table = 'wms_saran_pengisian_ulang';
    protected $guarded = ['id'];
    protected $casts = ['calculated_at' => 'date'];
}
