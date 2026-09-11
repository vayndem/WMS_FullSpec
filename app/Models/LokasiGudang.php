<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class LokasiGudang extends Model
{
    use Auditable;
    protected $table = 'wms_lokasi_gudang';
    protected $guarded = ['id'];
    protected $attributes = ['type' => 'STORAGE', 'active' => true];
    protected $casts = ['active' => 'boolean', 'capacity' => 'decimal:6'];
    public function gudang() { return $this->belongsTo(Gudang::class); }

    public function terisi(): float
    {
        return round((float) LayerPersediaan::where('warehouse_location_id', $this->id)
            ->where('remaining_quantity', '>', 0)->sum('remaining_quantity'), 6);
    }

    public function assertMuat(float $masuk): void
    {
        if ($this->capacity === null) {
            return;
        }

        $terisi = $this->terisi();
        $kapasitas = (float) $this->capacity;
        if ($terisi + $masuk > $kapasitas + .000001) {
            $angka = fn (float $nilai) => rtrim(rtrim(number_format($nilai, 4, ',', '.'), '0'), ',');
            throw new RuntimeException(
                "Kapasitas lokasi {$this->code} tidak cukup: terisi {$angka($terisi)} dari {$angka($kapasitas)}, masuk {$angka($masuk)}."
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
