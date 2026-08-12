<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentReversal extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['posted_at' => 'datetime'];
}
