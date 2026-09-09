# ERP - Modul WMS Technical Reference

Dokumen ini adalah referensi teknis untuk developer, QA, DBA, dan maintainer. **Refresh terakhir: 6 September 2026** — mencakup rename sweep baku Indonesia (tabel `wms_*`, class model/controller Indonesia), migrasi Tailwind CSS + daisyUI + Alpine.js, role `accounting_manager` (maker-checker approval invoice), Material Request yang dibuka untuk semua role, dashboard "all tasks" + chart per role, Dashboard Eksekutif, export Excel di seluruh laporan, depresiasi aset otomatis, PPh 22/4(2) Final, dan field multi-currency ringan pada faktur pembelian. Jika ada detail yang meleset, `CLAUDE.md` di root repo adalah sumber kebenaran paling mutakhir.

Untuk gambaran produk dan alur bisnis yang lebih singkat, lihat [README.md](README.md).

## 1. Technical baseline

| Komponen | Implementasi |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Database | MySQL |
| Web auth | Laravel session auth |
| API auth | Laravel Sanctum |
| UI | Blade, Tailwind CSS, daisyUI, Alpine.js |
| Build | Vite |
| Chart | Chart.js (via `taskChart` Alpine component) |
| Report | DomPDF, TCPDF, Laravel Excel (Maatwebsite) |
| Test | PHPUnit 11 |

Arsitektur aplikasi berbentuk modular monolith Laravel.

## 2. Lapisan aplikasi

| Lapisan | Lokasi | Tanggung jawab |
|---|---|---|
| Routes | `routes/*.php` (dipisah per domain: `auth.php`, `warehouse.php`, `procurement.php`, `finance.php`, `accounting.php`, `assets-and-services.php`, di-require dari `routes/web.php`) | endpoint dan middleware |
| Controllers | `app/Http/Controllers` | orkestrasi use case, HTTP-only |
| Form Requests | `app/Http/Requests` | validasi dan sebagian authorization |
| Policies | `app/Policies` | role dan state guard, auto-discovered |
| Services | `app/Services` | FIFO, accounting, numbering, opname, asset, reconciliation, transfer, three-way match, landed cost, reversal |
| Models | `app/Models` | relasi, cast, helper domain |
| Migrations | `database/migrations` | schema dan constraint |
| Seeders | `database/seeders` | role, user, master data, demo data |
| Views | `resources/views` | Blade + Tailwind CSS + daisyUI + Alpine.js |

Perubahan stok dan akuntansi penting dibungkus `DB::transaction()`. Operasi yang rawan race condition memakai `lockForUpdate()`. Controller tidak boleh memanggil `->validate()` langsung — semua validasi wajib lewat Form Request (diperiksa `tests/Unit/ArchitectureConventionTest.php`).

## 3. Authentication

### 3.1 Web login

Flow login:

1. `GET /` menampilkan form login
2. `POST /login` memvalidasi email dan password
3. `Auth::attempt()` memeriksa credential ke tabel `users`
4. session diregenerasi setelah login sukses
5. user diarahkan ke `dashboard`
6. logout menjalankan invalidate session dan regenerate CSRF token

### 3.2 User dan role

Role tidak lagi berupa angka liar langsung tanpa referensi. Struktur sekarang:

- `users.type` menyimpan foreign key ke `user_roles.id`
- `user_roles` menjadi kamus role

Role aktif:

| ID | Name | Keterangan |
|---:|---|---|
| 0 | `super_admin` | bypass semua policy |
| 1 | `purchasing` | operasional procurement |
| 2 | `finance` | pembayaran supplier |
| 3 | `warehouse` | operasional gudang umum |
| 4 | `accounting` | akuntansi dan kontrol |
| 5 | `production` | operasional gudang produksi |
| 6 | `accounting_manager` | approve faktur pembelian saja (maker-checker), ditambahkan 2026-09-05 |

Helper penting pada `User`:

