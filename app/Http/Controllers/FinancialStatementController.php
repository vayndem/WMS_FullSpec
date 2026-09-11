<?php

namespace App\Http\Controllers;

use App\Models\BaganAkun;
use App\Http\Requests\StorePerhitunganPajakPenghasilanRequest;
use App\Models\PerhitunganPajakPenghasilan;
use App\Services\FinancialStatementService;
use App\Services\PajakPenghasilanService;
use App\Services\RekonsiliasiFiskalService;
use App\Exports\FinancialStatementExport;
use RuntimeException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class FinancialStatementController extends Controller
{
    public function __construct(
        private FinancialStatementService $statements,
        private RekonsiliasiFiskalService $fiskal,
        private PajakPenghasilanService $pph,
    ) {}

    private function fiskalSections(array $data, bool $formatted): array
    {
        $uang = fn ($nilai) => $formatted ? 'Rp ' . number_format($nilai, 0, ',', '.') : (float) $nilai;
        $baris = fn ($rows, string $kolom) => collect($rows)->map(fn ($row) => [
            'nama_akun' => $row['kode_akun'] . ' — ' . $row['nama_akun'],
            'jumlah' => $uang($row[$kolom]),
        ]);

        return [
            ['label' => 'Laba (Rugi) Komersial', 'rows' => collect([[
                'nama_akun' => 'Laba komersial sebelum pajak',
                'jumlah' => $uang($data['laba_komersial']),
            ]]), 'subtotal' => null],
            ['label' => 'Koreksi Positif — Beda Tetap', 'rows' => $baris($data['beda_tetap_positif'], 'koreksi'),
                'subtotal' => ['nama_akun' => 'Subtotal', 'jumlah' => $uang($data['total_koreksi_positif'])]],
            ['label' => 'Koreksi Negatif — Beda Tetap', 'rows' => $baris($data['beda_tetap_negatif'], 'koreksi'),
                'subtotal' => ['nama_akun' => 'Subtotal', 'jumlah' => $uang($data['total_koreksi_negatif'])]],
            ['label' => 'Beda Waktu (menunggu jadwal penyusutan fiskal)', 'rows' => $baris($data['beda_waktu'], 'jumlah'),
                'subtotal' => ['nama_akun' => 'Koreksi diterapkan', 'jumlah' => $uang($data['total_koreksi_beda_waktu'])]],
        ];
    }

    public function pajakPenghasilan(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $tahun = (int) ($request->input('tahun_pajak') ?: today()->year);
        $peredaranBruto = (float) ($request->input('peredaran_bruto') ?: 0);
        $data = $this->pph->hitung($tahun, $peredaranBruto);
        $riwayat = PerhitunganPajakPenghasilan::with('jurnal')->orderByDesc('tahun_pajak')->limit(10)->get();
        $tersimpan = $riwayat->firstWhere('tahun_pajak', $tahun);

        return view('financial_statements.pajak-penghasilan', compact('tahun', 'peredaranBruto', 'data', 'riwayat', 'tersimpan'));
    }

    public function postingPajakPenghasilan(StorePerhitunganPajakPenghasilanRequest $request)
    {
        try {
            $perhitungan = $this->pph->posting(
                (int) $request->validated('tahun_pajak'),
                (float) $request->validated('peredaran_bruto'),
                $request->validated('posting_date'),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        return redirect()
            ->route('financial-statements.pajak-penghasilan', [
                'tahun_pajak' => $perhitungan->tahun_pajak,
                'peredaran_bruto' => $perhitungan->peredaran_bruto,
            ])
            ->with('success', "Jurnal PPh Badan tahun {$perhitungan->tahun_pajak} diposting ({$perhitungan->jurnal->no_jurnal}).");
    }

    public function rekonsiliasiFiskal(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $from = $this->parseDate($request->input('from'), today()->startOfYear());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->fiskal->reconcile($from, $to);

        return view('financial_statements.rekonsiliasi-fiskal', compact('from', 'to', 'data'));
    }

    public function rekonsiliasiFiskalPdf(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $from = $this->parseDate($request->input('from'), today()->startOfYear());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->fiskal->reconcile($from, $to);

        return Pdf::loadView('reports.financial-statement-pdf', [
            'title' => 'Rekonsiliasi Fiskal ' . $from->format('d-m-Y') . ' s.d. ' . $to->format('d-m-Y'),
            'columns' => [
                ['key' => 'nama_akun', 'label' => 'Keterangan', 'align' => 'left'],
                ['key' => 'jumlah', 'label' => 'Jumlah', 'align' => 'right'],
            ],
            'sections' => $this->fiskalSections($data, true),
            'footer' => 'Laba (Rugi) Fiskal: Rp ' . number_format($data['laba_fiskal'], 0, ',', '.'),
            'generatedAt' => now(),
        ])->stream('rekonsiliasi-fiskal.pdf');
    }

    public function rekonsiliasiFiskalExcel(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $from = $this->parseDate($request->input('from'), today()->startOfYear());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->fiskal->reconcile($from, $to);

        return Excel::download(
            new FinancialStatementExport(
                [['key' => 'nama_akun', 'label' => 'Keterangan'], ['key' => 'jumlah', 'label' => 'Jumlah']],
                $this->fiskalSections($data, false),
                'Laba (Rugi) Fiskal: ' . number_format($data['laba_fiskal'], 2, ',', '.')
            ),
            'rekonsiliasi-fiskal-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function neracaSaldo(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $asOf = $this->parseDate($request->input('as_of'), today());
        $data = $this->statements->trialBalance($asOf);

        return view('financial_statements.neraca-saldo', compact('asOf', 'data'));
    }

    public function neracaSaldoPdf(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $asOf = $this->parseDate($request->input('as_of'), today());
        $data = $this->statements->trialBalance($asOf);

        $rows = $data['rows']->map(fn ($row) => [
            'kode_akun' => $row['account']->kode_akun,
            'nama_akun' => $row['account']->nama_akun,
            'debit' => 'Rp ' . number_format($row['debit'], 0, ',', '.'),
            'kredit' => 'Rp ' . number_format($row['kredit'], 0, ',', '.'),
        ]);

        return Pdf::loadView('reports.financial-statement-pdf', [
            'title' => 'Neraca Saldo per ' . $asOf->format('d-m-Y'),
            'columns' => [
                ['key' => 'kode_akun', 'label' => 'Kode Akun', 'align' => 'left'],
                ['key' => 'nama_akun', 'label' => 'Nama Akun', 'align' => 'left'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'kredit', 'label' => 'Kredit', 'align' => 'right'],
            ],
            'sections' => [
                ['label' => null, 'rows' => $rows, 'subtotal' => [
                    'nama_akun' => 'Total',
                    'debit' => 'Rp ' . number_format($data['total_debit'], 0, ',', '.'),
                    'kredit' => 'Rp ' . number_format($data['total_kredit'], 0, ',', '.'),
                ]],
            ],
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream('neraca-saldo.pdf');
    }

    public function neracaSaldoExcel(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $asOf = $this->parseDate($request->input('as_of'), today());
        $data = $this->statements->trialBalance($asOf);

        $rows = $data['rows']->map(fn ($row) => [
            'kode_akun' => $row['account']->kode_akun,
            'nama_akun' => $row['account']->nama_akun,
            'debit' => (float) $row['debit'],
            'kredit' => (float) $row['kredit'],
        ]);

        $columns = [
            ['key' => 'kode_akun', 'label' => 'Kode Akun'],
            ['key' => 'nama_akun', 'label' => 'Nama Akun'],
            ['key' => 'debit', 'label' => 'Debit'],
            ['key' => 'kredit', 'label' => 'Kredit'],
        ];
        $sections = [
            ['label' => null, 'rows' => $rows, 'subtotal' => [
                'nama_akun' => 'Total',
                'debit' => (float) $data['total_debit'],
                'kredit' => (float) $data['total_kredit'],
            ]],
        ];

        return Excel::download(new FinancialStatementExport($columns, $sections), 'neraca-saldo-' . $asOf->format('Ymd') . '.xlsx');
    }

    public function bukuBesar(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $accounts = BaganAkun::orderBy('kode_akun')->get();
        $from = $this->parseDate($request->input('from'), today()->startOfMonth());
        $to = $this->parseDate($request->input('to'), today());
        $account = null;
        $data = null;

        if ($request->filled('coa_id')) {
            $account = BaganAkun::find($request->integer('coa_id'));
            if ($account) {
                $data = $this->statements->generalLedger($account, $from, $to);
            }
        }

        return view('financial_statements.buku-besar', compact('accounts', 'account', 'from', 'to', 'data'));
    }

    public function bukuBesarPdf(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $account = BaganAkun::findOrFail($request->integer('coa_id'));
        $from = $this->parseDate($request->input('from'), today()->startOfMonth());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->statements->generalLedger($account, $from, $to);

        $rows = $data['rows']->map(fn ($row) => [
            'tanggal' => $row['tanggal']->format('d-m-Y'),
            'no_jurnal' => $row['no_jurnal'],
            'keterangan' => $row['keterangan'] ?: '-',
            'debit' => 'Rp ' . number_format($row['debit'], 0, ',', '.'),
            'kredit' => 'Rp ' . number_format($row['kredit'], 0, ',', '.'),
            'balance' => 'Rp ' . number_format($row['balance'], 0, ',', '.'),
        ]);

        return Pdf::loadView('reports.financial-statement-pdf', [
            'title' => "Buku Besar {$account->kode_akun} - {$account->nama_akun}",
            'columns' => [
                ['key' => 'tanggal', 'label' => 'Tanggal', 'align' => 'left'],
                ['key' => 'no_jurnal', 'label' => 'No Jurnal', 'align' => 'left'],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'align' => 'left'],
                ['key' => 'debit', 'label' => 'Debit', 'align' => 'right'],
                ['key' => 'kredit', 'label' => 'Kredit', 'align' => 'right'],
                ['key' => 'balance', 'label' => 'Saldo', 'align' => 'right'],
            ],
            'sections' => [
                ['label' => 'Saldo Awal: Rp ' . number_format($data['opening_balance'], 0, ',', '.'), 'rows' => $rows, 'subtotal' => [
                    'keterangan' => 'Saldo Akhir',
                    'balance' => 'Rp ' . number_format($data['closing_balance'], 0, ',', '.'),
                ]],
            ],
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream('buku-besar.pdf');
    }

    public function bukuBesarExcel(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $account = BaganAkun::findOrFail($request->integer('coa_id'));
        $from = $this->parseDate($request->input('from'), today()->startOfMonth());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->statements->generalLedger($account, $from, $to);

        $rows = $data['rows']->map(fn ($row) => [
            'tanggal' => $row['tanggal']->format('d-m-Y'),
            'no_jurnal' => $row['no_jurnal'],
            'keterangan' => $row['keterangan'] ?: '-',
            'debit' => (float) $row['debit'],
            'kredit' => (float) $row['kredit'],
            'balance' => (float) $row['balance'],
        ]);

        $columns = [
            ['key' => 'tanggal', 'label' => 'Tanggal'],
            ['key' => 'no_jurnal', 'label' => 'No Jurnal'],
            ['key' => 'keterangan', 'label' => 'Keterangan'],
            ['key' => 'debit', 'label' => 'Debit'],
            ['key' => 'kredit', 'label' => 'Kredit'],
            ['key' => 'balance', 'label' => 'Saldo'],
        ];
        $sections = [
            ['label' => 'Saldo Awal: ' . number_format($data['opening_balance'], 0, ',', '.'), 'rows' => $rows, 'subtotal' => [
                'keterangan' => 'Saldo Akhir',
                'balance' => (float) $data['closing_balance'],
            ]],
        ];

        return Excel::download(new FinancialStatementExport($columns, $sections), "buku-besar-{$account->kode_akun}-" . now()->format('Ymd') . '.xlsx');
    }

    public function labaRugi(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $from = $this->parseDate($request->input('from'), today()->startOfMonth());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->statements->incomeStatement($from, $to);

        return view('financial_statements.laba-rugi', compact('from', 'to', 'data'));
    }

    public function labaRugiPdf(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $from = $this->parseDate($request->input('from'), today()->startOfMonth());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->statements->incomeStatement($from, $to);

        $mapRows = fn ($rows) => $rows->map(fn ($row) => [
            'kode_akun' => $row['account']->kode_akun,
            'nama_akun' => $row['account']->nama_akun,
            'jumlah' => 'Rp ' . number_format($row['amount'], 0, ',', '.'),
        ]);

        return Pdf::loadView('reports.financial-statement-pdf', [
            'title' => 'Laba Rugi periode ' . $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'columns' => [
                ['key' => 'kode_akun', 'label' => 'Kode Akun', 'align' => 'left'],
                ['key' => 'nama_akun', 'label' => 'Nama Akun', 'align' => 'left'],
                ['key' => 'jumlah', 'label' => 'Jumlah', 'align' => 'right'],
            ],
            'sections' => [
                ['label' => 'Pendapatan', 'rows' => $mapRows($data['pendapatan']), 'subtotal' => [
                    'nama_akun' => 'Total Pendapatan',
                    'jumlah' => 'Rp ' . number_format($data['total_pendapatan'], 0, ',', '.'),
                ]],
                ['label' => 'Beban', 'rows' => $mapRows($data['beban']), 'subtotal' => [
                    'nama_akun' => 'Total Beban',
                    'jumlah' => 'Rp ' . number_format($data['total_beban'], 0, ',', '.'),
                ]],
            ],
            'footer' => 'Laba (Rugi) Bersih: Rp ' . number_format($data['laba_bersih'], 0, ',', '.'),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream('laba-rugi.pdf');
    }

    public function labaRugiExcel(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $from = $this->parseDate($request->input('from'), today()->startOfMonth());
        $to = $this->parseDate($request->input('to'), today());
        $data = $this->statements->incomeStatement($from, $to);

        $mapRows = fn ($rows) => $rows->map(fn ($row) => [
            'kode_akun' => $row['account']->kode_akun,
            'nama_akun' => $row['account']->nama_akun,
            'jumlah' => (float) $row['amount'],
        ]);

        $columns = [
            ['key' => 'kode_akun', 'label' => 'Kode Akun'],
            ['key' => 'nama_akun', 'label' => 'Nama Akun'],
            ['key' => 'jumlah', 'label' => 'Jumlah'],
        ];
        $sections = [
            ['label' => 'Pendapatan', 'rows' => $mapRows($data['pendapatan']), 'subtotal' => [
                'nama_akun' => 'Total Pendapatan',
                'jumlah' => (float) $data['total_pendapatan'],
            ]],
            ['label' => 'Beban', 'rows' => $mapRows($data['beban']), 'subtotal' => [
                'nama_akun' => 'Total Beban',
                'jumlah' => (float) $data['total_beban'],
            ]],
        ];
        $footer = 'Laba (Rugi) Bersih: ' . number_format($data['laba_bersih'], 0, ',', '.');

        return Excel::download(new FinancialStatementExport($columns, $sections, $footer), 'laba-rugi-' . now()->format('Ymd') . '.xlsx');
    }

    private function arusKasData(Request $request): array
    {
        $from = $this->parseDate($request->input('from'), today()->startOfYear());
        $to = $this->parseDate($request->input('to'), today());

        return [$from, $to, $this->statements->cashFlow($from, $to)];
    }

    private function arusKasSections(array $data, bool $formatted): array
    {
        $label = [
            'OPERASI' => 'Arus Kas dari Aktivitas Operasi',
            'INVESTASI' => 'Arus Kas dari Aktivitas Investasi',
            'PENDANAAN' => 'Arus Kas dari Aktivitas Pendanaan',
        ];
        $uang = fn ($nilai) => $formatted ? 'Rp ' . number_format($nilai, 0, ',', '.') : (float) $nilai;

        return $data['sections']->map(fn ($section) => [
            'label' => $label[$section['kelompok']],
            'rows' => $section['rows']->map(fn ($row) => [
                'nama_akun' => $row['account']?->nama_akun ?? 'Tanpa akun lawan',
                'jumlah' => $uang($row['amount']),
            ]),
            'subtotal' => ['nama_akun' => 'Subtotal', 'jumlah' => $uang($section['subtotal'])],
        ])->push([
            'label' => 'Rekonsiliasi Saldo Kas',
            'rows' => collect([
                ['nama_akun' => 'Saldo kas awal', 'jumlah' => $uang($data['saldo_awal'])],
                ['nama_akun' => 'Kenaikan (penurunan) kas bersih', 'jumlah' => $uang($data['arus_bersih'])],
                ['nama_akun' => 'Saldo kas akhir menurut buku besar', 'jumlah' => $uang($data['saldo_akhir_buku'])],
            ]),
            'subtotal' => ['nama_akun' => 'Saldo kas akhir', 'jumlah' => $uang($data['saldo_akhir'])],
        ])->all();
    }

    public function arusKas(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        [$from, $to, $data] = $this->arusKasData($request);

        return view('financial_statements.arus-kas', compact('from', 'to', 'data'));
    }

    public function arusKasPdf(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        [$from, $to, $data] = $this->arusKasData($request);

        return Pdf::loadView('reports.financial-statement-pdf', [
            'title' => 'Laporan Arus Kas ' . $from->format('d-m-Y') . ' s.d. ' . $to->format('d-m-Y'),
            'columns' => [
                ['key' => 'nama_akun', 'label' => 'Keterangan', 'align' => 'left'],
                ['key' => 'jumlah', 'label' => 'Jumlah', 'align' => 'right'],
            ],
            'sections' => $this->arusKasSections($data, true),
            'footer' => null,
            'generatedAt' => now(),
        ])->stream('laporan-arus-kas.pdf');
    }

    public function arusKasExcel(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        [$from, $to, $data] = $this->arusKasData($request);

        return Excel::download(
            new FinancialStatementExport(
                [['key' => 'nama_akun', 'label' => 'Keterangan'], ['key' => 'jumlah', 'label' => 'Jumlah']],
                $this->arusKasSections($data, false)
            ),
            'laporan-arus-kas-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function neraca(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $asOf = $this->parseDate($request->input('as_of'), today());
        $data = $this->statements->balanceSheet($asOf);

        return view('financial_statements.neraca', compact('asOf', 'data'));
    }

    public function neracaPdf(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $asOf = $this->parseDate($request->input('as_of'), today());
        $data = $this->statements->balanceSheet($asOf);

        $mapRows = fn ($rows) => $rows->map(fn ($row) => [
            'kode_akun' => $row['account']->kode_akun,
            'nama_akun' => $row['account']->nama_akun,
            'jumlah' => 'Rp ' . number_format($row['amount'], 0, ',', '.'),
        ]);

        $ekuitasRows = $mapRows($data['ekuitas'])->push([
            'kode_akun' => '-',
            'nama_akun' => 'Laba Ditahan Berjalan (belum ditutup buku)',
            'jumlah' => 'Rp ' . number_format($data['laba_ditahan_berjalan'], 0, ',', '.'),
        ]);

        return Pdf::loadView('reports.financial-statement-pdf', [
            'title' => 'Neraca per ' . $asOf->format('d-m-Y'),
            'columns' => [
                ['key' => 'kode_akun', 'label' => 'Kode Akun', 'align' => 'left'],
                ['key' => 'nama_akun', 'label' => 'Nama Akun', 'align' => 'left'],
                ['key' => 'jumlah', 'label' => 'Jumlah', 'align' => 'right'],
            ],
            'sections' => [
                ['label' => 'Aset', 'rows' => $mapRows($data['aset']), 'subtotal' => [
                    'nama_akun' => 'Total Aset',
                    'jumlah' => 'Rp ' . number_format($data['total_aset'], 0, ',', '.'),
                ]],
                ['label' => 'Liabilitas', 'rows' => $mapRows($data['liabilitas']), 'subtotal' => [
                    'nama_akun' => 'Total Liabilitas',
                    'jumlah' => 'Rp ' . number_format($data['total_liabilitas'], 0, ',', '.'),
                ]],
                ['label' => 'Ekuitas', 'rows' => $ekuitasRows, 'subtotal' => [
                    'nama_akun' => 'Total Ekuitas',
                    'jumlah' => 'Rp ' . number_format($data['total_ekuitas'], 0, ',', '.'),
                ]],
            ],
            'footer' => 'Total Liabilitas + Ekuitas: Rp ' . number_format($data['total_liabilitas'] + $data['total_ekuitas'], 0, ',', '.'),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream('neraca.pdf');
    }

    public function neracaExcel(Request $request)
    {
        $this->authorize('viewFinancialStatements');

        $asOf = $this->parseDate($request->input('as_of'), today());
        $data = $this->statements->balanceSheet($asOf);

        $mapRows = fn ($rows) => $rows->map(fn ($row) => [
            'kode_akun' => $row['account']->kode_akun,
            'nama_akun' => $row['account']->nama_akun,
            'jumlah' => (float) $row['amount'],
        ]);

        $ekuitasRows = $mapRows($data['ekuitas'])->push([
            'kode_akun' => '-',
            'nama_akun' => 'Laba Ditahan Berjalan (belum ditutup buku)',
            'jumlah' => (float) $data['laba_ditahan_berjalan'],
        ]);

        $columns = [
            ['key' => 'kode_akun', 'label' => 'Kode Akun'],
            ['key' => 'nama_akun', 'label' => 'Nama Akun'],
            ['key' => 'jumlah', 'label' => 'Jumlah'],
        ];
        $sections = [
            ['label' => 'Aset', 'rows' => $mapRows($data['aset']), 'subtotal' => [
                'nama_akun' => 'Total Aset',
                'jumlah' => (float) $data['total_aset'],
            ]],
            ['label' => 'Liabilitas', 'rows' => $mapRows($data['liabilitas']), 'subtotal' => [
                'nama_akun' => 'Total Liabilitas',
                'jumlah' => (float) $data['total_liabilitas'],
            ]],
            ['label' => 'Ekuitas', 'rows' => $ekuitasRows, 'subtotal' => [
                'nama_akun' => 'Total Ekuitas',
                'jumlah' => (float) $data['total_ekuitas'],
            ]],
        ];
        $footer = 'Total Liabilitas + Ekuitas: ' . number_format($data['total_liabilitas'] + $data['total_ekuitas'], 0, ',', '.');

        return Excel::download(new FinancialStatementExport($columns, $sections, $footer), 'neraca-' . $asOf->format('Ymd') . '.xlsx');
    }

    private function parseDate(?string $value, Carbon $default): Carbon
    {
        if (!$value) {
            return $default;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception) {
            return $default;
        }
    }
}
