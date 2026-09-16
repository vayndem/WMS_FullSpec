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

Tabel `pembagian_gudangs` dipakai untuk pembatasan akses gudang, sejak 2026-09-13 untuk role `Warehouse` **dan** `Produksi`.

Field penting:

- `user_id`
- `gudang_id`
- `boleh_menerima`
- `boleh_npk`
- `boleh_transfer`
- `boleh_opname`

Perilaku saat ini (berubah 2026-09-13):

- `SuperAdmin` mendapat seluruh gudang aktif
- `Warehouse` **dan** `Produksi` hanya mendapat gudang yang di-assign
- operator tanpa assignment tidak mendapat gudang sama sekali
- filter controller memakai `accessibleGudangIds()`

Konteks perubahan: gudang perusahaan tersebar di beberapa lokasi dan tiap lokasi punya adminnya sendiri, sehingga operator lokasi A tidak boleh menerima/mengeluarkan/menghitung stok di lokasi B. Migrasi `2026_09_13_000004` memberi seluruh user `Warehouse` yang sudah ada akses ke semua gudang aktif supaya tidak ada yang kehilangan akses saat rilis; penyempitan dilakukan per orang lewat menu Pembagian Gudang.

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

### 6.7a Jasa: kapitalisasi, gate pass, barang titipan, tukar tambah (2026-09-14)

Empat hal yang sebelumnya tidak bisa dicatat sistem:

- **Kapitalisasi jasa ke aset.** `wms_kategori_jasa.perlakuan` = `BEBAN` (default) atau `KAPITALISASI`. Kategori kapitalisasi mewajibkan `wms_pesanan_jasa_detail.aset_id`; saat invoice diposting, nilainya didebit ke akun aset (`KategoriAset.akun_aset_id`), bukan ke `expense_coa_id`, lalu menambah `acquisition_cost`/`book_value` dan boleh memperpanjang `useful_life_months` lewat `tambahan_umur_bulan`. Jejaknya di `wms_kapitalisasi_aset` dengan unique `(sumber_type, sumber_id)` sehingga posting ulang invoice tidak menggelembungkan aset.
- **Gate pass** (`wms_pengeluaran_barang`): barang kita keluar ke vendor lalu kembali. Alur `DRAFT -> DI_VENDOR -> SEBAGIAN_KEMBALI -> SELESAI`. **Tidak membuat jurnal** — kepemilikan tidak berpindah, aset tetap `ACTIVE` dan tetap disusutkan. Mengeluarkan *persediaan* (bukan aset) untuk subkontrak sengaja tidak didukung di sini karena akan menyentuh layer FIFO; pakai Transfer Gudang ke gudang khusus.
- **Barang titipan vendor** (`wms_barang_titipan`): unit pinjaman selama barang kita diperbaiki. Nol jurnal, nol layer persediaan, nol aset — barang ini milik vendor. `nilai_taksiran` hanya memo.
- **Tukar tambah** (`disposal_type = TRADE_IN`): pelepasan aset via barter. DPP = nilai wajar barang yang diserahkan (UU PPN Pasal 1A), PPN Keluaran ke COA `2109`, dan nilai DPP+PPN menjadi **uang muka supplier** lewat mekanisme yang sudah ada sehingga bisa mengurangi tagihan pembelian penggantinya. Laba/rugi diukur terhadap DPP saja, bukan DPP+PPN. Belum menghasilkan e-Faktur — bagian itu memang butuh sisi penjualan.

### 6.7 Depresiasi aset otomatis (2026-09-05)

- `AsetAccountingService::runAutomaticDepreciation()` (`POST aset/penyusutan-otomatis`) menjalankan depresiasi garis lurus untuk semua aset aktif pada satu periode, melewati aset yang sudah didepresiasi di periode itu atau tidak punya umur manfaat.
- Form manual (`StorePenyusutanAsetRequest`) tetap ada untuk penyesuaian, default ke nilai garis lurus yang disarankan bila `amount` dikosongkan.
- Metode declining-balance belum diimplementasikan.

### 6.8 Material Request (dibuka untuk semua role, 2026-09-05)

