<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';
    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(RequestDetail::class, 'request_id');
    }

    public function approver()
    {
        return $this->belongsTo(ApiUser::class, 'approved_by');
    }
}
