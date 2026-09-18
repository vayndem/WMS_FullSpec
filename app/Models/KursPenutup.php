<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class KursPenutup extends Model
{
    use Auditable;

    protected $table = 'wms_kurs_penutup';

    protected $fillable = ['mata_uang', 'periode', 'kurs', 'sumber', 'dibuat_oleh'];

    protected $casts = ['kurs' => 'decimal:4'];
}
