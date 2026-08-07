<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NpkExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $periode;
    protected $flag;
    protected $jenis;

    /**
     * @param string $periode
     * @param string $flag
     * @param string $jenis
     */
    public function __construct(string $periode, string $flag, string $jenis)
    {
        $this->periode = $periode;
        $this->flag = $flag;
        $this->jenis = $jenis;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Query ini mengambil data yang sama persis seperti di controller Anda
        $query = DB::table('npk_planning as a')
            ->join('bahan as b', 'a.id_barang', '=', 'b.id')
            ->select(
                'a.kode',
                'a.tgl_terkirim',
                'a.keterangan',
                'b.nama as nama_barang',
                'a.jumlah_terkirim',
                'b.satuan',
                'a.operator'
            )
            ->orderBy('a.kode', 'asc')
            ->orderBy('a.id', 'asc');

        // Menerapkan filter berdasarkan jenis
        if ($this->jenis == '2') {
            $query->where('a.kode', 'LIKE', 'NPBT%');
        } elseif ($this->jenis == '3') {
            $query->where('a.kode', 'LIKE', 'NPBMO%');
        } else {
            $query->where('a.kode', 'NOT LIKE', 'NPBT%');
        }

        // Menerapkan filter berdasarkan flag (status kirim) dan periode
        if ($this->flag == '2') {
            $query->where('a.jumlah_terkirim', '!=', 0);
            if ($this->periode) {
                $query->where('a.tgl_terkirim', 'LIKE', $this->periode . '%');
            }
        }

        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Mendefinisikan header untuk kolom
        return [
            'Nomor NPK',
            'Tanggal Kirim',
            'Keterangan',
            'Nama Barang',
            'Jumlah Kirim',
            'Satuan',
            'Operator',
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        // Memetakan data dari collection ke array sederhana
        return [
            $row->kode,
            $row->tgl_terkirim,
            $row->keterangan,
            $row->nama_barang,
            $row->jumlah_terkirim,
            $row->satuan,
            $row->operator,
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $headerRange = 'A1:' . $highestColumn . '1';
                $dataRange = 'A1:' . $highestColumn . $highestRow;

                // 1. Memberi Style pada Header
                $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('4F81BD');
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 2. Memberi Border pada semua sel data
                $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // 3. Mengelompokkan data dengan menggabungkan sel (merge)
                $startMergeRow = 2; // Data dimulai dari baris ke-2
                for ($i = 2; $i <= $highestRow; $i++) {
                    // Cek jika baris berikutnya memiliki NPK yang sama
                    if ($i < $highestRow && $sheet->getCell('A' . $i)->getValue() === $sheet->getCell('A' . ($i + 1))->getValue()) {
                        continue; // Lanjutkan loop jika NPK masih sama
                    }

                    // Akhir dari grup ditemukan pada baris $i
                    $endMergeRow = $i;
                    if ($endMergeRow > $startMergeRow) {
                        // Gabungkan sel untuk grup saat ini
                        $sheet->mergeCells("A{$startMergeRow}:A{$endMergeRow}");
                        $sheet->mergeCells("B{$startMergeRow}:B{$endMergeRow}");
                        $sheet->mergeCells("C{$startMergeRow}:C{$endMergeRow}");
                        $sheet->mergeCells("G{$startMergeRow}:G{$endMergeRow}");
                    }

                    // Atur baris awal untuk grup berikutnya
                    $startMergeRow = $i + 1;
                }

                // 4. Mengatur perataan vertikal menjadi di tengah untuk semua data
                $sheet->getStyle('A2:' . $highestColumn . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // 5. Mengatur perataan horizontal menjadi rata kanan untuk kolom 'Jumlah Kirim'
                $sheet->getStyle('E2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
