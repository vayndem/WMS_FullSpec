<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    protected $guarded = [];
    protected $casts = [
        'disposal_date' => 'date', 'proceeds' => 'decimal:2', 'book_value_at_disposal' => 'decimal:2',
        'gain_amount' => 'decimal:2', 'loss_amount' => 'decimal:2',
    ];
    public function asset() { return $this->belongsTo(Asset::class); }
    public function cashBankAccount() { return $this->belongsTo(ChartOfAccount::class, 'cash_bank_coa_id'); }
    public function journal() { return $this->belongsTo(Jurnal::class); }
}
