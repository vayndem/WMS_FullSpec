<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class ExportLaporan implements WithMultipleSheets
{
    protected $dari;
    protected $sampai;

    public function __construct($dari, $sampai)
    {
        $this->dari = $dari;
        $this->sampai = $sampai;
    }

    public function sheets(): array
    {
        $sheets = [];
        $categories = DB::table('kategori_bahan')->get();

        foreach ($categories as $cat) {
            $sheets[] = new ExportLaporanSheet($this->dari, $this->sampai, $cat->katid, $cat->katnama);
        }

        return $sheets;
    }
}

class ExportLaporanSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithColumnFormatting, WithTitle
{
    protected $dari;
    protected $sampai;
    protected $katid;
    protected $katnama;
    private $rowNumber = 0;
    private $totalRows = 0;

    public function __construct($dari, $sampai, $katid, $katnama)
    {
        $this->dari = $dari;
        $this->sampai = $sampai;
        $this->katid = $katid;
        $this->katnama = $katnama;
    }

    public function title(): string
    {
        $safeTitle = str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $this->katnama);
        return substr($safeTitle, 0, 31);
    }

    public function collection()
    {
        $idsBahan = DB::table('bahan')->where('kategori', $this->katid)->pluck('id');

        if ($idsBahan->isEmpty()) {
            return collect([]);
        }

        $hargaAvg = DB::table('vw_harga_avg_barang')
            ->whereIn('id', $idsBahan)
            ->pluck('average_harga', 'id');

        $lpbBefore = DB::table('admin_lpb_detail')
            ->join('admin_lpb', 'admin_lpb_detail.id_lpb', '=', 'admin_lpb.id_lpb')
            ->select('id_bahan', DB::raw('SUM(jumlah_barang_diterima) as total'))
            ->whereIn('id_bahan', $idsBahan)
            ->where('admin_lpb.tanggal', '<', $this->dari)
            ->groupBy('id_bahan')->pluck('total', 'id_bahan');

        $lpbCurrent = DB::table('admin_lpb_detail')
            ->join('admin_lpb', 'admin_lpb_detail.id_lpb', '=', 'admin_lpb.id_lpb')
            ->select('id_bahan', DB::raw('SUM(jumlah_barang_diterima) as total'))
            ->whereIn('id_bahan', $idsBahan)
            ->whereBetween('admin_lpb.tanggal', [$this->dari, $this->sampai])
            ->groupBy('id_bahan')->pluck('total', 'id_bahan');

        $npkBefore = DB::table('npk_planning')
            ->select('id_barang', DB::raw('SUM(jumlah_terkirim) as total'))
            ->whereIn('id_barang', $idsBahan)
            ->where('tgl_terkirim', '<', $this->dari)
            ->groupBy('id_barang')->pluck('total', 'id_barang');

        $npkCurrent = DB::table('npk_planning')
            ->select('id_barang', DB::raw('SUM(jumlah_terkirim) as total'))
            ->whereIn('id_barang', $idsBahan)
            ->whereBetween('tgl_terkirim', [$this->dari, $this->sampai])
            ->groupBy('id_barang')->pluck('total', 'id_barang');

        $adjBefore = DB::table('stock_adjustments')
            ->select('id_barang', DB::raw('SUM(jumlah) as total'))
            ->whereIn('id_barang', $idsBahan)
            ->where('tanggal', '<', $this->dari)
            ->groupBy('id_barang')->pluck('total', 'id_barang');

        $adjCurrent = DB::table('stock_adjustments')
            ->select('id_barang', DB::raw('SUM(jumlah) as total'))
            ->whereIn('id_barang', $idsBahan)
            ->whereBetween('tanggal', [$this->dari, $this->sampai])
            ->groupBy('id_barang')->pluck('total', 'id_barang');

        $dataBahan = DB::table('bahan')
            ->where('kategori', $this->katid)
            ->select('id', 'nama', 'satuan', 'harga')
            ->orderBy('nama', 'asc')
            ->get();

        $mappedData = $dataBahan->map(function ($bahan) use ($lpbBefore, $lpbCurrent, $npkBefore, $npkCurrent, $adjBefore, $adjCurrent, $hargaAvg) {
            $sAwal = ($lpbBefore[$bahan->id] ?? 0) - ($npkBefore[$bahan->id] ?? 0) + ($adjBefore[$bahan->id] ?? 0);
            $in = $lpbCurrent[$bahan->id] ?? 0;
            $out = $npkCurrent[$bahan->id] ?? 0;
            $adj = $adjCurrent[$bahan->id] ?? 0;
            $sAkhir = $sAwal + ($in - $out) + $adj;

            $hargaDariView = $hargaAvg[$bahan->id] ?? 0;
            $hargaSatuan = (int) ($hargaDariView > 0 ? $hargaDariView : $bahan->harga);
            $totalHarga = (int) ($hargaSatuan * $sAkhir);

            return (object) [
                'nama' => $bahan->nama,
                'satuan' => $bahan->satuan,
                's_awal' => $sAwal,
                'masuk' => $in,
                'keluar' => $out,
                'adj' => $adj,
                's_akhir' => $sAkhir,
                'harga_satuan' => $hargaSatuan,
                'total_harga' => $totalHarga
            ];
        });

        $this->totalRows = $mappedData->count();
        return $mappedData;
    }

    public function map($item): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber . '.',
            $item->nama,
            $item->satuan,
            (float) $item->s_awal,
            (float) $item->masuk,
            (float) $item->keluar,
            (float) $item->adj,
            (float) $item->s_akhir,
            $item->harga_satuan,
            $item->total_harga,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '#,##0.00',
            'E' => '#,##0.00',
            'F' => '#,##0.00',
            'G' => '#,##0.00',
            'H' => '#,##0.00;[Red]-#,##0.00;0',
            'I' => '"Rp "#,##0',
            'J' => '"Rp "#,##0',
        ];
    }

    public function headings(): array
    {
        return [
            ['PT Muliaoffset Packindo'],
            ['Laporan Rekapitulasi Stok - Kategori: ' . $this->katnama],
            ['Periode: ' . Carbon::parse($this->dari)->format('d-m-Y') . ' s/d ' . Carbon::parse($this->sampai)->format('d-m-Y')],
            ['No', 'Nama Barang', 'Satuan', 'Saldo Awal', 'Masuk (+)', 'Keluar (-)', 'Penyesuaian', 'Saldo Akhir', 'Harga Satuan', 'Total Harga']
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
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->totalRows + 4;

                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');
                $sheet->mergeCells('A3:J3');

                $sheet->getStyle('A4:J' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $footerRow = $lastRow + 1;
                if ($this->totalRows > 0) {
                    $sheet->setCellValue('A' . $footerRow, 'TOTAL ITEM');
                    $sheet->mergeCells('A' . $footerRow . ':I' . $footerRow);
                    $sheet->setCellValue('J' . $footerRow, $this->totalRows);
                    $sheet->getStyle('A' . $footerRow . ':J' . $footerRow)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $footerRow . ':J' . $footerRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }
}
