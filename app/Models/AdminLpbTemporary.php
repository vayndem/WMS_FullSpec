<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLpbTemporary extends Model
{
    use HasFactory;

    protected $table = 'admin_lpb_temporary';

    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(AdminLpbDetailTemporary::class, 'id_lpb', 'id_lpb');
    }
}
