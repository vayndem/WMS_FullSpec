<?php

namespace App\Http\Controllers;

use App\Exports\GenericTableExport;
use App\Models\DataPesanan;
use App\Services\BomService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class VariansPemakaianController extends Controller
{
    public function __construct(private BomService $bom) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', DataPesanan::class);

        $pesanan = $this->pesanan($request);

        return view('varians_pemakaian.index', [
            'ringkasan' => $this->bom->ringkasan(),
            'pesanan' => $pesanan,
            'varians' => $pesanan ? $this->bom->varians($pesanan) : null,
            'pilihan' => DataPesanan::with('bahanHasil')
                ->whereNotIn('status', [DataPesanan::DIBATALKAN])
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'nomor', 'status', 'bahan_hasil_id']),
        ]);
    }

    public function pdf(Request $request)
    {
        $this->authorize('viewAny', DataPesanan::class);

        $pesanan = $this->pesanan($request);
        abort_if(!$pesanan, 404);

        $varians = $this->bom->varians($pesanan);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Varians Pemakaian ' . $pesanan->nomor,
            'columns' => $this->kolom(),
            'rows' => $this->rows($varians),
            'search' => '',
            'filters' => collect([
                'Perintah Kerja' => $pesanan->nomor,
                'Basis' => $this->angka($varians['basis'], 6),
                'BOM' => $varians['bom']?->kode ?? 'Tidak ada BOM aktif',
            ]),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('varians-pemakaian-' . $pesanan->nomor . '.pdf');
    }

    public function excel(Request $request)
    {
        $this->authorize('viewAny', DataPesanan::class);

        $pesanan = $this->pesanan($request);
        abort_if(!$pesanan, 404);

        return Excel::download(
            new GenericTableExport($this->kolom(), $this->rows($this->bom->varians($pesanan))),
            'varians-pemakaian-' . $pesanan->nomor . '-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    private function pesanan(Request $request): ?DataPesanan
    {
        $id = $request->integer('pesanan') ?: null;

        return $id ? DataPesanan::with('bahanHasil')->find($id) : null;
    }

    private function kolom(): array
    {
        return [
            ['key' => 'bahan', 'label' => 'Bahan', 'align' => 'left'],
            ['key' => 'standar', 'label' => 'Standar', 'align' => 'right'],
            ['key' => 'aktual', 'label' => 'Aktual', 'align' => 'right'],
            ['key' => 'selisih', 'label' => 'Selisih', 'align' => 'right'],
            ['key' => 'harga_acuan', 'label' => 'Harga Acuan', 'align' => 'right'],
            ['key' => 'nilai_standar', 'label' => 'Nilai Standar', 'align' => 'right'],
            ['key' => 'nilai_aktual', 'label' => 'Nilai Aktual', 'align' => 'right'],
            ['key' => 'selisih_nilai', 'label' => 'Selisih Nilai', 'align' => 'right'],
            ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
        ];
    }

    private function rows(array $varians): Collection
    {
        return collect($varians['baris'])->map(fn ($baris) => [
            'bahan' => $baris['bahan'],
            'standar' => $baris['standar'] === null ? '-' : $this->angka($baris['standar'], 6),
            'aktual' => $this->angka($baris['aktual'], 6),
            'selisih' => $baris['selisih'] === null ? '-' : $this->angka($baris['selisih'], 6),
            'harga_acuan' => $baris['harga_acuan'] === null ? '-' : $this->angka($baris['harga_acuan'], 2),
            'nilai_standar' => $baris['nilai_standar'] === null ? '-' : $this->angka($baris['nilai_standar'], 2),
            'nilai_aktual' => $this->angka($baris['nilai_aktual'], 2),
            'selisih_nilai' => $baris['selisih_nilai'] === null ? '-' : $this->angka($baris['selisih_nilai'], 2),
            'status' => $baris['status'],
        ]);
    }

    private function angka(?float $nilai, int $desimal): string
    {
        return number_format((float) $nilai, $desimal, ',', '.');
    }
}