- Semua role kecuali Purchasing sekarang bisa membuat Material Request (`MaterialRequestPolicy::create()`); Purchasing dan Accounting tetap yang approve.
- `requests.requested_by` mencatat pembuat; non-reviewer hanya melihat request miliknya sendiri di `request.index`.
- Status baru `FULFILLED` otomatis aktif begitu setiap baris `realisasi` mencapai `jumlah_acc` — dihitung terpusat di `MaterialRequestFulfillmentService::syncRealisasi()`, dipanggil dari setiap tempat yang dulu meng-update `realisasi` secara manual (`PesananPembelianController`/`PesananPembelianDetailController`). Status kembali ke `APPROVED` bila baris PO terkait diedit/dihapus di bawah kuota penuh.

### 6.9 Penjualan dan piutang (sejak 2026-09-16)

- `PesananPenjualan` menampung pesanan pelanggan beserta DPP/PPN; statusnya `OPEN` sampai seluruh baris terkirim.
- `SuratJalan` adalah tempat stok dan GL benar-benar bergerak: posting mengonsumsi layer lewat `StokGudangService::ambilLayer()` (jalur FIFO yang sama dengan NPK dan transfer), mencatat tiap tarikan di `wms_surat_jalan_alokasi`, lalu memposting **debit Beban Pokok Penjualan / kredit Persediaan dengan dimensi gudang**. Surat jalan draft tidak menyentuh apa pun.
- `FakturPenjualan` memposting **debit Piutang Usaha / kredit Penjualan + PPN Keluaran**, dan menolak posting bila ber-PPN tanpa NSFP.
- `PenerimaanPembayaran` mengurangi piutang; kelebihan bayar **ditolak**, bukan diam-diam jadi uang muka pelanggan.
- `ReturPenjualan` mengembalikan stok sebagai **layer baru** (`source_type='RETUR_PENJUALAN'`), bukan membatalkan konsumsi layer lama — layer lama bisa sudah terpakai dokumen lain dan menghidupkannya kembali akan merusak urutan FIFO.

### 6.10 Produksi dan barang dalam proses (sejak 2026-09-16)

- `DataPesanan` (perintah kerja) selalu lahir dari satu baris `PesananPenjualan`; produk yang dibuat mengikuti baris itu.
- NPK yang ditandai ke perintah kerja **mendebit Barang Dalam Proses (`1303`)**, bukan akun beban. NPK tanpa tanda perilakunya tidak berubah.
- Saldo WIP **diturunkan dari baris `wms_data_pesanan_biaya`**, bukan disimpan sebagai kolom berjalan.
- Saat dilaporkan selesai, harga pokok per unit dikunci dan perintah kerja **berhenti menerima biaya**. Konsekuensinya: NPK yang perintah kerjanya sudah disegel tidak bisa dibalik.
- Keluaran tidak dibukukan sebagai stok barang jadi. Tiap pengiriman melepas WIP secara proporsional ke Beban Pokok Penjualan; perintah kerja tutup saat terkirim penuh.

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
| Sales | `pelanggans`, `wms_pesanan_penjualan`, `wms_pesanan_penjualan_detail`, `wms_surat_jalan`, `wms_surat_jalan_detail`, `wms_surat_jalan_alokasi` |
| AR | `wms_faktur_penjualan`, `wms_faktur_penjualan_detail`, `wms_penerimaan_pembayaran`, `wms_retur_penjualan`, `wms_retur_penjualan_detail` |
| Produksi | `wms_data_pesanan`, `wms_data_pesanan_biaya` |
| Pelaporan | `wms_catatan_laporan_keuangan`, `wms_permintaan_persetujuan` |
| Pajak | `wms_perhitungan_pajak_penghasilan`, `wms_kompensasi_kerugian_fiskal` |
| Kitting | `wms_kit`, `wms_kit_komponen`, `wms_perakitan_kit`, `wms_perakitan_kit_detail` |
| Gelombang | `wms_gelombang_pengambilan` |
| Jasa lanjutan | `wms_kapitalisasi_aset`, `wms_pengeluaran_barang`, `wms_pengeluaran_barang_detail`, `wms_barang_titipan` |
| Lampiran | `wms_lampiran_dokumen` |
| WMS control | `wms_log_audit`, `wms_kunci_operasi_dokumen`, `wms_pembalikan_dokumen`, `wms_lokasi_gudang`, `wms_lot_persediaan`, `wms_serial_persediaan`, `wms_reservasi_persediaan`, `wms_pesanan_pengambilan`, `wms_pesanan_pengambilan_detail`, `wms_pemeriksaan_kualitas`, `wms_pemeriksaan_kualitas_detail`, `wms_biaya_tambahan`, `wms_biaya_tambahan_alokasi`, `wms_saran_pengisian_ulang` |

