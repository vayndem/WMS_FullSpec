<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use HasFactory;

    public const POSTED = 'POSTED';
    public const VOID = 'VOID';

    protected $table = 'invoice_payments';

    protected $fillable = [
        'payment_number',
        'invoice_lpb_id',
        'tanggal_pembayaran',
        'metode_pembayaran',
        'coa_kas_bank_id',
        'jumlah_pembayaran',
        'potongan_pph23',
        'potongan_materai',
        'biaya_transfer_bank',
        'selisih_bayar',
        'jenis_selisih',
        'coa_selisih_id',
        'kelebihan_pembayaran',
        'total_transaksi_pengurang_hutang',
        'keterangan',
        'finance_user_id',
        'status',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'jumlah_pembayaran' => 'decimal:2',
        'potongan_pph23' => 'decimal:2',
        'potongan_materai' => 'decimal:2',
        'biaya_transfer_bank' => 'decimal:2',
        'selisih_bayar' => 'decimal:2',
        'kelebihan_pembayaran' => 'decimal:2',
        'total_transaksi_pengurang_hutang' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(InvoiceLpb::class, 'invoice_lpb_id');
    }

    public function userFinance()
    {
        return $this->belongsTo(User::class, 'finance_user_id');
    }

    public function coaKasBank()
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_kas_bank_id');
    }

    public function coaSelisih()
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_selisih_id');
    }
}
