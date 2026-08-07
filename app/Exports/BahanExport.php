<?php

namespace App\Exports;

use App\Models\Bahan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\DB;

class BahanExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $jenis;
    protected $kategori;
    protected $rowNumber = 0;

    public function __construct($jenis, $kategori)
    {
        $this->jenis = $jenis;
        $this->kategori = $kategori;
    }

    public function query()
    {
        return Bahan::query()
            ->with(['kategoriBahan'])
            ->when(is_numeric($this->jenis), function ($q) {
                return $q->where('jenis', $this->jenis);
            })
            ->when($this->kategori && $this->kategori != -1, function ($q) {
                return $q->where('kategori', $this->kategori);
            })
            ->orderBy('kategori')
            ->orderBy('nama');
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Bahan',
            'Kategori',
            'Merk/Keterangan',
            'Satuan',
            'On Purchase',
            'On Hand',
            'Planning'
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->nama,
            $row->kategoriBahan->katnama ?? 'Tidak Ditemukan',
            $row->keterangan_bahan,
            $row->satuan,
            (float) $row->stok_onpurchase,
            (float) $row->stok_onhand,
            (float) $row->planning
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '0.0',
            'G' => '0.0',
            'H' => '0.0',
        ];
    }
}
