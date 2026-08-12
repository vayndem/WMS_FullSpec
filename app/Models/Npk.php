<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Npk extends Model
{
    use HasFactory, Auditable;

    public const DRAFT = 'DRAFT';
    public const POSTED = 'POSTED';
    public const REVERSED = 'REVERSED';

    protected $table = 'npks';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:6',
        'jumlah_stok' => 'decimal:6',
        'harga_satuan' => 'decimal:4',
        'total_nilai' => 'decimal:2',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'id_barang');
    }

    public function gudangAsal(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'id_gudang_asal');
    }

    public function gudangTujuan(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'id_gudang_tujuan');
    }

    public function allocations()
    {
        return $this->hasMany(NpkStockAllocation::class, 'npk_id');
    }
}
