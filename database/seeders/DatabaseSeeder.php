<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;
use App\Models\TipePembebanan;
use App\Models\Supplier;
use App\Models\Bahan;
use App\Models\Kategoribahan;
use App\Models\AdminNamagudang;
use App\Models\AccountingSetting;
use App\Models\TaxRate;
use App\Models\AssetCategory;
use App\Models\ServiceCategory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);

        // Konfigurasi master dan data transaksi simulasi dibuat otomatis agar
        // migrate:fresh --seed langsung menghasilkan lingkungan uji yang utuh.
        TaxRate::updateOrCreate(
            ['tax_type' => 'PPN', 'effective_from' => '2025-01-01'],
            ['rate' => 11, 'is_active' => true, 'description' => 'Default WMS; ubah melalui master tarif saat kebijakan berubah.']
        );
        TaxRate::updateOrCreate(
            ['tax_type' => 'PPH23', 'effective_from' => '2025-01-01'],
            ['rate' => 2, 'is_active' => true, 'description' => 'Snapshot dasar PPh supplier; pengakuan tetap saat pembayaran.']
        );
        $coas = [
            ['kode_akun' => '1101', 'nama_akun' => 'Kas Utama', 'kategori_akun' => 'ASET', 'posisi_normal' => 'DEBIT', 'is_cash_bank' => true],
            ['kode_akun' => '1102', 'nama_akun' => 'Bank BCA', 'kategori_akun' => 'ASET', 'posisi_normal' => 'DEBIT', 'is_cash_bank' => true],
            ['kode_akun' => '1103', 'nama_akun' => 'PPN Masukan', 'kategori_akun' => 'ASET', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '1301', 'nama_akun' => 'Persediaan Bahan Baku', 'kategori_akun' => 'ASET', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '1401', 'nama_akun' => 'Uang Muka Supplier', 'kategori_akun' => 'ASET', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '2101', 'nama_akun' => 'Hutang Usaha (Supplier)', 'kategori_akun' => 'LIABILITAS', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '2102', 'nama_akun' => 'Hutang LPB Belum Ditagih', 'kategori_akun' => 'LIABILITAS', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '2103', 'nama_akun' => 'Hutang PPh 23', 'kategori_akun' => 'LIABILITAS', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '5101', 'nama_akun' => 'Beban Bahan Baku Direct', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '5102', 'nama_akun' => 'Beban Perlengkapan Operasional', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '5103', 'nama_akun' => 'Beban Administrasi Bank', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '5104', 'nama_akun' => 'Beban Materai', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '4201', 'nama_akun' => 'Pendapatan Selisih Pembayaran', 'kategori_akun' => 'PENDAPATAN', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '5105', 'nama_akun' => 'Beban Angkut Pembelian', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '5201', 'nama_akun' => 'Diskon Pembelian', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '5106', 'nama_akun' => 'Beban Selisih Stock Opname', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '5107', 'nama_akun' => 'Beban Selisih Pembayaran', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '4202', 'nama_akun' => 'Koreksi Positif Stock Opname', 'kategori_akun' => 'PENDAPATAN', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '1302', 'nama_akun' => 'Barang Dalam Proses Jasa', 'kategori_akun' => 'ASET', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '1501', 'nama_akun' => 'Peralatan dan Inventaris', 'kategori_akun' => 'ASET', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '1591', 'nama_akun' => 'Akumulasi Penyusutan Peralatan', 'kategori_akun' => 'ASET', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '2104', 'nama_akun' => 'GRNI Jasa', 'kategori_akun' => 'LIABILITAS', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '2201', 'nama_akun' => 'Hutang Pembelian Asset', 'kategori_akun' => 'LIABILITAS', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '3102', 'nama_akun' => 'Ekuitas Saldo Awal Asset', 'kategori_akun' => 'EKUITAS', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '4203', 'nama_akun' => 'Keuntungan Pelepasan Asset', 'kategori_akun' => 'PENDAPATAN', 'posisi_normal' => 'KREDIT'],
            ['kode_akun' => '5202', 'nama_akun' => 'Beban Jasa Operasional', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '5301', 'nama_akun' => 'Beban Penyusutan Peralatan', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
            ['kode_akun' => '5302', 'nama_akun' => 'Kerugian Pelepasan Asset', 'kategori_akun' => 'BEBAN', 'posisi_normal' => 'DEBIT'],
        ];

        foreach ($coas as $coa) {
            ChartOfAccount::updateOrCreate(['kode_akun' => $coa['kode_akun']], array_merge([
                'is_active' => true,
                'is_postable' => true,
                'is_cash_bank' => false,
            ], $coa));
        }

        $accountMappings = [
            AccountingSetting::HUTANG_USAHA => '2101',
            AccountingSetting::PPN_MASUKAN => '1103',
            AccountingSetting::HUTANG_PPH23 => '2103',
            AccountingSetting::BIAYA_BANK => '5103',
            AccountingSetting::BEBAN_MATERAI => '5104',
            AccountingSetting::SELISIH_BAYAR => '4201',
            AccountingSetting::BIAYA_ONGKIR => '5105',
            AccountingSetting::DISKON_PEMBELIAN => '5201',
        ];
        foreach ($accountMappings as $key => $code) {
            AccountingSetting::updateOrCreate(
                ['key' => $key],
                [
                    'coa_id' => ChartOfAccount::where('kode_akun', $code)->value('id'),
                    'description' => 'Mapping sistem WMS; nama dan kode akun tetap dapat disesuaikan accountant.'
                ]
            );
        }

        $tipe1 = TipePembebanan::updateOrCreate(
            ['nama_tipe' => 'DIRECT_COST'],
            ['keterangan' => 'Pembebanan langsung ke Beban Baku']
        );

        $tipe2 = TipePembebanan::updateOrCreate(
            ['nama_tipe' => 'INVENTORY'],
            ['keterangan' => 'Pembebanan ke Persediaan Stok Gudang']
        );

        $coaPersediaan = ChartOfAccount::where('kode_akun', '1301')->first();
        $coaBeban = ChartOfAccount::where('kode_akun', '5101')->first();
        $coaClearing = ChartOfAccount::where('kode_akun', '2102')->first();
        $coaOpnameLoss = ChartOfAccount::where('kode_akun', '5106')->first();
        $coaOpnameGain = ChartOfAccount::where('kode_akun', '4202')->first();

        $katBahan = Kategoribahan::updateOrCreate(
            ['katnama' => 'Bahan Baku Paper'],
            [
                'tipe_pembebanan_id'  => $tipe2->id,
                'coa_persediaan_id'   => $coaPersediaan?->id,
                'coa_beban_id'        => $coaBeban?->id,
                'coa_clearing_lpb_id' => $coaClearing?->id,
                'coa_beban_selisih_opname_id' => $coaOpnameLoss?->id,
                'coa_koreksi_opname_id' => $coaOpnameGain?->id,
            ]
        );

        $katJasaOperasional = Kategoribahan::updateOrCreate(
            ['katnama' => 'Jasa Operasional'],
            [
                'tipe_pembebanan_id'  => $tipe1->id,
                'coa_persediaan_id'   => ChartOfAccount::where('kode_akun', '5202')->value('id'),
                'coa_beban_id'        => ChartOfAccount::where('kode_akun', '5202')->value('id'),
                'coa_clearing_lpb_id' => ChartOfAccount::where('kode_akun', '2104')->value('id'),
                'coa_beban_selisih_opname_id' => $coaOpnameLoss?->id,
                'coa_koreksi_opname_id' => $coaOpnameGain?->id,
            ]
        );

        $katJasaProduksi = Kategoribahan::updateOrCreate(
            ['katnama' => 'Jasa Produksi'],
            [
                'tipe_pembebanan_id'  => $tipe2->id,
                'coa_persediaan_id'   => ChartOfAccount::where('kode_akun', '1302')->value('id'),
                'coa_beban_id'        => ChartOfAccount::where('kode_akun', '1302')->value('id'),
                'coa_clearing_lpb_id' => ChartOfAccount::where('kode_akun', '2104')->value('id'),
                'coa_beban_selisih_opname_id' => $coaOpnameLoss?->id,
                'coa_koreksi_opname_id' => $coaOpnameGain?->id,
            ]
        );

        $gudang = AdminNamagudang::firstOrCreate(
            ['nama' => 'Gudang Utama']
        );

        $suppliers = [
            [
                'nama'       => 'PT. Jaya Mandiri Utama',
                'alamat'     => 'Jl. Industri No. 45, Jakarta',
                'telp'       => '021-5551234',
                'pembayaran' => 'CASH',
            ],
            [
                'nama'       => 'CV. Sumber Makmur',
                'alamat'     => 'Jl. Raya Banten No. 88, Tangerang',
                'telp'       => '021-5555678',
                'pembayaran' => 'KREDIT 30 HARI',
            ],
            [
                'nama'       => 'PT. Global Supply Indonesia',
                'alamat'     => 'Jl. Gatot Subroto Km 7, Surabaya',
                'telp'       => '031-7778899',
                'pembayaran' => 'KREDIT 14 HARI',
            ],
        ];

        foreach ($suppliers as $sup) {
            Supplier::updateOrCreate(['nama' => $sup['nama']], $sup);
        }

        $bahans = [
            [
                'kategori'         => $katBahan->id,
                'nama'             => 'Kertas Karton Dupleks 350gr',
                'keterangan_bahan' => 'Bahan Baku Kertas',
                'satuan'           => 'KG',
                'stok_onhand'      => 0,
                'stok_onpurchase'  => 0,
                'planning'         => 0,
                'stokawal'         => 0,
                'tipe_gudang'      => $gudang->id,
                'tipe_barang'      => $katBahan->id,
            ],
            [
                'kategori'         => $katBahan->id,
                'nama'             => 'Tinta Cetak Hitam Offset',
                'keterangan_bahan' => 'Bahan Penolong',
                'satuan'           => 'KG',
                'stok_onhand'      => 0,
                'stok_onpurchase'  => 0,
                'planning'         => 0,
                'stokawal'         => 0,
                'tipe_gudang'      => $gudang->id,
                'tipe_barang'      => $katBahan->id,
            ],
        ];

        foreach ($bahans as $bhn) {
            Bahan::updateOrCreate(['nama' => $bhn['nama']], $bhn);
        }

        AssetCategory::updateOrCreate(['code' => 'EQUIPMENT'], [
            'name' => 'Peralatan dan Inventaris',
            'asset_coa_id' => ChartOfAccount::where('kode_akun', '1501')->value('id'),
            'accumulated_depreciation_coa_id' => ChartOfAccount::where('kode_akun', '1591')->value('id'),
            'depreciation_expense_coa_id' => ChartOfAccount::where('kode_akun', '5301')->value('id'),
            'disposal_gain_coa_id' => ChartOfAccount::where('kode_akun', '4203')->value('id'),
            'disposal_loss_coa_id' => ChartOfAccount::where('kode_akun', '5302')->value('id'),
            'is_active' => true,
        ]);

        ServiceCategory::updateOrCreate(['code' => ServiceCategory::OPERATIONAL], [
            'display_code' => '98',
            'kategori_bahan_id' => $katJasaOperasional->id,
            'name' => 'Jasa Operasional',
            'expense_coa_id' => ChartOfAccount::where('kode_akun', '5202')->value('id'),
            'grni_coa_id' => ChartOfAccount::where('kode_akun', '2104')->value('id'),
            'requires_datapesanan' => false,
            'requires_cost_center' => true,
            'is_active' => true,
        ]);
        ServiceCategory::updateOrCreate(['code' => ServiceCategory::PRODUCTION], [
            'display_code' => '99',
            'kategori_bahan_id' => $katJasaProduksi->id,
            'name' => 'Jasa Produksi',
            'expense_coa_id' => ChartOfAccount::where('kode_akun', '1302')->value('id'),
            'grni_coa_id' => ChartOfAccount::where('kode_akun', '2104')->value('id'),
            'requires_datapesanan' => true,
            'requires_cost_center' => false,
            'is_active' => true,
        ]);

        $this->call([
            WmsDemoSeeder::class,
            WmsTransactionScenarioSeeder::class,
            AssetAndServiceDemoSeeder::class,
        ]);
    }
}
