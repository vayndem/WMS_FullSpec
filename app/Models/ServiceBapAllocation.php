<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceBapAllocation extends Model
{
    protected $guarded = [];
    protected $casts = ['percentage' => 'decimal:4', 'amount' => 'decimal:2'];
    public function bapDetail()
    {
        return $this->belongsTo(ServiceBapDetail::class, 'service_bap_detail_id');
    }
}
