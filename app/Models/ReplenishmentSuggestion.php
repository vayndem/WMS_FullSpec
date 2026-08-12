<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplenishmentSuggestion extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['calculated_at' => 'date'];
}
