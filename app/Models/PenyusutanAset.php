<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenyusutanAset extends Model
{
    protected $table = 'wms_penyusutan_asets';
    protected $guarded = [];
    protected $casts = [
        'posting_date' => 'date', 'suggested_amount' => 'decimal:2', 'amount' => 'decimal:2',
        'book_value_before' => 'decimal:2', 'book_value_after' => 'decimal:2',
    ];
    public function asset() { return $this->belongsTo(Aset::class, 'aset_id'); }
    public function journal() { return $this->belongsTo(Jurnal::class); }
}
