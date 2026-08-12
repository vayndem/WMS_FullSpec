<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use Auditable;
    protected $guarded = ['id'];
    protected $attributes = ['type' => 'STORAGE', 'active' => true];
    protected $casts = ['active' => 'boolean', 'capacity' => 'decimal:6'];
    public function gudang() { return $this->belongsTo(Gudang::class); }
}
