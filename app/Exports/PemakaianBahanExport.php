<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class PemakaianBahanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $filters;
    private $rowCount = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $data = DB::table('npk_planning')
            ->join('bahan', 'npk_planning.id_barang', '=', 'bahan.id')
            ->leftJoin('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')
            ->leftJoin('vw_harga_avg_barang', 'bahan.id', '=', 'vw_harga_avg_barang.id')
            ->select(
                'bahan.nama as nama_bahan',
                'kategori_bahan.katnama as nama_kategori',
                'bahan.satuan',
                DB::raw('SUM(npk_planning.jumlah_terkirim) as total_keluar'),
                DB::raw('IFNULL(vw_harga_avg_barang.average_harga, 0) as harga_satuan'),
                DB::raw('SUM(npk_planning.jumlah_terkirim) * IFNULL(vw_harga_avg_barang.average_harga, 0) as total_nominal')
            )
            ->whereBetween('npk_planning.tanggal', [$this->filters['tgl_awal'], $this->filters['tgl_akhir']])
            ->when($this->filters['kategori'], function ($query, $kategori) {
                return $query->whereIn('bahan.kategori', is_array($kategori) ? $kategori : [$kategori]);
            })
            ->groupBy('npk_planning.id_barang', 'bahan.nama', 'kategori_bahan.katnama', 'bahan.satuan', 'vw_harga_avg_barang.average_harga')
            ->get();

        $this->rowCount = $data->count();
        return $data;
    }

    public function headings(): array
    {
        return [
            ['LAPORAN PEMAKAIAN BAHAN'],
            ['Periode: ' . date('d/m/Y', strtotime($this->filters['tgl_awal'])) . ' - ' . date('d/m/Y', strtotime($this->filters['tgl_akhir']))],
            [],
            [
                'NO',
                'NAMA BAHAN',
                'KATEGORI',
                'SATUAN',
                'TOTAL KELUAR',
                'HARGA SATUAN',
                'TOTAL NOMINAL',
            ]
        ];
    }

    public function map($row): array
    {
        static $no = 1;
        $currentRow = 4 + $no;
        $no++;

        return [
            $no - 1,
            $row->nama_bahan,
            $row->nama_kategori,
            $row->satuan,
            (float) $row->total_keluar,
            (float) $row->harga_satuan,
            "=E" . $currentRow . "*F" . $currentRow,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Segoe UI', 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2F4F4F']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $startDataRow = 5;
                $lastDataRow = $startDataRow + $this->rowCount - 1;
                $totalRow = $lastDataRow + 1;
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2F4F4F'));
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('555555'));
                $sheet->getRowDimension(4)->setRowHeight(28);
                $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", "TOTAL KESELURUHAN");
                $sheet->setCellValue("E{$totalRow}", "=SUM(E{$startDataRow}:E{$lastDataRow})");
                $sheet->setCellValue("G{$totalRow}", "=SUM(G{$startDataRow}:G{$lastDataRow})");

                $sheet->getStyle("A4:G{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'D3D3D3'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                for ($i = $startDataRow; $i <= $lastDataRow; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(20);
                    $sheet->getStyle("A{$i}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("D{$i}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("E{$i}:G{$i}")->getAlignment()->setHorizontal('right');
                    if ($i % 2 == 0) {
                        $sheet->getStyle("A{$i}:G{$i}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                    }
                }

                $sheet->getRowDimension($totalRow)->setRowHeight(24);
                $sheet->getStyle("A{$totalRow}:G{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Segoe UI'],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAEDED']
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']],
                        'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['rgb' => '000000']]
                    ]
                ]);
                $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal('right');
                $sheet->getStyle("E{$totalRow}")->getAlignment()->setHorizontal('right');
                $sheet->getStyle("G{$totalRow}")->getAlignment()->setHorizontal('right');

                $sheet->getStyle("E5:E{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F5:F{$lastDataRow}")->getNumberFormat()->setFormatCode('Rp #,##0.00');
                $sheet->getStyle("G5:G{$totalRow}")->getNumberFormat()->setFormatCode('Rp #,##0.00');

                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(22);
            },
        ];
    }
}
