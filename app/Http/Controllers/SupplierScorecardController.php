<?php

namespace App\Http\Controllers;

use App\Exports\GenericTableExport;
use App\Services\SupplierScorecardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class SupplierScorecardController extends Controller
{
    public function __construct(private SupplierScorecardService $scorecard) {}

    public function index(Request $request)
    {
        $this->authorize('viewProcurementAnalytics');

        $data = $this->data($request);

        return view('supplier_scorecard.index', compact('data'));
    }

    public function pdf(Request $request)
    {
        $this->authorize('viewProcurementAnalytics');

        $data = $this->data($request);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Kartu Skor Supplier ' . $data['from']->format('d-m-Y') . ' s.d. ' . $data['to']->format('d-m-Y'),
            'columns' => $this->kolom(),
            'rows' => $this->rows($data, true),
            'search' => '',
            'filters' => collect(['Target Lead Time' => $data['target_lead_time'] . ' hari']),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('kartu-skor-supplier.pdf');
    }

    public function excel(Request $request)
    {
        $this->authorize('viewProcurementAnalytics');

        $data = $this->data($request);

        return Excel::download(
            new GenericTableExport($this->kolom(), $this->rows($data, false)),
            'kartu-skor-supplier-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    private function data(Request $request): array
    {
        return $this->scorecard->ringkasan(
            $this->tanggal($request->input('from'), today()->startOfYear()),
            $this->tanggal($request->input('to'), today()),
            (int) ($request->input('target_lead_time') ?: SupplierScorecardService::TARGET_LEAD_TIME_HARI)
        );
    }

    private function tanggal(?string $nilai, Carbon $bawaan): Carbon
    {
        if (!$nilai) {
            return $bawaan;
        }

        try {
            return Carbon::parse($nilai)->startOfDay();
        } catch (\Exception) {
            return $bawaan;
        }
    }

    private function kolom(): array
    {
        return [
            ['key' => 'supplier', 'label' => 'Supplier', 'align' => 'left'],
            ['key' => 'penerimaan', 'label' => 'Penerimaan', 'align' => 'right'],
            ['key' => 'lead_time_rata', 'label' => 'Lead Time Rata (hari)', 'align' => 'right'],
            ['key' => 'rentang', 'label' => 'Tercepat - Terlama', 'align' => 'right'],
            ['key' => 'ketepatan', 'label' => 'Dalam Target', 'align' => 'right'],
            ['key' => 'nilai_pembelian', 'label' => 'Nilai Pembelian', 'align' => 'right'],
            ['key' => 'nilai_retur', 'label' => 'Nilai Retur', 'align' => 'right'],
            ['key' => 'rasio_retur', 'label' => 'Rasio Retur', 'align' => 'right'],
            ['key' => 'rasio_reject', 'label' => 'Rasio Reject QC', 'align' => 'right'],
        ];
    }

    private function rows(array $data, bool $formatted)
    {
        $uang = fn ($nilai) => $formatted ? 'Rp ' . number_format($nilai, 0, ',', '.') : (float) $nilai;
        $persen = fn ($nilai) => $nilai === null
            ? ($formatted ? 'Belum ada data' : null)
            : ($formatted ? number_format($nilai, 1, ',', '.') . '%' : (float) $nilai);

        return $data['baris']->map(fn ($row) => [
            'supplier' => $row['supplier'],
            'penerimaan' => $row['penerimaan'],
            'lead_time_rata' => $row['lead_time_rata'] === null
                ? '-'
                : ($formatted ? number_format($row['lead_time_rata'], 1, ',', '.') : (float) $row['lead_time_rata']),
            'rentang' => $row['lead_time_tercepat'] === null
                ? '-'
                : $row['lead_time_tercepat'] . ' - ' . $row['lead_time_terlama'],
            'ketepatan' => $persen($row['ketepatan']),
            'nilai_pembelian' => $uang($row['nilai_pembelian']),
            'nilai_retur' => $uang($row['nilai_retur']),
            'rasio_retur' => $persen($row['rasio_retur']),
            'rasio_reject' => $persen($row['rasio_reject']),
        ])->values();
    }
}
