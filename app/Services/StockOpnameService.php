<?php

namespace App\Services;

use App\Models\Bahan;
use App\Models\InventoryLayer;
use App\Models\Jurnal;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockOpnameService
{
    public function __construct(
        private AccountingPeriodService $periods,
        private DocumentNumberService $numbers
    ) {}

    public function confirmPhysical(StockOpname $opname): void
    {
        $opname->loadMissing('details.bahan.tipeBarang');
        foreach ($opname->details as $detail) {
            $difference = (float) $detail->physical_quantity - (float) $detail->system_quantity;
            if (abs($difference) > 0 && !$detail->reason) {
                throw new RuntimeException("Alasan selisih {$detail->bahan->nama} wajib diisi.");
            }
            $this->assertMapping($detail);
            $detail->update([
                'difference_quantity' => $difference,
                'physical_confirmed_by' => Auth::id(),
                'physical_confirmed_at' => now(),
                'valuation_confirmed_by' => null,
                'valuation_confirmed_at' => null,
            ]);
        }
    }

    public function confirmValuation(StockOpname $opname, array $costs): void
    {
        $opname->loadMissing('details.bahan.tipeBarang');
        $costs = collect($costs)->keyBy('id');
        foreach ($opname->details as $detail) {
            $difference = (float) $detail->difference_quantity;
            $cost = 0;
            $value = 0;
            if ($difference > 0) {
                $cost = (float) data_get($costs->get($detail->id), 'unit_cost', 0);
                if ($cost <= 0) {
                    throw new RuntimeException("Harga koreksi positif {$detail->bahan->nama} wajib diisi Accounting.");
                }
                $value = round($difference * $cost, 2);
            } elseif ($difference < 0) {
                $value = $this->fifoValue($detail, abs($difference), $opname->warehouse_id, false);
                $cost = abs($difference) > 0 ? round($value / abs($difference), 4) : 0;
            }
            $this->assertMapping($detail);
            $detail->update([
                'unit_cost' => $cost,
                'difference_value' => $value,
                'valuation_confirmed_by' => Auth::id(),
                'valuation_confirmed_at' => now(),
            ]);
        }
    }

    public function post(StockOpname $opname): ?Jurnal
    {
        $this->periods->assertOpen($opname->cutoff_at, 'Stock opname');
        return DB::transaction(function () use ($opname) {
            $opname = StockOpname::lockForUpdate()->with('details.bahan.tipeBarang')->findOrFail($opname->id);
            if ($opname->status !== StockOpname::APPROVED) {
                throw new RuntimeException('Hanya stock opname approved yang dapat diposting.');
            }
            if ($opname->details->contains(fn($detail) => !$detail->physical_confirmed_at || !$detail->valuation_confirmed_at)) {
                throw new RuntimeException('Konfirmasi fisik Gudang dan valuasi Accounting harus lengkap sebelum posting.');
            }

            $lines = [];
            foreach ($opname->details as $detail) {
                $bahan = Bahan::lockForUpdate()->findOrFail($detail->bahan_id);
                if ((int) $bahan->tipe_gudang !== (int) $opname->warehouse_id) {
                    throw new RuntimeException("Gudang barang {$bahan->nama} tidak sesuai dokumen.");
                }
                if (abs((float) $bahan->stok_onhand - (float) $detail->system_quantity) > 0.000001) {
                    throw new RuntimeException("Stok {$bahan->nama} berubah setelah penghitungan. Opname harus dihitung ulang.");
                }

                $difference = (float) $detail->difference_quantity;
                $value = (float) $detail->difference_value;
                if ($difference < 0) {
                    $value = $this->fifoValue($detail, abs($difference), $opname->warehouse_id, true);
                    $this->line($lines, $detail->bahan->tipeBarang->coa_beban_selisih_opname_id, $value, 0, "Beban selisih opname {$bahan->nama}");
                    $this->line($lines, $detail->bahan->tipeBarang->coa_persediaan_id, 0, $value, "Pengurangan persediaan {$bahan->nama}");
                } elseif ($difference > 0) {
                    InventoryLayer::create([
                        'bahan_id' => $bahan->id,
                        'gudang_id' => $opname->warehouse_id,
                        'source_type' => 'STOCK_OPNAME_DETAIL',
                        'source_id' => $detail->id,
                        'transaction_date' => $opname->cutoff_at->toDateString(),
                        'initial_quantity' => $difference,
                        'remaining_quantity' => $difference,
                        'unit_cost' => $detail->unit_cost,
                    ]);
                    $this->line($lines, $detail->bahan->tipeBarang->coa_persediaan_id, $value, 0, "Penambahan persediaan {$bahan->nama}");
                    $this->line($lines, $detail->bahan->tipeBarang->coa_koreksi_opname_id, 0, $value, "Koreksi positif opname {$bahan->nama}");
                }
                $bahan->update(['stok_onhand' => $detail->physical_quantity]);
            }

            $debit = round(collect($lines)->sum('debit'), 2);
            $credit = round(collect($lines)->sum('kredit'), 2);
            if ($debit > 0 && abs($debit - $credit) > 0.01) {
                throw new RuntimeException('Jurnal stock opname tidak seimbang.');
            }

            $journal = null;
            if ($lines) {
                $journal = Jurnal::create([
                    'no_jurnal' => $this->numbers->financial('JR', $opname->cutoff_at),
                    'tanggal' => $opname->cutoff_at->toDateString(),
                    'keterangan' => 'Penyesuaian Stock Opname ' . $opname->number,
                    'sumber_transaksi' => 'STOCK_OPNAME',
                    'reff_id' => $opname->id,
                    'status' => 'POSTED',
                    'created_by' => Auth::id(),
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                    'total_debit' => $debit,
                    'total_kredit' => $credit,
                ]);
                $journal->details()->createMany($lines);
            }
            $opname->update(['status' => StockOpname::POSTED, 'posted_by' => Auth::id(), 'posted_at' => now()]);
            return $journal;
        });
    }

    private function fifoValue(StockOpnameDetail $detail, float $quantity, int $warehouseId, bool $consume): float
    {
        $layers = InventoryLayer::where('bahan_id', $detail->bahan_id)->where('gudang_id', $warehouseId)
            ->whereDate('transaction_date', '<=', $detail->opname->cutoff_at)
            ->where('remaining_quantity', '>', 0)->orderBy('transaction_date')->orderBy('id')->lockForUpdate()->get();
        if ((float) $layers->sum('remaining_quantity') + 0.000001 < $quantity) {
            throw new RuntimeException("Layer stok {$detail->bahan->nama} tidak mencukupi.");
        }
        $remaining = $quantity;
        $total = 0;
        foreach ($layers as $layer) {
            if ($remaining <= 0) break;
            $take = min($remaining, (float) $layer->remaining_quantity);
            $lineValue = round($take * (float) $layer->unit_cost, 2);
            $total += $lineValue;
            if ($consume) {
                DB::table('stock_opname_allocations')->insert([
                    'stock_opname_detail_id' => $detail->id,
                    'inventory_layer_id' => $layer->id,
                    'quantity' => $take,
                    'unit_cost' => $layer->unit_cost,
                    'total_cost' => $lineValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $layer->decrement('remaining_quantity', $take);
            }
            if ($consume && $layer->source_type === 'LPB_DETAIL') {
                DB::table('lpbdetails')->where('id', $layer->source_id)->update([
                    'jumlah_dipakai' => DB::raw('jumlah_dipakai + ' . (float) $take),
                    'jumlah_tersisa' => DB::raw('GREATEST(jumlah_tersisa - ' . (float) $take . ', 0)'),
                    'flag_dipakai' => DB::raw("CASE WHEN jumlah_tersisa - " . (float) $take . " > 0 THEN 1 ELSE 0 END"),
                    'updated_at' => now(),
                ]);
            }
            $remaining -= $take;
        }
        return round($total, 2);
    }

    private function assertMapping(StockOpnameDetail $detail): void
    {
        $category = $detail->bahan?->tipeBarang;
        if (!$category || !$category->coa_persediaan_id || !$category->coa_beban_selisih_opname_id || !$category->coa_koreksi_opname_id) {
            throw new RuntimeException("Mapping COA stock opname kategori {$category?->katnama} belum lengkap.");
        }
        ChartOfAccount::assertUsable($category->coa_persediaan_id, [['ASET', 'DEBIT']], 'persediaan stock opname');
        ChartOfAccount::assertUsable($category->coa_beban_selisih_opname_id, [['BEBAN', 'DEBIT']], 'beban selisih stock opname');
        ChartOfAccount::assertUsable($category->coa_koreksi_opname_id, [['PENDAPATAN', 'KREDIT']], 'koreksi positif stock opname');
    }

    private function line(array &$lines, ?int $accountId, float $debit, float $credit, string $description): void
    {
        if (!$accountId) throw new RuntimeException("Mapping akun {$description} belum tersedia.");
        $lines[] = ['coa_id' => $accountId, 'debit' => $debit, 'kredit' => $credit, 'keterangan' => $description];
    }
}
