<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lpb extends Model
{
    use HasFactory;

    protected $table = 'lpbs';

    protected $fillable = [
        'id_lpb',
        'document_type',
        'tanggal',
        'no_po',
        'no_sj',
        'id_user',
        'flag',
        'no_invoice',
        'status',
        'is_cancelled',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'jenis_lpb',
        'ulang',
        'kunci',
        'cetakan',
        'cetak_ulang',
    ];

    public function details()
    {
        return $this->hasMany(LpbDetail::class, 'id_lpb', 'id_lpb');
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'no_po', 'no_po');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function invoiceReceipts()
    {
        return $this->hasMany(InvoiceLpbReceipt::class, 'lpb_id');
    }

    public function serviceDetails()
    {
        return $this->hasMany(ServiceBapDetail::class, 'lpb_id');
    }

    protected $casts = [
        'tanggal' => 'date',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];
}
