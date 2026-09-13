<?php

namespace App\Services;

use App\Models\GelombangPengambilan;
use App\Models\LokasiGudang;
use App\Models\PesananPengambilan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GelombangPengambilanService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function kandidat(int $gudangId): Collection
    {
        return PesananPengambilan::with('lines')
            ->where('gudang_id', $gudangId)
            ->whereNull('gelombang_id')
            ->whereIn('status', ['RELEASED', 'PICKING'])
            ->orderBy('id')
            ->get();
    }

    public function buat(int $gudangId, array $pickIds, string $strategi = 'LOKASI', ?string $catatan = null): GelombangPengambilan
    {
        if ($pickIds === []) {
            throw new RuntimeException('Pilih minimal satu perintah pengambilan untuk digabung.');
        }

        if (!array_key_exists($strategi, GelombangPengambilan::STRATEGI)) {
            throw new RuntimeException('Strategi gelombang tidak dikenal.');
        }

        return DB::transaction(function () use ($gudangId, $pickIds, $strategi, $catatan) {
            $picks = PesananPengambilan::with('lines')
                ->whereIn('id', $pickIds)
                ->lockForUpdate()
                ->get();

            if ($picks->count() !== count(array_unique($pickIds))) {
                throw new RuntimeException('Sebagian perintah pengambilan tidak ditemukan.');
            }

            foreach ($picks as $pick) {
                if ((int) $pick->gudang_id !== $gudangId) {
                    throw new RuntimeException("Perintah {$pick->number} bukan milik gudang yang dipilih.");
                }
                if ($pick->gelombang_id !== null) {
                    throw new RuntimeException("Perintah {$pick->number} sudah tergabung di gelombang lain.");
                }
                if (!in_array($pick->status, ['RELEASED', 'PICKING'], true)) {
                    throw new RuntimeException("Perintah {$pick->number} berstatus {$pick->status} dan tidak dapat digelombangkan.");
                }
            }

            $gelombang = GelombangPengambilan::create([
                'nomor' => $this->numbers->internal('WAV', 'STK'),
                'gudang_id' => $gudangId,
                'status' => GelombangPengambilan::DIRENCANAKAN,
                'strategi' => $strategi,
                'catatan' => $catatan,
                'dibuat_oleh' => Auth::id(),
            ]);

            foreach ($this->urutkan($picks, $strategi) as $urutan => $pick) {
                $pick->update([
                    'gelombang_id' => $gelombang->id,
                    'urutan_dalam_gelombang' => $urutan + 1,
                ]);
            }

            return $gelombang->fresh('pesanan');
        });
    }

    private function urutkan(Collection $picks, string $strategi): Collection
    {
        if ($strategi === 'BAHAN') {
            $bahanPertama = fn (PesananPengambilan $pick) => (int) ($pick->lines
                ->sortBy('inventory_layer_id')->first()->inventory_layer_id ?? PHP_INT_MAX);

            return $picks->sortBy($bahanPertama)->values();
        }

        $urutanLokasi = LokasiGudang::whereIn('id', $picks->flatMap->lines->pluck('warehouse_location_id')->filter()->unique())
            ->pluck('urutan_pick', 'id');

        return $picks->sortBy(function (PesananPengambilan $pick) use ($urutanLokasi) {
            return $pick->lines
                ->map(fn ($line) => $urutanLokasi->get($line->warehouse_location_id) ?? PHP_INT_MAX)
                ->min() ?? PHP_INT_MAX;
        })->values();
    }

    public function rilis(GelombangPengambilan $gelombang, ?User $petugas = null): GelombangPengambilan
    {
        if ($gelombang->status !== GelombangPengambilan::DIRENCANAKAN) {
            throw new RuntimeException('Hanya gelombang berstatus DIRENCANAKAN yang dapat dirilis.');
        }

        if ($gelombang->pesanan()->count() === 0) {
            throw new RuntimeException('Gelombang tanpa perintah pengambilan tidak dapat dirilis.');
        }

        $gelombang->update([
            'status' => GelombangPengambilan::DIRILIS,
            'ditugaskan_ke' => $petugas?->id,
            'dirilis_pada' => now(),
        ]);

        return $gelombang;
    }

    public function selesaikan(GelombangPengambilan $gelombang): GelombangPengambilan
    {
        if ($gelombang->status !== GelombangPengambilan::DIRILIS) {
            throw new RuntimeException('Hanya gelombang yang sudah dirilis yang dapat diselesaikan.');
        }

        $belum = $gelombang->pesanan()->where('status', '!=', 'COMPLETED')->count();

        if ($belum > 0) {
            throw new RuntimeException("Masih ada {$belum} perintah pengambilan yang belum selesai di gelombang ini.");
        }

        $gelombang->update([
            'status' => GelombangPengambilan::SELESAI,
            'selesai_pada' => now(),
        ]);

        return $gelombang;
    }

    public function batalkan(GelombangPengambilan $gelombang): GelombangPengambilan
    {
        if ($gelombang->status === GelombangPengambilan::SELESAI) {
            throw new RuntimeException('Gelombang yang sudah selesai tidak dapat dibatalkan.');
        }

        return DB::transaction(function () use ($gelombang) {
            $gelombang->pesanan()->update(['gelombang_id' => null, 'urutan_dalam_gelombang' => null]);
            $gelombang->update(['status' => GelombangPengambilan::DIBATALKAN]);

            return $gelombang;
        });
    }
}
