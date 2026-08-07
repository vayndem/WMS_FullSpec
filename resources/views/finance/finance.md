# Dokumentasi Fitur: Pembayaran Supplier (Bahan Baku & Penolong)

> **Controller:** `PembayaranController`  
> **View:** `finance.index`  
> **Prefix Route:** `/pembayaran-nonkertas`  
> **Last Updated:** 2025

---

## Daftar Isi
1. [Gambaran Umum](#1-gambaran-umum)
2. [Alur & Endpoint](#2-alur--endpoint)
3. [Detail Setiap Endpoint](#3-detail-setiap-endpoint)
4. [Logika Bisnis Penting](#4-logika-bisnis-penting)
5. [Struktur Database](#5-struktur-database)
6. [Jenis Pembayaran](#6-jenis-pembayaran)
7. [Edge Cases & Validasi](#7-edge-cases--validasi)

---

## 1. Gambaran Umum

Fitur ini digunakan oleh tim **Finance** untuk mencatat, memantau, dan mengelola pembayaran kepada supplier bahan baku & penolong (non-kertas).

**Yang bisa dilakukan:**
- Melihat daftar invoice supplier dengan filter status (Belum Lunas / Lunas)
- Expand baris tabel untuk melihat komposisi LPB (Laporan Penerimaan Barang) per invoice
- Membuka workspace pembayaran: menambah transaksi pembayaran secara *staged* (belum tersimpan) lalu menyimpan semuanya sekaligus
- Menghapus transaksi pembayaran yang sudah tersimpan
- Export laporan mutu supplier ke Excel berdasarkan rentang tanggal faktur

---

## 2. Alur & Endpoint

### Peta Alur Lengkap

```
User buka halaman /pembayaran
        │
        ▼
[GET] /pembayaran  →  tampil tabel kosong (DataTables server-side)
        │
        ▼
[GET] /pembayaran-nonkertas/data  ←── DataTables request (ajax)
        │   filter: status_filter = "belum_lunas" | "lunas"
        │   DB: invoice_lpb JOIN suppliers
        ▼
Tabel tampil (no_invoice, supplier, grand_total, sisa_tagihan, status, dll)

═══════════════════════════════════════════════════════
[User klik ikon ▶ expand baris]
        │
        ▼
[GET] /pembayaran-nonkertas/{no_invoice}/full-lpb-detail
        │   DB: admin_lpb → admin_lpb_detail JOIN bahan
        ▼
Tampil sub-tabel: ID LPB, No. PO, No. Surat Jalan, Nama Bahan, Jumlah, Lot Number

═══════════════════════════════════════════════════════
[User klik tombol "Bayar" / "Lihat"]
        │
        ▼
[GET] /pembayaran-nonkertas/{no_invoice}/detail
        │   DB: invoice_lpb JOIN suppliers  →  data invoice
        │   DB: pembayaran_transaksi_detail  →  riwayat pembayaran sebelumnya
        ▼
Modal terbuka:
  - Info invoice (no_invoice, deadline, grand_total, sub_total, ppn, diskon, ongkir)
  - Sisa tagihan dihitung live di frontend
  - Tabel riwayat transaksi (sudah tersimpan) + staged (belum disimpan)

[User tambah transaksi di modal]
        │   validasi frontend: jumlah > 0, tidak melebihi sisa tagihan
        ▼
Masuk array stagedPayments (hanya di memori browser, belum ke DB)

[User klik "Validasi & Simpan"]
        │
        ▼
[POST] /pembayaran-nonkertas/store
        │   validasi Laravel: no_invoice exists, transactions array min 1
        │   DB INSERT: pembayaran_transaksi_detail (per transaksi)
        │   DB: hitung ulang total_pembayaran dari semua transaksi
        │   DB UPDATE: invoice_lpb (total_pembayaran, status_pembayaran, status)
        ▼
Tabel utama di-reload, modal ditutup

═══════════════════════════════════════════════════════
[User klik ✕ hapus transaksi yang sudah tersimpan]
        │
        ▼
[DELETE] /pembayaran-nonkertas/detail/{id}
        │   DB DELETE: pembayaran_transaksi_detail WHERE id = {id}
        │   DB: hitung ulang total dari sisa transaksi (pakai kolom total_transaksi_pengurang_hutang)
        │   DB UPDATE: invoice_lpb (total_pembayaran, status_pembayaran, status)
        ▼
Riwayat di-refresh, sisa tagihan diupdate

═══════════════════════════════════════════════════════
[User klik "Laporan Mutu"]
        │   input: dari tanggal, sampai tanggal
        ▼
[GET] /pembayaran/export-mutu?dari=...&sampai=...
        │   generate file Excel via PembayaranSuplierExport
        ▼
Download file: Laporan_Mutu_Supplier_nonkertas{dari}_sd_{sampai}.xlsx
```

---

## 3. Detail Setiap Endpoint

### `GET /pembayaran`
- **Controller:** `index()`
- **Auth:** wajib login (`auth.custom` middleware)
- **Yang dilakukan:** Render view `finance.index`, inject `user` dari session
- **Catatan:** Tabel diisi via AJAX, bukan dari sini

---

### `GET /pembayaran-nonkertas/data`
- **Controller:** `data(Request $request)`
- **Query utama:** `invoice_lpb JOIN suppliers ON kode_supplier = suppliers.id`
- **Filter:** 
  - `status_filter=lunas` → `WHERE invoice_lpb.status = 2`
  - selain itu → `WHERE invoice_lpb.status < 2`
- **Kolom subquery:**
  - `list_lpb` — GROUP_CONCAT dari `admin_lpb` berdasarkan `no_invoice`
  - `jenis_transaksi` — ambil `jenis` dari `inv_po` JOIN `admin_lpb` (LIMIT 1)
  - `tanggal_pembayaran_terakhir` — MAX dari `pembayaran_transaksi_detail`
  - `tanggal_bayar_awal` — MIN dari `pembayaran_transaksi_detail`
- **Kolom yang di-render:**
  - `tgl_deadline_pembayaran` — merah + bold jika sudah lewat dan `sisa_tagihan > 0`
  - `status_pembayaran` — badge `Lunas` (hijau) / `Dibayar Sebagian` (kuning) / `Belum Dibayar` (merah)
  - `jenis_transaksi_label` — badge `PO` (biru, jenis=0) / `PP` (info, jenis=1) / `Lainnya`
  - `action` — tombol **Bayar** (biru) jika `status < 2`, **Lihat** (abu) jika `status = 2`

---

### `GET /pembayaran-nonkertas/{no_invoice}/full-lpb-detail`
- **Controller:** `getInvoiceCompositionJson($no_invoice)`
- **Tabel yang diakses:**
  1. `admin_lpb` — ambil semua LPB berdasarkan `no_invoice`, order by `id_lpb ASC`
  2. `admin_lpb_detail JOIN bahan` — per `id_lpb`, ambil `nama bahan`, `jumlah_barang_diterima`, `lot_number`
- **Response:**
  ```json
  {
    "data": [
      {
        "header": { "id_lpb": "...", "no_po": "...", "no_sj": "...", "tanggal": "..." },
        "details": [
          { "nm_bahan": "...", "jumlah_barang_diterima": 100, "lot_number": "LOT-001" }
        ]
      }
    ]
  }
  ```
- **Jika tidak ada LPB:** `data: []`

---

### `GET /pembayaran-nonkertas/{no_invoice}/detail`
- **Controller:** `getDetailJson($no_invoice)`
- **Tabel yang diakses:**
  1. `invoice_lpb JOIN suppliers` — data invoice + nama supplier
  2. `pembayaran_transaksi_detail WHERE id_invoice_lpb = invoice.id` — riwayat pembayaran, order `tanggal_pembayaran DESC`
- **Response:**
  ```json
  {
    "invoice": { "no_invoice": "...", "grand_total": 5000000, "sisa_tagihan": 2000000, ... },
    "riwayat": [ { "id": 1, "tanggal_pembayaran": "2025-01-10", "jumlah_pembayaran": 3000000, ... } ]
  }
  ```
- **Jika invoice tidak ditemukan:** HTTP 404

---

### `POST /pembayaran-nonkertas/store`
- **Controller:** `storePembayaran(Request $request)`
- **Validasi Laravel:**
  ```
  no_invoice       → required, string, exists di invoice_lpb
  transactions     → required, array, min:1
  transactions.*.jenis_bayar  → required, integer
  transactions.*.tanggal      → required, date
  transactions.*.pembayaran   → required, numeric, min:0
  ```
- **Alur di dalam transaksi DB (`DB::beginTransaction`):**
  1. Ambil data invoice dari `invoice_lpb` berdasarkan `no_invoice`
  2. Loop setiap item `transactions`:
     - Buat record baru di `pembayaran_transaksi_detail`
     - Kolom jumlah diisi ke kolom yang sesuai berdasarkan `jenis_bayar` (lihat bagian [Jenis Pembayaran](#6-jenis-pembayaran))
     - Semua kolom jumlah lain diisi `0`
  3. Setelah semua insert selesai, hitung ulang total dari DB:
     ```sql
     SELECT SUM(jumlah_pembayaran + potongan_materai + biaya_transfer_bank + selisih_bayar + potongan_pph23)
     FROM pembayaran_transaksi_detail
     WHERE id_invoice_lpb = {invoice.id}
     ```
  4. Hitung `sisa_tagihan = grand_total - total_pembayaran`
  5. Tentukan status:
     - `sisa_tagihan <= 0.01` → `Lunas`, `status = 2`
     - `total_pembayaran > 0` → `Dibayar Sebagian`, `status = 1`
     - selain itu → `Belum Dibayar`, `status = 0`
  6. UPDATE `invoice_lpb` dengan `total_pembayaran`, `status_pembayaran`, `status`
- **Jika error:** `DB::rollBack()`, log error, return HTTP 500

---

### `DELETE /pembayaran-nonkertas/detail/{id}`
- **Controller:** `destroyDetail($id)`
- **Alur di dalam transaksi DB:**
  1. Ambil record dari `pembayaran_transaksi_detail` berdasarkan `id` → ambil `id_invoice_lpb`
  2. Jika tidak ditemukan → HTTP 404
  3. DELETE record tersebut
  4. Hitung ulang total dari sisa transaksi menggunakan kolom `total_transaksi_pengurang_hutang`:
     ```sql
     SELECT SUM(total_transaksi_pengurang_hutang)
     FROM pembayaran_transaksi_detail
     WHERE id_invoice_lpb = {id_invoice_lpb}
     ```
     > ⚠️ **Catatan:** Kolom yang dipakai di sini (`total_transaksi_pengurang_hutang`) berbeda dari yang dipakai di `storePembayaran` (sum manual per kolom). Pastikan kolom ini selalu sinkron / ada computed column / trigger di DB.
  5. Hitung ulang `sisa_tagihan` dan update `invoice_lpb` (status, status_pembayaran, total_pembayaran)
- **Jika error:** `DB::rollBack()`, return HTTP 500

---

### `GET /pembayaran/export-mutu`
- **Controller:** `exportmutu(Request $request)`
- **Parameter query:** `dari` (tanggal awal), `sampai` (tanggal akhir)
- **Default jika kosong:** awal bulan s/d hari ini
- **Output:** File Excel `Laporan_Mutu_Supplier_nonkertas{dari}_sd_{sampai}.xlsx` via `PembayaranSuplierExport`

---

## 4. Logika Bisnis Penting

### Perhitungan Sisa Tagihan (Frontend)
Sisa tagihan tidak diambil mentah dari DB, tapi dihitung ulang di frontend setiap kali ada perubahan:

```
sisa = grand_total - (total_riwayat_tersimpan + total_staged_belum_simpan)
```

- Untuk riwayat tersimpan: cari nilai > 0 dari kolom `jumlah_pembayaran`, `potongan_materai`, `potongan_pph23`, `biaya_transfer_bank`, `selisih_bayar` (ambil yang pertama non-zero)
- Untuk staged: ambil langsung dari field `pembayaran`
- Jika sisa `<= 1` → field sisa tagihan berubah warna jadi **hijau** (lunas)

### Validasi Overpayment (Frontend)
Sebelum menambah transaksi ke staged list, sistem mengecek:
```
jika jumlah_input > sisa_tagihan_saat_ini → tolak, tampilkan error SweetAlert
```
Ini hanya validasi di sisi client. Tidak ada validasi overpayment di server-side.

### Status Invoice
| `status` (int) | `status_pembayaran` (string) | Kondisi |
|---|---|---|
| `0` | `Belum Dibayar` | Belum ada pembayaran sama sekali |
| `1` | `Dibayar Sebagian` | Ada pembayaran tapi sisa > 0.01 |
| `2` | `Lunas` | Sisa tagihan <= 0.01 |

Invoice dengan `status = 2` tidak bisa ditambah transaksi baru (form disembunyikan di frontend).

### Staged Payments (Pola Kerja Modal)
Modal menggunakan pola *stage-then-commit*:
1. User tambah transaksi → masuk array `stagedPayments` di memori browser
2. User bisa hapus transaksi staged sebelum disimpan
3. Klik "Validasi & Simpan" → semua staged dikirim sekaligus ke server dalam satu request
4. Jika salah satu gagal di server → semua di-rollback (DB transaction)

---

## 5. Struktur Database

### Tabel Utama yang Terlibat

#### `invoice_lpb`
| Kolom | Keterangan |
|---|---|
| `id` | Primary key |
| `no_invoice` | Nomor invoice unik (dipakai sebagai identifier di URL) |
| `kode_supplier` | Foreign key ke `suppliers.id` |
| `grand_total` | Total tagihan keseluruhan |
| `sub_total` | Sub total sebelum pajak/diskon |
| `ppn` | Pajak pertambahan nilai |
| `diskon` | Diskon |
| `ongkir` | Biaya pengiriman |
| `total_pembayaran` | Akumulasi pembayaran yang sudah masuk |
| `sisa_tagihan` | `grand_total - total_pembayaran` (disimpan di DB) |
| `status` | `0` = belum bayar, `1` = sebagian, `2` = lunas |
| `status_pembayaran` | String label status |
| `tgl_deadline_pembayaran` | Jatuh tempo |
| `tanggal` | Tanggal nota/faktur |

#### `pembayaran_transaksi_detail`
| Kolom | Keterangan |
|---|---|
| `id` | Primary key |
| `id_invoice_lpb` | Foreign key ke `invoice_lpb.id` |
| `tanggal_pembayaran` | Tanggal transaksi |
| `metode_pembayaran` | Label jenis bayar (string) |
| `jumlah_pembayaran` | Diisi jika jenis = 0 (Pembayaran Supplier) |
| `potongan_materai` | Diisi jika jenis = 1 |
| `biaya_transfer_bank` | Diisi jika jenis = 2 |
| `selisih_bayar` | Diisi jika jenis = 3 |
| `potongan_pph23` | Diisi jika jenis = 4 |
| `total_transaksi_pengurang_hutang` | Kolom computed/virtual — dipakai saat `destroyDetail` |
| `id_user_finance` | ID user yang input (dari session) |

#### `admin_lpb`
| Kolom | Keterangan |
|---|---|
| `id_lpb` | ID LPB |
| `no_invoice` | Relasi ke `invoice_lpb.no_invoice` |
| `no_po` | Nomor Purchase Order |
| `no_sj` | Nomor Surat Jalan |

#### `admin_lpb_detail`
| Kolom | Keterangan |
|---|---|
| `id_lpb` | Relasi ke `admin_lpb.id_lpb` |
| `id_bahan` | Foreign key ke `bahan.id` |
| `jumlah_barang_diterima` | Jumlah barang yang diterima |
| `lot_number` | Nomor lot (bisa null, ditampilkan `-`) |

---

## 6. Jenis Pembayaran

Setiap transaksi hanya mengisi **satu** kolom di `pembayaran_transaksi_detail`, sisanya `0`.

| Kode (`jenis_bayar`) | Label | Kolom yang Diisi |
|---|---|---|
| `0` | Pembayaran Supplier | `jumlah_pembayaran` |
| `1` | Potongan Materai | `potongan_materai` |
| `2` | Biaya Transfer Bank | `biaya_transfer_bank` |
| `3` | Selisih Bayar | `selisih_bayar` |
| `4` | PPh 23 | `potongan_pph23` |

---

## 7. Edge Cases & Validasi

### ✅ Sudah Ditangani
| Skenario | Penanganan |
|---|---|
| Invoice tidak ditemukan saat buka detail | HTTP 404 |
| Staged payment melebihi sisa tagihan | Ditolak di frontend (SweetAlert error) |
| Jumlah pembayaran `<= 0` | Ditolak di frontend |
| Tidak ada staged payment saat klik simpan | Ditolak di frontend (SweetAlert info) |
| Error DB saat simpan | `DB::rollBack()` + log + HTTP 500 |
| Error DB saat hapus | `DB::rollBack()` + HTTP 500 |
| Invoice `status = 2` (lunas) | Form tambah transaksi disembunyikan di frontend |
| Deadline lewat & masih ada sisa | Tanggal tampil merah di tabel |

### ⚠️ Perlu Diperhatikan
| Skenario | Catatan |
|---|---|
| **Overpayment tidak dicek di server** | Validasi overpayment hanya di frontend. Jika request dikirim langsung ke endpoint (bypass UI), server tidak menolak jika jumlah > sisa tagihan |
| **Kolom berbeda untuk hitung total** | `storePembayaran` SUM manual per kolom; `destroyDetail` pakai `total_transaksi_pengurang_hutang`. Jika kolom ini tidak sinkron (bukan computed column / tidak ada trigger), angka bisa berbeda |
| **`no_invoice` di URL bisa mengandung karakter apapun** | Route sudah pakai `.where('no_invoice', '.*')` untuk handle format seperti `INV/2025/001` |
| **Session user** | `id_user_finance` diambil dari `session('user_data')['id']`. Jika session kosong/expired saat simpan, field ini null |
| **Export mutu** | Logika filter data ada di class `PembayaranSuplierExport`, tidak ada di controller ini |
