<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurnal extends Model
{
    use HasFactory;

    protected $table = 'jurnals';

    protected $fillable = [
        'no_jurnal',
        'tanggal',
        'keterangan',
        'sumber_transaksi',
        'reff_id',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
        'reversal_of_id',
        'total_debit',
        'total_kredit',
    ];

    protected $casts = ['tanggal' => 'date', 'posted_at' => 'datetime'];

    public function isManual(): bool
    {
        return $this->sumber_transaksi === 'MANUAL';
    }

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function details(): HasMany
    {
        return $this->hasMany(JurnalDetail::class, 'jurnal_id');
    }
}
