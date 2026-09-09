<?php

namespace App\Exports;

use App\Exports\Concerns\HasStandardHeaderStyle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericTableExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use HasStandardHeaderStyle;

    public function __construct(private array $columns, private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return array_map(fn ($column) => $column['label'], $this->columns);
    }

    public function map($row): array
    {
        return array_map(fn ($column) => data_get($row, $column['key']), $this->columns);
    }

    public function styles(Worksheet $sheet): array
    {
        return $this->applyHeaderStyle($sheet);
    }
}