Penambahan kolom penting sejak refresh sebelumnya (semua di tabel yang sudah ada, bukan tabel baru): `wms_faktur_pembelian.jenis_pph`/`mata_uang_asing`/`kurs`/`nilai_asing`/`ppn_impor`/`approved_by`/`approved_at`, `wms_pembayaran_faktur.potongan_pph` (rename dari `potongan_pph23`)/`uang_muka_sumber_payment_id`/`uang_muka_dipakai`, `wms_retur_pembelian.invoice_id`/`hutang_reduction`/`advance_payment_id`, `requests.requested_by`.

Kolom lintas-domain yang ditambahkan 2026-09-16 dan gampang terlewat: **`wms_jurnal_detail.gudang_id`** (dimensi gudang; sudah di-backfill ke jurnal lama lewat migration `2026_09_16_000004`), `wms_pemakaian_barang.data_pesanan_id`, `wms_surat_jalan_detail.data_pesanan_id`, dan `wms_penerimaan_jasa_alokasi.data_pesanan_id`. Ketiga kolom `data_pesanan_id` itulah yang mengubah `kode_datapesanan` yang dulu sekadar teks bebas menjadi kaitan nyata ke perintah kerja.

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

Status terakhir (16 September 2026):

- 202 tests passed, 4.201 assertions
- architecture convention test: 6 passed
- diverifikasi berturut-turut setelah `migrate:fresh --seed`, bukan sekali jalan

Perintah:

```bash
php artisan test                              # full suite
php artisan test --filter=TestClassName       # satu class
composer test:case-sensitive                  # cek casing ala Linux CI, dari Windows
```

Cakupan utama:

- accounting control policy, chart of account safety, financial statement (Neraca Saldo/Buku Besar/Laba Rugi/Neraca/Arus Kas/Perubahan Ekuitas/CALK — Arus Kas dan Perubahan Ekuitas punya tie-out sendiri ke buku besar dan ke neraca)
- maker-checker jurnal manual, pembalikan dokumen, dan perubahan COA/mapping; pemohon tidak bisa menyetujui permintaannya sendiri
- dimensi gudang pada baris jurnal: nilai persediaan per gudang, dan transfer internal yang netral secara total
- lacak pembelian per penerimaan dan kartu skor supplier
- penjualan end-to-end: pesanan, surat jalan (FIFO + HPP), faktur (piutang + PPN Keluaran), penerimaan pembayaran, retur penjualan
- produksi: perintah kerja, akumulasi WIP dari NPK, penguncian harga pokok saat selesai, pelepasan WIP proporsional saat kirim, dan invarian WIP vs buku besar
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

Daftar yang masih relevan per 2026-09-16:

1. `DatabaseSeeder` masih mencampur master dan demo — jangan dijalankan penuh ke produksi
2. belum ada token management UI/API untuk Sanctum — catatan: **layer integrasi API/EDI sendiri sudah dicoret dari perencanaan**, jadi ini hanya soal mengelola token internal
3. beberapa file lama masih punya encoding teks yang jelek
4. daftar modul penjualan memakai tabel Blade berpaginasi, bukan Yajra + `wmsDataTable()` seperti modul lain — penyimpangan yang disengaja dan dicatat; konversinya mekanis
5. `UpdateAccountingMappingRequest` memvalidasi semua `KategoriBahan` dengan aturan kategori barang, padahal dua kategori jasa bawaan seeder tidak memenuhinya — form mapping tidak bisa disimpan untuk daftar kategori lengkap
6. stok dalam perjalanan masih menempel ke gudang asal sampai diterima, karena jurnal transfer baru terbentuk saat penerimaan — memperbaikinya butuh akun "Persediaan Dalam Perjalanan", sebuah keputusan akuntansi
7. perintah kerja yang dibatalkan setelah menyerap biaya belum punya jalur hapus buku — butuh keputusan akun kerugian
8. `plafon_kredit` pelanggan dicatat dan ditampilkan tapi belum ditegakkan — memblokir pesanan karena plafon adalah keputusan kebijakan
9. suite tes berjalan dekat plafon memori PHP; `phpunit.xml` menyetel `memory_limit=512M` agar deterministik. Kalau export terus bertambah besar, alihkan tes export ke koleksi `Excel::fake()` daripada mengunduh sungguhan

