<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class PembayaranSuplierExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithColumnFormatting
{
    protected $dari;
    protected $sampai;
    private $rowNumber = 0;
    private $validDataCount = 0;
    private $tepatWaktuCount = 0;
    private $tepatBayarCount = 0;
    private $totalRowsInQuery = 0;
    private $batalRowIndices = [];

    public function __construct($dari, $sampai)
    {
        $this->dari = $dari;
        $this->sampai = $sampai;
    }

    public function collection()
    {
        $subqueryBayar = DB::table('pembayaran_transaksi_detail')
            ->select('id_invoice_lpb', DB::raw('MIN(tanggal_pembayaran) as tanggal_bayar_pertama'))
            ->groupBy('id_invoice_lpb');

        $subqueryLPB = DB::table('admin_lpb')
            ->select(
                'no_invoice',
                DB::raw('GROUP_CONCAT(id_lpb SEPARATOR ", ") as list_lpb'),
                DB::raw('MIN(id_lpb) as min_lpb')
            )
            ->groupBy('no_invoice');

        $data = DB::table('invoice_lpb')
            ->join('suppliers', 'invoice_lpb.kode_supplier', '=', 'suppliers.id')
            ->leftJoinSub($subqueryBayar, 'bayar', function ($join) {
                $join->on('invoice_lpb.id', '=', 'bayar.id_invoice_lpb');
            })
            ->leftJoinSub($subqueryLPB, 'lpb_data', function ($join) {
                $join->on('invoice_lpb.no_invoice', '=', 'lpb_data.no_invoice');
            })
            ->select([
                'invoice_lpb.id',
                'invoice_lpb.tanggal',
                'invoice_lpb.no_invoice',
                'lpb_data.list_lpb',
                'lpb_data.min_lpb',
                'suppliers.nama as nama_supplier',
                'invoice_lpb.tgl_deadline_pembayaran',
                'invoice_lpb.grand_total',
                'invoice_lpb.total_pembayaran',
                'invoice_lpb.sisa_tagihan',
                'bayar.tanggal_bayar_pertama'
            ])
            ->whereBetween('invoice_lpb.tgl_deadline_pembayaran', [$this->dari, $this->sampai])
            ->orderBy('invoice_lpb.tgl_deadline_pembayaran', 'asc')
            ->get();

        $this->totalRowsInQuery = $data->count();
        return $data;
    }

    public function map($invoice): array
    {
        $this->rowNumber++;
        $tglInvoice = Carbon::parse($invoice->tanggal);
        $tglDeadline = Carbon::parse($invoice->tgl_deadline_pembayaran);
        $statusWaktu = '';
        $statusBayar = 'Tidak Tepat';

        $this->validDataCount++;

        if ($invoice->grand_total == 0) {
            $statusWaktu = 'Tepat';
            $statusBayar = 'Tepat';
            $this->tepatWaktuCount++;
            $this->tepatBayarCount++;
            $this->batalRowIndices[] = $this->rowNumber + 4;
        } else {
            if ($invoice->tanggal_bayar_pertama) {
                $tglBayar = Carbon::parse($invoice->tanggal_bayar_pertama);
                if ($tglBayar->lte($tglDeadline)) {
                    $statusWaktu = 'Tepat';
                    $this->tepatWaktuCount++;
                } else {
                    $statusWaktu = 'Tidak Tepat';
                }
            } else {
                $statusWaktu = '';
            }

            if ($invoice->sisa_tagihan < 1) {
                $statusBayar = 'Tepat';
                $this->tepatBayarCount++;
            }
        }

        return [
            (string) $this->rowNumber . '.',
            $tglInvoice->format('d-m-Y'),
            (string) $invoice->no_invoice,
            (string) ($invoice->list_lpb ?? '-'),
            (string) $invoice->nama_supplier,
            $tglDeadline->format('d-m-Y'),
            $invoice->tanggal_bayar_pertama ? Carbon::parse($invoice->tanggal_bayar_pertama)->format('d-m-Y') : '-',
            $statusWaktu,
            (float) $invoice->grand_total,
            (float) $invoice->total_pembayaran,
            $statusBayar,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => '#,##0.00',
            'J' => '#,##0.00',
            'K' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function headings(): array
    {
        return [
            ['PT Muliaoffset Packindo'],
            ['Laporan Sasaran Mutu Pembayaran Supplier (Berdasarkan Tgl Jatuh Tempo)'],
            ['Periode Jatuh Tempo: ' . Carbon::parse($this->dari)->format('d-m-Y') . ' s/d ' . Carbon::parse($this->sampai)->format('d-m-Y')],
            [
                'No',
                'Tanggal Faktur',
                'No Invoice',
                'LPB',
                'Nama Supplier',
                'Tanggal Jatuh Tempo',
                'Tanggal Pembayaran',
                'Status Ketepatan Waktu Pembayaran',
                'Nominal Tagihan',
                'Tagihan Terbayar',
                'Status Ketepatan Nominal Pembayaran'
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '29ABE2']]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1948A']]
            ],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $this->totalRowsInQuery + 4;
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');
                $sheet->mergeCells('A3:K3');

                $sheet->getStyle('A4:K' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                foreach ($this->batalRowIndices as $rowIndex) {
                    $sheet->getStyle('A' . $rowIndex . ':K' . $rowIndex)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFF2CC');
                }

                $footerRow1 = $lastRow + 1;
                $sheet->setCellValue('A' . $footerRow1, 'Total Seluruh Data');
                $sheet->mergeCells('A' . $footerRow1 . ':J' . $footerRow1);
                $sheet->setCellValue('K' . $footerRow1, $this->validDataCount);
                $sheet->getStyle('A' . $footerRow1 . ':K' . $footerRow1)->getFont()->setBold(true);
                $sheet->getStyle('A' . $footerRow1 . ':K' . $footerRow1)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $footerRow2 = $footerRow1 + 2;
                $persenWaktu = $this->validDataCount > 0 ? ($this->tepatWaktuCount / $this->validDataCount) * 100 : 0;
                $sheet->mergeCells('A' . $footerRow2 . ':G' . $footerRow2);
                $sheet->setCellValue('A' . $footerRow2, 'Ada berapa jumlah pembayaran yang TEPAT WAKTU?');
                $sheet->setCellValue('H' . $footerRow2, $this->tepatWaktuCount);

                $footerRow3 = $footerRow2 + 1;
                $sheet->mergeCells('A' . $footerRow3 . ':G' . $footerRow3);
                $sheet->setCellValue('A' . $footerRow3, 'Berapa % jumlah KETEPATAN WAKTU dalam melakukan pembayaran?');
                $sheet->setCellValue('H' . $footerRow3, number_format($persenWaktu, 0) . '%');

                $footerRow4 = $footerRow3 + 2;
                $persenBayar = $this->validDataCount > 0 ? ($this->tepatBayarCount / $this->validDataCount) * 100 : 0;
                $sheet->mergeCells('A' . $footerRow4 . ':J' . $footerRow4);
                $sheet->setCellValue('A' . $footerRow4, 'Ada berapa jumlah pembayaran yang TEPAT NOMINAL (Lunas)?');
                $sheet->setCellValue('K' . $footerRow4, $this->tepatBayarCount);

                $footerRow5 = $footerRow4 + 1;
                $sheet->mergeCells('A' . $footerRow5 . ':J' . $footerRow5);
                $sheet->setCellValue('A' . $footerRow5, 'Berapa % jumlah KETEPATAN PEMBAYARAN (Nominal)?');
                $sheet->setCellValue('K' . $footerRow5, number_format($persenBayar, 0) . '%');

                $sheet->getStyle('A' . $footerRow2 . ':H' . $footerRow3)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A' . $footerRow4 . ':K' . $footerRow5)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('H' . $footerRow2 . ':H' . $footerRow3)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('K' . $footerRow1 . ':K' . $footerRow5)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
