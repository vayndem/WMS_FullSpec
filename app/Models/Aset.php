<?php

namespace App\Models;

use App\Models\Concerns\HasLampiran;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasLampiran;

    public const STRAIGHT_LINE = 'STRAIGHT_LINE';
    public const DECLINING_BALANCE = 'DECLINING_BALANCE';

    public const KELOMPOK_FISKAL = [
        'KELOMPOK_1' => ['label' => 'Kelompok 1 — 4 tahun', 'bulan' => 48, 'bangunan' => false],
        'KELOMPOK_2' => ['label' => 'Kelompok 2 — 8 tahun', 'bulan' => 96, 'bangunan' => false],
        'KELOMPOK_3' => ['label' => 'Kelompok 3 — 16 tahun', 'bulan' => 192, 'bangunan' => false],
        'KELOMPOK_4' => ['label' => 'Kelompok 4 — 20 tahun', 'bulan' => 240, 'bangunan' => false],
        'BANGUNAN_PERMANEN' => ['label' => 'Bangunan Permanen — 20 tahun', 'bulan' => 240, 'bangunan' => true],
        'BANGUNAN_NON_PERMANEN' => ['label' => 'Bangunan Tidak Permanen — 10 tahun', 'bulan' => 120, 'bangunan' => true],
    ];

    protected $table = 'wms_asets';
    protected $guarded = [];
    protected $casts = [
        'acquisition_date' => 'date', 'depreciation_start_date' => 'date',
        'last_depreciation_date' => 'date', 'acquisition_cost' => 'decimal:2',
        'residual_value' => 'decimal:2', 'opening_accumulated_depreciation' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2', 'book_value' => 'decimal:2',
    ];

    public function category() { return $this->belongsTo(KategoriAset::class, 'kategori_aset_id'); }
    public function acquisitionCreditAccount() { return $this->belongsTo(BaganAkun::class, 'acquisition_credit_coa_id'); }
    public function depreciations() { return $this->hasMany(PenyusutanAset::class, 'aset_id'); }
    public function disposal() { return $this->hasOne(PelepasanAset::class, 'aset_id'); }

    public function suggestedMonthlyDepreciation(): float
    {
        if (!$this->useful_life_months) return 0;

        if ($this->depreciation_method === self::DECLINING_BALANCE) {
            $sisa = max((float) $this->book_value - (float) $this->residual_value, 0);
            return round($sisa * 2 / $this->useful_life_months, 2);
        }

        return round(max((float) $this->acquisition_cost - (float) $this->residual_value, 0) / $this->useful_life_months, 2);
    }

    public function usefulLifeMonthsFiskal(): ?int
    {
        return self::KELOMPOK_FISKAL[$this->kelompok_fiskal]['bulan'] ?? null;
    }

    public function nilaiBukuFiskal(): float
    {
        return round((float) $this->acquisition_cost - (float) $this->akumulasi_penyusutan_fiskal, 2);
    }

    public function suggestedMonthlyFiscalDepreciation(): float
    {
        $bulan = $this->usefulLifeMonthsFiskal();
        if (!$bulan) return 0;

        $sisa = $this->nilaiBukuFiskal();
        if ($sisa <= 0) return 0;

        $jumlah = $this->metode_penyusutan_fiskal === self::DECLINING_BALANCE
            ? $sisa * 2 / $bulan
            : (float) $this->acquisition_cost / $bulan;

        return round(min($jumlah, $sisa), 2);
    }
}
