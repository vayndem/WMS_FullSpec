<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SmokeSemuaHalamanTest extends TestCase
{
    use DatabaseTransactions;

    private const DILEWATI = [
        'ignition.healthCheck',
        'ignition.executeSolution',
        'ignition.updateConfig',
        'password.reset',   // butuh token asli dari email
        'logout',
    ];

    private function nilaiParameter(string $nama): ?string
    {
        $peta = [
            'aset' => ['wms_asets', 'id'],
            'bahan' => ['bahans', 'id'],
            'chart_of_account' => ['wms_bagan_akun', 'id'],
            'faktur' => ['wms_faktur_penjualan', 'id'],
            'faktur_pembelian' => ['wms_faktur_pembelian', 'id'],
            'gudang' => ['gudangs', 'id'],
            'id_lpb' => ['wms_penerimaan_barang', 'id_lpb'],
            'jurnal' => ['wms_jurnal', 'id'],
            'kategori_bahan' => ['kategori_bahans', 'id'],
            'kit' => ['wms_kit', 'id'],
            'lampiran' => ['wms_lampiran_dokumen', 'id'],
            'lpb' => ['wms_penerimaan_barang', 'id'],
            'no_po' => ['wms_pesanan_pembelian', 'no_po'],
            'npk' => ['wms_pemakaian_barang', 'id'],
            'pelanggan' => ['pelanggans', 'id'],
            'pemeriksaan_consider' => ['pemeriksaan_considers', 'id'],
            'pesanan' => ['wms_data_pesanan', 'id'],
            'request' => ['requests', 'id'],
            'requestdetail' => ['request_details', 'id'],
            'retur_pembelian' => ['wms_retur_pembelian', 'id'],
            'service_bap' => ['wms_penerimaan_barang', 'id'],
            'service_purchase' => ['wms_pesanan_pembelian', 'id'],
            'stock_opname' => ['wms_stock_opname', 'id'],
            'stok_gudang' => ['stok_gudangs', 'id'],
            'supplier' => ['suppliers', 'id'],
            'suratJalan' => ['wms_surat_jalan', 'id'],
            'tipe_pembebanan' => ['tipe_pembebanans', 'id'],
            'transfer_gudang' => ['transfer_gudangs', 'id'],
            'user' => ['users', 'id'],
        ];

        if ($nama === 'check') {
            return 'stock';
        }

        if (!isset($peta[$nama])) {
            return null;
        }

        [$tabel, $kolom] = $peta[$nama];

        $nilai = DB::table($tabel)->value($kolom);

        return $nilai === null ? null : (string) $nilai;
    }

    private function siapkanDokumen(User $admin): void
    {
        $stok = \App\Models\StokGudang::whereRaw('stok_tersedia - stok_direservasi >= 2')
            ->whereHas('gudang', fn ($q) => $q->where('jenis', \App\Models\Gudang::NORMAL))
            ->first();

        if (!$stok) {
            return;
        }

        $pelanggan = \App\Models\Pelanggan::create([
            'kode' => 'CUST-SMOKE',
            'nama' => 'PT Uji Smoke',
            'termin_hari' => 30,
            'is_active' => true,
        ]);

        $penjualan = app(\App\Services\PenjualanService::class);

        $so = $penjualan->buatPesanan([
            'tanggal' => today()->toDateString(),
            'pelanggan_id' => $pelanggan->id,
            'gudang_id' => $stok->gudang_id,
            'is_ppn' => true,
            'tarif_ppn' => 11,
            'details' => [['bahan_id' => $stok->bahan_id, 'jumlah' => 2, 'harga_satuan' => 50000]],
        ], $admin);

        app(\App\Services\DataPesananService::class)->buat($so->details->first(), [
            'tanggal' => today()->toDateString(),
            'gudang_id' => $stok->gudang_id,
            'jumlah_rencana' => 2,
        ], $admin);

        $sj = $penjualan->postingSuratJalan(
            $penjualan->buatSuratJalan($so->fresh('details'), [
                'tanggal' => today()->toDateString(),
                'details' => [['pesanan_penjualan_detail_id' => $so->details->first()->id, 'jumlah' => 2]],
            ], $admin),
            $admin
        );

        app(\App\Services\FakturPenjualanService::class)->buatDariSuratJalan(
            $sj,
            ['tanggal' => today()->toDateString()],
            $admin
        );

        $tujuan = \App\Models\Gudang::where('jenis', \App\Models\Gudang::NORMAL)
            ->whereKeyNot($stok->gudang_id)
            ->first();

        if ($tujuan) {
            $transfer = \App\Models\TransferGudang::create([
                'nomor_transfer' => 'TRF-SMOKE-1',
                'tanggal' => today(),
                'gudang_asal_id' => $stok->gudang_id,
                'gudang_tujuan_id' => $tujuan->id,
                'status' => \App\Models\TransferGudang::DRAFT,
                'dibuat_oleh' => $admin->id,
            ]);
            $transfer->details()->create(['bahan_id' => $stok->bahan_id, 'jumlah' => 1]);
        }
    }

    public function test_no_get_page_in_the_whole_application_returns_a_server_error(): void
    {
        $admin = User::factory()->create(['type' => User::ROLE_SUPER_ADMIN]);
        $this->siapkanDokumen($admin);

        $meledak = [];
        $dicoba = 0;
        $dilewati = [];

        foreach (Route::getRoutes() as $route) {
            $nama = $route->getName();

            if (!$nama || !in_array('GET', $route->methods(), true)) {
                continue;
            }

            if (in_array($nama, self::DILEWATI, true)) {
                continue;
            }

            $parameter = [];
            $lengkap = true;

            foreach ($route->parameterNames() as $param) {
                $nilai = $this->nilaiParameter($param);

                if ($nilai === null) {
                    $lengkap = false;
                    break;
                }

                $parameter[$param] = $nilai;
            }

            if (!$lengkap) {
                $dilewati[] = $nama;
                continue;
            }

            $dicoba++;

            try {
                $response = $this->actingAs($admin)->get(route($nama, $parameter));
                $status = $response->getStatusCode();
            } catch (\Throwable $e) {
                $meledak[] = $nama . ' => ' . get_class($e) . ': ' . $e->getMessage();
                continue;
            }

            if ($status >= 500) {
                $meledak[] = $nama . ' => HTTP ' . $status;
            }
        }

        $this->assertGreaterThan(130, $dicoba, 'Smoke test harus benar-benar menembak banyak halaman.');

        $this->assertLessThanOrEqual(
            6,
            count($dilewati),
            "Terlalu banyak halaman terlewat karena parameternya tidak bisa diresolusi:
" . implode("
", $dilewati)
                . "

Tambahkan datanya di siapkanDokumen() atau petakan parameternya di nilaiParameter()."
        );

        $this->assertSame(
            [],
            $meledak,
            "Halaman berikut menghasilkan error server:\n" . implode("\n", $meledak)
                . "\n\n(dilewati karena parameternya tidak bisa diresolusi: " . implode(', ', $dilewati) . ')'
        );
    }

    public function test_no_mutating_route_is_reachable_without_authentication(): void
    {
        $publik = ['login.attempt', 'password.email', 'password.update'];
        $tembus = [];
        $meledak = [];
        $dicoba = 0;

        foreach (Route::getRoutes() as $route) {
            $nama = $route->getName();
            $metode = array_values(array_diff($route->methods(), ['HEAD']));

            if (!$nama || in_array('GET', $metode, true)) {
                continue;
            }

            if (in_array($nama, self::DILEWATI, true) || in_array($nama, $publik, true)) {
                continue;
            }

            $parameter = [];
            $lengkap = true;

            foreach ($route->parameterNames() as $param) {
                $nilai = $this->nilaiParameter($param);

                if ($nilai === null) {
                    $lengkap = false;
                    break;
                }

                $parameter[$param] = $nilai;
            }

            if (!$lengkap) {
                continue;
            }

            $verb = strtolower($metode[0]);
            $dicoba++;

            try {
                $response = $this->call($verb, route($nama, $parameter));
                $status = $response->getStatusCode();
            } catch (\Throwable $e) {
                $meledak[] = "{$nama} => " . get_class($e) . ': ' . $e->getMessage();
                continue;
            }

            if ($status >= 500) {
                $meledak[] = "{$nama} => HTTP {$status} untuk tamu";
                continue;
            }

            if ($status < 300) {
                $tembus[] = "{$nama} ({$verb}) => HTTP {$status}; tamu seharusnya ditolak";
            }
        }

        $this->assertGreaterThan(100, $dicoba, 'Terlalu sedikit route mutasi yang diuji.');

        $this->assertSame(
            [],
            $meledak,
            "Route mutasi meledak alih-alih menolak tamu:\n" . implode("\n", $meledak)
        );

        $this->assertSame(
            [],
            $tembus,
            "Route mutasi berikut dapat dijalankan tanpa login:\n" . implode("\n", $tembus)
        );
    }
}
