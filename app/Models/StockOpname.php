<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasFactory;

    public const DRAFT = 'DRAFT';
    public const SUBMITTED = 'SUBMITTED';
    public const APPROVED = 'APPROVED';
    public const POSTED = 'POSTED';
    public const REJECTED = 'REJECTED';

    protected $fillable = [
        'number',
        'warehouse_id',
        'cutoff_at',
        'status',
        'notes',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'approval_note',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'cutoff_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(StockOpnameDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(AdminNamagudang::class, 'warehouse_id');
    }
}
