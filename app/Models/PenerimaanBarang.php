<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLampiran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanBarang extends Model
{
    use HasFactory, Auditable, HasLampiran;

    public const DRAFT = 'DRAFT';
    public const POSTED = 'POSTED';
    public const REVERSED = 'REVERSED';
    public const CANCELLED = 'CANCELLED';

    protected $table = 'wms_penerimaan_barang';

    protected $fillable = [
        'id_lpb',
        'document_type',
        'tanggal',
        'no_po',
        'gudang_id',
        'no_sj',
        'id_user',
        'flag',
        'no_invoice',
        'status',
        'receiving_status',
        'putaway_by',
        'putaway_at',
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
        return $this->hasMany(PenerimaanBarangDetail::class, 'id_lpb', 'id_lpb');
    }

    public function pembelian()
    {
        return $this->belongsTo(PesananPembelian::class, 'no_po', 'no_po');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function invoiceReceipts()
    {
        return $this->hasMany(FakturPembelianPenerimaan::class, 'lpb_id');
    }

    public function serviceDetails()
    {
        return $this->hasMany(PenerimaanJasaDetail::class, 'lpb_id');
    }

    protected $casts = [
        'tanggal' => 'date',
        'cancelled_at' => 'datetime',
        'putaway_at' => 'datetime',
    ];
}
