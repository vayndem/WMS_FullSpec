# WMS FullSpec Technical Reference

Dokumen ini adalah referensi teknis untuk developer, QA, DBA, dan maintainer. Isinya disesuaikan dengan codebase aktif per 10 Agustus 2026.

Untuk gambaran produk dan alur bisnis yang lebih singkat, lihat [README.md](README.md).

## 1. Technical baseline

| Komponen | Implementasi |
|---|---|
| Backend | PHP 8.1+, Laravel 10 |
| Database | MySQL |
| Web auth | Laravel session auth |
| API auth | Laravel Sanctum |
| UI | Blade, Bootstrap 5, jQuery, DataTables |
| Build | Vite 5 |
| Report | DomPDF, TCPDF, Laravel Excel |
| Test | PHPUnit 10 |

Arsitektur aplikasi berbentuk modular monolith Laravel.

## 2. Lapisan aplikasi

| Lapisan | Lokasi | Tanggung jawab |
|---|---|---|
| Routes | `routes/web.php`, `routes/api.php` | endpoint dan middleware |
| Controllers | `app/Http/Controllers` | orkestrasi use case |
| Form Requests | `app/Http/Requests` | validasi dan sebagian authorization |
| Policies | `app/Policies` | role dan state guard |
| Services | `app/Services` | FIFO, accounting, numbering, opname, asset |
| Models | `app/Models` | relasi, cast, helper domain |
| Migrations | `database/migrations` | schema dan constraint |
| Seeders | `database/seeders` | role, user, master data, demo data |
| Views | `resources/views` | halaman dan template report |

Perubahan stok dan akuntansi penting dibungkus `DB::transaction()`. Operasi yang rawan race condition memakai `lockForUpdate()`.

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

Helper penting pada `User`:

- `isSuperAdmin()`
- `isPurchasing()`
- `isFinance()`
- `isWarehouse()`
- `isAccounting()`
- `isProduction()`
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

## 4. Authorization

### 4.1 Gambaran role

| Capability | 0 | 1 | 2 | 3 | 4 | 5 |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Bypass policy | yes | no | no | no | no | no |
| Supplier | yes | yes | no | no | no | no |
| Request | yes | yes | no | yes | yes | no |
| LPB | yes | yes | no | yes | no | no |
| NPK | yes | yes | no | yes | yes | yes |
| Transfer Gudang | yes | no | no | yes | no | yes |
| Stock Opname physical | yes | no | no | yes | no | yes |
| Stock Opname valuation/posting | yes | no | no | no | yes | no |
| Payment | yes | no | yes | no | no | no |
| COA/Jurnal/Tax/Period Lock | yes | no | no | no | yes | no |

### 4.2 Produksi

Role `Produksi` adalah role operasional baru untuk tahap 1 `Gudang Produksi`.

Karakteristiknya:

- bisa masuk dashboard produksi
- bisa melihat saldo stok dan mutasi gudang yang di-assign
- bisa membuat dan melihat `NPK` dari gudang yang di-assign
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

- `NpkPolicy`
- `TransferGudangPolicy`
- `StockOpnamePolicy`
- beberapa policy accounting/procurement lain

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

### 6.1 LPB

- LPB mengacu ke PO
- posting LPB menambah `stok_gudangs`
- posting LPB membuat `inventory_layers`
- jurnal LPB: debit persediaan, kredit GRNI

### 6.2 NPK

- input bisa memakai satuan kecil bila bahan punya konversi
- sistem menyimpan:
  - `jumlah` sebagai jumlah transaksi
  - `jumlah_stok` sebagai jumlah dalam satuan stok utama
  - `satuan_transaksi` sebagai snapshot unit input
- FIFO layer dikonsumsi dari gudang asal
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

## 7. Dashboard

Dashboard aktif:

- `dashboard` umum untuk SuperAdmin/fallback
- `purchasing.dashboard`
- `finance.payment-dashboard`
- `gudang.dashboard`
- `accouting.dashboard`
- `produksi.dashboard`

