<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StockOpnameDetailExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $kode;
    protected $tanggal;
    protected $userType;

    public function __construct(string $kode, $userType)
    {
        $this->kode = $kode;
        $this->userType = $userType;
        $opname = DB::table('stok_opname')->where('kode', $kode)->first();
        $this->tanggal = $opname ? $opname->tanggal : '-';
    }

    private function canViewPrice(): bool
    {
        return in_array($this->userType, [11, 33]);
    }

    public function headings(): array
    {
        if ($this->canViewPrice()) {
            return [
                ['OPNAME BARANG NON-KERTAS'],
                ['Kode Referensi: ' . $this->kode . ' | Tanggal: ' . $this->tanggal],
                [''],
                ['Nama Bahan', 'Kategori', 'Harga', 'Stok Sistem', 'Stok Real', 'Selisih Stok', 'Total Kerugian']
            ];
        }

        return [
            ['OPNAME BARANG NON-KERTAS'],
            ['Kode Referensi: ' . $this->kode . ' | Tanggal: ' . $this->tanggal],
            [''],
            ['Nama Bahan', 'Kategori', 'Stok Sistem', 'Stok Real', 'Selisih Stok', 'Total Kerugian']
        ];
    }

    public function map($detail): array
    {
        if ($this->canViewPrice()) {
            return [
                $detail->nama_bahan,
                $detail->nama_kategori,
                (float)$detail->harga,
                (float)$detail->stok_sistem,
                (float)$detail->stok_real,
                (float)$detail->selisih,
                (float)$detail->kerugian
            ];
        }

        return [
            $detail->nama_bahan,
            $detail->nama_kategori,
            (float)$detail->stok_sistem,
            (float)$detail->stok_real,
            (float)$detail->selisih,
            (float)$detail->kerugian
        ];
    }

    public function query()
    {
        $query = DB::table('stok_opname_detail')
            ->join('bahan', 'stok_opname_detail.id_bahan', '=', 'bahan.id')
            ->join('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid');

        if ($this->canViewPrice()) {
            $query->select(
                'stok_opname_detail.harga',
                'stok_opname_detail.stok_sistem',
                'stok_opname_detail.stok_real',
                'bahan.nama as nama_bahan',
                'kategori_bahan.katnama as nama_kategori',
                'stok_opname_detail.selisih',
                'stok_opname_detail.kerugian'
            );
        } else {
            $query->select(
                'stok_opname_detail.stok_sistem',
                'stok_opname_detail.stok_real',
                'bahan.nama as nama_bahan',
                'kategori_bahan.katnama as nama_kategori',
                'stok_opname_detail.selisih',
                'stok_opname_detail.kerugian'
            );
        }

        return $query->where('stok_opname_detail.kode', $this->kode)
            ->orderBy('bahan.nama', 'asc');
    }

    public function columnFormats(): array
    {
        if ($this->canViewPrice()) {
            return [
                'C' => '"Rp "#,##0',
                'G' => '"Rp "#,##0',
            ];
        }

        return [
            'F' => '"Rp "#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $canView = $this->canViewPrice();
        $lastColumn = $canView ? 'G' : 'F';

        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:' . $lastColumn . '2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRange = 'A4:' . $lastColumn . '4';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '2F5597'],
            ],
        ]);

        $sheet->getStyle('A4:' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        $data = $this->query()->get();
        foreach ($data as $index => $detail) {
            $currentRow = $index + 5;
            if ($detail->stok_sistem != $detail->stok_real) {
                $sheet->getStyle('A' . $currentRow . ':' . $lastColumn . $currentRow)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF2CC'],
                    ],
                ]);
            }
        }

        $summaryRow = $lastRow + 1;
        $mergeEndColumn = $canView ? 'F' : 'E';

        $sheet->mergeCells('A' . $summaryRow . ':' . $mergeEndColumn . $summaryRow);
        $sheet->setCellValue('A' . $summaryRow, 'TOTAL ESTIMASI KERUGIAN');

        $totalKerugian = $data->sum('kerugian');
        $sheet->setCellValue($lastColumn . $summaryRow, $totalKerugian);

        $sheet->getStyle('A' . $summaryRow . ':' . $lastColumn . $summaryRow)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'E2EFDA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle($lastColumn . $summaryRow)->getNumberFormat()->setFormatCode('"Rp "#,##0');

        return [];
    }
}
