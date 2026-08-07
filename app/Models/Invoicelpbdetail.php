<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoicelpbdetail extends Model
{
    use HasFactory;

    protected $table = 'invoicelpbdetails';

    protected $fillable = [
        'payment_number',
        'id_invoice_lpb',
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
        'id_user_finance',
        'is_void',
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
        'is_void' => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoicelpb::class, 'id_invoice_lpb');
    }

    public function userFinance()
    {
        return $this->belongsTo(User::class, 'id_user_finance');
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
