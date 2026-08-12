<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LandedCost extends Model
{
    use Auditable;
    protected $guarded = ['id'];
    protected $attributes = ['status' => 'DRAFT', 'allocation_basis' => 'VALUE'];
    protected $casts = ['date' => 'date', 'total_amount' => 'decimal:2', 'posted_at' => 'datetime'];
    public function allocations()
    {
        return $this->hasMany(LandedCostAllocation::class);
    }
}
