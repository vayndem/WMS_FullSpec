<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLampiran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengeluaranBarang extends Model
{
    use Auditable, HasLampiran;

    protected $table = 'wms_pengeluaran_barang';
    protected $guarded = ['id'];

    public const DRAFT = 'DRAFT';
    public const DI_VENDOR = 'DI_VENDOR';
    public const SEBAGIAN_KEMBALI = 'SEBAGIAN_KEMBALI';
    public const SELESAI = 'SELESAI';
    public const DIBATALKAN = 'DIBATALKAN';

    public const KEPERLUAN = [
        'PERBAIKAN' => 'Perbaikan / servis',
        'KALIBRASI' => 'Kalibrasi',
        'SUBKONTRAK' => 'Pekerjaan subkontrak',
        'PEMINJAMAN' => 'Dipinjamkan',
        'LAIN' => 'Lain-lain',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'estimasi_kembali' => 'date',
        'tanggal_kembali' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function pesananJasa(): BelongsTo
    {
        return $this->belongsTo(PesananJasa::class, 'pesanan_jasa_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PengeluaranBarangDetail::class, 'pengeluaran_id');
    }

    public function barangTitipan(): HasMany
    {
        return $this->hasMany(BarangTitipan::class, 'pengeluaran_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikeluarkan_oleh');
    }

    public function terlambat(): bool
    {
        return $this->estimasi_kembali !== null
            && $this->tanggal_kembali === null
            && in_array($this->status, [self::DI_VENDOR, self::SEBAGIAN_KEMBALI], true)
            && $this->estimasi_kembali->isPast();
    }
}
