<?php

namespace App\Services;

use App\Models\AlokasiTransferGudang;
use App\Models\Gudang;
use App\Models\InventoryLayer;
use App\Models\TransferGudang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferGudangService
{
    public function __construct(private StokGudangService $stok) {}

    public function konfirmasi(TransferGudang $transfer): TransferGudang
    {
        return DB::transaction(function () use ($transfer) {
            $transfer = TransferGudang::with('details')->lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->status !== TransferGudang::DIAJUKAN) throw new RuntimeException('Hanya transfer diajukan yang dapat dikonfirmasi.');
            $asal = Gudang::lockForUpdate()->findOrFail($transfer->gudang_asal_id);
            $tujuan = Gudang::lockForUpdate()->findOrFail($transfer->gudang_tujuan_id);
            if (!$asal->aktif || !$tujuan->aktif) throw new RuntimeException('Gudang asal dan tujuan harus aktif.');
            if ($asal->jenis === Gudang::RUSAK) throw new RuntimeException('Barang yang sudah masuk Gudang Rusak tidak dapat ditransfer kembali.');
            if ($asal->jenis === Gudang::CONSIDER) throw new RuntimeException('Barang Consider hanya dapat keluar melalui pemeriksaan Consider.');
            if ($tujuan->jenis === Gudang::RUSAK) throw new RuntimeException('Barang harus melalui Consider sebelum dinyatakan rusak.');
            if (!$asal->boleh_transfer || !$tujuan->boleh_transfer) throw new RuntimeException('Gudang tidak mengizinkan transfer.');

            foreach ($transfer->details as $detail) {
                $this->stok->saldo($asal->id, $detail->bahan_id);
                $allocations = $this->stok->ambilLayer($asal->id, $detail->bahan_id, (float) $detail->jumlah, $transfer->tanggal);
                $total = collect($allocations)->sum(fn($a) => $a['jumlah'] * $a['harga']);
                $average = (float) $detail->jumlah > 0 ? $total / (float) $detail->jumlah : 0;
                $this->stok->keluar($asal->id, $detail->bahan_id, (float) $detail->jumlah, $average, 'TRANSFER_KELUAR', 'TRANSFER_GUDANG', $transfer->id, $transfer->nomor_transfer);
                $this->stok->masuk($tujuan->id, $detail->bahan_id, (float) $detail->jumlah, $average, $tujuan->jenis === Gudang::CONSIDER ? 'CONSIDER_MASUK' : 'TRANSFER_MASUK', 'TRANSFER_GUDANG', $transfer->id, $transfer->nomor_transfer);
                foreach ($allocations as $allocation) {
                    $alokasi = AlokasiTransferGudang::create([
                        'detail_transfer_gudang_id' => $detail->id,
                        'inventory_layer_asal_id' => $allocation['layer']->id,
                        'jumlah' => $allocation['jumlah'],
                        'harga_satuan' => $allocation['harga'],
                        'total_nilai' => round($allocation['jumlah'] * $allocation['harga'], 2),
                    ]);
                    $layerTujuan = InventoryLayer::create([
                        'bahan_id' => $detail->bahan_id,
                        'gudang_id' => $tujuan->id,
                        'source_type' => 'TRANSFER_ALLOCATION',
                        'source_id' => $alokasi->id,
                        'transaction_date' => $transfer->tanggal,
                        'initial_quantity' => $allocation['jumlah'],
                        'remaining_quantity' => $allocation['jumlah'],
                        'unit_cost' => $allocation['harga'],
                    ]);
                    $alokasi->update(['inventory_layer_tujuan_id' => $layerTujuan->id]);
                }
            }
            $transfer->update(['status' => TransferGudang::DIKONFIRMASI, 'dikonfirmasi_oleh' => Auth::id(), 'dikonfirmasi_pada' => now()]);
            return $transfer->fresh('details');
        });
    }
}
