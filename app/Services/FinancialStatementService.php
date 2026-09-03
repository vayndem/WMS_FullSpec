<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JurnalDetail;
use Illuminate\Support\Carbon;

class FinancialStatementService
{
    public function trialBalance(Carbon $asOf): array
    {
        $accounts = ChartOfAccount::orderBy('kode_akun')->get();

        $sums = JurnalDetail::query()
            ->whereHas('jurnal', fn ($q) => $q->where('status', 'POSTED')->whereDate('tanggal', '<=', $asOf))
            ->selectRaw('coa_id, SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        $rows = $accounts->map(function (ChartOfAccount $account) use ($sums) {
            $sum = $sums->get($account->id);

            return [
                'account' => $account,
                'debit' => (float) ($sum->total_debit ?? 0),
                'kredit' => (float) ($sum->total_kredit ?? 0),
            ];
        })->values();

        return [
            'as_of' => $asOf,
            'rows' => $rows,
            'total_debit' => $rows->sum('debit'),
            'total_kredit' => $rows->sum('kredit'),
        ];
    }

    public function generalLedger(ChartOfAccount $account, Carbon $from, Carbon $to): array
    {
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $sign = $account->posisi_normal === 'DEBIT' ? 1 : -1;

        $openingSum = JurnalDetail::where('coa_id', $account->id)
            ->whereHas('jurnal', fn ($q) => $q->where('status', 'POSTED')->whereDate('tanggal', '<', $from))
            ->selectRaw('SUM(debit) as debit, SUM(kredit) as kredit')
            ->first();

        $opening = $sign * ((float) ($openingSum->debit ?? 0) - (float) ($openingSum->kredit ?? 0));

        $details = JurnalDetail::where('coa_id', $account->id)
            ->whereHas('jurnal', fn ($q) => $q->where('status', 'POSTED')->whereBetween('tanggal', [$from, $to]))
            ->with('jurnal')
            ->get()
            ->sortBy(fn (JurnalDetail $detail) => $detail->jurnal->tanggal->format('Y-m-d') . '-' . str_pad((string) $detail->id, 10, '0', STR_PAD_LEFT))
            ->values();

        $running = $opening;
        $rows = $details->map(function (JurnalDetail $detail) use (&$running, $sign) {
            $running += $sign * ((float) $detail->debit - (float) $detail->kredit);

            return [
                'tanggal' => $detail->jurnal->tanggal,
                'no_jurnal' => $detail->jurnal->no_jurnal,
                'keterangan' => $detail->keterangan ?: $detail->jurnal->keterangan,
                'debit' => (float) $detail->debit,
                'kredit' => (float) $detail->kredit,
                'balance' => $running,
            ];
        });

        return [
            'account' => $account,
            'from' => $from,
            'to' => $to,
            'opening_balance' => $opening,
            'rows' => $rows,
            'closing_balance' => $running,
        ];
    }

    public function incomeStatement(Carbon $from, Carbon $to): array
    {
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $sums = JurnalDetail::query()
            ->whereHas('coa', fn ($q) => $q->whereIn('kategori_akun', ['PENDAPATAN', 'BEBAN']))
            ->whereHas('jurnal', fn ($q) => $q->where('status', 'POSTED')->whereBetween('tanggal', [$from, $to]))
            ->selectRaw('coa_id, SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        $accounts = ChartOfAccount::whereIn('kategori_akun', ['PENDAPATAN', 'BEBAN'])->orderBy('kode_akun')->get();

        $section = function (string $kategori, bool $normalCredit) use ($accounts, $sums) {
            return $accounts->where('kategori_akun', $kategori)->map(function (ChartOfAccount $account) use ($sums, $normalCredit) {
                $sum = $sums->get($account->id);
                $debit = (float) ($sum->total_debit ?? 0);
                $kredit = (float) ($sum->total_kredit ?? 0);

                return [
                    'account' => $account,
                    'amount' => $normalCredit ? ($kredit - $debit) : ($debit - $kredit),
                ];
            })->filter(fn ($row) => $row['amount'] != 0)->values();
        };

        $pendapatan = $section('PENDAPATAN', true);
        $beban = $section('BEBAN', false);

        $totalPendapatan = $pendapatan->sum('amount');
        $totalBeban = $beban->sum('amount');

        return [
            'from' => $from,
            'to' => $to,
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'total_pendapatan' => $totalPendapatan,
            'total_beban' => $totalBeban,
            'laba_bersih' => $totalPendapatan - $totalBeban,
        ];
    }

    public function balanceSheet(Carbon $asOf): array
    {
        $trial = $this->trialBalance($asOf);
        $byCategory = $trial['rows']->groupBy(fn ($row) => $row['account']->kategori_akun);

        $section = function (string $kategori, bool $normalDebit) use ($byCategory) {
            return ($byCategory->get($kategori) ?? collect())->map(function ($row) use ($normalDebit) {
                return [
                    'account' => $row['account'],
                    'amount' => $normalDebit ? ($row['debit'] - $row['kredit']) : ($row['kredit'] - $row['debit']),
                ];
            })->filter(fn ($row) => $row['amount'] != 0)->values();
        };

        $aset = $section('ASET', true);
        $liabilitas = $section('LIABILITAS', false);
        $ekuitas = $section('EKUITAS', false);

        $totalAset = $aset->sum('amount');
        $totalLiabilitas = $liabilitas->sum('amount');
        $labaDitahanBerjalan = $this->cumulativeNetIncome($asOf);
        $totalEkuitas = $ekuitas->sum('amount') + $labaDitahanBerjalan;

        return [
            'as_of' => $asOf,
            'aset' => $aset,
            'liabilitas' => $liabilitas,
            'ekuitas' => $ekuitas,
            'laba_ditahan_berjalan' => $labaDitahanBerjalan,
            'total_aset' => $totalAset,
            'total_liabilitas' => $totalLiabilitas,
            'total_ekuitas' => $totalEkuitas,
        ];
    }

    private function cumulativeNetIncome(Carbon $asOf): float
    {
        $accountIds = ChartOfAccount::whereIn('kategori_akun', ['PENDAPATAN', 'BEBAN'])->pluck('id');

        $net = JurnalDetail::query()
            ->whereIn('coa_id', $accountIds)
            ->whereHas('jurnal', fn ($q) => $q->where('status', 'POSTED')->whereDate('tanggal', '<=', $asOf))
            ->selectRaw('SUM(kredit) - SUM(debit) as net')
            ->value('net');

        return (float) ($net ?? 0);
    }
}
