<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoicelpb extends Model
{
    use HasFactory;

    protected $table = 'invoicelpbs';

    protected $fillable = [
        'no_invoice',
        'kode_supplier',
        'tanggal',
        'tgl_deadline_pembayaran',
        'sub_total',
        'jenis_pajak',
        'dpp_ppn',
        'tarif_ppn',
        'ppn',
        'dasar_pph',
        'tarif_pph',
        'diskon',
        'ongkir',
        'pph',
        'grand_total',
        'status_pembayaran',
        'total_pembayaran',
        'sisa_tagihan',
        'note',
        'status',
        'is_void',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'kode_supplier');
    }

    public function details()
    {
        return $this->hasMany(Invoicelpbdetail::class, 'id_invoice_lpb')->where('is_void', false);
    }

    public function receipts()
    {
        return $this->hasMany(InvoiceLpbReceipt::class, 'invoice_lpb_id');
    }

    public function lpbs()
    {
        return $this->belongsToMany(Lpb::class, 'invoice_lpb_receipts', 'invoice_lpb_id', 'lpb_id')
            ->withPivot('amount')->withTimestamps();
    }

    protected $casts = [
        'tanggal' => 'date',
        'tgl_deadline_pembayaran' => 'date',
        'sub_total' => 'decimal:2',
        'ppn' => 'decimal:2',
        'dpp_ppn' => 'decimal:2',
        'tarif_ppn' => 'decimal:4',
        'dasar_pph' => 'decimal:2',
        'tarif_pph' => 'decimal:4',
        'grand_total' => 'decimal:2',
        'is_void' => 'boolean',
        'voided_at' => 'datetime',
    ];
}
