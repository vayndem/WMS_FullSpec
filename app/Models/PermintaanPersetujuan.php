<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanPersetujuan extends Model
{
    protected $table = 'wms_permintaan_persetujuan';

    public const PENDING = 'PENDING';
    public const APPROVED = 'APPROVED';
    public const REJECTED = 'REJECTED';

    public const PEMBALIKAN_DOKUMEN = 'PEMBALIKAN_DOKUMEN';
    public const PERUBAHAN_COA = 'PERUBAHAN_COA';

    public const LABEL_JENIS = [
        self::PEMBALIKAN_DOKUMEN => 'Pembalikan Dokumen',
        self::PERUBAHAN_COA => 'Perubahan Bagan Akun',
    ];

    protected $fillable = [
        'nomor',
        'jenis',
        'sub_jenis',
        'referensi_type',
        'referensi_id',
        'ringkasan',
        'payload',
        'status',
        'alasan',
        'catatan_checker',
        'diminta_oleh',
        'diputuskan_oleh',
        'diputuskan_pada',
    ];

    protected $casts = [
        'payload' => 'array',
        'diputuskan_pada' => 'datetime',
    ];

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diminta_oleh');
    }

    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }
}
