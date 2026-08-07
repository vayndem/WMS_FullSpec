<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SupplierExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    public function collection()
    {
        return Supplier::all();
    }

    public function headings(): array
    {
        // Hanya sampai Pembayaran sesuai permintaan
        return [
            'NO',
            'NAMA SUPPLIER',
            'ALAMAT',
            'NPWP',
            'TELP',
            'UP',
            'PEMBAYARAN',
        ];
    }

    public function map($supplier): array
    {
        static $no = 1;
        return [
            $no++,
            $supplier->nama,
            $supplier->alamat,
            $supplier->npwp,
            $supplier->telp,
            $supplier->up,
            $supplier->pembayaran,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Menghias baris nomor 1 (Header)
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4B5D67'] // Warna Abu-abu Gelap Elegant
                ],
                'alignment' => ['horizontal' => 'center']
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $lastCol = 'G'; // Kolom terakhir adalah G (Pembayaran)

                // Menambahkan Border ke seluruh tabel
                $event->sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => 'center',
                    ],
                ]);

                // Membuat teks di kolom No, NPWP, Telp, dan Pembayaran jadi rata tengah
                $event->sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle("D2:E{$lastRow}")->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
