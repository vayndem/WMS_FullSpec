<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PelepasanAset extends Model
{
    protected $table = 'wms_pelepasan_asets';
    protected $guarded = [];

    public const SALE = 'SALE';
    public const WRITE_OFF = 'WRITE_OFF';
    public const TRADE_IN = 'TRADE_IN';

    public const JENIS = [
        self::SALE => 'Penjualan aset',
        self::WRITE_OFF => 'Penghapusan aset',
        self::TRADE_IN => 'Tukar tambah (barter — PPN Keluaran atas nilai wajar)',
    ];

    protected $casts = [
        'disposal_date' => 'date', 'proceeds' => 'decimal:2', 'book_value_at_disposal' => 'decimal:2',
        'gain_amount' => 'decimal:2', 'loss_amount' => 'decimal:2',
        'dpp_ppn_keluaran' => 'decimal:2', 'ppn_keluaran' => 'decimal:2',
    ];
    public function asset() { return $this->belongsTo(Aset::class, 'aset_id'); }
    public function cashBankAccount() { return $this->belongsTo(BaganAkun::class, 'cash_bank_coa_id'); }
    public function journal() { return $this->belongsTo(Jurnal::class); }
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function pesananPembelian() { return $this->belongsTo(PesananPembelian::class, 'pesanan_pembelian_id'); }
    public function uangMuka() { return $this->belongsTo(PembayaranFaktur::class, 'advance_payment_id'); }
}
