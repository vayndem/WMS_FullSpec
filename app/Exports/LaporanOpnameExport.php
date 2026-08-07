<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanOpnameExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithCustomStartCell
{
    protected $tanggal_mulai;
    protected $tanggal_akhir;
    protected $kode_opname;

    public function __construct($tanggal_mulai, $tanggal_akhir)
    {
        $this->tanggal_mulai = $tanggal_mulai;
        $this->tanggal_akhir = $tanggal_akhir;

        $so = DB::table('stok_opname')->orderBy('tanggal', 'desc')->first();
        $this->kode_opname = $so ? $so->kode : '-';
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $latestOpname = DB::table('stok_opname')->orderBy('tanggal', 'desc')->first();
        if (!$latestOpname) return collect([]);

        $details = DB::table('stok_opname_detail as detail')
            ->join('bahan', 'detail.id_bahan', '=', 'bahan.id')
            ->where('detail.kode', $latestOpname->kode)
            ->where('detail.stok_real', '>', 0)
            ->select('detail.id_bahan', 'bahan.nama', 'detail.stok_real', 'detail.harga')
            ->get();

        $data = $details->map(function ($item) {
            $total_masuk = DB::table('admin_lpb_detail as d')
                ->join('admin_lpb as h', 'd.id_lpb', '=', 'h.id_lpb')
                ->where('d.id_bahan', $item->id_bahan)
                ->whereBetween('h.tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
                ->sum('d.jumlah_barang_diterima');

            $total_keluar = DB::table('npk_planning')
                ->where('id_barang', $item->id_bahan)
                ->whereBetween('tgl_terkirim', [$this->tanggal_mulai, $this->tanggal_akhir])
                ->sum('jumlah_terkirim');

            $mutasi_stok = $total_masuk - $total_keluar;

            return [
                'nama' => $item->nama,
                'stok_awal_so' => $item->stok_real,
                'total_masuk' => $total_masuk,
                'total_keluar' => $total_keluar,
                'stok_akhir' => $mutasi_stok,
                'harga' => $item->harga,
                'total_keuangan' => $mutasi_stok * $item->harga
            ];
        });

        $totalKeuanganSemua = $data->sum('total_keuangan');

        $data->push([
            'nama' => 'TOTAL KESELURUHAN',
            'stok_awal_so' => '',
            'total_masuk' => '',
            'total_keluar' => '',
            'stok_akhir' => '',
            'harga' => '',
            'total_keuangan' => $totalKeuanganSemua
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Nama Bahan',
            'Stok Opname (SO)',
            'Total Masuk (LPB)',
            'Total Keluar (NPK)',
            'Saldo Akhir',
            'Harga Satuan',
            'Total Keuangan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'LAPORAN UNTUK OPNAME = ' . $this->kode_opname);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Periode Mutasi: ' . $this->tanggal_mulai . ' s/d ' . $this->tanggal_akhir);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD']
            ],
            'alignment' => ['horizontal' => 'center']
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A' . $lastRow . ':G' . $lastRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA']
            ]
        ]);

        $sheet->getStyle('F5:G' . $lastRow)->getNumberFormat()
            ->setFormatCode('"Rp "#,##0');

        return [];
    }
}