Dashboard produksi menampilkan:

- jumlah gudang tugas
- NPK hari ini
- transfer aktif
- opname aktif
- transfer terbaru
- NPK terbaru

## 8. Database aktif

### 8.1 Tabel utama

| Kelompok | Tabel |
|---|---|
| Auth | `users`, `user_roles`, `personal_access_tokens`, `password_reset_tokens` |
| Master | `suppliers`, `gudangs`, `bahan`, `kategoribahan`, `tipe_pembebanans` |
| Gudang | `stok_gudangs`, `pembagian_gudangs`, `pengaturan_bahan_gudangs`, `mutasi_stoks` |
| Transfer | `transfer_gudangs`, `detail_transfer_gudangs`, `alokasi_transfer_gudangs` |
| Consider | `pemeriksaan_considers`, `detail_pemeriksaan_considers` |
| Procurement | `requests`, `request_details`, `pembelians`, `pembelian_details` |
| Receipt | `lpbs`, `lpbdetails` |
| Issue | `npks`, `npk_stock_allocations`, `inventory_layers` |
| AP | `invoicelpbs`, `invoice_lpb_receipts`, `invoicelpbdetails` |
| Ledger | `chart_of_accounts`, `accounting_settings`, `jurnals`, `jurnal_details` |
| Controls | `accounting_period_locks`, `tax_rates`, `document_sequences` |
| Opname | `stock_opnames`, `stock_opname_details`, `stock_opname_allocations` |
| Asset | `asset_categories`, `assets`, `asset_depreciations`, `asset_disposals` |
| Service | `service_categories`, `service_po_details`, `service_bap_details`, `service_bap_allocations` |

### 8.2 Migration yang relevan dengan role

- `2026_08_07_000001_add_type_to_users_table.php`
- `2026_08_10_000001_create_user_roles_table.php`
- `2026_08_10_000002_add_production_role_to_user_roles.php`

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

- repository belum menyediakan `.env.example`
- `migrate --seed` dipakai untuk development/test
- production sebaiknya memisahkan master seeder dan demo seeder

## 11. Testing

Status terakhir:

- 35 tests passed
- 125 assertions

Perintah:

```bash
php artisan test
```

Cakupan utama:

- accounting control policy
- asset and service policy
- chart of account safety
- inventory cost calculator
- material unit conversion
- multi-warehouse policy
- payment allocation
- stock opname policy
- warehouse role policy
- feature auth dasar

Tambahan test terbaru mencakup role `Produksi`.

## 12. Technical debt

Daftar yang masih relevan:

1. belum ada `.env.example`
2. belum ada UI admin user dan reset password
3. `DatabaseSeeder` masih mencampur master dan demo
4. belum ada integration test workflow penuh request -> PO -> LPB -> invoice -> payment
5. belum ada token management UI/API untuk Sanctum
6. beberapa file lama masih punya encoding teks yang jelek
7. naming `accouting` masih typo historis pada sebagian view/path
8. belum ada CI/CD definition
9. belum ada model manufacturing/WIP khusus untuk produksi

## 13. Next development yang masuk akal

Setelah tahap 1 `Produksi`, pengembangan berikut yang paling natural:

1. validasi transfer masuk khusus gudang produksi
2. pembatasan list gudang lebih konsisten di seluruh form dan report
3. flow WIP / barang dalam proses bila produksi ingin dihitung sebagai aset sementara
4. approval transfer antar gudang yang lebih ketat
5. dashboard dan laporan khusus produksi per gudang
6. integration test untuk role `Produksi` pada controller

## 14. Source of truth

Jika dokumentasi dan kode berbeda, gunakan urutan berikut:

1. migration untuk schema
2. policy untuk otorisasi
3. controller + form request untuk workflow
4. service untuk aturan stok dan akuntansi
5. model untuk helper dan relation
6. test untuk behavior guarantee