- `isSuperAdmin()`
- `isPurchasing()`
- `isFinance()`
- `isWarehouse()`
- `isAccounting()`
- `isProduction()`
- `isAccountingManager()`
- `isWarehouseOperator()`
- `accessibleGudangIds($ability)`
- `canAccessGudang($gudangId, $ability)`

### 3.3 SuperAdmin

`SuperAdmin` memakai `type = 0` dan mendapat bypass policy melalui `Gate::before` pada `AuthServiceProvider`.

Artinya:

- dapat mengakses seluruh modul
- tidak dibatasi state policy biasa
- cocok untuk maintenance dan audit

### 3.4 Development accounts

`UserSeeder` membuat akun:

| Email | Password | Type | Role |
|---|---|---:|---|
| `superadmin@wms.local` | `Wms12345!` | 0 | SuperAdmin |
| `purchasing@wms.local` | `Wms12345!` | 1 | Purchasing |
| `finance@wms.local` | `Wms12345!` | 2 | Finance |
| `warehouse@wms.local` | `Wms12345!` | 3 | Warehouse |
| `accounting@wms.local` | `Wms12345!` | 4 | Accounting |
| `produksi@wms.local` | `Wms12345!` | 5 | Produksi |
| `accounting-manager@wms.local` | `Wms12345!` | 6 | Accounting Manager |

## 4. Authorization

### 4.1 Gambaran role

| Capability | 0 | 1 | 2 | 3 | 4 | 5 | 6 |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Bypass policy | yes | no | no | no | no | no | no |
| Supplier | yes | yes | no | no | no | no | no |
| Request (semua role kecuali Purchasing bisa buat) | yes | approve | yes | yes | approve | yes | yes |
| LPB | yes | yes | no | yes | no | no | no |
| NPK | yes | yes | no | yes | yes | yes | no |
| Transfer Gudang | yes | no | no | yes | no | yes | no |
| Stock Opname physical | yes | no | no | yes | no | yes | no |
| Stock Opname valuation/posting | yes | no | no | no | yes | no | no |
| Faktur Pembelian — create | yes | yes | no | no | no | no | no |
| Faktur Pembelian — approve (maker-checker) | yes | no | no | no | no | no | yes |
| Payment | yes | no | yes | no | no | no | no |
| COA/Jurnal/Tax/Period Lock | yes | no | no | no | yes | no | no |
| Dashboard Eksekutif | yes | no | no | no | yes | no | yes |

### 4.2 Produksi

Role `Produksi` adalah role operasional baru untuk tahap 1 `Gudang Produksi`.

Karakteristiknya:

- bisa masuk dashboard produksi
- bisa melihat saldo stok dan mutasi gudang yang di-assign
- bisa membuat dan melihat `NPK` (Pemakaian Barang) dari gudang yang di-assign
- bisa membuat dan melihat `Transfer Gudang` yang melibatkan gudang yang di-assign
- bisa membuat dan submit `Stock Opname` pada gudang yang di-assign
- tidak punya visibilitas finansial
- tidak mengelola master gudang atau pembagian gudang

Pembatasan gudangnya memakai `pembagian_gudangs`.

### 4.3 Pattern policy

Policy sekarang diarahkan mengikuti pola helper private di atas, misalnya:

- private helper siapa yang boleh
- private helper warehouse access
- public method `view/create/update/...` di bawah

Contoh file yang sudah mengikuti pola ini:

- `PemakaianBarangPolicy`
- `TransferGudangPolicy`
- `StockOpnamePolicy`
- beberapa policy accounting/procurement lain

### 4.4 Maker-checker faktur pembelian (2026-09-05)

Supplier invoice sekarang tidak langsung diposting saat dibuat:

