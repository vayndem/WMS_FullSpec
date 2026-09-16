<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AksesPerRoleTest extends TestCase
{
    use DatabaseTransactions;

    public static function kontrakAkses(): array
    {
        return [
            'purchasing' => [User::ROLE_PURCHASING, [
                'pembelian.index', 'request.index', 'supplier.index',
                'pelanggan.index', 'pesanan-penjualan.index', 'surat-jalan.index',
                'data-pesanan.index', 'lacak-pembelian.index', 'supplier-scorecard.index',
            ], [
                'bagan-akun.index', 'period-lock.index',
            ]],

            'finance' => [User::ROLE_FINANCE, [
                'faktur-pembelian.index', 'faktur-penjualan.index',
            ], [
                'bagan-akun.index', 'data-pesanan.index',
            ]],

            'warehouse' => [User::ROLE_WAREHOUSE, [
                'penerimaan-barang.index', 'pemakaian-barang.index', 'transfer-gudangs.index',
                'stock-opname.index', 'antrean-kerja.index',
                'surat-jalan.index', 'pesanan-penjualan.index',
            ], [
                'bagan-akun.index', 'faktur-penjualan.index',
            ]],

            'accounting' => [User::ROLE_ACCOUNTING, [
                'bagan-akun.index', 'jurnal.index', 'period-lock.index', 'tax-rate.index',
                'financial-statements.neraca-saldo', 'financial-statements.calk',
                'reconciliation.index', 'rekonsiliasi-gudangs.index',
                'permintaan-persetujuan.index', 'faktur-penjualan.index', 'aset.index',
            ], []],

            'accounting_manager' => [User::ROLE_ACCOUNTING_MANAGER, [
                'jurnal.index', 'permintaan-persetujuan.index',
                'faktur-pembelian.index', 'executive-dashboard.index',
            ], []],

            'produksi' => [User::ROLE_PRODUCTION, [
                'pemakaian-barang.index', 'transfer-gudangs.index', 'data-pesanan.index',
            ], [
                'bagan-akun.index', 'faktur-penjualan.index', 'pelanggan.index',
            ]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('kontrakAkses')]
    public function test_each_role_can_open_the_pages_it_owns(int $tipe, array $wajibBisa, array $wajibDitolak): void
    {
        $user = User::factory()->create(['type' => $tipe]);

        $gagal = [];

        foreach ($wajibBisa as $nama) {
            $status = $this->actingAs($user)->get(route($nama))->getStatusCode();

            if ($status !== 200) {
                $gagal[] = "{$nama} => HTTP {$status} (seharusnya bisa dibuka)";
            }
        }

        foreach ($wajibDitolak as $nama) {
            $status = $this->actingAs($user)->get(route($nama))->getStatusCode();

            if ($status < 400) {
                $gagal[] = "{$nama} => HTTP {$status} (seharusnya ditolak)";
            }
        }

        $this->assertSame([], $gagal, "Kontrak akses role dilanggar:\n" . implode("\n", $gagal));
    }
}
