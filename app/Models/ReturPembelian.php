<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    use HasFactory;

    public const POSTED = 'POSTED';
    public const REVERSED = 'REVERSED';

    protected $table = 'retur_pembelians';

    protected $fillable = [
        'no_retur',
        'lpb_id',
        'tanggal',
        'alasan',
        'status',
        'total_nilai',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_nilai' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function lpb()
    {
        return $this->belongsTo(Lpb::class, 'lpb_id');
    }

    public function details()
    {
        return $this->hasMany(ReturPembelianDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