- `FakturPembelianController::store()`/`update()` menyimpan invoice dengan `status = PENDING_APPROVAL`, tanpa memanggil `postInvoice()`.
- Hanya role `accounting_manager` yang bisa approve, lewat `POST faktur-pembelian/{id}/approve` (`FakturPembelianController::approve()`, gated `FakturPembelianPolicy::approve()`). Approve inilah yang memicu posting jurnal ke GL.
- Invoice berstatus `PENDING_APPROVAL` tidak bisa dibayar (`FakturPembelianPolicy::pay()`) atau diretur (`ReturPembelianController` menolak LPB dari invoice yang masih pending).
- Cek rekonsiliasi (`AccountingReconciliationService`) dan dashboard Finance mengecualikan invoice `PENDING_APPROVAL` supaya tidak muncul sebagai temuan/aksi palsu.
- Gate lain (COA/jurnal manual, period lock, controlled reversal) **belum** ikut pola maker-checker ini — masih dipegang penuh oleh `accounting`.

## 5. Gudang dan multi-warehouse

### 5.1 Master gudang aktif

Seeder default membuat:

- `GDG-UTAMA` / Gudang Utama
- `GDG-PRODUKSI` / Gudang Produksi
- `GDG-CONSIDER` / Gudang Consider
- `GDG-RUSAK` / Gudang Rusak

### 5.2 Jenis gudang

`Gudang` memakai constant:

- `NORMAL`
- `CONSIDER`
- `RUSAK`

Capability gudang:

- `boleh_penerimaan`
- `boleh_npk`
- `boleh_transfer`
- `boleh_opname`

### 5.3 Pembagian gudang

Tabel `pembagian_gudangs` sekarang benar-benar dipakai untuk pembatasan akses, terutama untuk role `Produksi`.

Field penting:

- `user_id`
- `gudang_id`
- `boleh_menerima`
- `boleh_npk`
- `boleh_transfer`
- `boleh_opname`

Perilaku saat ini:

- `Warehouse` tetap dapat seluruh gudang aktif
- `Produksi` hanya dapat gudang yang di-assign
- filter controller memakai `accessibleGudangIds()`

### 5.4 Gudang Produksi tahap 1

Implementasi tahap 1 saat ini:

- transfer `Gudang Utama -> Gudang Produksi` dilakukan melalui dokumen transfer biasa
- tidak ada jurnal beban saat transfer internal
- NPK dari gudang produksi menjadi titik mulai konsumsi bahan
- opname tetap per gudang

Belum ada WIP manufacturing khusus. Sistem masih memakai pola pemakaian bahan langsung saat NPK keluar.

## 6. Domain flow penting

### 6.1 LPB (Penerimaan Barang)

- LPB mengacu ke PO
- posting LPB menambah `stok_gudangs`
- posting LPB membuat `wms_layer_persediaan`
- jurnal LPB: debit persediaan, kredit GRNI
- form penerimaan menangkap `lot_number` **dan** `expires_at` (sejak 2026-09-09); lot dibuat/dipakai ulang per `(bahan_id, lot_number)`, dan tanggal kedaluwarsa diisikan ke lot lama yang masih kosong. Ini yang mengaktifkan FEFO picking dan seluruh guard stok kedaluwarsa — sebelumnya lot dari penerimaan selalu ber-`expires_at` NULL sehingga FEFO turun jadi FIFO biasa.

### 6.2 NPK (Pemakaian Barang)

- input bisa memakai satuan kecil bila bahan punya konversi
- sistem menyimpan:
  - `jumlah` sebagai jumlah transaksi
  - `jumlah_stok` sebagai jumlah dalam satuan stok utama
  - `satuan_transaksi` sebagai snapshot unit input
- FIFO layer dikonsumsi dari gudang asal (hanya layer `AVAILABLE`, tidak blocked, tidak expired)
- jurnal NPK: debit beban, kredit persediaan

Catatan untuk produksi:

- role `Produksi` hanya dapat membuat NPK dari gudang yang di-assign
- dashboard produksi menampilkan NPK terbaru dari gudang produksinya

### 6.3 Transfer gudang

- transfer memakai `transfer_gudangs`, detail, dan alokasi
- nilai layer dipertahankan
- transfer tidak membentuk jurnal selama perpindahan internal
- role `Produksi` dapat melihat transfer bila gudang asal atau tujuan termasuk assignment-nya

### 6.4 Stock opname

State:

