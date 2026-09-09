<?php

namespace App\Exports;

use App\Exports\Concerns\HasStandardHeaderStyle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialStatementExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    use HasStandardHeaderStyle;

    private array $sectionLabelRows = [];
    private array $totalRows = [];

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
                $this->sectionLabelRows[] = $rowIndex;
            }

            foreach ($section['rows'] as $row) {
                $rowIndex++;
                $data[] = array_map(fn ($column) => data_get($row, $column['key']), $this->columns);
            }

            if (!empty($section['subtotal'])) {
                $rowIndex++;
                $data[] = array_map(fn ($column) => $section['subtotal'][$column['key']] ?? '', $this->columns);
                $this->totalRows[] = $rowIndex;
            }
        }

        if ($this->footer) {
            $rowIndex++;
            $data[] = array_pad([$this->footer], count($this->columns), '');
            $this->totalRows[] = $rowIndex;
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = $this->applyHeaderStyle($sheet, freezeHeader: false, zebraStripe: false);
        $highestColumn = $sheet->getHighestColumn();

        foreach ($this->sectionLabelRows as $row) {
            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'italic' => true, 'color' => ['rgb' => '1E3A8A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            ]);
        }

        foreach ($this->totalRows as $row) {
            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1E3A8A']]],
            ]);
        }

        return $styles;
    }
}