**Bukan lagi utang teknis** (dipertahankan sebagai catatan historis): UI manajemen user + reset password + audit login, `.env.example`, NSFP pada faktur pembelian, lima laporan PSAK 1 lengkap termasuk Arus Kas, Perubahan Ekuitas dan CALK, PPh 22/4(2) Final, depresiasi otomatis garis lurus & saldo menurun, rekonsiliasi fiskal + PPh Badan + pajak tangguhan + kompensasi kerugian, field multi-currency ringan + PPN Impor, retur pembelian pasca-invoice, rename sweep baku Indonesia, migrasi Tailwind/daisyUI/Alpine, Material Request lintas role, dashboard task grid + chart + pengingat + progres, Dashboard Eksekutif, export Excel di seluruh laporan, scheduler + notifikasi terantre, lampiran dokumen, antrean kerja operator, cycle count ABC, wave picking, kitting, seluruh celah jasa (kapitalisasi, gate pass, barang titipan, tukar tambah), Lacak Pembelian, Kartu Skor Supplier, **maker-checker yang kini mencakup jurnal manual/pembalikan dokumen/perubahan COA**, **penilaian persediaan per gudang lewat dimensi gudang pada baris jurnal**, **sisi AR/penjualan lengkap**, dan **perintah kerja produksi dengan barang dalam proses**.

CI GitHub Actions sudah berjalan untuk build, fresh migration+seed, dan test — itu infrastruktur yang ada, bukan utang.

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
- `wms_gelombang_pengambilan` untuk wave/batch picking, dengan `gelombang_id` + `urutan_dalam_gelombang` pada `wms_pesanan_pengambilan`
- `wms_kit`, `wms_kit_komponen`, `wms_perakitan_kit` + detail untuk kitting/bundling
- klasifikasi ABC pada `pengaturan_bahan_gudangs` (`kelas_abc`, `nilai_pemakaian`, `kontribusi_kumulatif`) sebagai dasar cycle count dan slotting
- dimensi dan kapasitas volume pada `wms_lokasi_gudang`, plus `volume_cm3` pada `bahans`
- `wms_stock_opname.jenis` (`PENUH`/`SIKLUS`) + `kelas_abc` untuk cycle count parsial
- `wms_pengeluaran_barang` + detail (gate pass barang keluar ke vendor) dan `wms_barang_titipan` (unit pinjaman vendor) — keduanya catatan kustodian tanpa jurnal
- `wms_kapitalisasi_aset` sebagai jejak audit jasa yang dikapitalisasi ke aset, unique per sumber agar tidak dobel

Urutan transfer standar adalah `DRAFT -> DIAJUKAN -> DIKIRIM -> DITERIMA`. Urutan receiving adalah `RECEIVED -> QC_COMPLETED/QC_COMPLETED_WITH_HOLD -> PUTAWAY`. Dokumen posted tidak boleh diedit; gunakan workflow reversal atau void sumber transaksi.

Sejak 2026-09-13, QC boleh dijalankan **dua kali**: pemeriksaan awal dari `RECEIVED`, dan pemeriksaan lanjutan dari `QC_COMPLETED_WITH_HOLD` yang wajib menyebutkan baris mana yang diperiksa ulang. Baris yang stoknya masih QC hold ditolak dan diarahkan ke Pemeriksaan Consider, bukan ke QC ulang.

Gelombang pengambilan memakai urutan `DIRENCANAKAN -> DIRILIS -> SELESAI` (atau `DIBATALKAN`, yang melepas kembali perintah pengambilannya). Perakitan kit memakai `DRAFT -> POSTED`, dengan jenis `RAKIT` atau `URAI`.

## 14. Next development yang masuk akal

Berdasarkan "Open-items index" di `CLAUDE.md` (paling relevan per 2026-09-16). **Pekerjaan kecil yang tinggal dikerjakan sudah habis** — sisanya domain baru, format eksternal, atau keputusan akuntansi.

