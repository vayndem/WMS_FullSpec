<?php

namespace App\Services;

use App\Models\DocumentReversal;
use App\Models\Bahan;
use App\Models\InventoryLayer;
use App\Models\Lpb;
use App\Models\Npk;
use App\Models\PembelianDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryReversalService
{
    public function __construct(
        private StokGudangService $stock,
        private WmsAccountingService $accounting,
        private DocumentNumberService $numbers,
        private AccountingPeriodService $periods,
    ) {}

    public function reverseNpk(Npk $npk, string $reason): DocumentReversal
    {
        $this->periods->assertOpen(now(), 'Reversal NPK');
        return DB::transaction(function () use ($npk, $reason) {
            $npk = Npk::lockForUpdate()->findOrFail($npk->id);
            $this->assertNotReversed('NPK', $npk->id);
            if ($npk->status !== Npk::POSTED) throw new RuntimeException('Hanya NPK posted yang dapat dibalik.');
            $unitCost = (float) $npk->harga_satuan;
            $quantity = (float) $npk->jumlah_stok > 0 ? (float) $npk->jumlah_stok : (float) $npk->jumlah;
            if ($quantity <= 0) throw new RuntimeException('Jumlah NPK tidak valid untuk reversal.');
            $this->accounting->restoreStock($npk, false);
            $this->stock->masuk((int) $npk->id_gudang_asal, (int) $npk->id_barang, $quantity, $unitCost, 'REVERSAL_NPK', 'NPK', $npk->id, $reason);
            $journal = $this->accounting->reverseAutomaticJournal('NPK', $npk->id, "Reversal NPK {$npk->kode}: {$reason}");
            $npk->update(['status' => Npk::REVERSED]);
            return $this->record('NPK', $npk->id, $reason, $journal->id);
        });
    }

    public function reverseLpb(Lpb $lpb, string $reason): DocumentReversal
    {
        $this->periods->assertOpen(now(), 'Reversal LPB');
        return DB::transaction(function () use ($lpb, $reason) {
            $lpb = Lpb::with('details')->lockForUpdate()->findOrFail($lpb->id);
            $this->assertNotReversed('LPB', $lpb->id);
            if ($lpb->status !== Lpb::POSTED) throw new RuntimeException('Hanya LPB posted aktif yang dapat dibalik.');
            if ($lpb->invoiceReceipts()->exists()) throw new RuntimeException('LPB sudah ditagih. Void invoice terlebih dahulu.');

            foreach ($lpb->details as $detail) {
                $layer = InventoryLayer::where('source_type', 'LPB_DETAIL')->where('source_id', $detail->id)->lockForUpdate()->firstOrFail();
                if (abs((float) $layer->initial_quantity - (float) $layer->remaining_quantity) > 0.000001) {
                    throw new RuntimeException("Stok LPB {$lpb->id_lpb} sudah digunakan dan tidak dapat dibalik langsung.");
                }
                $this->stock->keluar((int) $lpb->gudang_id, (int) $detail->id_bahan, (float) $detail->jumlah_barang_diterima, (float) $detail->harga, 'REVERSAL_LPB', 'LPB', $lpb->id, $reason);
                $layer->update(['remaining_quantity' => 0, 'stock_status' => 'REVERSED']);
                $poLine = PembelianDetail::where('no_po', $lpb->no_po)->where('bahan_id', $detail->id_bahan)->lockForUpdate()->first();
                if ($poLine) {
                    $poLine->decrement('diterima', min((float) $poLine->diterima, (float) $detail->jumlah_barang_diterima));
                    $poLine->refresh();
                    $restoreOrdered = min((float) $detail->jumlah_barang_diterima, max(0, (float) $poLine->jumlah - (float) $poLine->diterima));
                    if ($restoreOrdered > 0) {
                        Bahan::whereKey($detail->id_bahan)->increment('stok_onpurchase', $restoreOrdered);
                        $this->stock->tambahPesanan((int) $lpb->gudang_id, (int) $detail->id_bahan, $restoreOrdered);
                    }
                }
            }
            $journal = $this->accounting->reverseAutomaticJournal('LPB', $lpb->id, "Reversal LPB {$lpb->id_lpb}: {$reason}");
            $lpb->update(['cancelled_by' => Auth::id(), 'cancelled_at' => now(), 'cancellation_reason' => $reason, 'status' => Lpb::REVERSED]);
            return $this->record('LPB', $lpb->id, $reason, $journal->id);
        });
    }

    private function assertNotReversed(string $type, int $id): void
    {
        if (DocumentReversal::where('document_type', $type)->where('document_id', $id)->exists()) throw new RuntimeException('Dokumen sudah pernah dibalik.');
    }

    private function record(string $type, int $id, string $reason, int $journalId): DocumentReversal
    {
        return DocumentReversal::create(['number' => $this->numbers->internal('RVS', 'DOC'), 'document_type' => $type, 'document_id' => $id, 'reason' => $reason, 'reversal_journal_id' => $journalId, 'created_by' => Auth::id(), 'posted_at' => now()]);
    }
}
