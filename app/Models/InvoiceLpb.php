<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceLpb extends Model
{
    use HasFactory, Auditable;

    public const UNPAID = 'UNPAID';
    public const PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const PAID = 'PAID';
    public const VOID = 'VOID';

    protected $table = 'invoice_lpbs';

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
        'total_pembayaran',
        'sisa_tagihan',
        'note',
        'status',
        'match_status',
        'match_summary',
        'matched_by',
        'matched_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $appends = ['status_pembayaran'];

    public static function paymentStatus(float $grandTotal, float $totalPaid): string
    {
        if ($grandTotal > 0 && $totalPaid >= $grandTotal - 0.01) {
            return self::PAID;
        }

        return $totalPaid > 0 ? self::PARTIALLY_PAID : self::UNPAID;
    }

    public function getStatusPembayaranAttribute(): string
    {
        return match ($this->status) {
            self::PAID => 'Lunas',
            self::PARTIALLY_PAID => 'Dibayar Sebagian',
            self::VOID => 'Dibatalkan',
            default => 'Belum Dibayar',
        };
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'kode_supplier');
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class)
            ->where('status', InvoicePayment::POSTED);
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
        'voided_at' => 'datetime',
        'match_summary' => 'array',
        'matched_at' => 'datetime',
    ];
}
