<?php

namespace App\Services;

use App\Models\Bahan;
use App\Models\LayerPersediaan;
use App\Models\LotPersediaan;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangDetail;
use App\Models\PesananPembelian;
use App\Models\PesananPembelianDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenerimaanBarangService
{
    public function __construct(
        private WmsAccountingService $accounting,
        private StokGudangService $stokGudang,
        private DocumentNumberService $numbers,
    ) {}

    public function kelebihanPenerimaan(PesananPembelian $po, array $details): array
    {
        $po->loadMissing('details');
        $kelebihan = [];

        foreach ($details as $item) {
            $poDetail = $po->details->where('bahan_id', $item['id_bahan'])->first();

            if (!$poDetail) {
                continue;
            }

            $sisa = (float) $poDetail->jumlah - (float) $poDetail->diterima;

            if ((float) $item['jumlah_barang_diterima'] > $sisa) {
                $kelebihan[] = [
                    'nama' => Bahan::find($item['id_bahan'])->nama ?? 'Bahan #' . $item['id_bahan'],
                    'minta_sisa' => max($sisa, 0),
                    'input' => $item['jumlah_barang_diterima'],
                ];
            }
        }

        return $kelebihan;
    }

    public function terima(PesananPembelian $po, array $data, User $user): PenerimaanBarang
    {
        return DB::transaction(function () use ($po, $data, $user) {
            $lpb = $this->buatHeader($po, $data, $user);

            foreach ($data['details'] as $item) {
                $this->terimaSatuBaris($lpb, $po, $item, !empty($data['confirm_over_receive']));
            }

            $this->accounting->postLpb($lpb);
            $lpb->update(['kunci' => true, 'status' => PenerimaanBarang::POSTED]);

            return $lpb;
        });
    }

    private function buatHeader(PesananPembelian $po, array $data, User $user): PenerimaanBarang
    {
        return PenerimaanBarang::create([
            'id_lpb' => $this->numbers->external('LPB'),
            'tanggal' => $data['tanggal'],
            'no_po' => $data['no_po'],
            'gudang_id' => $po->gudang_id,
            'no_sj' => $data['no_sj'],
            'id_user' => $user->id,
            'flag' => 0,
            'no_invoice' => $data['no_invoice'] ?? null,
            'status' => PenerimaanBarang::DRAFT,
            'jenis_lpb' => $data['jenis_lpb'] ?? 1,
            'ulang' => 0,
            'kunci' => 0,
            'cetakan' => 0,
            'cetak_ulang' => 0,
        ]);
    }

    private function terimaSatuBaris(PenerimaanBarang $lpb, PesananPembelian $po, array $item, bool $izinkanKelebihan): void
    {
        $poDetail = PesananPembelianDetail::where('no_po', $lpb->no_po)
            ->where('bahan_id', $item['id_bahan'])
            ->lockForUpdate()
            ->firstOrFail();

        $diterima = (float) $item['jumlah_barang_diterima'];
        $sisaTerkunci = (float) $poDetail->jumlah - (float) $poDetail->diterima;

        if ($diterima > $sisaTerkunci && !$izinkanKelebihan) {
            throw new RuntimeException('Jumlah penerimaan berubah atau melebihi sisa PO. Periksa kembali lalu konfirmasi over-receive.');
        }

        $bahan = Bahan::findOrFail($item['id_bahan']);

        if ((int) $item['id_kategori'] !== (int) $bahan->tipe_barang) {
            throw new RuntimeException("Kategori bahan {$bahan->nama} tidak sesuai master.");
        }

        $hargaSatuan = (float) $poDetail->harga;

        $lpbDetail = PenerimaanBarangDetail::create([
            'id_lpb' => $lpb->id_lpb,
            'id_bahan' => $item['id_bahan'],
            'id_kategori' => $item['id_kategori'] ?? null,
            'jumlah_barang_diterima' => $diterima,
            'lot_number' => $item['lot_number'] ?? null,
            'harga' => $hargaSatuan,
            'nilai_awal' => $diterima * $hargaSatuan,
            'jumlah_dipakai' => 0,
            'jumlah_tersisa' => $diterima,
            'flag_dipakai' => 1,
        ]);

        $lot = $this->resolveLot($item);

        LayerPersediaan::create([
            'bahan_id' => $item['id_bahan'],
            'gudang_id' => $po->gudang_id,
            'inventory_lot_id' => $lot?->id,
            'source_type' => 'LPB_DETAIL',
            'source_id' => $lpbDetail->id,
            'transaction_date' => $lpb->tanggal,
            'initial_quantity' => $diterima,
            'remaining_quantity' => $diterima,
            'unit_cost' => $hargaSatuan,
        ]);

        $this->stokGudang->masuk(
            (int) $po->gudang_id,
            (int) $item['id_bahan'],
            $diterima,
            $hargaSatuan,
            'PENERIMAAN',
            'LPB',
            $lpb->id,
            $lpb->id_lpb,
        );

        $this->serapPesanan($poDetail, (int) $po->gudang_id, (int) $item['id_bahan'], $diterima);
    }

    private function resolveLot(array $item): ?LotPersediaan
    {
        if (empty($item['lot_number'])) {
            return null;
        }

        $lot = LotPersediaan::firstOrCreate(
            ['bahan_id' => $item['id_bahan'], 'lot_number' => $item['lot_number']],
            ['quality_status' => 'RELEASED', 'expires_at' => $item['expires_at'] ?? null],
        );

        if (!$lot->expires_at && !empty($item['expires_at'])) {
            $lot->update(['expires_at' => $item['expires_at']]);
        }

        return $lot;
    }

    private function serapPesanan(PesananPembelianDetail $poDetail, int $gudangId, int $bahanId, float $diterima): void
    {
        $poDetail->increment('diterima', $diterima);

        $sisaSebelumBarisIni = max(0, (float) $poDetail->jumlah - ((float) $poDetail->diterima - $diterima));
        $potong = min($sisaSebelumBarisIni, $diterima);

        if ($potong <= 0) {
            return;
        }

        Bahan::where('id', $bahanId)->decrement('stok_onpurchase', $potong);
        $this->stokGudang->kurangiPesanan($gudangId, $bahanId, $potong);
    }
}
