<?php

namespace App\Services;

use App\Models\FakturPenjualan;
use App\Models\PesananPenjualan;
use App\Models\ReturPenjualan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class KinerjaSalesService
{
    public const TANPA_SALES = 'Tanpa sales';

    public function laporan(Carbon $dari, Carbon $sampai): array
    {
        $dari = $dari->copy()->startOfDay();
        $sampai = $sampai->copy()->endOfDay();

        $pesanan = PesananPenjualan::whereBetween('tanggal', [$dari, $sampai])
            ->where('status', '!=', PesananPenjualan::DIBATALKAN)
            ->selectRaw('sales_user_id, COUNT(*) as jumlah, SUM(total_dpp) as nilai')
            ->groupBy('sales_user_id')
            ->get()
            ->keyBy('sales_user_id');

        $faktur = FakturPenjualan::whereBetween('tanggal', [$dari, $sampai])
            ->whereNotIn('status', [FakturPenjualan::DRAFT, FakturPenjualan::VOID])
            ->selectRaw('sales_user_id, COUNT(*) as jumlah, SUM(total_dpp) as nilai, SUM(sisa_tagihan) as piutang')
            ->groupBy('sales_user_id')
            ->get()
            ->keyBy('sales_user_id');

        $retur = ReturPenjualan::query()
            ->join('wms_faktur_penjualan as f', 'f.id', '=', 'wms_retur_penjualan.faktur_penjualan_id')
            ->whereBetween('wms_retur_penjualan.tanggal', [$dari, $sampai])
            ->where('wms_retur_penjualan.status', ReturPenjualan::POSTED)
            ->selectRaw('f.sales_user_id as sales_user_id, SUM(wms_retur_penjualan.total_dpp) as nilai')
            ->groupBy('f.sales_user_id')
            ->get()
            ->keyBy('sales_user_id');

        $ids = collect([$pesanan->keys(), $faktur->keys(), $retur->keys()])
            ->flatten()
            ->unique()
            ->values();

        $nama = User::whereIn('id', $ids->filter())->pluck('name', 'id');

        $baris = $ids
            ->map(function ($id) use ($pesanan, $faktur, $retur, $nama) {
                $penjualan = round((float) ($faktur[$id]->nilai ?? 0), 2);
                $nilaiRetur = round((float) ($retur[$id]->nilai ?? 0), 2);

                return [
                    'sales_user_id' => $id,
                    'sales' => $id ? ($nama[$id] ?? 'Pengguna terhapus') : self::TANPA_SALES,
                    'jumlah_pesanan' => (int) ($pesanan[$id]->jumlah ?? 0),
                    'nilai_pesanan' => round((float) ($pesanan[$id]->nilai ?? 0), 2),
                    'jumlah_faktur' => (int) ($faktur[$id]->jumlah ?? 0),
                    'penjualan' => $penjualan,
                    'retur' => $nilaiRetur,
                    'penjualan_bersih' => round($penjualan - $nilaiRetur, 2),
                    'piutang_beredar' => round((float) ($faktur[$id]->piutang ?? 0), 2),
                ];
            })
            ->sortByDesc('penjualan_bersih')
            ->values();

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'baris' => $baris,
            'total' => $this->total($baris),
        ];
    }

    private function total(Collection $baris): array
    {
        return [
            'jumlah_pesanan' => (int) $baris->sum('jumlah_pesanan'),
            'nilai_pesanan' => round($baris->sum('nilai_pesanan'), 2),
            'jumlah_faktur' => (int) $baris->sum('jumlah_faktur'),
            'penjualan' => round($baris->sum('penjualan'), 2),
            'retur' => round($baris->sum('retur'), 2),
            'penjualan_bersih' => round($baris->sum('penjualan_bersih'), 2),
            'piutang_beredar' => round($baris->sum('piutang_beredar'), 2),
        ];
    }
}
