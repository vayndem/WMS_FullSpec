<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class PoKertasExport implements FromQuery, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell, WithColumnFormatting
{
    protected $bulan;
    protected $tahun;
    protected $jenis;

    public function __construct($bulan, $tahun, $jenis)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->jenis = $jenis;
    }

    public function query()
    {
        $query = DB::table('inv_podetail as detail')
            ->join('inv_po', 'detail.no_po', '=', 'inv_po.no_po')
            ->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
            ->join('bahan', 'detail.id_bahan', '=', 'bahan.id')
            ->leftJoin('permintaan', 'detail.id_permintaan', '=', 'permintaan.id')
            ->leftJoin('admin_lpb', 'inv_po.no_po', '=', 'admin_lpb.no_po')
            ->select(
                'permintaan.created_at as tanggal_minta_gudang',
                'detail.created_at as tanggal_order',
                'admin_lpb.tanggal as tanggal_terima',
                'admin_lpb.no_sj as no_invoice',
                'admin_lpb.id_lpb as no_lpb',
                'detail.no_po',
                'suppliers.nama as nama_supplier',
                'bahan.nama as nama_bahan_master',
                'detail.jumlah',
                'bahan.satuan as satuan_master',
                'detail.harga',
                'detail.exclude',
                'detail.ppn',
                'detail.include',
            )
            ->where('inv_po.jenis', $this->jenis);

        if ($this->bulan != '0') {
            $query->whereMonth('inv_po.tanggal', '=', $this->bulan);
        }
        if ($this->tahun) {
            $query->whereYear('inv_po.tanggal', '=', $this->tahun);
        }

        return $query->orderBy('inv_po.tanggal', 'asc')->orderBy('detail.id', 'asc');
    }

    public function map($row): array
    {
        return [
            !empty($row->tanggal_minta_gudang) ? Carbon::parse($row->tanggal_minta_gudang)->translatedFormat('d F Y') : '',
            !empty($row->tanggal_order) ? Carbon::parse($row->tanggal_order)->translatedFormat('d F Y') : '',
            !empty($row->tanggal_terima) ? Carbon::parse($row->tanggal_terima)->translatedFormat('d F Y') : '',
            $row->no_lpb,
            $row->no_po,
            $row->nama_supplier,
            $row->nama_bahan_master,
            $row->jumlah,
            $row->satuan_master,
            $row->harga,
            $row->exclude,
            $row->ppn,
            $row->include,
            $row->no_invoice,
        ];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function columnFormats(): array
    {
        return [
            'J' => '"Rp" #,##0',
            'K' => '"Rp" #,##0',
            'L' => '"Rp" #,##0',
            'M' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->setCellValue('A1', 'TGL');
                $sheet->setCellValue('C1', 'TGL');
                $sheet->setCellValue('D1', 'NO LPB');
                $sheet->setCellValue('E1', 'MO PO');
                $sheet->setCellValue('F1', 'SUPPLIER');
                $sheet->setCellValue('G1', 'NAMA BARANG');
                $sheet->setCellValue('H1', 'QTY');
                $sheet->setCellValue('I1', 'SATUAN');
                $sheet->setCellValue('J1', 'HARGA');
                $sheet->setCellValue('K1', 'DPP');
                $sheet->setCellValue('L1', 'PPN');
                $sheet->setCellValue('M1', 'DPP + PPN');
                $sheet->setCellValue('N1', 'INVOICE');

                $sheet->setCellValue('A2', 'PERMINTAAN');
                $sheet->setCellValue('B2', 'ORDER');
                $sheet->setCellValue('C2', 'DITERIMA');

                $sheet->mergeCells('A1:C1');
                // $sheet->mergeCells('C1:C2');
                $sheet->mergeCells('D1:D2');
                $sheet->mergeCells('E1:E2');
                $sheet->mergeCells('F1:F2');
                $sheet->mergeCells('G1:G2');
                $sheet->mergeCells('H1:H2');
                $sheet->mergeCells('I1:I2');
                $sheet->mergeCells('J1:J2');
                $sheet->mergeCells('K1:K2');
                $sheet->mergeCells('L1:L2');
                $sheet->mergeCells('M1:M2');
                $sheet->mergeCells('N1:N2');

                $headerStyle = [
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];
                $sheet->getStyle('A1:N2')->applyFromArray($headerStyle);

                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(20);
                $sheet->getColumnDimension('F')->setWidth(25);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(8);
                $sheet->getColumnDimension('I')->setWidth(10);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(15);
                $sheet->getColumnDimension('L')->setWidth(15);
                $sheet->getColumnDimension('M')->setWidth(15);
                $sheet->getColumnDimension('N')->setWidth(15);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        if ($lastRow < 3) {
            return [];
        }
        
        $dataRange = 'A3:N' . $lastRow;

        $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle($dataRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $sheet->getStyle('H3:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('J3:M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [
            $dataRange => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }
}