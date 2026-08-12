<?php

namespace App\Services;

use App\Models\Bahan;
use App\Models\InventoryLayer;
use App\Models\MutasiStok;
use App\Models\StokGudang;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class StokGudangService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function saldo(int $gudangId, int $bahanId): StokGudang
    {
        StokGudang::insertOrIgnore([
            'gudang_id' => $gudangId,
            'bahan_id' => $bahanId,
            'stok_tersedia' => 0,
            'stok_direservasi' => 0,
            'stok_dipesan' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return StokGudang::where('gudang_id', $gudangId)
            ->where('bahan_id', $bahanId)->lockForUpdate()->firstOrFail();
    }

    public function tambahPesanan(int $gudangId, int $bahanId, float $jumlah): void
    {
        $saldo = $this->saldo($gudangId, $bahanId);
        $saldo->update(['stok_dipesan' => (float) $saldo->stok_dipesan + $jumlah]);
    }

    public function kurangiPesanan(int $gudangId, int $bahanId, float $jumlah): void
    {
        $saldo = $this->saldo($gudangId, $bahanId);
        $saldo->update(['stok_dipesan' => max(0, (float) $saldo->stok_dipesan - $jumlah)]);
    }

    public function masuk(int $gudangId, int $bahanId, float $jumlah, float $harga, string $jenis, string $referensi, int $referensiId, ?string $keterangan = null, bool $affectGlobal = true): StokGudang
    {
        if ($jumlah <= 0) throw new RuntimeException('Jumlah stok masuk harus lebih besar dari nol.');
        $saldo = $this->saldo($gudangId, $bahanId);
        $sebelum = (float) $saldo->stok_tersedia;
        $saldo->update(['stok_tersedia' => $sebelum + $jumlah]);
        if ($affectGlobal) Bahan::whereKey($bahanId)->increment('stok_onhand', $jumlah);
        $this->catat($saldo, $jenis, $jumlah, 0, $sebelum, $harga, $referensi, $referensiId, $keterangan);
        return $saldo->fresh();
    }

    public function keluar(int $gudangId, int $bahanId, float $jumlah, float $harga, string $jenis, string $referensi, int $referensiId, ?string $keterangan = null, bool $affectGlobal = true): StokGudang
    {
        if ($jumlah <= 0) throw new RuntimeException('Jumlah stok keluar harus lebih besar dari nol.');
        $saldo = $this->saldo($gudangId, $bahanId);
        $sebelum = (float) $saldo->stok_tersedia;
        if ($sebelum - (float) $saldo->stok_direservasi + 0.000001 < $jumlah) {
            throw new RuntimeException('Stok bebas gudang tidak mencukupi.');
        }
        $saldo->update(['stok_tersedia' => $sebelum - $jumlah]);
        if ($affectGlobal) Bahan::whereKey($bahanId)->decrement('stok_onhand', $jumlah);
        $this->catat($saldo, $jenis, 0, $jumlah, $sebelum, $harga, $referensi, $referensiId, $keterangan);
        return $saldo->fresh();
    }

    public function ambilLayer(int $gudangId, int $bahanId, float $jumlah, $tanggal, array $statuses = ['AVAILABLE']): array
    {
        $layers = InventoryLayer::where('gudang_id', $gudangId)->where('bahan_id', $bahanId)
            ->whereIn('stock_status', $statuses)
            ->where(function ($query) {
                $query->whereNull('inventory_lot_id')->orWhereHas('lot', fn ($lot) => $lot
                    ->where('blocked', false)
                    ->where(fn ($expiry) => $expiry->whereNull('expires_at')->orWhereDate('expires_at', '>=', today())));
            })
            ->where('remaining_quantity', '>', 0)->whereDate('transaction_date', '<=', $tanggal)
            ->orderBy('transaction_date')->orderBy('id')->lockForUpdate()->get();
        if ((float) $layers->sum('remaining_quantity') + 0.000001 < $jumlah) {
            throw new RuntimeException('Layer persediaan gudang tidak mencukupi.');
        }
        $remaining = $jumlah;
        $allocations = [];
        foreach ($layers as $layer) {
            if ($remaining <= 0) break;
            $take = min($remaining, (float) $layer->remaining_quantity);
            $layer->update(['remaining_quantity' => (float) $layer->remaining_quantity - $take]);
            $allocations[] = ['layer' => $layer, 'jumlah' => $take, 'harga' => (float) $layer->unit_cost];
            $remaining -= $take;
        }
        return $allocations;
    }

    private function catat(StokGudang $saldo, string $jenis, float $masuk, float $keluar, float $sebelum, float $harga, string $referensi, int $referensiId, ?string $keterangan): void
    {
        MutasiStok::create([
            'nomor_mutasi' => $this->numbers->internal('MTS', 'STK'),
            'tanggal' => now(),
            'jenis_mutasi' => $jenis,
            'gudang_id' => $saldo->gudang_id,
            'bahan_id' => $saldo->bahan_id,
            'jumlah_masuk' => $masuk,
            'jumlah_keluar' => $keluar,
            'saldo_sebelum' => $sebelum,
            'saldo_setelah' => $sebelum + $masuk - $keluar,
            'harga_satuan' => $harga,
            'total_nilai' => round(($masuk ?: $keluar) * $harga, 2),
            'jenis_referensi' => $referensi,
            'referensi_id' => $referensiId,
            'user_id' => Auth::id(),
            'keterangan' => $keterangan,
        ]);
    }
}
