<?php

namespace App\Models;

use App\Models\Concerns\HasLampiran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    use HasFactory, HasLampiran;

    public const POSTED = 'POSTED';
    public const REVERSED = 'REVERSED';

    protected $table = 'wms_retur_pembelian';

    protected $fillable = [
        'no_retur',
        'lpb_id',
        'invoice_id',
        'tanggal',
        'alasan',
        'status',
        'total_nilai',
        'hutang_reduction',
        'advance_payment_id',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_nilai' => 'decimal:2',
        'hutang_reduction' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function lpb()
    {
        return $this->belongsTo(PenerimaanBarang::class, 'lpb_id');
    }

    public function invoice()
    {
        return $this->belongsTo(FakturPembelian::class, 'invoice_id');
    }

    public function advancePayment()
    {
        return $this->belongsTo(PembayaranFaktur::class, 'advance_payment_id');
    }

    public function details()
    {
        return $this->hasMany(ReturPembelianDetail::class, 'retur_pembelian_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
