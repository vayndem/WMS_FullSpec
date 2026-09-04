<?php

namespace App\Http\Controllers;

use App\Models\BaganAkun;
use App\Services\FinancialStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinancialStatementController extends Controller
{
    public function __construct(private FinancialStatementService $statements) {}

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
