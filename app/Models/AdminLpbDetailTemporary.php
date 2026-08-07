<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLpbDetailTemporary extends Model
{
    use HasFactory;

    protected $table = 'admin_lpb_detail_temporary';

    protected $guarded = ['id'];

    public function header()
    {
        return $this->belongsTo(AdminLpbTemporary::class, 'id_lpb', 'id_lpb');
    }
}
