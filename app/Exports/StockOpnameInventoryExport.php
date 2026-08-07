<?php

namespace App\Exports;

use App\Models\Bahan;
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
        $query = Bahan::with(['gudang', 'kategoriBahan'])->orderBy('nama');
        if ($this->financial) {
            $values = InventoryLayer::query()
                ->selectRaw('bahan_id, SUM(remaining_quantity * unit_cost) inventory_value, SUM(remaining_quantity) layer_quantity')
                ->groupBy('bahan_id')
                ->get()
                ->keyBy('bahan_id');
            return $query->get()->each(function ($bahan) use ($values) {
                $summary = $values->get($bahan->id);
                $bahan->setAttribute('inventory_value', $summary?->inventory_value ?? 0);
                $bahan->setAttribute('layer_quantity', $summary?->layer_quantity ?? 0);
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

    public function map($bahan): array
    {
        $row = [
            $bahan->id,
            $bahan->nama,
            $bahan->kategoriBahan->katnama ?? '-',
            $bahan->gudang->nama ?? '-',
            $bahan->satuan,
            (float) $bahan->stok_onhand,
        ];
        if ($this->financial) {
            $value = (float) $bahan->inventory_value;
            $row[] = (float) $bahan->layer_quantity > 0 ? $value / (float) $bahan->layer_quantity : 0;
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
