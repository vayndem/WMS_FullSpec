<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\CatatanLaporanKeuangan;
use App\Models\PerhitunganPajakPenghasilan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalkService
{
    public function __construct(private FinancialStatementService $statements) {}

    public const TEMPLATE_KEBIJAKAN = <<<'TEKS'
    a. Dasar penyusunan
    Laporan keuangan disusun berdasarkan Standar Akuntansi Keuangan yang berlaku di Indonesia, menggunakan dasar akrual dan konsep biaya historis, kecuali dinyatakan lain.

    b. Persediaan
    Persediaan dicatat dengan sistem perpetual dan dinilai memakai metode Masuk Pertama Keluar Pertama (FIFO) per gudang, sesuai PSAK 14. Biaya perolehan mencakup harga beli ditambah biaya-biaya yang dapat diatribusikan langsung (landed cost).

    c. Aset tetap
    Aset tetap dicatat sebesar biaya perolehan dikurangi akumulasi penyusutan, sesuai PSAK 16. Penyusutan komersial memakai metode garis lurus atau saldo menurun sesuai penetapan per aset. Pengeluaran setelah perolehan yang memperpanjang masa manfaat dikapitalisasi ke nilai tercatat aset.

    d. Pajak penghasilan
    Beban pajak terdiri atas pajak kini dan pajak tangguhan. Pajak tangguhan diakui memakai metode liabilitas atas perbedaan temporer antara nilai tercatat dan dasar pengenaan pajak, sesuai PSAK 46.

    e. Mata uang asing
    Transaksi dalam mata uang asing dicatat memakai kurs pada tanggal transaksi. Nilai persediaan telah ditetapkan dalam Rupiah pada saat penerimaan barang.
    TEKS;

    public function susun(Carbon $from, Carbon $to): array
    {
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $catatan = $this->catatan($from, $to);

        return [
            'from' => $from,
            'to' => $to,
            'catatan' => $catatan,
            'naratif' => $this->naratif($catatan),
            'rincian' => $this->rincian($from, $to),
        ];
    }

    public function catatan(Carbon $from, Carbon $to): ?CatatanLaporanKeuangan
    {
        return CatatanLaporanKeuangan::with('penyusun')
            ->whereDate('periode_dari', $from)
            ->whereDate('periode_sampai', $to)
            ->first();
    }

    public function simpan(Carbon $from, Carbon $to, array $isi, ?int $userId): CatatanLaporanKeuangan
    {
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return CatatanLaporanKeuangan::updateOrCreate(
            ['periode_dari' => $from->toDateString(), 'periode_sampai' => $to->toDateString()],
            array_merge(
                array_intersect_key($isi, CatatanLaporanKeuangan::BAGIAN_NARATIF),
                ['disusun_oleh' => $userId]
            )
        );
    }

    private function naratif(?CatatanLaporanKeuangan $catatan): array
    {
        $naratif = [];

        foreach (CatatanLaporanKeuangan::BAGIAN_NARATIF as $kunci => $judul) {
            $isi = $catatan?->{$kunci};

            if (($isi === null || trim($isi) === '') && $kunci === 'kebijakan_akuntansi') {
                $isi = self::TEMPLATE_KEBIJAKAN;
            }

            $naratif[$kunci] = [
                'judul' => $judul,
                'isi' => $isi,
                'bawaan' => $kunci === 'kebijakan_akuntansi' && $catatan?->kebijakan_akuntansi === null,
            ];
        }

        return $naratif;
    }

    private function rincian(Carbon $from, Carbon $to): Collection
    {
        $neraca = $this->statements->balanceSheet($to);
        $labaRugi = $this->statements->incomeStatement($from, $to);

        $dariAkun = fn (Collection $rows) => $rows->map(fn ($row) => [
            'label' => $row['account']->kode_akun . ' — ' . $row['account']->nama_akun,
            'nilai' => round((float) $row['amount'], 2),
        ])->values();

        return collect([
            $this->bagian('Rincian Aset', $dariAkun($neraca['aset']), $neraca['total_aset']),
            $this->bagian('Rincian Liabilitas', $dariAkun($neraca['liabilitas']), $neraca['total_liabilitas']),
            $this->bagian('Rincian Ekuitas', $dariAkun($neraca['ekuitas'])->push([
                'label' => 'Laba (rugi) ditahan',
                'nilai' => round((float) $neraca['laba_ditahan_berjalan'], 2),
            ]), $neraca['total_ekuitas']),
            $this->bagian('Rincian Pendapatan', $dariAkun($labaRugi['pendapatan']), $labaRugi['total_pendapatan']),
            $this->bagian('Rincian Beban', $dariAkun($labaRugi['beban']), $labaRugi['total_beban']),
            $this->asetTetap($to),
            $this->perpajakan($to),
        ])->filter(fn ($bagian) => $bagian !== null)->values();
    }

    private function bagian(string $judul, Collection $baris, $total): array
    {
        return [
            'judul' => $judul,
            'baris' => $baris,
            'total' => round((float) $total, 2),
        ];
    }

    private function asetTetap(Carbon $to): array
    {
        $aset = Aset::with('category')
            ->whereDate('acquisition_date', '<=', $to)
            ->get()
            ->groupBy(fn (Aset $item) => $item->category?->name ?? 'Tanpa kategori');

        $baris = $aset->map(fn (Collection $group, string $kategori) => [
            'label' => $kategori,
            'nilai' => round($group->sum(fn (Aset $item) => (float) $item->book_value), 2),
            'catatan' => 'Perolehan ' . number_format($group->sum(fn (Aset $item) => (float) $item->acquisition_cost), 0, ',', '.')
                . ' / Akumulasi ' . number_format($group->sum(fn (Aset $item) => (float) $item->accumulated_depreciation), 0, ',', '.')
                . ' / ' . $group->count() . ' unit',
        ])->values();

        return $this->bagian('Rincian Aset Tetap', $baris, $baris->sum('nilai'));
    }

    private function perpajakan(Carbon $to): ?array
    {
        $pajak = PerhitunganPajakPenghasilan::where('tahun_pajak', $to->year)->first();

        if (!$pajak) {
            return null;
        }

        $baris = collect([
            ['label' => 'Laba (rugi) komersial sebelum pajak', 'nilai' => round((float) $pajak->laba_komersial, 2)],
            ['label' => 'Koreksi fiskal positif', 'nilai' => round((float) $pajak->koreksi_positif, 2)],
            ['label' => 'Koreksi fiskal negatif', 'nilai' => round((float) $pajak->koreksi_negatif, 2)],
            ['label' => 'Koreksi beda waktu', 'nilai' => round((float) $pajak->koreksi_beda_waktu, 2)],
            ['label' => 'Laba (rugi) fiskal', 'nilai' => round((float) $pajak->laba_fiskal, 2)],
            ['label' => 'Kompensasi kerugian tahun sebelumnya', 'nilai' => round((float) $pajak->kompensasi_kerugian, 2)],
            ['label' => 'Penghasilan kena pajak', 'nilai' => round((float) $pajak->penghasilan_kena_pajak, 2)],
            ['label' => 'PPh Badan terutang', 'nilai' => round((float) $pajak->pph_terutang, 2)],
            ['label' => 'Gerakan pajak tangguhan', 'nilai' => round((float) $pajak->gerakan_pajak_tangguhan, 2)],
        ]);

        return $this->bagian("Perpajakan Tahun {$pajak->tahun_pajak}", $baris, $pajak->pph_terutang);
    }
}
