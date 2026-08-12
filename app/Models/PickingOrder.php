<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PickingOrder extends Model
{
    use Auditable;
    protected $guarded = ['id'];
    protected $casts = ['picked_at' => 'datetime'];
    public function lines()
    {
        return $this->hasMany(PickingOrderLine::class);
    }
}
