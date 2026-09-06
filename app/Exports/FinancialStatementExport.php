<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialStatementExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    private array $boldRows = [];

    public function __construct(private array $columns, private array $sections, private ?string $footer = null) {}

    public function headings(): array
    {
        return array_map(fn ($column) => $column['label'], $this->columns);
    }

    public function array(): array
    {
        $data = [];
        $rowIndex = 1;

        foreach ($this->sections as $section) {
            if (!empty($section['label'])) {
                $rowIndex++;
                $data[] = array_pad([$section['label']], count($this->columns), '');
                $this->boldRows[] = $rowIndex;
            }

            foreach ($section['rows'] as $row) {
                $rowIndex++;
                $data[] = array_map(fn ($column) => data_get($row, $column['key']), $this->columns);
            }

            if (!empty($section['subtotal'])) {
                $rowIndex++;
                $data[] = array_map(fn ($column) => $section['subtotal'][$column['key']] ?? '', $this->columns);
                $this->boldRows[] = $rowIndex;
            }
        }

        if ($this->footer) {
            $rowIndex++;
            $data[] = array_pad([$this->footer], count($this->columns), '');
            $this->boldRows[] = $rowIndex;
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach ($this->boldRows as $row) {
            $sheet->getStyle("{$row}:{$row}")->getFont()->setBold(true);
        }

        return [1 => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DCEBFF']]]];
    }
}
