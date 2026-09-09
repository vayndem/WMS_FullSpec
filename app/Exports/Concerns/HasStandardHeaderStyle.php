<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait HasStandardHeaderStyle
{
    protected function applyHeaderStyle(Worksheet $sheet, bool $freezeHeader = true, bool $zebraStripe = true): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        $headerRange = "A1:{$highestColumn}1";

        if ($freezeHeader) {
            $sheet->freezePane('A2');
        }

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1E3A8A']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->setAutoFilter($headerRange);

        if ($highestRow > 1) {
            $dataRange = "A2:{$highestColumn}{$highestRow}";
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            if ($zebraStripe) {
                $stripe = new Conditional();
                $stripe->setConditionType(Conditional::CONDITION_EXPRESSION);
                $stripe->addCondition('MOD(ROW(),2)=0');
                $stripe->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
                $sheet->getStyle($dataRange)->setConditionalStyles([$stripe]);
            }
        }

        return [];
    }
}
