<?php

namespace App\Services;

use App\Models\FakturPenjualan;
use App\Models\LayerPersediaan;
use App\Models\ReturPenjualan;
use App\Models\SuratJalan;
use App\Models\SuratJalanAlokasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReturPenjualanService
{
    public function __construct(
        private StokGudangService $stok,
        private WmsAccountingService $akuntansi,
        private DocumentNumberService $numbers,
    ) {}

    public function buat(SuratJalan $suratJalan, array $data, User $user): ReturPenjualan
    {
        return DB::transaction(function () use ($suratJalan, $data, $user) {
            $suratJalan = SuratJalan::with('details.alokasi', 'pesanan')
                ->lockForUpdate()
                ->findOrFail($suratJalan->id);

            if (!$suratJalan->isPosted()) {
                throw new RuntimeException('Hanya surat jalan yang sudah diposting yang dapat diretur.');
            }

            $faktur = FakturPenjualan::whereHas(
                'details',
                fn ($query) => $query->whereIn('surat_jalan_detail_id', $suratJalan->details->pluck('id'))
            )->whereIn('status', [
                FakturPenjualan::POSTED,
                FakturPenjualan::PARTIALLY_PAID,
            ])->first();

            $retur = ReturPenjualan::create([
                'nomor' => $this->numbers->external('RJL'),
                'tanggal' => $data['tanggal'],
                'surat_jalan_id' => $suratJalan->id,
                'pelanggan_id' => $suratJalan->pelanggan_id,
                'faktur_penjualan_id' => $faktur?->id,
                'status' => ReturPenjualan::POSTED,
                'alasan' => $data['alasan'],
                'dibuat_oleh' => $user->id,
            ]);

            $totalDpp = 0.0;
            $totalHpp = 0.0;

            foreach ($data['details'] as $baris) {
                $jumlah = round((float) ($baris['jumlah'] ?? 0), 6);

                if ($jumlah <= 0) {
                    continue;
                }

                $detail = $suratJalan->details->firstWhere('id', (int) $baris['surat_jalan_detail_id']);

                if (!$detail) {
                    throw new RuntimeException('Baris surat jalan tidak ditemukan.');
                }

                $sudahDiretur = (float) $retur->details()
                    ->where('surat_jalan_detail_id', $detail->id)
                    ->sum('jumlah');

                $diretur = (float) DB::table('wms_retur_penjualan_detail')
                    ->where('surat_jalan_detail_id', $detail->id)
                    ->sum('jumlah');

                if ($diretur + $sudahDiretur + $jumlah > (float) $detail->jumlah + 0.000001) {
                    throw new RuntimeException("Jumlah retur {$detail->bahan?->nama} melebihi jumlah yang dikirim.");
                }

                $harga = (float) $detail->harga_satuan;
                $hppSatuan = (float) $detail->jumlah > 0 ? (float) $detail->hpp / (float) $detail->jumlah : 0.0;
                $hpp = round($jumlah * $hppSatuan, 2);

                $retur->details()->create([
                    'surat_jalan_detail_id' => $detail->id,
                    'bahan_id' => $detail->bahan_id,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $harga,
                    'total_harga' => round($jumlah * $harga, 2),
                    'hpp' => $hpp,
                ]);

                $this->kembalikanStok($suratJalan, $detail, $jumlah, $hppSatuan, $retur);

                $totalDpp += round($jumlah * $harga, 2);
                $totalHpp += $hpp;
            }

            if ($totalDpp <= 0) {
                throw new RuntimeException('Retur penjualan harus memiliki minimal satu baris dengan jumlah lebih dari nol.');
            }

            $tarif = (float) ($suratJalan->pesanan?->tarif_ppn ?? 11);
            $kenaPpn = (bool) ($suratJalan->pesanan?->is_ppn ?? true);

            $retur->update([
                'total_dpp' => round($totalDpp, 2),
                'total_ppn' => $kenaPpn ? round($totalDpp * $tarif / 100, 2) : 0,
                'total_hpp' => round($totalHpp, 2),
            ]);

            $jurnal = $this->akuntansi->postReturPenjualan($retur->fresh('details.bahan.tipeBarang', 'suratJalan'));
            $retur->update(['journal_id' => $jurnal->id]);

            if ($faktur) {
                $this->kurangiFaktur($faktur, $retur->fresh());
            }

            return $retur->fresh('details');
        });
    }

    private function kembalikanStok(SuratJalan $suratJalan, $detail, float $jumlah, float $hppSatuan, ReturPenjualan $retur): void
    {
        LayerPersediaan::create([
            'bahan_id' => $detail->bahan_id,
            'gudang_id' => $suratJalan->gudang_id,
            'stock_status' => 'AVAILABLE',
            'source_type' => 'RETUR_PENJUALAN',
            'source_id' => $retur->id,
            'transaction_date' => $retur->tanggal,
            'initial_quantity' => $jumlah,
            'remaining_quantity' => $jumlah,
            'unit_cost' => $hppSatuan,
        ]);

        $this->stok->masuk(
            (int) $suratJalan->gudang_id,
            (int) $detail->bahan_id,
            $jumlah,
            $hppSatuan,
            'RETUR_PENJUALAN_MASUK',
            'RETUR_PENJUALAN',
            $retur->id,
            $retur->nomor
        );
    }

    private function kurangiFaktur(FakturPenjualan $faktur, ReturPenjualan $retur): void
    {
        $nilai = round((float) $retur->total_dpp + (float) $retur->total_ppn, 2);

        if ($nilai > (float) $faktur->sisa_tagihan + 0.005) {
            throw new RuntimeException(
                "Nilai retur melebihi sisa tagihan faktur {$faktur->nomor}. "
                . 'Sistem ini belum punya mekanisme pengembalian uang ke pelanggan.'
            );
        }

        $grandTotal = max(0, round((float) $faktur->grand_total - $nilai, 2));
        $dibayar = round((float) $faktur->pembayaran()
            ->where('status', \App\Models\PenerimaanPembayaran::POSTED)
            ->sum('jumlah'), 2);

        $faktur->update([
            'grand_total' => $grandTotal,
            'sisa_tagihan' => max(0, round($grandTotal - $dibayar, 2)),
            'status' => FakturPenjualan::statusPembayaran($grandTotal, $dibayar),
        ]);
    }
}
