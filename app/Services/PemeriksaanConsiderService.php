<?php

namespace App\Services;

use App\Models\Gudang;
use App\Models\InventoryLayer;
use App\Models\PemeriksaanConsider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PemeriksaanConsiderService
{
    public function __construct(private StokGudangService $stok) {}

    public function konfirmasi(PemeriksaanConsider $pemeriksaan): PemeriksaanConsider
    {
        return DB::transaction(function () use ($pemeriksaan) {
            $pemeriksaan = PemeriksaanConsider::with('details')->lockForUpdate()->findOrFail($pemeriksaan->id);
            if ($pemeriksaan->status !== PemeriksaanConsider::DRAFT) throw new RuntimeException('Pemeriksaan sudah dikonfirmasi.');
            $consider = Gudang::lockForUpdate()->findOrFail($pemeriksaan->gudang_consider_id);
            $baik = Gudang::lockForUpdate()->findOrFail($pemeriksaan->gudang_baik_id);
            $rusak = Gudang::lockForUpdate()->findOrFail($pemeriksaan->gudang_rusak_id);
            if ($consider->jenis !== Gudang::CONSIDER || $baik->jenis !== Gudang::NORMAL || $rusak->jenis !== Gudang::RUSAK) throw new RuntimeException('Jenis gudang pemeriksaan Consider tidak valid.');
            if (!$consider->aktif || !$baik->aktif || !$rusak->aktif) throw new RuntimeException('Seluruh gudang pemeriksaan Consider harus aktif.');

            foreach ($pemeriksaan->details as $detail) {
                if (abs(((float)$detail->jumlah_baik + (float)$detail->jumlah_rusak) - (float)$detail->jumlah_diperiksa) > 0.000001) throw new RuntimeException('Jumlah baik dan rusak harus sama dengan jumlah diperiksa.');
                $this->stok->saldo($consider->id, $detail->bahan_id);
                $allocations = $this->stok->ambilLayer($consider->id, $detail->bahan_id, (float)$detail->jumlah_diperiksa, $pemeriksaan->tanggal);
                $total = collect($allocations)->sum(fn($a) => $a['jumlah'] * $a['harga']);
                $avg = (float)$detail->jumlah_diperiksa > 0 ? $total / (float)$detail->jumlah_diperiksa : 0;
                $this->stok->keluar($consider->id, $detail->bahan_id, (float)$detail->jumlah_diperiksa, $avg, 'CONSIDER_KELUAR', 'PEMERIKSAAN_CONSIDER', $pemeriksaan->id, $pemeriksaan->nomor_pemeriksaan);
                $remainingGood = (float)$detail->jumlah_baik;
                foreach ($allocations as $allocation) {
                    $good = min($remainingGood, $allocation['jumlah']);
                    $damaged = $allocation['jumlah'] - $good;
                    if ($good > 0) {
                        InventoryLayer::create(['bahan_id' => $detail->bahan_id, 'gudang_id' => $baik->id, 'source_type' => 'CONSIDER_BAIK_' . $detail->id, 'source_id' => $allocation['layer']->id, 'transaction_date' => $pemeriksaan->tanggal, 'initial_quantity' => $good, 'remaining_quantity' => $good, 'unit_cost' => $allocation['harga']]);
                        $this->stok->masuk($baik->id, $detail->bahan_id, $good, $allocation['harga'], 'PEMERIKSAAN_BAIK', 'PEMERIKSAAN_CONSIDER', $pemeriksaan->id, $pemeriksaan->nomor_pemeriksaan);
                    }
                    if ($damaged > 0) {
                        InventoryLayer::create(['bahan_id' => $detail->bahan_id, 'gudang_id' => $rusak->id, 'source_type' => 'CONSIDER_RUSAK_' . $detail->id, 'source_id' => $allocation['layer']->id, 'transaction_date' => $pemeriksaan->tanggal, 'initial_quantity' => $damaged, 'remaining_quantity' => $damaged, 'unit_cost' => $allocation['harga']]);
                        $this->stok->masuk($rusak->id, $detail->bahan_id, $damaged, $allocation['harga'], 'RUSAK_MASUK', 'PEMERIKSAAN_CONSIDER', $pemeriksaan->id, $pemeriksaan->nomor_pemeriksaan);
                    }
                    $remainingGood -= $good;
                }
            }
            $pemeriksaan->update(['status' => PemeriksaanConsider::DIKONFIRMASI, 'dikonfirmasi_oleh' => Auth::id(), 'dikonfirmasi_pada' => now()]);
            return $pemeriksaan->fresh('details');
        });
    }
}
