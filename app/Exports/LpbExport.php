<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class LpbExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Ambil data utama dari tabel admin_lpb dengan filter
        $lpbQuery = DB::table('admin_lpb')
            ->select(
                'admin_lpb.id_lpb',
                'admin_lpb.tanggal',
                'admin_lpb.no_sj',
                'inv_po.id_suplier'
            )
            ->leftJoin('inv_po', 'admin_lpb.no_po', '=', 'inv_po.no_po')
            ->where('admin_lpb.flag', 0);

        // Filter berdasarkan bulan dan tahun
        if (!empty($this->filters['filterMonthYear'])) {
            $filterMonthYear = explode('-', $this->filters['filterMonthYear']);
            $filterYear = $filterMonthYear[0];
            $filterMonth = $filterMonthYear[1];

            $lpbQuery->whereYear('admin_lpb.tanggal', $filterYear)
                ->whereMonth('admin_lpb.tanggal', $filterMonth);
        }

        // Filter berdasarkan jenis LPB
        if (!empty($this->filters['filterLpbType']) && $this->filters['filterLpbType'] != 'all') {
            $lpbQuery->where('admin_lpb.id_lpb', 'like', $this->filters['filterLpbType'] . '%');
        }

        // Filter pencarian (LPB ID, No PO, atau No Surat Jalan)
        if (!empty($this->filters['searchTerm'])) {
            $searchTerm = $this->filters['searchTerm'];
            $lpbQuery->where(function ($query) use ($searchTerm) {
                $query->where('admin_lpb.id_lpb', 'like', "%$searchTerm%")
                    ->orWhere('admin_lpb.no_po', 'like', "%$searchTerm%")
                    ->orWhere('admin_lpb.no_sj', 'like', "%$searchTerm%");
            });
        }

        // Ambil data dari LPB dengan filter yang diterapkan
        $lpbData = $lpbQuery->get();

        $finalData = [];

        // Loop untuk mengambil data detail per LPB
        foreach ($lpbData as $lpb) {
            // Ambil nama supplier berdasarkan id_suplier
            $supplierName = DB::table('suppliers')->where('id', $lpb->id_suplier)->value('nama');

            // Ambil data detail berdasarkan id_lpb
            $detailData = DB::table('admin_lpb_detail')
                ->join('bahan', 'admin_lpb_detail.id_bahan', '=', 'bahan.id')
                ->join('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')
                ->where('admin_lpb_detail.id_lpb', $lpb->id_lpb)
                ->select(
                    'bahan.nama as nama_bahan',
                    'admin_lpb_detail.jumlah_barang_diterima',
                    'bahan.satuan'
                )
                ->get();

            // Gabungkan data utama dengan detail
            foreach ($detailData as $detail) {
                $finalData[] = [
                    'Tanggal' => $lpb->tanggal,
                    'Nama Bahan' => $detail->nama_bahan,
                    'Supplier' => $supplierName, // Supplier Name instead of ID
                    'No Surat Jalan' => $lpb->no_sj,
                    'Jumlah' => $detail->jumlah_barang_diterima . ' ' . $detail->satuan
                ];
            }
        }

        // Return the final combined data as a collection
        return collect($finalData);
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Bahan',
            'Supplier',
            'No Surat Jalan',
            'Jumlah'
        ];
    }

    // Apply styling
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1:E1')->getFill()->getStartColor()->setRGB('F2F2F2'); // Light grey background

        $sheet->getStyle('A:E')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A:E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Freeze the first row for header
        $sheet->freezePane('A2');

        // Adjust column width automatically
        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        return [
            // Additional custom styling can go here
        ];
    }

    // Apply column formatting
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_XLSX14, // Format tanggal
            'E' => NumberFormat::FORMAT_NUMBER, // Format jumlah sebagai angka
        ];
    }

    public function title(): string
    {
        return 'Laporan Penerimaan Barang'; // Set title of the sheet
    }
}
