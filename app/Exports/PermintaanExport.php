<?php

namespace App\Exports;

use App\Models\Permintaan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PermintaanExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithColumnFormatting,
    WithTitle,
    WithEvents
{
    protected $jenis;
    protected $status;
    protected $startDate;
    protected $endDate;
    protected $namaJenis;
    protected $totalRows = 0; // Untuk menyimpan jumlah baris data

    public function __construct($jenis, $status, $startDate, $endDate)
    {
        $this->jenis = $jenis;
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->namaJenis = $jenis == 0 ? 'Penunjang' : 'Penolong';
    }

    public function query()
    {
        $start = date('Y-m-d', strtotime($this->startDate)) . ' 00:00:00';
        $end = date('Y-m-d', strtotime($this->endDate)) . ' 23:59:59';

        $permintaan = Permintaan::join('bahan', 'permintaan.id_bahan', '=', 'bahan.id')
            ->where('bahan.jenis', $this->jenis)
            ->whereBetween('permintaan.created_at', [$start, $end])
            ->orderBy('permintaan.id', 'desc')
            ->select(
                'permintaan.id',
                'permintaan.created_at',
                'bahan.nama as bahan_nama',
                'bahan.satuan as satuan',
                'permintaan.jumlah_order',
                'permintaan.realisasi',
                'permintaan.finish'
            );

        // Menghitung total baris untuk styling
        $this->totalRows = (clone $permintaan)->count();

        return $permintaan;
    }

    /**
     * Mendefinisikan judul kolom.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Tanggal Permintaan',
            'Bahan',
            'Satuan',
            'Jumlah Order',
            'Realisasi',
            'Status',
        ];
    }

    /**
     * Memetakan data untuk setiap baris.
     */
    public function map($row): array
    {
        return [
            $row->id,
            \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i'),
            $row->bahan_nama ?? '-',
            $row->satuan,
            $row->jumlah_order,
            $row->realisasi,
            $row->finish ? 'Selesai' : 'Proses',
        ];
    }

    /**
     * Memberi nama pada sheet.
     */
    public function title(): string
    {
        return 'Laporan Permintaan';
    }

    /**
     * Format kolom spesifik.
     */
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_GENERAL, // Format untuk Jumlah Order
            'F' => NumberFormat::FORMAT_GENERAL, // Format untuk Realisasi
        ];
    }

    /**
     * Menerapkan event styling setelah sheet dibuat.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerCellRange = 'A4:G4'; // Header tabel ada di baris 4
                $dataCellRange = 'A5:G' . ($this->totalRows + 4); // Rentang sel data
                $fullTableRange = 'A4:G' . ($this->totalRows + 4); // Rentang tabel penuh

                // 1. Menambahkan baris di atas untuk JUDUL
                $sheet->insertNewRowBefore(1, 3);

                // 2. Set Judul Laporan
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'LAPORAN PERMINTAAN BAHAN');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 3. Set Sub-Judul (Jenis Bahan dan Periode)
                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', 'Jenis Bahan: ' . $this->namaJenis);
                $sheet->getStyle('A2')->getFont()->setItalic(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A3:G3');
                $sheet->setCellValue('A3', 'Periode: ' . $this->startDate . ' s/d ' . $this->endDate);
                $sheet->getStyle('A3')->getFont()->setItalic(true);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 4. Styling Header Tabel (Baris 4)
                $sheet->getStyle($headerCellRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                $sheet->getStyle($headerCellRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('4F81BD'); // Warna biru
                $sheet->getStyle($headerCellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 5. Menambahkan Border ke seluruh tabel
                $sheet->getStyle($fullTableRange)->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));
                
                // 6. Merapikan alignment
                // Rata tengah untuk 'Satuan' (Kolom D) dan 'Status' (Kolom G)
                $sheet->getStyle('D5:D' . ($this->totalRows + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G5:G' . ($this->totalRows + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Rata kanan untuk 'Jumlah Order' (E) dan 'Realisasi' (F)
                // (Sudah diatur oleh columnFormats, tapi ini untuk jaga-jaga)
                $sheet->getStyle('E5:F' . ($this->totalRows + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}