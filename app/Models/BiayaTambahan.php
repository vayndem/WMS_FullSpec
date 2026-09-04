<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class BiayaTambahan extends Model
{
    use Auditable;
    protected $table = 'wms_biaya_tambahan';
    protected $guarded = ['id'];
    protected $attributes = ['status' => 'DRAFT', 'allocation_basis' => 'VALUE'];
    protected $casts = ['date' => 'date', 'total_amount' => 'decimal:2', 'posted_at' => 'datetime'];
    public function allocations()
    {
        return $this->hasMany(BiayaTambahanAlokasi::class, 'landed_cost_id');
    }
}
