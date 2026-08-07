<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\AssetDisposal;
use App\Models\Jurnal;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssetAccountingService
{
    public function __construct(private AccountingPeriodService $periods, private DocumentNumberService $numbers) {}

    public function postAcquisition(Asset $asset): Jurnal
    {
        $this->periods->assertOpen($asset->acquisition_date, 'Perolehan asset');
        $asset->loadMissing('category');
        $this->assertCategoryMapping($asset);
        $this->assertAcquisitionAccount($asset);
        $cost = (float) $asset->acquisition_cost;
        $opening = (float) $asset->opening_accumulated_depreciation;
        if ($cost <= 0 || $opening < 0 || $opening > $cost) {
            throw new RuntimeException('Nilai perolehan atau akumulasi penyusutan awal tidak valid.');
        }

        $lines = [[
            'coa_id' => $asset->category->asset_coa_id,
            'debit' => $cost,
            'kredit' => 0,
            'keterangan' => "Perolehan {$asset->asset_number}"
        ]];
        if ($opening > 0) {
            $lines[] = [
                'coa_id' => $asset->category->accumulated_depreciation_coa_id,
                'debit' => 0,
                'kredit' => $opening,
                'keterangan' => 'Akumulasi penyusutan sebelum sistem'
            ];
        }
        $lines[] = [
            'coa_id' => $asset->acquisition_credit_coa_id,
            'debit' => 0,
            'kredit' => $cost - $opening,
            'keterangan' => "Sumber perolehan {$asset->acquisition_type}"
        ];

        return $this->post(
            "AST-{$asset->asset_number}",
            $asset->acquisition_date,
            'ASSET_ACQUISITION',
            $asset->id,
            "Perolehan asset {$asset->name}",
            $lines
        );
    }

    public function depreciate(Asset $asset, array $data): AssetDepreciation
    {
        $this->periods->assertOpen($data['posting_date'], 'Penyusutan asset');
        return DB::transaction(function () use ($asset, $data) {
            $asset = Asset::with('category')->lockForUpdate()->findOrFail($asset->id);
            if ($asset->status !== 'ACTIVE') throw new RuntimeException('Hanya asset aktif yang dapat disusutkan.');
            $this->assertCategoryMapping($asset);
            $amount = round((float) $data['amount'], 2);
            $maximum = round((float) $asset->book_value - (float) $asset->residual_value, 2);
            if ($amount <= 0 || $amount > $maximum) {
                throw new RuntimeException("Penyusutan maksimal adalah {$maximum} agar tidak melewati nilai residu.");
            }
            $depreciation = $asset->depreciations()->create([
                'posting_date' => $data['posting_date'],
                'period_label' => $data['period_label'],
                'suggested_amount' => $asset->suggestedMonthlyDepreciation(),
                'amount' => $amount,
                'book_value_before' => $asset->book_value,
                'book_value_after' => $maximum + $asset->residual_value - $amount,
                'reason' => $data['reason'] ?? null,
                'posted_by' => Auth::id(),
            ]);
            $journal = $this->post(
                "DEP-{$asset->asset_number}-{$depreciation->id}",
                $data['posting_date'],
                'ASSET_DEPRECIATION',
                $depreciation->id,
                "Penyusutan {$asset->name}",
                [
                    ['coa_id' => $asset->category->depreciation_expense_coa_id, 'debit' => $amount, 'kredit' => 0, 'keterangan' => 'Beban penyusutan'],
                    ['coa_id' => $asset->category->accumulated_depreciation_coa_id, 'debit' => 0, 'kredit' => $amount, 'keterangan' => 'Akumulasi penyusutan'],
                ]
            );
            $depreciation->update(['journal_id' => $journal->id]);
            $asset->update([
                'accumulated_depreciation' => (float) $asset->accumulated_depreciation + $amount,
                'book_value' => (float) $asset->book_value - $amount,
                'last_depreciation_date' => $data['posting_date'],
            ]);
            return $depreciation->fresh('journal');
        });
    }

    public function dispose(Asset $asset, array $data): AssetDisposal
    {
        $this->periods->assertOpen($data['disposal_date'], 'Pelepasan asset');
        return DB::transaction(function () use ($asset, $data) {
            $asset = Asset::with('category')->lockForUpdate()->findOrFail($asset->id);
            if ($asset->status !== 'ACTIVE') throw new RuntimeException('Asset sudah tidak aktif.');
            $this->assertCategoryMapping($asset);
            $proceeds = $data['disposal_type'] === 'SALE' ? round((float) $data['proceeds'], 2) : 0;
            if ($data['disposal_type'] === 'SALE' && empty($data['cash_bank_coa_id'])) {
                throw new RuntimeException('Akun kas/bank wajib dipilih untuk penjualan asset.');
            }
            if ($data['disposal_type'] === 'SALE') {
                ChartOfAccount::assertUsable($data['cash_bank_coa_id'], [['ASET', 'DEBIT']], 'kas/bank penjualan asset', true);
            }
            $book = (float) $asset->book_value;
            $gain = max($proceeds - $book, 0);
            $loss = max($book - $proceeds, 0);
            $disposal = AssetDisposal::create([
                'asset_id' => $asset->id,
                'disposal_date' => $data['disposal_date'],
                'disposal_type' => $data['disposal_type'],
                'proceeds' => $proceeds,
                'cash_bank_coa_id' => $data['cash_bank_coa_id'] ?? null,
                'book_value_at_disposal' => $book,
                'gain_amount' => $gain,
                'loss_amount' => $loss,
                'reason' => $data['reason'],
                'disposed_by' => Auth::id(),
            ]);
            $lines = [];
            if ($proceeds > 0) $lines[] = ['coa_id' => $data['cash_bank_coa_id'], 'debit' => $proceeds, 'kredit' => 0, 'keterangan' => 'Hasil penjualan asset'];
            if ((float) $asset->accumulated_depreciation > 0) $lines[] = [
                'coa_id' => $asset->category->accumulated_depreciation_coa_id,
                'debit' => (float) $asset->accumulated_depreciation,
                'kredit' => 0,
                'keterangan' => 'Hapus akumulasi penyusutan'
            ];
            if ($loss > 0) $lines[] = ['coa_id' => $asset->category->disposal_loss_coa_id, 'debit' => $loss, 'kredit' => 0, 'keterangan' => 'Rugi pelepasan asset'];
            $lines[] = ['coa_id' => $asset->category->asset_coa_id, 'debit' => 0, 'kredit' => (float) $asset->acquisition_cost, 'keterangan' => 'Hapus harga perolehan asset'];
            if ($gain > 0) $lines[] = ['coa_id' => $asset->category->disposal_gain_coa_id, 'debit' => 0, 'kredit' => $gain, 'keterangan' => 'Keuntungan pelepasan asset'];
            $journal = $this->post(
                "DSP-{$asset->asset_number}",
                $data['disposal_date'],
                'ASSET_DISPOSAL',
                $disposal->id,
                "Pelepasan asset {$asset->name}",
                $lines
            );
            $disposal->update(['journal_id' => $journal->id]);
            $asset->update(['status' => $data['disposal_type'] === 'SALE' ? 'SOLD' : 'DISPOSED']);
            return $disposal->fresh('journal');
        });
    }

    private function post(string $number, $date, string $source, int $referenceId, string $description, array $lines): Jurnal
    {
        $lines = collect($lines)->filter(fn($line) => $line['debit'] > 0 || $line['kredit'] > 0)->values();
        $debit = round($lines->sum('debit'), 2);
        $credit = round($lines->sum('kredit'), 2);
        if ($debit <= 0 || abs($debit - $credit) > .01) throw new RuntimeException("Jurnal {$number} tidak seimbang.");
        $existing = Jurnal::where('sumber_transaksi', $source)->where('reff_id', $referenceId)->first();
        $journal = Jurnal::updateOrCreate([
            'sumber_transaksi' => $source,
            'reff_id' => $referenceId,
        ], [
            'no_jurnal' => $existing?->no_jurnal ?? $this->numbers->financial('JR', $date),
            'tanggal' => $date,
            'keterangan' => $description,
            'status' => 'POSTED',
            'created_by' => Auth::id(),
            'posted_by' => Auth::id(),
            'posted_at' => now(),
            'total_debit' => $debit,
            'total_kredit' => $credit,
        ]);
        $journal->details()->delete();
        $journal->details()->createMany($lines->all());
        return $journal;
    }

    private function assertCategoryMapping(Asset $asset): void
    {
        if (!$asset->category) {
            throw new RuntimeException('Kategori asset tidak tersedia.');
        }
        ChartOfAccount::assertUsable($asset->category->asset_coa_id, [['ASET', 'DEBIT']], 'harga perolehan asset');
        ChartOfAccount::assertUsable($asset->category->accumulated_depreciation_coa_id, [['ASET', 'KREDIT']], 'akumulasi penyusutan');
        ChartOfAccount::assertUsable($asset->category->depreciation_expense_coa_id, [['BEBAN', 'DEBIT']], 'beban penyusutan');
        ChartOfAccount::assertUsable($asset->category->disposal_gain_coa_id, [['PENDAPATAN', 'KREDIT']], 'keuntungan pelepasan asset');
        ChartOfAccount::assertUsable($asset->category->disposal_loss_coa_id, [['BEBAN', 'DEBIT']], 'kerugian pelepasan asset');
    }

    private function assertAcquisitionAccount(Asset $asset): void
    {
        [$allowed, $mustBeCash] = match ($asset->acquisition_type) {
            'CASH' => [[['ASET', 'DEBIT']], true],
            'CREDIT' => [[['LIABILITAS', 'KREDIT']], null],
            'GRANT', 'OPENING_BALANCE' => [[['EKUITAS', 'KREDIT']], null],
            'CORRECTION' => [[['EKUITAS', 'KREDIT'], ['PENDAPATAN', 'KREDIT']], null],
            default => throw new RuntimeException('Jenis perolehan asset tidak dikenali.'),
        };
        ChartOfAccount::assertUsable($asset->acquisition_credit_coa_id, $allowed, 'lawan perolehan asset', $mustBeCash);
    }
}
