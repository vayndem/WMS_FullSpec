<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class QualityInspection extends Model
{
    use Auditable;
    protected $guarded = ['id'];
    protected $casts = ['inspected_at' => 'datetime'];
    public function lpb()
    {
        return $this->belongsTo(Lpb::class);
    }
    public function lines()
    {
        return $this->hasMany(QualityInspectionLine::class);
    }
}
