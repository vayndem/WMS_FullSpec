<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LokasiGudang extends Model
{
    use Auditable;
    protected $table = 'wms_lokasi_gudang';
    protected $guarded = ['id'];
    protected $attributes = ['type' => 'STORAGE', 'active' => true];
    protected $casts = [
        'active' => 'boolean',
        'capacity' => 'decimal:6',
        'panjang_cm' => 'decimal:2',
        'lebar_cm' => 'decimal:2',
        'tinggi_cm' => 'decimal:2',
        'kapasitas_volume_cm3' => 'decimal:2',
    ];
    public function gudang() { return $this->belongsTo(Gudang::class); }

    public function terisi(): float
    {
        return round((float) LayerPersediaan::where('warehouse_location_id', $this->id)
            ->where('remaining_quantity', '>', 0)->sum('remaining_quantity'), 6);
    }

    public function kapasitasVolume(): ?float
    {
        if ($this->kapasitas_volume_cm3 !== null) {
            return (float) $this->kapasitas_volume_cm3;
        }

        if ($this->panjang_cm === null || $this->lebar_cm === null || $this->tinggi_cm === null) {
            return null;
        }

        return round((float) $this->panjang_cm * (float) $this->lebar_cm * (float) $this->tinggi_cm, 2);
    }

    public function volumeTerisi(): float
    {
        return round((float) LayerPersediaan::where('wms_layer_persediaan.warehouse_location_id', $this->id)
            ->where('wms_layer_persediaan.remaining_quantity', '>', 0)
            ->join('bahans', 'bahans.id', '=', 'wms_layer_persediaan.bahan_id')
            ->sum(DB::raw('wms_layer_persediaan.remaining_quantity * COALESCE(bahans.volume_cm3, 0)')), 2);
    }

    public function assertMuat(float $masuk, ?Bahan $bahan = null): void
    {
        $angka = fn (float $nilai) => rtrim(rtrim(number_format($nilai, 4, ',', '.'), '0'), ',');

        if ($this->capacity !== null) {
            $terisi = $this->terisi();
            $kapasitas = (float) $this->capacity;
            if ($terisi + $masuk > $kapasitas + .000001) {
                throw new RuntimeException(
                    "Kapasitas lokasi {$this->code} tidak cukup: terisi {$angka($terisi)} dari {$angka($kapasitas)}, masuk {$angka($masuk)}."
                );
            }
        }

        $kapasitasVolume = $this->kapasitasVolume();
        $volumeSatuan = $bahan?->volume_cm3 !== null ? (float) $bahan->volume_cm3 : null;

        if ($kapasitasVolume === null || $volumeSatuan === null || $volumeSatuan <= 0) {
            return;
        }

        $volumeTerisi = $this->volumeTerisi();
        $volumeMasuk = $masuk * $volumeSatuan;

        if ($volumeTerisi + $volumeMasuk > $kapasitasVolume + .01) {
            throw new RuntimeException(
                "Volume lokasi {$this->code} tidak cukup: terisi {$angka($volumeTerisi)} cm3 dari {$angka($kapasitasVolume)} cm3, masuk {$angka($volumeMasuk)} cm3."
            );
        }
    }

    public function assertMilikGudang(int $gudangId): void
    {
        if ((int) $this->gudang_id !== $gudangId || !$this->active) {
            throw new RuntimeException("Lokasi {$this->code} tidak valid atau tidak aktif untuk gudang ini.");
        }
    }
}
