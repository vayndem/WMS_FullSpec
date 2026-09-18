<?php

namespace App\Http\Controllers;

use App\Exports\GenericTableExport;
use App\Models\Pelanggan;
use App\Services\PiutangAgingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class PiutangAgingController extends Controller
{
    public function __construct(private PiutangAgingService $aging) {}

    public function index(Request $request)
    {
        $this->authorize('viewPiutangAging');

        $laporan = $this->laporan($request);

        return view('piutang_aging.index', [
            'laporan' => $laporan,
            'ember' => PiutangAgingService::EMBER,
            'pelanggans' => Pelanggan::aktif()->orderBy('nama')->get(['id', 'nama']),
            'pelangganId' => $request->integer('pelanggan_id') ?: null,
            'perTanggal' => $laporan['per_tanggal'],
        ]);
    }

    public function pdf(Request $request)
    {
        $this->authorize('viewPiutangAging');

        $laporan = $this->laporan($request);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Umur Piutang per ' . $laporan['per_tanggal']->format('d-m-Y'),
            'columns' => $this->kolom(),
            'rows' => $this->rows($laporan),
            'search' => '',
            'filters' => collect(array_filter([
                'Per Tanggal' => $laporan['per_tanggal']->format('d-m-Y'),
                'Pelanggan' => $this->namaPelanggan($request),
            ])),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('umur-piutang-' . $laporan['per_tanggal']->format('Ymd') . '.pdf');
    }

    public function excel(Request $request)
    {
        $this->authorize('viewPiutangAging');

        $laporan = $this->laporan($request);

        return Excel::download(
            new GenericTableExport($this->kolom(), $this->rows($laporan)),
            'umur-piutang-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    private function laporan(Request $request): array
    {
        $tanggal = $request->filled('per_tanggal')
            ? Carbon::parse($request->input('per_tanggal'))
            : now();

        return $this->aging->laporan($tanggal, $request->integer('pelanggan_id') ?: null);
    }

    private function namaPelanggan(Request $request): ?string
    {
        $id = $request->integer('pelanggan_id') ?: null;

        return $id ? Pelanggan::whereKey($id)->value('nama') : null;
    }

    private function kolom(): array
    {
        return [
            ['key' => 'pelanggan', 'label' => 'Pelanggan', 'align' => 'left'],
            ['key' => 'nomor', 'label' => 'Faktur', 'align' => 'left'],
            ['key' => 'tanggal', 'label' => 'Tanggal', 'align' => 'left'],
            ['key' => 'jatuh_tempo', 'label' => 'Jatuh Tempo', 'align' => 'left'],
            ['key' => 'hari_lewat', 'label' => 'Hari Lewat', 'align' => 'right'],
            ['key' => 'ember', 'label' => 'Umur', 'align' => 'left'],
            ['key' => 'tagihan', 'label' => 'Tagihan', 'align' => 'right'],
            ['key' => 'dibayar', 'label' => 'Dibayar', 'align' => 'right'],
            ['key' => 'sisa', 'label' => 'Sisa Piutang', 'align' => 'right'],
        ];
    }

    private function rows(array $laporan): Collection
    {
        return collect($laporan['baris'])->map(fn ($baris) => [
            'pelanggan' => $baris['pelanggan'],
            'nomor' => $baris['nomor'],
            'tanggal' => $baris['tanggal']?->format('d-m-Y'),
            'jatuh_tempo' => $baris['jatuh_tempo']?->format('d-m-Y') ?? '-',
            'hari_lewat' => $baris['hari_lewat'] > 0 ? $baris['hari_lewat'] : '-',
            'ember' => $baris['ember'],
            'tagihan' => number_format($baris['tagihan'], 2, ',', '.'),
            'dibayar' => number_format($baris['dibayar'], 2, ',', '.'),
            'sisa' => number_format($baris['sisa'], 2, ',', '.'),
        ]);
    }
}
