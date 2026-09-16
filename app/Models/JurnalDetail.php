<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalDetail extends Model
{
    use HasFactory;

    protected $table = 'wms_jurnal_detail';

    protected $fillable = [
        'jurnal_id',
        'coa_id',
        'gudang_id',
        'debit',
        'kredit',
        'keterangan',
    ];

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(BaganAkun::class, 'coa_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
