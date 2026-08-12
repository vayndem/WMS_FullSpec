# WMS FullSpec

Warehouse, procurement, finance, dan accounting dalam satu alur yang terkontrol.

- Laravel 10
- PHP 8.1+
- MySQL
- Bootstrap 5
- 51 tests passed, 1.173 assertions

Dokumen ini ditujukan untuk pembaca bisnis dan developer yang ingin cepat memahami sistem. Referensi teknis yang lebih detail ada di [feed.MD](feed.MD).

## Gambaran umum

WMS FullSpec menghubungkan proses:

1. Request barang
2. Purchase Order
3. LPB / penerimaan
4. Multi gudang
5. NPK / pemakaian
6. Invoice supplier
7. Pembayaran
8. Jurnal akuntansi

Dokumen operasional tidak berhenti sebagai catatan administratif. LPB, NPK, invoice, pembayaran, dan stock opname ikut memengaruhi stok, nilai persediaan, hutang, dan general ledger.

## Standar arsitektur

- Route dipisahkan per domain pada `routes/auth.php`, `warehouse.php`, `procurement.php`, `finance.php`, `accounting.php`, dan `assets-and-services.php`.
- Controller menangani HTTP orchestration; validasi wajib berada di `app/Http/Requests` dan proses lintas model berada di `app/Services`.
- Policy model ditemukan otomatis melalui konvensi Laravel. Gate hanya dipakai untuk capability lintas model.
- Nama class dan file mengikuti PSR-4 dengan casing yang identik.
- Nama tabel memakai plural `snake_case`; foreign key baru memakai pola `<model>_id`.
- Kuantitas memakai `DECIMAL(18,6)`, unit cost `DECIMAL(18,4)`, uang `DECIMAL(18,2)`, dan tarif `DECIMAL(8,4)`.
- Status database selalu machine code uppercase. Label bahasa Indonesia hanya dibentuk pada presentation layer.
- Dokumen posted tidak diedit atau dihapus; koreksi memakai void/reversal beserta audit metadata dan jurnal pembalik.

Architecture test menjaga aturan tersebut agar nama legacy, floating point untuk data bisnis, inline validation, route monolitik, dan class/file mismatch tidak masuk kembali.

## Lifecycle dokumen

| Dokumen | Lifecycle standar |
|---|---|
| Material Request | `PENDING -> APPROVED / REJECTED` |
| Purchase Order | `OPEN -> CLOSED` |
| LPB/BAP | `DRAFT -> POSTED -> REVERSED` atau `CANCELLED` |
| NPK | `DRAFT -> POSTED -> REVERSED` |
| Invoice supplier | `UNPAID -> PARTIALLY_PAID -> PAID` atau `VOID` |
| Pembayaran invoice | `POSTED -> VOID` |
| Transfer gudang | `DRAFT -> DIAJUKAN -> DIKIRIM -> DITERIMA` atau `DIBATALKAN` |
| Stock opname | `DRAFT -> SUBMITTED -> APPROVED -> POSTED` atau `REJECTED` |

## Modul aktif

| Area | Modul | Fungsi utama |
|---|---|---|
| Master | Supplier | Data pemasok, termin, kontak |
| Master | Bahan | Kategori, gudang, satuan utama/kecil, planning |
| Procurement | Request | Pengajuan kebutuhan dan approval |
| Procurement | PO Barang | Pembelian barang, realisasi request, histori revisi |
| Warehouse | LPB | Penerimaan barang terhadap PO |
| Warehouse | NPK | Pemakaian barang dan pengurangan layer FIFO |
| Warehouse | Multi Gudang | Saldo per gudang, transfer, mutasi, planning, Consider, Rusak |
| Warehouse | Stock Opname | Hitung fisik, approval accounting, koreksi stok |
| Finance | Invoice LPB | Penggabungan LPB/BAP menjadi tagihan supplier |
| Finance | Pembayaran | Pembayaran parsial/penuh, PPh, biaya bank, materai |
| Accounting | COA dan Mapping | Mapping persediaan, beban, GRNI, akun global |
| Accounting | Jurnal | Jurnal otomatis, jurnal manual, reversal |
| Accounting | Kontrol | Kunci periode, tarif pajak, rekonsiliasi |
| Asset | Asset Tetap | Perolehan, penyusutan, pelepasan |
| Jasa | PO Jasa dan BAP | Jasa operasional/produksi dan progress BAP |
| WMS Control | Traceability | Bin, lot, serial, expiry, block/release |
| WMS Control | Warehouse Execution | QC, putaway, reservation, FEFO/FIFO picking |
| WMS Control | Financial Control | Three-way match, landed cost, controlled reversal |
| WMS Control | Planning | Reorder point, safety stock, replenishment suggestion |

## Alur bisnis singkat

### Barang

1. User membuat request.
2. Purchasing atau Accounting approve.
3. Purchasing membuat PO.
4. Gudang melakukan LPB saat barang datang.
5. Sistem membentuk stok dan layer persediaan.
6. Invoice supplier dicatat.
7. Finance melakukan pembayaran.
8. Accounting menerima jurnal otomatis dari transaksi yang relevan.

### Pemakaian barang

1. User membuat NPK.
2. Jika bahan punya satuan kecil, input transaksi memakai satuan kecil.
3. Sistem mengonversi ke satuan stok utama.
4. Layer FIFO dikonsumsi.
5. Stok berkurang dan jurnal pemakaian terbentuk saat status keluar.

### Multi gudang

Jenis gudang aktif:

- `NORMAL`: gudang operasional biasa
- `CONSIDER`: karantina / status belum pasti
- `RUSAK`: barang rusak final

