<?php

namespace App\Exports;

use App\Models\StokGudang;
use App\Models\InventoryLayer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOpnameInventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly bool $financial) {}

    public function collection(): Collection
    {
        $query = StokGudang::with(['gudang', 'bahan.kategoriBahan'])->orderBy('gudang_id')->orderBy('bahan_id');
        if ($this->financial) {
            $values = InventoryLayer::query()
                ->selectRaw('gudang_id, bahan_id, SUM(remaining_quantity * unit_cost) inventory_value, SUM(remaining_quantity) layer_quantity')
                ->groupBy('gudang_id', 'bahan_id')
                ->get()
                ->keyBy(fn ($row) => $row->gudang_id . ':' . $row->bahan_id);
            return $query->get()->each(function ($stock) use ($values) {
                $summary = $values->get($stock->gudang_id . ':' . $stock->bahan_id);
                $stock->setAttribute('inventory_value', $summary?->inventory_value ?? 0);
                $stock->setAttribute('layer_quantity', $summary?->layer_quantity ?? 0);
            });
        }
        return $query->get();
    }

    public function headings(): array
    {
        $columns = ['Kode/ID', 'Nama Barang', 'Kategori', 'Gudang', 'Satuan', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Alasan'];
        if ($this->financial) {
            array_splice($columns, 6, 0, ['Harga Rata-rata Buku', 'Nilai Persediaan']);
        }
        return $columns;
    }

    public function map($stock): array
    {
        $bahan = $stock->bahan;
        $row = [
            $bahan->id,
            $bahan->nama,
            $bahan->kategoriBahan->katnama ?? '-',
            $stock->gudang->nama ?? '-',
            $bahan->satuan,
            (float) $stock->stok_tersedia,
        ];
        if ($this->financial) {
            $value = (float) $stock->inventory_value;
            $row[] = (float) $stock->layer_quantity > 0 ? $value / (float) $stock->layer_quantity : 0;
            $row[] = $value;
        }
        return [...$row, '', '', ''];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        return [1 => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DCEBFF']]]];
    }
}
