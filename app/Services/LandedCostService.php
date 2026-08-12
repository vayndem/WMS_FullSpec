<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\InventoryLayer;
use App\Models\Jurnal;
use App\Models\LandedCost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LandedCostService
{
    public function __construct(private DocumentNumberService $numbers, private AccountingPeriodService $periods) {}

    public function post(LandedCost $cost): Jurnal
    {
        $this->periods->assertOpen($cost->date, 'Landed cost');
        return DB::transaction(function () use ($cost) {
            $cost = LandedCost::with('allocations.layer.bahan.tipeBarang')->lockForUpdate()->findOrFail($cost->id);
            if ($cost->status !== 'DRAFT') throw new RuntimeException('Landed cost sudah diposting.');
            if ($cost->allocations->isEmpty()) throw new RuntimeException('Landed cost belum memiliki alokasi layer.');
            if (abs((float) $cost->allocations->sum('allocated_amount') - (float) $cost->total_amount) > .01) throw new RuntimeException('Total alokasi landed cost tidak sama dengan dokumen.');

            $debits = [];
            foreach ($cost->allocations as $allocation) {
                $layer = InventoryLayer::lockForUpdate()->findOrFail($allocation->inventory_layer_id);
                if ((float) $layer->remaining_quantity <= 0) throw new RuntimeException('Landed cost hanya dapat dialokasikan ke layer aktif.');
                $increment = (float) $allocation->allocated_amount / (float) $layer->remaining_quantity;
                $before = (float) $layer->unit_cost;
                $after = round($before + $increment, 4);
                $layer->update(['unit_cost' => $after]);
                $allocation->update(['unit_cost_before' => $before, 'unit_cost_after' => $after]);
                $account = $layer->bahan?->tipeBarang?->coa_persediaan_id;
                if (!$account) throw new RuntimeException('Mapping akun persediaan bahan landed cost belum lengkap.');
                ChartOfAccount::assertUsable($account, [['ASET', 'DEBIT']], 'landed cost persediaan');
                $debits[$account] = ($debits[$account] ?? 0) + (float) $allocation->allocated_amount;
            }
            ChartOfAccount::assertUsable($cost->credit_coa_id, [['LIABILITAS', 'KREDIT'], ['ASET', 'KREDIT']], 'lawan landed cost');
            $lines = collect($debits)->map(fn ($amount, $account) => ['coa_id' => $account, 'debit' => round($amount, 2), 'kredit' => 0, 'keterangan' => 'Kapitalisasi landed cost'])->values()->all();
            $lines[] = ['coa_id' => $cost->credit_coa_id, 'debit' => 0, 'kredit' => (float) $cost->total_amount, 'keterangan' => 'Lawan landed cost'];
            $journal = Jurnal::create(['no_jurnal' => $this->numbers->financial('JR', $cost->date), 'tanggal' => $cost->date, 'keterangan' => 'Landed cost ' . $cost->number, 'sumber_transaksi' => 'LANDED_COST', 'reff_id' => $cost->id, 'status' => 'POSTED', 'created_by' => Auth::id(), 'posted_by' => Auth::id(), 'posted_at' => now(), 'total_debit' => $cost->total_amount, 'total_kredit' => $cost->total_amount]);
            $journal->details()->createMany($lines);
            $cost->update(['status' => 'POSTED', 'posted_by' => Auth::id(), 'posted_at' => now(), 'journal_id' => $journal->id]);
            return $journal;
        });
    }

    public function allocate(LandedCost $cost, array $layerIds): void
    {
        DB::transaction(function () use ($cost, $layerIds) {
            $cost = LandedCost::lockForUpdate()->findOrFail($cost->id);
            if ($cost->status !== 'DRAFT') throw new RuntimeException('Alokasi dokumen posted tidak dapat diubah.');
            $layers = InventoryLayer::whereIn('id', $layerIds)->where('remaining_quantity', '>', 0)->get();
            if ($layers->count() !== count(array_unique($layerIds))) throw new RuntimeException('Layer landed cost tidak valid.');
            $weights = $layers->mapWithKeys(fn ($layer) => [$layer->id => $cost->allocation_basis === 'QUANTITY' ? (float) $layer->remaining_quantity : (float) $layer->remaining_quantity * (float) $layer->unit_cost]);
            $totalWeight = (float) $weights->sum();
            if ($totalWeight <= 0) throw new RuntimeException('Dasar alokasi landed cost harus lebih besar dari nol.');
            $cost->allocations()->delete();
            $allocated = 0.0;
            foreach ($layers->values() as $index => $layer) {
                $amount = $index === $layers->count() - 1 ? (float) $cost->total_amount - $allocated : round((float) $cost->total_amount * $weights[$layer->id] / $totalWeight, 2);
                $cost->allocations()->create(['inventory_layer_id' => $layer->id, 'base_value' => $weights[$layer->id], 'allocated_amount' => $amount, 'unit_cost_before' => $layer->unit_cost, 'unit_cost_after' => $layer->unit_cost]);
                $allocated += $amount;
            }
        });
    }
}
