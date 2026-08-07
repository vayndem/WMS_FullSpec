<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class InvoiceLpbImport implements ToCollection, WithStartRow, WithCalculatedFormulas
{
    public function startRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
    {
        Log::info('=== MEMULAI PROSES UPSERT INVOICE V. 2.0 ===');

        $groupedData = [];

        foreach ($rows as $row) {
            $noLpbRaw = trim((string)($row[0] ?? ''));
            $tipeRaw  = trim((string)($row[2] ?? ''));

            if (!empty($noLpbRaw) && in_array(strtoupper($tipeRaw), ['PO', 'PP'])) {
                $idLpbTarget = "LPB" . strtoupper($tipeRaw) . $noLpbRaw;

                if (!isset($groupedData[$idLpbTarget])) {
                    $groupedData[$idLpbTarget] = new Collection();
                }
                $groupedData[$idLpbTarget]->push($row);
            }
        }

        foreach ($groupedData as $idLpb => $items) {
            $firstItem = $items->first();

            DB::beginTransaction();
            try {
                $lpbRecord = DB::table('admin_lpb')->where('id_lpb', $idLpb)->first();

                if (!$lpbRecord || empty($lpbRecord->no_invoice)) {
                    Log::warning("SKIPPED: $idLpb tidak punya no_invoice di DB.");
                    DB::rollBack();
                    continue;
                }

                $noInvoice = $lpbRecord->no_invoice;
                $existingInvoice = DB::table('invoice_lpb')->where('no_invoice', $noInvoice)->first();

                if (!$existingInvoice) {
                    Log::warning("SKIPPED: Data invoice $noInvoice tidak ditemukan di tabel invoice_lpb.");
                    DB::rollBack();
                    continue;
                }

                if ($existingInvoice->status == 2) {
                    Log::info("[SKIP - ALREADY LUNAS] Invoice: $noInvoice (LPB: $idLpb) sudah LUNAS. Update dilewati.");
                    DB::rollBack();
                    continue;
                }

                $totalDpp    = 0;
                $totalPpn    = 0;
                $totalDiskon = 0;
                $totalOngkir = 0;

                foreach ($items as $item) {
                    $totalDpp    += $this->parseCurrency($item[11]);
                    $totalPpn    += $this->parseCurrency($item[12]);
                    $totalDiskon += $this->parseCurrency($item[14]);
                    $totalOngkir += $this->parseCurrency($item[15]);
                }

                $grandTotalFinal = ($totalDpp + $totalPpn + $totalOngkir) - $totalDiskon;

                $updateData = [
                    'sub_total'   => $totalDpp,
                    'ppn'         => $totalPpn,
                    'diskon'      => $totalDiskon,
                    'ongkir'      => $totalOngkir,
                    'grand_total' => $grandTotalFinal,
                ];

                $jthTempoRaw = trim($firstItem[16] ?? '');
                $isLunasExcel = (strtoupper($jthTempoRaw) === 'LUNAS');

                if ($isLunasExcel) {
                    $updateData['status'] = 2;
                    $updateData['status_pembayaran'] = 'Lunas';
                    $updateData['total_pembayaran'] = $grandTotalFinal;
                    $updateData['tgl_deadline_pembayaran'] = now()->format('Y-m-d');
                } else {
                    $deadline = $this->parseDateExcel($jthTempoRaw);
                    if ($deadline) {
                        $updateData['tgl_deadline_pembayaran'] = $deadline;
                    }
                }

                $isChanged = false;
                foreach ($updateData as $key => $newValue) {
                    $oldValue = $existingInvoice->$key;

                    if (is_numeric($newValue)) {
                        if (abs((float)$newValue - (float)$oldValue) > 0.001) {
                            $isChanged = true;
                            break;
                        }
                    } else {
                        if ((string)$newValue !== (string)$oldValue) {
                            $isChanged = true;
                            break;
                        }
                    }
                }

                if (!$isChanged) {
                    Log::info("[NO CHANGE] LPB $idLpb -> Invoice $noInvoice: data sudah benar.");
                    DB::rollBack();
                    continue;
                }

                $updateData['note'] = 'Perubahan Upsert V.2.0';
                $updateData['updated_at'] = now();

                DB::table('invoice_lpb')
                    ->where('no_invoice', $noInvoice)
                    ->update($updateData);

                DB::commit();
                Log::info("[SUCCESS] LPB $idLpb -> Invoice $noInvoice UPDATED.");
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("[ERROR] LPB $idLpb: " . $e->getMessage());
            }
        }

        Log::info('=== SELESAI ===');
    }

    private function parseCurrency($value)
    {
        if (empty($value)) return 0;
        $number = preg_replace('/[^0-9,.-]/', '', (string)$value);
        $number = str_replace(',', '.', $number);
        return (float) $number;
    }

    private function parseDateExcel($value)
    {
        if (empty($value) || strtoupper($value) === 'LUNAS') return null;
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }
}
