<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriodLock extends Model
{
    protected $fillable = [
        'period_start',
        'period_end',
        'status',
        'reason',
        'locked_by',
        'locked_by_name',
        'locked_at',
        'unlocked_by',
        'unlocked_by_name',
        'unlocked_at',
        'unlock_reason',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime',
    ];

    public function isLocked(): bool
    {
        return $this->status === 'LOCKED';
    }
}
