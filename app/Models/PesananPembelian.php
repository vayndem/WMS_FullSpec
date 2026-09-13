<?php

namespace App\Models;

use App\Models\Concerns\HasLampiran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesananPembelian extends Model
{
    use HasFactory, HasLampiran;

    public const OPEN = 'OPEN';
    public const CLOSED = 'CLOSED';

    protected $table = 'wms_pesanan_pembelian';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date', 'kunci' => 'boolean'];

    protected $appends = [
        'id_suplier',
        'untukperhatian',
        'totalexclude',
        'totalppn',
        'totalinclude',
        'GrandTotalPesananPembelian',
        'inputlabel'
    ];

    public function getIdSuplierAttribute()
    {
        return $this->attributes['supplier_id'] ?? null;
    }

    public function getUntukperhatianAttribute()
    {
        return $this->attributes['untuk_perhatian'] ?? '-';
    }

    public function getTotalexcludeAttribute()
    {
        return $this->attributes['total_exclude'] ?? 0;
    }

    public function getTotalppnAttribute()
    {
        return $this->attributes['total_ppn'] ?? 0;
    }

    public function getTotalincludeAttribute()
    {
        return $this->attributes['total_include'] ?? 0;
    }

    public function getGrandTotalPesananPembelianAttribute()
    {
        return $this->attributes['grand_total'] ?? 0;
    }

    public function getInputlabelAttribute()
    {
        return $this->attributes['input_label'] ?? 'Freight Handling';
    }

    public function details(): HasMany
    {
        return $this->hasMany(PesananPembelianDetail::class, 'no_po', 'no_po');
    }

    public function lpbs(): HasMany
    {
        return $this->hasMany(PenerimaanBarang::class, 'no_po', 'no_po');
    }

    public function serviceDetails(): HasMany
    {
        return $this->hasMany(PesananJasaDetail::class, 'pembelian_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }
}
