<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelians';
    protected $guarded = ['id'];

    protected $appends = [
        'id_suplier',
        'untukperhatian',
        'totalexclude',
        'totalppn',
        'totalinclude',
        'GrandTotalPembelian',
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

    public function getGrandTotalPembelianAttribute()
    {
        return $this->attributes['grand_total'] ?? 0;
    }

    public function getInputlabelAttribute()
    {
        return $this->attributes['input_label'] ?? 'Freight Handling';
    }

    public function details(): HasMany
    {
        return $this->hasMany(Pembeliandetail::class, 'no_po', 'no_po');
    }

    public function lpbs(): HasMany
    {
        return $this->hasMany(Lpb::class, 'no_po', 'no_po');
    }

    public function serviceDetails(): HasMany
    {
        return $this->hasMany(ServicePoDetail::class, 'pembelian_id');
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
