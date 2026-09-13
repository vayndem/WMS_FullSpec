<?php

namespace App\Services;

use App\Models\PengaturanBahanGudang;
use App\Models\StockOpname;
use App\Models\StokGudang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CycleCountService
{
    public function __construct(
        private AbcAnalysisService $abc,
        private DocumentNumberService $numbers,
        private AccountingPeriodService $periods,
    ) {}

    public function bahanUntukSiklus(int $gudangId, string $kelas): Collection
    {
        $bahanIds = PengaturanBahanGudang::where('gudang_id', $gudangId)
            ->where('kelas_abc', $kelas)
            ->where('aktif', true)
            ->pluck('bahan_id');

        if ($bahanIds->isEmpty()) {
            return collect();
        }

        return StokGudang::with('bahan')
            ->where('gudang_id', $gudangId)
            ->whereIn('bahan_id', $bahanIds)
            ->where('stok_tersedia', '>', 0)
            ->get();
    }

    public function mulai(int $gudangId, string $kelas, string $cutoff, ?string $catatan = null): StockOpname
    {
        if (!array_key_exists($kelas, AbcAnalysisService::SIKLUS_BULAN)) {
            throw new RuntimeException('Kelas ABC tidak dikenal. Gunakan A, B, atau C.');
        }

        $this->periods->assertOpen($cutoff, 'Cycle count');

        $saldo = $this->bahanUntukSiklus($gudangId, $kelas);

        if ($saldo->isEmpty()) {
            throw new RuntimeException("Tidak ada bahan kelas {$kelas} bersaldo di gudang ini. Jalankan perhitungan ABC lebih dulu.");
        }

        return DB::transaction(function () use ($gudangId, $kelas, $cutoff, $catatan, $saldo) {
            $terbuka = StockOpname::where('warehouse_id', $gudangId)
                ->whereIn('status', [StockOpname::DRAFT, StockOpname::SUBMITTED, StockOpname::APPROVED, StockOpname::REJECTED])
                ->exists();

            if ($terbuka) {
                throw new RuntimeException('Masih ada stock opname gudang ini yang belum selesai.');
            }

            $opname = StockOpname::create([
                'number' => $this->numbers->internal('OPS', 'STK'),
                'warehouse_id' => $gudangId,
                'jenis' => StockOpname::SIKLUS,
                'kelas_abc' => $kelas,
                'cutoff_at' => $cutoff,
                'status' => StockOpname::DRAFT,
                'notes' => $catatan ?? "Cycle count kelas {$kelas}",
                'created_by' => Auth::id(),
            ]);

            foreach ($saldo as $row) {
                $opname->details()->create([
                    'bahan_id' => $row->bahan_id,
                    'system_quantity' => $row->stok_tersedia,
                    'physical_quantity' => $row->stok_tersedia,
                    'difference_quantity' => 0,
                ]);
            }

            return $opname->fresh('details');
        });
    }

    public function papanJadwal(array $gudangIds): Collection
    {
        return collect($gudangIds)->map(fn ($gudangId) => [
            'gudang_id' => (int) $gudangId,
            'kelas' => $this->abc->jatuhTempoSiklus((int) $gudangId),
        ])->filter(fn ($row) => $row['kelas'] !== [])->values();
    }
}
