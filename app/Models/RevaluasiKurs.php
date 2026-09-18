<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevaluasiKurs extends Model
{
    use Auditable;

    protected $table = 'wms_revaluasi_kurs';

    protected $fillable = [
        'periode', 'tanggal', 'faktur_pembelian_id', 'mata_uang', 'valas_beredar',
        'kurs_lama', 'kurs_baru', 'nilai_lama', 'nilai_baru', 'selisih', 'journal_id', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'valas_beredar' => 'decimal:2',
        'kurs_lama' => 'decimal:4',
        'kurs_baru' => 'decimal:4',
        'nilai_lama' => 'decimal:2',
        'nilai_baru' => 'decimal:2',
        'selisih' => 'decimal:2',
    ];

    public function faktur(): BelongsTo
    {
        return $this->belongsTo(FakturPembelian::class, 'faktur_pembelian_id');
    }

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class, 'journal_id');
    }
}
