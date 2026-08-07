<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    protected $guarded = [];
    protected $casts = [
        'posting_date' => 'date', 'suggested_amount' => 'decimal:2', 'amount' => 'decimal:2',
        'book_value_before' => 'decimal:2', 'book_value_after' => 'decimal:2',
    ];
    public function asset() { return $this->belongsTo(Asset::class); }
    public function journal() { return $this->belongsTo(Jurnal::class); }
}