- `DRAFT`
- `SUBMITTED`
- `APPROVED`
- `POSTED`
- `REJECTED`

Role:

- `Warehouse` dan `Produksi`: hitung fisik, update, submit
- `Accounting`: approve, reject, post

### 6.5 Retur Pembelian (termasuk pasca-invoice, 2026-09-05)

- Retur bisa dibuat terhadap LPB yang belum di-invoice (mengurangi GRNI) **atau** yang sudah di-invoice/dibayar.
- Jika invoice masih punya `sisa_tagihan`, nilai retur langsung mengurangi `grand_total`/`sisa_tagihan` invoice tersebut (debit Hutang Usaha, bukan GRNI).
- Jika nilai retur melebihi sisa tagihan (termasuk kasus sudah lunas penuh), kelebihannya dicatat sebagai uang muka supplier (`jenis_selisih=UANG_MUKA_SUPPLIER`, tanpa kas keluar) yang bisa dipakai di pembayaran berikutnya lewat `available-advances/{supplier}`.
- Reversal retur (`InventoryReversalService::reverseReturPembelian()`) memulihkan saldo invoice dan membatalkan uang muka terkait (ditolak bila uang muka sudah terpakai).

### 6.6 Faktur Pembelian — approval, PPh, dan multi-currency ringan

