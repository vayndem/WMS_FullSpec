<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierScorecardService
{
    public const TARGET_LEAD_TIME_HARI = 14;

    public function ringkasan(Carbon $from, Carbon $to, int $targetLeadTime = self::TARGET_LEAD_TIME_HARI): array
    {
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $targetLeadTime = max(1, $targetLeadTime);

        $pengiriman = $this->pengiriman($from, $to, $targetLeadTime);
        $retur = $this->retur($from, $to);
        $kualitas = $this->kualitas($from, $to);

        $baris = $pengiriman->map(function (array $row) use ($retur, $kualitas) {
            $supplierId = $row['supplier_id'];
            $nilaiRetur = (float) ($retur[$supplierId] ?? 0);
            $qc = $kualitas[$supplierId] ?? null;

            $diperiksa = (float) ($qc->diperiksa ?? 0);
            $ditolak = (float) ($qc->ditolak ?? 0);

            return $row + [
                'nilai_retur' => round($nilaiRetur, 2),
                'rasio_retur' => $row['nilai_pembelian'] > 0
                    ? round($nilaiRetur / $row['nilai_pembelian'] * 100, 2)
                    : null,
                'qc_diperiksa' => $diperiksa,
                'qc_ditolak' => $ditolak,
                'rasio_reject' => $diperiksa > 0 ? round($ditolak / $diperiksa * 100, 2) : null,
            ];
        });

        return [
            'from' => $from,
            'to' => $to,
            'target_lead_time' => $targetLeadTime,
            'baris' => $baris,
            'ada_data_qc' => $baris->contains(fn ($row) => $row['rasio_reject'] !== null),
            'total_penerimaan' => $baris->sum('penerimaan'),
            'total_nilai_pembelian' => round($baris->sum('nilai_pembelian'), 2),
            'total_nilai_retur' => round($baris->sum('nilai_retur'), 2),
        ];
    }

    private function pengiriman(Carbon $from, Carbon $to, int $targetLeadTime): Collection
    {
        $selisih = 'DATEDIFF(lpb.tanggal, po.tanggal)';

        return DB::table('wms_penerimaan_barang as lpb')
            ->join('wms_pesanan_pembelian as po', 'po.no_po', '=', 'lpb.no_po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->leftJoin('wms_penerimaan_barang_detail as d', 'd.id_lpb', '=', 'lpb.id_lpb')
            ->where('lpb.document_type', 'GOODS')
            ->where('lpb.status', 'POSTED')
            ->whereBetween('lpb.tanggal', [$from->toDateString(), $to->toDateString()])
            ->groupBy('s.id', 's.nama')
            ->select([
                's.id as supplier_id',
                's.nama as supplier',
                DB::raw('COUNT(DISTINCT lpb.id) as penerimaan'),
                DB::raw("ROUND(AVG({$selisih}), 1) as lead_time_rata"),
                DB::raw("MIN({$selisih}) as lead_time_tercepat"),
                DB::raw("MAX({$selisih}) as lead_time_terlama"),
                DB::raw("COUNT(DISTINCT CASE WHEN {$selisih} <= {$targetLeadTime} THEN lpb.id END) as penerimaan_tepat"),
                DB::raw('COALESCE(SUM(d.jumlah_barang_diterima * d.harga), 0) as nilai_pembelian'),
            ])
            ->orderByDesc('nilai_pembelian')
            ->get()
            ->map(fn ($row) => [
                'supplier_id' => (int) $row->supplier_id,
                'supplier' => $row->supplier,
                'penerimaan' => (int) $row->penerimaan,
                'lead_time_rata' => $row->lead_time_rata === null ? null : (float) $row->lead_time_rata,
                'lead_time_tercepat' => $row->lead_time_tercepat === null ? null : (int) $row->lead_time_tercepat,
                'lead_time_terlama' => $row->lead_time_terlama === null ? null : (int) $row->lead_time_terlama,
                'penerimaan_tepat' => (int) $row->penerimaan_tepat,
                'ketepatan' => (int) $row->penerimaan > 0
                    ? round((int) $row->penerimaan_tepat / (int) $row->penerimaan * 100, 1)
                    : null,
                'nilai_pembelian' => round((float) $row->nilai_pembelian, 2),
            ]);
    }

    private function retur(Carbon $from, Carbon $to): Collection
    {
        return DB::table('wms_retur_pembelian as r')
            ->join('wms_penerimaan_barang as lpb', 'lpb.id', '=', 'r.lpb_id')
            ->join('wms_pesanan_pembelian as po', 'po.no_po', '=', 'lpb.no_po')
            ->whereBetween('lpb.tanggal', [$from->toDateString(), $to->toDateString()])
            ->groupBy('po.supplier_id')
            ->select('po.supplier_id', DB::raw('SUM(r.total_nilai) as nilai'))
            ->pluck('nilai', 'supplier_id');
    }

    private function kualitas(Carbon $from, Carbon $to): Collection
    {
        return DB::table('wms_pemeriksaan_kualitas as q')
            ->join('wms_pemeriksaan_kualitas_detail as qd', 'qd.quality_inspection_id', '=', 'q.id')
            ->join('wms_penerimaan_barang as lpb', 'lpb.id', '=', 'q.lpb_id')
            ->join('wms_pesanan_pembelian as po', 'po.no_po', '=', 'lpb.no_po')
            ->whereBetween('lpb.tanggal', [$from->toDateString(), $to->toDateString()])
            ->groupBy('po.supplier_id')
            ->select([
                'po.supplier_id',
                DB::raw('SUM(qd.quantity_received) as diperiksa'),
                DB::raw('SUM(qd.quantity_rejected) as ditolak'),
            ])
            ->get()
            ->keyBy('supplier_id');
    }
}
