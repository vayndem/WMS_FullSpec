<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\KapitalisasiAset;
use App\Models\PenerimaanJasaDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KapitalisasiAsetService
{
    public const SUMBER_JASA = 'PENERIMAAN_JASA_DETAIL';

    public function akunAsetUntuk(Aset $aset): int
    {
        $aset->loadMissing('category');

        if (!$aset->category?->akun_aset_id) {
            throw new RuntimeException("Kategori aset {$aset->nomor_aset} belum punya mapping akun aset.");
        }

        return (int) $aset->category->akun_aset_id;
    }

    public function assertDapatDikapitalisasi(Aset $aset): void
    {
        if ($aset->status !== 'ACTIVE') {
            throw new RuntimeException("Aset {$aset->nomor_aset} tidak aktif, jasa tidak dapat dikapitalisasi ke aset ini.");
        }

        $this->akunAsetUntuk($aset);
    }

    public function catatDariJasa(PenerimaanJasaDetail $detail, ?int $journalId, $tanggal): ?KapitalisasiAset
    {
        $detail->loadMissing('servicePoDetail.aset.category');
        $aset = $detail->servicePoDetail?->aset;
        $nilai = round((float) $detail->amount, 2);

        if (!$aset || $nilai <= 0) {
            return null;
        }

        if (KapitalisasiAset::where('sumber_type', self::SUMBER_JASA)->where('sumber_id', $detail->id)->exists()) {
            return null;
        }

        $this->assertDapatDikapitalisasi($aset);

        return DB::transaction(function () use ($detail, $aset, $nilai, $journalId, $tanggal) {
            $terkunci = Aset::lockForUpdate()->findOrFail($aset->id);

            $sebelum = round((float) $terkunci->acquisition_cost, 2);
            $tambahanUmur = $this->tambahanUmurBelumTerpakai($detail);

            $terkunci->forceFill([
                'acquisition_cost' => $sebelum + $nilai,
                'book_value' => round((float) $terkunci->book_value, 2) + $nilai,
                'useful_life_months' => $tambahanUmur > 0 && $terkunci->useful_life_months
                    ? (int) $terkunci->useful_life_months + $tambahanUmur
                    : $terkunci->useful_life_months,
            ])->save();

            return KapitalisasiAset::create([
                'aset_id' => $terkunci->id,
                'sumber_type' => self::SUMBER_JASA,
                'sumber_id' => $detail->id,
                'tanggal' => $tanggal,
                'nilai' => $nilai,
                'tambahan_umur_bulan' => $tambahanUmur > 0 ? $tambahanUmur : null,
                'nilai_sebelum' => $sebelum,
                'nilai_sesudah' => $sebelum + $nilai,
                'keterangan' => 'Kapitalisasi jasa: ' . ($detail->servicePoDetail->description ?? '-'),
                'journal_id' => $journalId,
                'dicatat_oleh' => Auth::id(),
            ]);
        });
    }

    private function tambahanUmurBelumTerpakai(PenerimaanJasaDetail $detail): int
    {
        $diminta = (int) ($detail->servicePoDetail->tambahan_umur_bulan ?? 0);

        if ($diminta <= 0) {
            return 0;
        }

        $sudahDipakai = (int) KapitalisasiAset::where('sumber_type', self::SUMBER_JASA)
            ->whereIn('sumber_id', PenerimaanJasaDetail::where('service_po_detail_id', $detail->servicePoDetail->id)->pluck('id'))
            ->sum('tambahan_umur_bulan');

        return max($diminta - $sudahDipakai, 0);
    }

    public function batalkanDariJasa(PenerimaanJasaDetail $detail): bool
    {
        $catatan = KapitalisasiAset::where('sumber_type', self::SUMBER_JASA)
            ->where('sumber_id', $detail->id)->first();

        if (!$catatan) {
            return false;
        }

        return DB::transaction(function () use ($catatan) {
            $aset = Aset::lockForUpdate()->find($catatan->aset_id);

            if ($aset) {
                $nilai = round((float) $catatan->nilai, 2);
                $umur = (int) ($catatan->tambahan_umur_bulan ?? 0);

                $aset->forceFill([
                    'acquisition_cost' => max(round((float) $aset->acquisition_cost, 2) - $nilai, 0),
                    'book_value' => max(round((float) $aset->book_value, 2) - $nilai, 0),
                    'useful_life_months' => $umur > 0 && $aset->useful_life_months
                        ? max((int) $aset->useful_life_months - $umur, 1)
                        : $aset->useful_life_months,
                ])->save();
            }

            $catatan->delete();

            return true;
        });
    }
}
