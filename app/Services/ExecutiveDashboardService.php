<?php

namespace App\Services;

use App\Models\FakturPembelian;
use App\Models\JurnalDetail;
use App\Models\KategoriBahan;
use Illuminate\Support\Carbon;

class ExecutiveDashboardService
{
    public function inventoryValueTrend(int $months = 6): array
    {
        $persediaanAccountIds = KategoriBahan::whereNotNull('coa_persediaan_id')->distinct()->pluck('coa_persediaan_id');
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $balance = JurnalDetail::query()
                ->whereIn('coa_id', $persediaanAccountIds)
                ->whereHas('jurnal', fn($query) => $query->where('status', 'POSTED')->whereDate('tanggal', '<=', $monthEnd))
                ->selectRaw('SUM(debit - kredit) as balance')
                ->value('balance');
            $labels[] = $monthEnd->translatedFormat('M Y');
            $data[] = round((float) $balance, 2);
        }

        return compact('labels', 'data');
    }

    public function apAgingBuckets(): array
    {
        $buckets = [
            'Belum Jatuh Tempo' => 0.0,
            '1-30 Hari' => 0.0,
            '31-60 Hari' => 0.0,
            '61-90 Hari' => 0.0,
            '> 90 Hari' => 0.0,
        ];

        $today = now()->startOfDay();
        FakturPembelian::whereNotIn('status', [FakturPembelian::VOID, FakturPembelian::PENDING_APPROVAL])
            ->where('sisa_tagihan', '>', 0)
            ->get(['sisa_tagihan', 'tgl_deadline_pembayaran'])
            ->each(function ($invoice) use (&$buckets, $today) {
                $dueDate = $invoice->tgl_deadline_pembayaran ? Carbon::parse($invoice->tgl_deadline_pembayaran)->startOfDay() : null;
                if (!$dueDate || $dueDate->greaterThanOrEqualTo($today)) {
                    $buckets['Belum Jatuh Tempo'] += (float) $invoice->sisa_tagihan;
                    return;
                }
                $daysOverdue = $dueDate->diffInDays($today);
                $bucket = match (true) {
                    $daysOverdue <= 30 => '1-30 Hari',
                    $daysOverdue <= 60 => '31-60 Hari',
                    $daysOverdue <= 90 => '61-90 Hari',
                    default => '> 90 Hari',
                };
                $buckets[$bucket] += (float) $invoice->sisa_tagihan;
            });

        return [
            'labels' => array_keys($buckets),
            'data' => array_map(fn($amount) => round($amount, 2), array_values($buckets)),
        ];
    }

    public function topSuppliers(int $limit = 5): array
    {
        $rows = FakturPembelian::whereNotIn('status', [FakturPembelian::VOID, FakturPembelian::PENDING_APPROVAL])
            ->selectRaw('kode_supplier, SUM(grand_total) as total')
            ->groupBy('kode_supplier')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('supplier')
            ->get();

        return [
            'labels' => $rows->map(fn($row) => $row->supplier->nama ?? '-')->all(),
            'data' => $rows->map(fn($row) => round((float) $row->total, 2))->all(),
        ];
    }

    public function costByCategoryThisYear(): array
    {
        $categories = KategoriBahan::whereNotNull('coa_beban_id')->get(['id', 'katnama', 'coa_beban_id']);
        $bebanSums = JurnalDetail::query()
            ->whereIn('coa_id', $categories->pluck('coa_beban_id')->unique())
            ->whereHas('jurnal', fn($query) => $query->where('status', 'POSTED')->whereYear('tanggal', now()->year))
            ->selectRaw('coa_id, SUM(debit) as total')
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        $rows = $categories->groupBy('coa_beban_id')->map(function ($group) use ($bebanSums) {
            $coaId = $group->first()->coa_beban_id;
            return [
                'label' => $group->pluck('katnama')->implode(' / '),
                'amount' => round((float) ($bebanSums->get($coaId)?->total ?? 0), 2),
            ];
        })->filter(fn($row) => $row['amount'] > 0)->sortByDesc('amount')->values();

        return [
            'labels' => $rows->pluck('label')->all(),
            'data' => $rows->pluck('amount')->all(),
        ];
    }
}