Aturan penting:

- LPB masuk ke gudang tujuan PO.
- NPK mengurangi stok dari gudang asal yang dipilih.
- Transfer antar gudang mempertahankan nilai layer.
- Gudang Consider diproses lewat pemeriksaan Consider.
- Gudang Rusak bersifat final.

Transfer baru memakai standar `DRAFT -> DIAJUKAN -> DIKIRIM -> DITERIMA`. Saat dikirim, barang berada pada layer `IN_TRANSIT`; saldo global perusahaan tidak berubah. Selisih penerimaan dipertahankan sebagai exception rekonsiliasi.

## Standar transaksi inventory

- Dokumen posted bersifat immutable.
- Koreksi LPB dan NPK dilakukan melalui controlled reversal, bukan edit/delete.
- Setiap reversal memulihkan saldo, FIFO, komitmen PO, dan jurnal dalam satu transaksi.
- Operasi transfer memakai idempotency key untuk menolak request ganda.
- NPK hanya mengonsumsi layer `AVAILABLE`, tidak blocked, dan belum expired.
- Picking mengurutkan lot dengan FEFO lalu FIFO.
- Rekonsiliasi wajib memenuhi: master quantity = gudang + transit, saldo gudang = layer, dan nilai layer = GL persediaan.
- Invoice supplier menjalani three-way matching PO-LPB-Invoice sebelum jurnal hutang diposting.
- Landed cost dikapitalisasi ke layer aktif dan diposting seimbang ke GL.

Pusat operasional fitur tersebut tersedia pada menu **Multi Gudang -> WMS Control Center**.

## Gudang Produksi

Mulai tahap 1, sistem sudah memiliki `Gudang Produksi`.

Konsep operasional saat ini:

- `Gudang Utama -> Gudang Produksi` dilakukan lewat `Transfer Gudang`
- Perpindahan ini aman sebagai perpindahan internal stok
- `NPK` dari `Gudang Produksi` menjadi titik mulai pemakaian bahan
- `Stock Opname` tetap per gudang, termasuk untuk gudang produksi

Seeder default sekarang membuat:

- `Gudang Utama`
- `Gudang Produksi`
- `Gudang Consider`
- `Gudang Rusak`

## Role dan akses

Role disimpan pada tabel `user_roles` dan direferensikan oleh kolom `users.type`.

| Type | Role | Akses utama |
|---:|---|---|
| 0 | SuperAdmin | Akses penuh tanpa pengecualian |
| 1 | Purchasing | Supplier, request, PO, LPB, invoice, jasa, visibilitas finansial operasional |
| 2 | Finance | Melihat invoice dan mencatat/void pembayaran supplier |
| 3 | Warehouse | Operasional gudang lintas gudang aktif, tanpa visibilitas finansial sensitif |
| 4 | Accounting | COA, mapping, jurnal, pajak, period lock, rekonsiliasi, approval/posting opname, aset |
| 5 | Produksi | Transfer, NPK, saldo stok, mutasi, dan opname sesuai assignment gudang |

Catatan:

- `SuperAdmin` melewati policy melalui `Gate::before`.
- `Warehouse` memiliki cakupan operasional gudang yang luas.
- `Produksi` dibatasi ke gudang yang di-assign di `pembagian_gudangs`.
- `Produksi` saat ini difokuskan ke `Gudang Produksi`.

## Akun development

Seeder default membuat akun berikut:

| Role | Email | Password | Type |
|---|---|---|---:|
| SuperAdmin | `superadmin@wms.local` | `Wms12345!` | 0 |
| Purchasing | `purchasing@wms.local` | `Wms12345!` | 1 |
| Finance | `finance@wms.local` | `Wms12345!` | 2 |
| Warehouse | `warehouse@wms.local` | `Wms12345!` | 3 |
| Accounting | `accounting@wms.local` | `Wms12345!` | 4 |
| Produksi | `produksi@wms.local` | `Wms12345!` | 5 |

Kredensial ini hanya untuk development/demo.

## Menjalankan secara lokal

### Prasyarat

- PHP 8.1 atau lebih baru
- Composer
- Node.js dan npm
- MySQL

### Instalasi

```bash
composer install
npm install
```

Buat file `.env` lokal sesuai konfigurasi Laravel dan isi koneksi database.

Lalu jalankan:

```bash
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Untuk frontend development:

```bash
npm run dev
```

Catatan:

- `php artisan migrate --seed` membuat master data sekaligus data demo
- gunakan itu untuk development/test
- untuk production, hindari `DatabaseSeeder` penuh

## Pengujian

Hasil terakhir pada 12 Agustus 2026:

- 51 tests passed
- 1.173 assertions

Jalankan test dengan:

```bash
php artisan test
```

Suite saat ini mencakup:

- policy role
- multi-warehouse policy
- stock opname policy
- keamanan mapping COA
- payment allocation
- konversi satuan
- inventory cost calculation
- asset dan service policy
- basic feature auth
- WMS control, transfer in-transit, reservation/picking, three-way match, landed cost, reversal, dan rekonsiliasi

## Catatan deployment

- arahkan document root ke `public/`
- pastikan `storage/` dan `bootstrap/cache/` writable
- gunakan `APP_DEBUG=false` di production
- jalankan `php artisan optimize` setelah konfigurasi production siap
- jangan commit `.env`, password, token, atau secret

## Dokumentasi lanjutan

Lihat [feed.MD](feed.MD) untuk:

- arsitektur aplikasi
- struktur database
- policy dan otorisasi
- flow inventory dan akuntansi
- seeder dan akun demo
- technical debt dan rekomendasi pengembangan
