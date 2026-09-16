<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriJasa extends Model
{
    protected $table = 'wms_kategori_jasa';
    public const OPERATIONAL = 'SERVICE_OPERATIONAL';
    public const PRODUCTION = 'SERVICE_PRODUCTION';

    public const BEBAN = 'BEBAN';
    public const KAPITALISASI = 'KAPITALISASI';

    public const PERLAKUAN = [
        self::BEBAN => 'Dibebankan langsung ke laba rugi',
        self::KAPITALISASI => 'Menambah nilai tercatat aset (PSAK 16)',
    ];
    protected $guarded = [];
    protected $casts = [
        'requires_datapesanan' => 'boolean',
        'requires_cost_center' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function dikapitalisasi(): bool
    {
        return $this->perlakuan === self::KAPITALISASI;
    }

    public function expenseAccount()
    {
        return $this->belongsTo(BaganAkun::class, 'expense_coa_id');
    }
    public function grniAccount()
    {
        return $this->belongsTo(BaganAkun::class, 'grni_coa_id');
    }
    public function kategoriBahan(): BelongsTo
    {
        return $this->belongsTo(KategoriBahan::class, 'kategori_bahan_id');
    }
    public function poDetails(): HasMany
    {
        return $this->hasMany(PesananJasaDetail::class, 'service_category_id');
    }
}
