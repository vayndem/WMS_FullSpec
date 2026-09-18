<?php

namespace App\Http\Controllers;

use App\Exports\GenericTableExport;
use App\Services\KinerjaSalesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class KinerjaSalesController extends Controller
{
    public function __construct(private KinerjaSalesService $kinerja) {}

    public function index(Request $request)
    {
        $this->authorize('viewKinerjaSales');

        return view('kinerja_sales.index', ['laporan' => $this->laporan($request)]);
    }

    public function pdf(Request $request)
    {
        $this->authorize('viewKinerjaSales');

        $laporan = $this->laporan($request);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Kinerja Sales',
            'columns' => $this->kolom(),
            'rows' => $this->rows($laporan),
            'search' => '',
            'filters' => collect([
                'Dari' => $laporan['dari']->format('d-m-Y'),
                'Sampai' => $laporan['sampai']->format('d-m-Y'),
            ]),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('kinerja-sales-' . now()->format('Ymd') . '.pdf');
    }

    public function excel(Request $request)
    {
        $this->authorize('viewKinerjaSales');

        return Excel::download(
            new GenericTableExport($this->kolom(), $this->rows($this->laporan($request))),
            'kinerja-sales-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    private function laporan(Request $request): array
    {
        $dari = $request->filled('dari') ? Carbon::parse($request->input('dari')) : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? Carbon::parse($request->input('sampai')) : now()->endOfMonth();

        return $this->kinerja->laporan($dari, $sampai);
    }

    private function kolom(): array
    {
        return [
            ['key' => 'sales', 'label' => 'Sales', 'align' => 'left'],
            ['key' => 'jumlah_pesanan', 'label' => 'Pesanan', 'align' => 'right'],
            ['key' => 'nilai_pesanan', 'label' => 'Nilai Pesanan', 'align' => 'right'],
            ['key' => 'jumlah_faktur', 'label' => 'Faktur', 'align' => 'right'],
            ['key' => 'penjualan', 'label' => 'Penjualan (DPP)', 'align' => 'right'],
            ['key' => 'retur', 'label' => 'Retur', 'align' => 'right'],
            ['key' => 'penjualan_bersih', 'label' => 'Penjualan Bersih', 'align' => 'right'],
            ['key' => 'piutang_beredar', 'label' => 'Piutang Beredar', 'align' => 'right'],
        ];
    }

    private function rows(array $laporan): Collection
    {
        return collect($laporan['baris'])->map(fn ($baris) => [
            'sales' => $baris['sales'],
            'jumlah_pesanan' => $baris['jumlah_pesanan'],
            'nilai_pesanan' => number_format($baris['nilai_pesanan'], 2, ',', '.'),
            'jumlah_faktur' => $baris['jumlah_faktur'],
            'penjualan' => number_format($baris['penjualan'], 2, ',', '.'),
            'retur' => number_format($baris['retur'], 2, ',', '.'),
            'penjualan_bersih' => number_format($baris['penjualan_bersih'], 2, ',', '.'),
            'piutang_beredar' => number_format($baris['piutang_beredar'], 2, ',', '.'),
        ]);
    }
}