- Alur approval: lihat [4.4](#44-maker-checker-faktur-pembelian-2026-09-05).
- `jenis_pph` (`PPH23`/`PPH22`/`PPH4A2`, nullable) dipilih saat input invoice; masing-masing punya akun hutang sendiri (`HUTANG_PPH23`/`HUTANG_PPH22`/`HUTANG_PPH4A2`). Pembayaran menolak potongan PPh bila invoice tidak punya `jenis_pph`.
- `mata_uang_asing`/`kurs`/`nilai_asing` menyimpan referensi kurs asal invoice supplier luar negeri — **bukan** ledger multi-currency sungguhan; `grand_total` tetap IDR, dihitung dari nilai yang sudah dikonversi saat LPB. Selisih kurs saat pembayaran memakai mekanisme umum `jenis_selisih`/`coa_selisih_id` (akun baru: `4204` Laba Selisih Kurs, `5303` Rugi Selisih Kurs).
- `ppn_impor` diposting sebagai baris nyata ke `AccountingSetting::PPN_IMPOR` (akun aset, `1104`), dikreditkan ke Hutang Usaha bersama `grand_total`.

### 6.7 Depresiasi aset otomatis (2026-09-05)

- `AsetAccountingService::runAutomaticDepreciation()` (`POST aset/penyusutan-otomatis`) menjalankan depresiasi garis lurus untuk semua aset aktif pada satu periode, melewati aset yang sudah didepresiasi di periode itu atau tidak punya umur manfaat.
- Form manual (`StorePenyusutanAsetRequest`) tetap ada untuk penyesuaian, default ke nilai garis lurus yang disarankan bila `amount` dikosongkan.
- Metode declining-balance belum diimplementasikan.

### 6.8 Material Request (dibuka untuk semua role, 2026-09-05)

- Semua role kecuali Purchasing sekarang bisa membuat Material Request (`MaterialRequestPolicy::create()`); Purchasing dan Accounting tetap yang approve.
- `requests.requested_by` mencatat pembuat; non-reviewer hanya melihat request miliknya sendiri di `request.index`.
- Status baru `FULFILLED` otomatis aktif begitu setiap baris `realisasi` mencapai `jumlah_acc` — dihitung terpusat di `MaterialRequestFulfillmentService::syncRealisasi()`, dipanggil dari setiap tempat yang dulu meng-update `realisasi` secara manual (`PesananPembelianController`/`PesananPembelianDetailController`). Status kembali ke `APPROVED` bila baris PO terkait diedit/dihapus di bawah kuota penuh.

## 7. Dashboard

Dashboard aktif:

- `dashboard` umum untuk SuperAdmin/fallback
- `purchasing.dashboard`
- `finance.payment-dashboard`
- `gudang.dashboard`
- `accounting.dashboard`
- `produksi.dashboard`
- `executive-dashboard.index` — khusus Accounting dan Accounting Manager

Dashboard produksi menampilkan:

- jumlah gudang tugas
- NPK hari ini
- transfer aktif
- opname aktif
- transfer terbaru
- NPK terbaru

### 7.1 "All tasks" grid + chart per role (2026-09-05)

Setiap dashboard role (lewat `AuthController::dashboard()`) sekarang juga menampilkan koleksi `tasks` — setiap item outstanding yang relevan untuk role tersebut sebagai kartu klik (`task-grid.blade.php`) plus chart bar/doughnut (`task-chart.blade.php` + `resources/js/task-chart.js`, Alpine component `taskChart`). Ini murni tambahan — stat-card dan daftar top-5/top-8 yang sudah ada sebelumnya tidak dihapus. Setiap kartu tugas mengarah ke halaman index terkait (kebanyakan tidak otomatis ter-filter ke status spesifik itu).

### 7.2 Dashboard Eksekutif (2026-09-06)

`App\Http\Controllers\ExecutiveDashboardController` + `App\Services\ExecutiveDashboardService`, gate `viewExecutiveDashboard` (Accounting + Accounting Manager). Menyajikan 4 chart langsung dari data GL yang sudah posted, tanpa tabel baru:

1. Tren nilai persediaan — saldo GL akhir bulan (6 bulan terakhir) pada setiap akun `KategoriBahan.coa_persediaan_id`.
2. Aging hutang (AP aging) — `sisa_tagihan` dikelompokkan per bucket hari terlambat dari `tgl_deadline_pembayaran`, mengecualikan invoice `VOID`/`PENDING_APPROVAL`.
3. Top 5 supplier berdasarkan total invoice sepanjang waktu.
4. Biaya per kategori bahan tahun berjalan — debit GL pada `KategoriBahan.coa_beban_id`.

Chart memakai komponen `taskChart` yang sama (diperluas dengan tipe `line` dan opsi `beginAtZero`) lewat partial baru `metric-chart.blade.php` (label/data-driven, bukan task-count-driven).

## 8. Database aktif

### 8.1 Tabel utama

| Kelompok | Tabel |
|---|---|
| Auth | `users`, `user_roles`, `personal_access_tokens`, `password_reset_tokens` |
| Master | `suppliers`, `gudangs`, `bahan`, `kategoribahan`, `tipe_pembebanans` |
| Gudang | `stok_gudangs`, `pembagian_gudangs`, `pengaturan_bahan_gudangs`, `mutasi_stoks` |
| Transfer | `transfer_gudangs`, `detail_transfer_gudangs`, `alokasi_transfer_gudangs` |
| Consider | `pemeriksaan_considers`, `detail_pemeriksaan_considers` |
| Procurement | `requests`, `request_details`, `wms_pesanan_pembelian`, `wms_pesanan_pembelian_detail` |
| Receipt | `wms_penerimaan_barang`, `wms_penerimaan_barang_detail` |
| Issue | `wms_pemakaian_barang`, `wms_pemakaian_barang_alokasi_stok`, `wms_layer_persediaan` |
| Retur | `wms_retur_pembelian`, `wms_retur_pembelian_detail` |
| AP | `wms_faktur_pembelian`, `wms_faktur_pembelian_penerimaan`, `wms_pembayaran_faktur` |
| Ledger | `wms_bagan_akun`, `accounting_settings`, `wms_jurnal`, `wms_jurnal_detail` |
| Controls | `accounting_period_locks`, `tax_rates`, `document_sequences` |
| Opname | `wms_stock_opname`, `wms_stock_opname_detail`, `wms_stock_opname_alokasi` |
| Asset | `wms_kategori_asets`, `wms_asets`, `wms_penyusutan_asets`, `wms_pelepasan_asets` |
| Service | `wms_kategori_jasa`, `wms_pesanan_jasa_detail`, `wms_penerimaan_jasa_detail`, `wms_penerimaan_jasa_alokasi` |
| WMS control | `wms_log_audit`, `wms_kunci_operasi_dokumen`, `wms_pembalikan_dokumen`, `wms_lokasi_gudang`, `wms_lot_persediaan`, `wms_serial_persediaan`, `wms_reservasi_persediaan`, `wms_pesanan_pengambilan`, `wms_pesanan_pengambilan_detail`, `wms_pemeriksaan_kualitas`, `wms_pemeriksaan_kualitas_detail`, `wms_biaya_tambahan`, `wms_biaya_tambahan_alokasi`, `wms_saran_pengisian_ulang` |

Penambahan kolom penting sejak refresh sebelumnya (semua di tabel yang sudah ada, bukan tabel baru): `wms_faktur_pembelian.jenis_pph`/`mata_uang_asing`/`kurs`/`nilai_asing`/`ppn_impor`/`approved_by`/`approved_at`, `wms_pembayaran_faktur.potongan_pph` (rename dari `potongan_pph23`)/`uang_muka_sumber_payment_id`/`uang_muka_dipakai`, `wms_retur_pembelian.invoice_id`/`hutang_reduction`/`advance_payment_id`, `requests.requested_by`.

### 8.2 Migration yang relevan dengan role

- `2026_08_07_000001_add_type_to_users_table.php`
- `2026_08_10_000001_create_user_roles_table.php`
- `2026_08_10_000002_add_production_role_to_user_roles.php`
- `2026_09_05_000003_add_accounting_manager_role_to_user_roles.php`

## 9. Seeder

Urutan utama `DatabaseSeeder`:

1. `UserRoleSeeder`
2. `UserSeeder`
3. master tax / COA / accounting setting
4. kategori bahan dan service category
5. warehouse master termasuk `Gudang Produksi`
6. supplier dan bahan demo
7. seeder demo transaksi
8. sinkronisasi multi-warehouse demo data

Pada sinkronisasi multi-warehouse:

- user `Warehouse` mendapat akses ke semua gudang
- user `Produksi` otomatis di-assign ke `Gudang Produksi`

`UserRoleSeeder`/`UserSeeder` sekarang juga membuat role dan akun `accounting_manager` (lihat [3.4](#34-development-accounts)).

## 10. Installation

Development:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Frontend hot reload:

```bash
npm run dev
```

Catatan:

- `.env.example` sudah tersedia di root repo
- `migrate --seed` dipakai untuk development/test
- production sebaiknya memisahkan master seeder dan demo seeder

## 11. Testing

Status terakhir (6 September 2026):

- 79 tests passed, 2.499+ assertions

Perintah:

```bash
php artisan test                              # full suite
php artisan test --filter=TestClassName       # satu class
composer test:case-sensitive                  # cek casing ala Linux CI, dari Windows
```

Cakupan utama:

- accounting control policy, chart of account safety, financial statement (Neraca Saldo/Buku Besar/Laba Rugi/Neraca)
- asset and service policy, depresiasi otomatis
- inventory cost calculator, material unit conversion
- multi-warehouse policy, transfer in-transit, reservation/picking, three-way match, landed cost, reversal, rekonsiliasi
- payment allocation, PPh 23/22/4(2) Final, supplier advance generation & consumption
- stock opname policy, retur pembelian pasca-invoice
- warehouse role policy, feature auth dasar
- role `Produksi`
- maker-checker approval invoice (`accounting_manager`)
- Material Request lintas role + auto-`FULFILLED`
- dashboard per role (task grid + chart), Dashboard Eksekutif
- export Excel (generic table + financial statement)

## 12. Technical debt

Daftar yang masih relevan:

1. belum ada UI admin user dan reset password
2. `DatabaseSeeder` masih mencampur master dan demo
3. belum ada token management UI/API untuk Sanctum
4. beberapa file lama masih punya encoding teks yang jelek
5. CI GitHub Actions sudah tersedia untuk build, fresh migration+seed, dan test
6. belum ada model manufacturing/WIP khusus untuk produksi
7. maker-checker baru mencakup faktur pembelian — COA/jurnal manual, period lock, dan controlled reversal masih dipegang penuh oleh `accounting` tanpa checker kedua
8. "Lacak Pembelian" (laporan disposisi per-pembelian: sisa di gudang vs jadi beban vs jadi selisih opname) sudah didesain (lihat `CLAUDE.md`) tapi belum dibangun

Sudah selesai (dipertahankan sebagai catatan historis, bukan tugas aktif lagi): `.env.example`, NSFP pada faktur pembelian, laporan keuangan statutori, PPh 22/4(2) Final, depresiasi otomatis, field multi-currency ringan + PPN Impor, retur pembelian pasca-invoice, rename sweep baku Indonesia, migrasi Tailwind/daisyUI/Alpine, maker-checker invoice, Material Request lintas role, dashboard task grid + chart, Dashboard Eksekutif, export Excel di seluruh laporan.

## 13. WMS control standard (12 Agustus 2026)

Framework kontrol mencakup:

- `wms_log_audit` untuk histori perubahan dokumen kritis
- `wms_kunci_operasi_dokumen` untuk idempotency
- `wms_pembalikan_dokumen` untuk pembalikan LPB/NPK
- `wms_lokasi_gudang`, `wms_lot_persediaan`, dan `wms_serial_persediaan`
- status layer `AVAILABLE`, `QC_HOLD`, `IN_TRANSIT`, `REVERSED`, dan `TRANSFER_SHORTAGE`
- `wms_pemeriksaan_kualitas` dan putaway
- `wms_biaya_tambahan` beserta alokasi ke layer
- `wms_reservasi_persediaan` dan `wms_pesanan_pengambilan`
- `wms_saran_pengisian_ulang`, lengkap dengan tombol "Buat Request" yang mengubah saran jadi Material Request `PENDING` dan menandai saran itu `REQUESTED` (kolom `material_request_id`)
- three-way matching tersimpan pada faktur pembelian
- peringatan stok kedaluwarsa/mendekati kedaluwarsa muncul sebagai kartu tugas di dashboard gudang dan produksi

Urutan transfer standar adalah `DRAFT -> DIAJUKAN -> DIKIRIM -> DITERIMA`. Urutan receiving adalah `RECEIVED -> QC_COMPLETED/QC_COMPLETED_WITH_HOLD -> PUTAWAY`. Dokumen posted tidak boleh diedit; gunakan workflow reversal atau void sumber transaksi.

## 14. Next development yang masuk akal

Berdasarkan "Known gaps" di `CLAUDE.md` (paling relevan per 2026-09-06), yang paling natural dikerjakan berikutnya:

1. Sisi outbound-to-customer (Sales Order, Surat Jalan/Delivery Order, retur penjualan, AR/piutang) — belum ada sama sekali, butuh sesi desain/scoping sendiri sebelum dibangun.
2. Barcode/RF scanning sungguhan untuk putaway/picking (saat ini murni form web manual).
3. Wave/batch picking, cross-docking, slotting/ABC analysis.
4. Model BOM/manufacturing order/WIP untuk `Gudang Produksi` (saat ini masih direct-consumption via NPK).
5. Layer integrasi eksternal (API/EDI ke supplier, marketplace, ERP lain) di luar base Sanctum.
6. Kitting/bundling.
7. Perluasan pola maker-checker ke COA/jurnal manual, period lock, dan controlled reversal, bila kebutuhan "dual control" itu masih dianggap relevan.
8. "Lacak Pembelian" — laporan tiga-arah (sisa stok vs beban NPK vs selisih opname) per LPB/invoice; data sudah ada dan saling terhubung, tinggal disatukan dalam satu view.

## 15. Source of truth

Jika dokumentasi dan kode berbeda, gunakan urutan berikut:

1. migration untuk schema
2. policy untuk otorisasi
3. controller + form request untuk workflow
4. service untuk aturan stok dan akuntansi
5. model untuk helper dan relation
6. test untuk behavior guarantee
