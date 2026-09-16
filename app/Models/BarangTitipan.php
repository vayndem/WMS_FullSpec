<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangTitipan extends Model
{
    use Auditable;

    protected $table = 'wms_barang_titipan';
    protected $guarded = ['id'];

    public const DITERIMA = 'DITERIMA';
    public const DIKEMBALIKAN = 'DIKEMBALIKAN';
    public const DIBELI = 'DIBELI';
    public const HILANG = 'HILANG';

    protected $casts = [
        'jumlah' => 'decimal:6',
        'tanggal_terima' => 'date',
        'estimasi_kembali' => 'date',
        'tanggal_kembali' => 'date',
        'nilai_taksiran' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function pengeluaran(): BelongsTo
    {
        return $this->belongsTo(PengeluaranBarang::class, 'pengeluaran_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function terlambat(): bool
    {
        return $this->estimasi_kembali !== null
            && $this->status === self::DITERIMA
            && $this->estimasi_kembali->isPast();
    }
}
