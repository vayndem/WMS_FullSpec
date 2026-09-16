<?php

namespace App\Services;

use App\Models\BarangTitipan;
use App\Models\PengeluaranBarang;
use App\Models\PengeluaranBarangDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PengeluaranBarangService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function buat(array $data): PengeluaranBarang
    {
        return DB::transaction(function () use ($data) {
            $pengeluaran = PengeluaranBarang::create([
                'nomor' => $this->numbers->internal('GTP', 'WH'),
                'tanggal' => $data['tanggal'],
                'supplier_id' => $data['supplier_id'],
                'pesanan_jasa_id' => $data['pesanan_jasa_id'] ?? null,
                'keperluan' => $data['keperluan'],
                'status' => PengeluaranBarang::DRAFT,
                'estimasi_kembali' => $data['estimasi_kembali'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'dikeluarkan_oleh' => Auth::id(),
            ]);

            foreach ($data['items'] as $item) {
                $pengeluaran->details()->create([
                    'aset_id' => $item['aset_id'] ?? null,
                    'deskripsi' => $item['deskripsi'],
                    'nomor_seri' => $item['nomor_seri'] ?? null,
                    'jumlah' => $item['jumlah'] ?? 1,
                    'satuan' => $item['satuan'] ?? 'UNIT',
                    'kondisi_keluar' => $item['kondisi_keluar'] ?? null,
                    'status' => PengeluaranBarangDetail::DI_VENDOR,
                ]);
            }

            return $pengeluaran->fresh('details');
        });
    }

    public function kirim(PengeluaranBarang $pengeluaran): PengeluaranBarang
    {
        if ($pengeluaran->status !== PengeluaranBarang::DRAFT) {
            throw new RuntimeException('Hanya gate pass berstatus DRAFT yang dapat dikirim.');
        }

        if ($pengeluaran->details()->count() === 0) {
            throw new RuntimeException('Gate pass tanpa barang tidak dapat dikirim.');
        }

        $pengeluaran->update(['status' => PengeluaranBarang::DI_VENDOR]);

        return $pengeluaran;
    }

    public function terimaKembali(PengeluaranBarang $pengeluaran, array $barisKembali, string $tanggal): PengeluaranBarang
    {
        if (!in_array($pengeluaran->status, [PengeluaranBarang::DI_VENDOR, PengeluaranBarang::SEBAGIAN_KEMBALI], true)) {
            throw new RuntimeException('Gate pass ini tidak sedang berada di vendor.');
        }

        if ($barisKembali === []) {
            throw new RuntimeException('Pilih minimal satu barang yang kembali.');
        }

        return DB::transaction(function () use ($pengeluaran, $barisKembali, $tanggal) {
            foreach ($barisKembali as $detailId => $info) {
                $detail = $pengeluaran->details()->whereKey($detailId)->first();

                if (!$detail || $detail->status !== PengeluaranBarangDetail::DI_VENDOR) {
                    continue;
                }

                $detail->update([
                    'status' => ($info['status'] ?? PengeluaranBarangDetail::KEMBALI) === PengeluaranBarangDetail::TIDAK_KEMBALI
                        ? PengeluaranBarangDetail::TIDAK_KEMBALI
                        : PengeluaranBarangDetail::KEMBALI,
                    'kondisi_kembali' => $info['kondisi_kembali'] ?? null,
                    'tanggal_kembali' => $tanggal,
                ]);
            }

            $masihDiVendor = $pengeluaran->details()
                ->where('status', PengeluaranBarangDetail::DI_VENDOR)->count();

            $pengeluaran->update([
                'status' => $masihDiVendor > 0 ? PengeluaranBarang::SEBAGIAN_KEMBALI : PengeluaranBarang::SELESAI,
                'tanggal_kembali' => $masihDiVendor > 0 ? null : $tanggal,
            ]);

            return $pengeluaran->fresh('details');
        });
    }

    public function batalkan(PengeluaranBarang $pengeluaran): PengeluaranBarang
    {
        if (!in_array($pengeluaran->status, [PengeluaranBarang::DRAFT, PengeluaranBarang::DIBATALKAN], true)) {
            throw new RuntimeException(
                'Gate pass yang barangnya sudah keluar tidak dapat dibatalkan. Catat penerimaan kembali (atau tandai tidak kembali) lewat alur pengembalian.'
            );
        }

        $pengeluaran->update(['status' => PengeluaranBarang::DIBATALKAN]);

        return $pengeluaran;
    }

    public function terimaTitipan(array $data): BarangTitipan
    {
        return BarangTitipan::create([
            'nomor' => $this->numbers->internal('TTP', 'WH'),
            'supplier_id' => $data['supplier_id'],
            'pengeluaran_id' => $data['pengeluaran_id'] ?? null,
            'deskripsi' => $data['deskripsi'],
            'nomor_seri' => $data['nomor_seri'] ?? null,
            'jumlah' => $data['jumlah'] ?? 1,
            'satuan' => $data['satuan'] ?? 'UNIT',
            'tanggal_terima' => $data['tanggal_terima'],
            'estimasi_kembali' => $data['estimasi_kembali'] ?? null,
            'nilai_taksiran' => $data['nilai_taksiran'] ?? null,
            'status' => BarangTitipan::DITERIMA,
            'catatan' => $data['catatan'] ?? null,
            'diterima_oleh' => Auth::id(),
        ]);
    }

    public function selesaikanTitipan(BarangTitipan $titipan, string $status, string $tanggal): BarangTitipan
    {
        if ($titipan->status !== BarangTitipan::DITERIMA) {
            throw new RuntimeException('Barang titipan ini sudah tidak berada dalam penguasaan kita.');
        }

        if (!in_array($status, [BarangTitipan::DIKEMBALIKAN, BarangTitipan::DIBELI, BarangTitipan::HILANG], true)) {
            throw new RuntimeException('Status penyelesaian barang titipan tidak dikenal.');
        }

        $titipan->update(['status' => $status, 'tanggal_kembali' => $tanggal]);

        return $titipan;
    }

    public function terlambat(): Collection
    {
        $keluar = PengeluaranBarang::with('supplier')
            ->whereIn('status', [PengeluaranBarang::DI_VENDOR, PengeluaranBarang::SEBAGIAN_KEMBALI])
            ->whereNotNull('estimasi_kembali')
            ->whereDate('estimasi_kembali', '<', today())
            ->orderBy('estimasi_kembali')
            ->get();

        $titipan = BarangTitipan::with('supplier')
            ->where('status', BarangTitipan::DITERIMA)
            ->whereNotNull('estimasi_kembali')
            ->whereDate('estimasi_kembali', '<', today())
            ->orderBy('estimasi_kembali')
            ->get();

        return collect(['keluar' => $keluar, 'titipan' => $titipan]);
    }
}