1. **BOM sebagai standar** — perintah kerja dan WIP sudah ada sejak 2026-09-16, tapi costing-nya berdasarkan pemakaian aktual, bukan terhadap daftar komponen yang diharapkan. Tanpa BOM belum ada selisih pemakaian material. Ini kelanjutan alami dari produksi.
2. **Penyerapan tenaga kerja dan overhead ke WIP** — butuh tarif per jam, pencatatan jam kerja, dan dasar alokasi; ketiganya belum ada dan masing-masing keputusan kebijakan.
3. **Hapus buku perintah kerja yang dibatalkan di tengah jalan** — saat ini perintah kerja hanya bisa dibatalkan selama biayanya nol. Kalau pelanggan batal setelah material masuk, WIP-nya perlu dihapusbukukan ke akun kerugian yang belum ditentukan.
4. **Format DJP** — e-Faktur CSV, SPT Masa PPN 1111, e-Bupot. Sejak sisi penjualan ada (2026-09-16) datanya lengkap termasuk PPN Keluaran dan NSFP, jadi tinggal pekerjaan format. Formatnya ditentukan eksternal dan sensitif versi: **cek spesifikasi DJP yang berlaku sebelum membangun, jangan mengira-ngira layout-nya**.
5. **Cross-docking** — tidak lagi terhalang sejak sisi outbound ada; sekarang sekadar pekerjaan yang belum dibangun.
6. **Multi-currency penuh** — revaluasi dan laba/rugi selisih kurs per PSAK 10; membuka ini berarti layer FIFO berdenominasi valas.
7. **Akun "Persediaan Dalam Perjalanan"** — hanya jika stok dalam perjalanan tidak boleh lagi menempel ke gudang asal. Jurnal transfer sekarang terbentuk saat penerimaan, jadi ini keputusan akuntansi, bukan bug.
8. **Uang muka pelanggan dan penegakan plafon kredit** — `pelanggans.plafon_kredit` sudah dicatat dan ditampilkan tapi sengaja belum ditegakkan; memblokir pesanan karena plafon adalah keputusan kebijakan.
9. **Barcode/QR** untuk putaway/picking — **ditunda pemilik produk pada 2026-09-13**. Catatan desain yang harus diingat: QR-nya **per bundling/kit, bukan per barang**.

Sudah selesai dan tidak perlu direncanakan lagi — **2026-09-13**: antrean kerja operator, re-inspeksi QC, cycle count berbasis ABC, auto-reservasi saat approval Material Request, dimensi/volume bin + slotting ABC, pembatasan gudang per operator, wave/batch picking, kitting/bundling. **2026-09-14**: kapitalisasi jasa ke aset (PSAK 16), gate pass barang keluar ke vendor, barang titipan vendor, tukar tambah aset, dan Laporan Perubahan Ekuitas. **2026-09-16**: Lacak Pembelian, Kartu Skor Supplier, CALK (PSAK 1 lengkap lima laporan), perluasan maker-checker ke jurnal manual/pembalikan dokumen/perubahan COA, **dimensi gudang pada baris jurnal** sehingga nilai persediaan per gudang akhirnya bisa ditarik dari buku besar, **seluruh sisi penjualan** (pelanggan, pesanan penjualan, surat jalan dengan konsumsi FIFO + jurnal HPP, faktur penjualan dengan piutang + PPN Keluaran, penerimaan pembayaran, dan retur penjualan) — sejak ini Laba Rugi punya sisi pendapatan dan Neraca punya piutang — serta **perintah kerja produksi dengan Barang Dalam Proses** (job costing aktual untuk produksi pesanan pelanggan).

### Dicoret dari perencanaan

**Layer integrasi eksternal (API/EDI ke supplier, marketplace, atau ERP lain) dicoret pemilik produk pada 2026-09-16.** Dicatat di sini, bukan sekadar dihapus, supaya tidak ditemukan ulang sebagai celah oleh pembacaan kode berikutnya lalu dimasukkan lagi. Tidak adanya API publik adalah keadaan yang memang dikehendaki. Base Sanctum yang sudah ada tetap dibiarkan.

### Catatan terbuka yang ditemukan 2026-09-16

`UpdateAccountingMappingRequest` memvalidasi **semua** `KategoriBahan` dengan aturan kategori barang, padahal dua kategori jasa bawaan seeder tidak memenuhinya: "Jasa Operasional" memetakan `coa_persediaan_id` ke akun BEBAN, dan "Jasa Produksi" memetakan `coa_beban_id` ke akun ASET. Akibatnya form mapping tidak bisa disimpan untuk daftar kategori lengkap. Ini bukan bug baru — sudah ada sebelum pekerjaan maker-checker — dan perlu keputusan: kategori jasa dikecualikan dari aturan mapping barang, atau diberi akun yang sesuai.

## 15. Source of truth

Jika dokumentasi dan kode berbeda, gunakan urutan berikut:

1. migration untuk schema
2. policy untuk otorisasi
3. controller + form request untuk workflow
4. service untuk aturan stok dan akuntansi
5. model untuk helper dan relation
6. test untuk behavior guarantee
