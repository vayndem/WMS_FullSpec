# Dokumentasi Fitur: Laporan Penerimaan Barang (LPB) & Manajemen Gudang

> **Controller:** `GudangController`  
> **View:** `gudang.lpb`  
> **Prefix Route:** `/gudang`, `/lpb`  
> **Last Updated:** 2025

---

## Daftar Isi
1. [Gambaran Umum](#1-gambaran-umum)
2. [Alur & Endpoint — LPB Utama](#2-alur--endpoint--lpb-utama)
3. [Alur & Endpoint — Tambah LPB (Modal)](#3-alur--endpoint--tambah-lpb-modal)
4. [Alur & Endpoint — Stok Opname](#4-alur--endpoint--stok-opname)
5. [Alur & Endpoint — Stok Adjustment](#5-alur--endpoint--stok-adjustment)
6. [Alur & Endpoint — Stok Awal](#6-alur--endpoint--stok-awal)
7. [Detail Setiap Endpoint](#7-detail-setiap-endpoint)
8. [Logika Bisnis Penting](#8-logika-bisnis-penting)
9. [Struktur Database](#9-struktur-database)
10. [Edge Cases & Validasi](#10-edge-cases--validasi)

---

## 1. Gambaran Umum

Fitur ini digunakan oleh tim **Gudang** untuk mencatat penerimaan barang dari supplier, mengelola stok, melakukan opname, dan mengajukan penyesuaian stok (adjustment).

**Yang bisa dilakukan:**
- Melihat daftar LPB dengan filter bulan/tahun dan jenis (LPBPO / LPBPP)
- Expand baris untuk melihat detail barang per LPB (inline editable)
- Tambah LPB baru: pilih supplier → pilih PO → pilih barang → input jumlah & lot number → simpan
- Cetak LPB ke PDF (menggunakan TCPDF, otomatis mengunci LPB setelah cetak)
- Edit inline: tanggal dan no. surat jalan di tabel utama; jumlah diterima dan lot number di detail
- Hapus detail barang dari LPB (dengan pembalikan stok)
- Export data LPB ke Excel
- Manajemen Stok Opname (buat sesi, isi stok real, finalisasi → update stok)
- Manajemen Stok Awal (input stok awal satu kali)
- Pengajuan & persetujuan Stok Adjustment

---

## 2. Alur & Endpoint — LPB Utama

```
User buka /gudang/lpb
        │
        ▼
[GET] /gudang/lpb  →  render view gudang.lpb, inject session user
        │
        ▼
[GET] /gudang/lpbData  ←── DataTables AJAX request
        │   params: filterMonthYear, searchTerm, filterLpbType, start, length, order
        │   DB: admin_lpb
        │       LEFT JOIN inv_po ON no_po
        │       LEFT JOIN suppliers ON id_suplier
        │   WHERE admin_lpb.flag = 0  (LPB normal, bukan stok awal)
        ▼
Tabel tampil (id_lpb, tanggal, supplier, no_po, no_sj, tombol cetak/kunci)

═══════════════════════════════════════════════════════
[User klik ikon ▶ expand baris]
        │
        ▼
Sub-tabel DataTables dibuat secara inline:
[GET] /lpb/detail?id_lpb={id_lpb}
        │   DB: admin_lpb_detail
        │       JOIN bahan ON id_bahan
        │       JOIN kategori_bahan ON kategori
        │   WHERE id_lpb = {id_lpb}
        │   Kolom editable: jumlah_barang_diterima (column=9), lot_number (column=10)
        │   Jika LPB kunci=1 → semua kolom readonly, tombol "Terkunci"
        ▼
Tampil detail barang: nama, keterangan, kategori, satuan, jumlah diterima, lot number

═══════════════════════════════════════════════════════
[User klik edit inline tanggal / no_sj di tabel utama]
        │   User mengubah nilai → blur/Enter → SweetAlert konfirmasi
        ▼
[POST] /gudang/updateLpbData
        │   validate: id_lpb exists, type in [tanggal, no_sj], value required
        │   DB READ: admin_lpb WHERE id_lpb = {id_lpb}
        │   DB UPDATE: admin_lpb SET tanggal/no_sj = {value}
        │   Jika kunci!=0 OR ulang!=0 OR cetakan!=0 → SET cetak_ulang=1
        ▼
Nilai di sel diperbarui langsung (tanpa reload tabel)

═══════════════════════════════════════════════════════
[User klik edit inline jumlah/lot di sub-tabel detail]
        │   User mengubah nilai → blur/Enter → SweetAlert konfirmasi
        ▼
[POST] /lpb/detail/update
        │   validate: id exists di admin_lpb_detail, column in [9, 10]
        │
        │   Jika column = 9 (jumlah_barang_diterima):
        │     DB READ: admin_lpb_detail → ambil oldValue & id_bahan
        │     DB UPDATE: admin_lpb_detail SET jumlah_barang_diterima = newValue
        │     difference = newValue - oldValue
        │     DB READ: bahan → stok_onpurchase
        │     Jika (stok_onpurchase - difference) < 0 → difference dikap di stok_onpurchase
        │     DB UPDATE: bahan SET
        │       stok_onhand  = stok_onhand  + difference
        │       stok_onpurchase = stok_onpurchase - difference
        │
        │   Jika column = 10 (lot_number):
        │     DB UPDATE: admin_lpb_detail SET lot_number = newValue
        ▼
DB transaction → commit/rollback

═══════════════════════════════════════════════════════
[User klik Hapus di sub-tabel detail]
        │   SweetAlert konfirmasi
        ▼
[DELETE] /lpb/detail/{id}
        │   validate: id exists di admin_lpb_detail
        │   DB READ: admin_lpb_detail → ambil id_bahan & jumlah_barang_diterima
        │   DB DELETE: admin_lpb_detail WHERE id = {id}
        │   DB UPDATE: bahan SET
        │     stok_onhand     = stok_onhand     - jumlah_barang_diterima   (balik masuk)
        │     stok_onpurchase = stok_onpurchase + jumlah_barang_diterima   (kembalikan ke on-purchase)
        ▼
Tabel utama di-reload

═══════════════════════════════════════════════════════
[User klik Cetak]
        ▼
[POST] /cetak-lpb
        │   DB READ: admin_lpb JOIN inv_po JOIN suppliers → data header LPB
        │   DB READ: admin_lpb_detail JOIN bahan JOIN kategori_bahan → detail barang
        │
        │   Update status cetak di admin_lpb:
        │     Jika cetak_ulang = 0 → SET kunci=1, ulang = ulang+1
        │     Jika cetak_ulang = 1 → SET kunci=1, cetakan = cetakan+1, ulang=0
        │
        │   Generate PDF via TCPDF (MyPDF):
        │     Header: LPB No, PO No, Supplier, No SJ, Tanggal
        │     Body: tabel barang (nama, kategori, jumlah+satuan)
        │     Footer: "Cetakan ke X dan duplikasi ke Y"
        ▼
Response: PDF binary (Content-Type: application/pdf)
Browser buka PDF di tab baru, tabel di-reload

═══════════════════════════════════════════════════════
[User klik Export ke Excel]
        ▼
[GET] /gudang/lpb-export?filterMonthYear=...&filterLpbType=...&searchTerm=...
        │   Langsung trigger download via LpbExport class (Maatwebsite Excel)
        ▼
Download file lpb_data.xlsx
```

---

## 3. Alur & Endpoint — Tambah LPB (Modal)

```
User klik tombol "Tambah LPB"
        │
        ▼
Modal tambahLpbModal terbuka → event shown.bs.modal
        │
        ▼
[GET] /getSuppliers
        │   DB: suppliers JOIN inv_po ON id
        │   WHERE inv_po.status != 2 (PO tidak selesai)
        │         AND inv_po.jenis {= atau !=} 2 tergantung user.type
        │         (user.type=5 → jenis=2, selainnya → jenis!=2)
        ▼
Tabel supplier tampil (id_supplier, nama)

═══════════════════════════════════════════════════════
[User klik baris supplier]
        │
        ▼
[GET] /gudang/no-po?id_supplier={id}
        │   DB: inv_po
        │   WHERE status != 2 AND id_suplier = {id}
        │   GROUP BY no_po, tanggal, no_order
        │   ORDER BY tanggal DESC
        ▼
Tabel PO tampil (no_po, tanggal, no_order)

═══════════════════════════════════════════════════════
[User klik baris PO]
        │
        ▼
[GET] /getDetailByNoPo?no_po={no_po}
        │   DB: inv_podetail
        │       JOIN bahan ON id_bahan
        │       JOIN kategori_bahan ON kategori
        │   WHERE no_po = {no_po}
        │   SELECT: no_po, nama_barang, harga, jumlah, satuan, kategori, id_bahan, katid
        ▼
Tabel detail barang tampil (untuk dipilih)

═══════════════════════════════════════════════════════
[User klik baris barang di tabel detail]
        │   Simpan data barang ke selectedDetailData (memori browser)
        ▼
Modal inputJumlahModal terbuka
        │   User isi: jumlah diterima, lot number (opsional), bisa scan barcode
        │
        │   Validasi frontend:
        │     - jumlah > 0
        │     - total (keranjang + input) tidak melebihi jumlah PO
        │       KECUALI kategori = PACKING (katid=12 atau nama 'PACKING') → boleh berlebih
        │
        │   Jika barang+lot sudah ada di keranjang → jumlah ditambahkan (merge)
        │   Jika belum ada → tambah baris baru ke tabel keranjang (table_detailpilih)
        ▼
Barang masuk ke array selectedLpbItems (memori browser)

═══════════════════════════════════════════════════════
[User isi tanggal diterima & no. surat jalan]
        │
        │   Validasi no. surat jalan (debounce 500ms):
        ▼
[POST] /gudang/check-surat-jalan
        │   DB: admin_lpb WHERE no_sj = {nomor}
        │   Jika ada → tampil pesan merah "sudah dipakai"
        │   Jika tidak ada → tampil pesan hijau "belum dipakai"

═══════════════════════════════════════════════════════
[User klik Simpan LPB]
        │   validasi frontend: tanggal wajib, no_sj wajib, keranjang tidak kosong
        ▼
[POST] /gudang/save-lpb
        │   DB READ: inv_po WHERE no_po = {no_po} → ambil jenis PO
        │
        │   Tentukan prefix berdasarkan jenis PO:
        │     jenis=0 → prefix='LPBPO', jenisLpb=1, sjPrefix='MOPO'
        │     jenis=1 → prefix='LPBPP', jenisLpb=2, sjPrefix='MOPP'
        │     jenis=2 → prefix='LPBMO', jenisLpb=3, sjPrefix='NONE'
        │
        │   Generate ID LPB:
        │     yearLpb = 2 digit tahun dari tanggalBarangDiterima
        │     Ambil LPB terakhir: WHERE id_lpb LIKE '{prefix}{yearLpb}%' ORDER BY id_lpb DESC
        │     lastNumber = 3 digit terakhir dari id_lpb terakhir (atau 000 jika belum ada)
        │     idLpb = prefix + yearLpb + str_pad(lastNumber+1, 3, '0')
        │     Contoh: LPBPO25001
        │
        │   Tentukan no. surat jalan:
        │     Jika input kosong → auto-generate: sjPrefix + yearLpb + counter (4 digit)
        │     Jika diisi → cek duplikat di admin_lpb; jika duplikat → return error
        │
        │   DB INSERT: admin_lpb (id_lpb, tanggal, no_po, no_sj, id_user, jenis_lpb)
        │
        │   Untuk setiap barang di details:
        │     DB INSERT: admin_lpb_detail (id_lpb, id_bahan, id_kategori, jumlah, lot_number, harga)
        │     DB UPDATE: bahan SET
        │       stok_onhand    = stok_onhand    + jumlah_diterima
        │       stok_onpurchase = GREATEST(0, stok_onpurchase - jumlah_diterima)
        │     DB UPDATE: inv_podetail SET diterima = diterima + jumlah_diterima
        │       WHERE no_po = {no_po} AND id_bahan = {id_bahan}
        │
        │   DB UPDATE: inv_po SET status=1 WHERE no_po = {no_po}
        ▼
DB transaction → commit/rollback
Modal ditutup, tabel LPB di-reload
```

---

## 4. Alur & Endpoint — Stok Opname

```
[GET] /stokopname  →  render view gudang.stokopname

[GET] /gudang/generateStockOpnameCode
        │   Format kode: STO-NK{YY}{NN}
        │   Ambil opname terakhir tahun ini → increment 2 digit terakhir
        ▼
Return: { kode: "STO-NK2501" }

[GET] /gudang/getStockOpnameData  (DataTables)
        │   DB: stok_opname ORDER BY updated_at DESC
        │   Aksi: Edit & Hapus (hanya user.type=14 & flag=0), Export Excel selalu tampil
        ▼
Tabel opname (kode, tanggal, flag/status)

[POST] /gudang/storeStockOpname
        │   validate: kode unique di stok_opname, tanggal required
        │   Hanya user.type=14 (Gudang) yang boleh buat
        │
        │   DB INSERT: stok_opname (kode, tanggal, user_id, flag=0)
        │
        │   Snapshot stok semua bahan (jenis IN [0,1], kategori != 17):
        │     Harga diambil dari rata-rata 5 harga LPB terakhir per bahan
        │     Jika tidak ada LPB → pakai bahan.harga
        │     stok_sistem = stok_real = stok_onhand saat ini
        │     selisih = 0, kerugian = 0
        │   DB INSERT: stok_opname_detail (bulk insert semua bahan)
        ▼
Sesi opname dibuat, semua bahan sudah ter-snapshot

[GET] /gudang/getDetailStockOpname?kode={kode}  (DataTables)
        │   DB: stok_opname_detail JOIN bahan JOIN kategori_bahan
        │   WHERE kode = {kode}
        │   Kolom: nama_bahan, kategori, stok_sistem, stok_real, harga, selisih, kerugian

[POST] /gudang/updateDetailStockOpname
        │   validate: id_detail exists, column in [stok_real, harga], value numeric >= 0
        │   DB UPDATE: stok_opname_detail SET {column} = {value}
        │   Hitung ulang:
        │     selisih  = stok_real_baru - stok_sistem
        │     kerugian = MAX(0, (stok_sistem - stok_real_baru) * harga_baru)
        ▼
Selisih dan kerugian langsung terupdate

[POST] /gudang/updateStockOpname/{id}
        │   validate: tanggal required
        │   DB UPDATE: stok_opname SET tanggal = {tanggal}

[DELETE] /gudang/deleteStockOpname/{id}
        │   DB DELETE: stok_opname_detail WHERE kode = {kode}
        │   DB DELETE: stok_opname WHERE id = {id}
        ▼
Cascade delete detail sebelum header

[POST] /gudang/completeStockOpname/{id}
        │   DB UPDATE: stok_opname SET flag=1
        ▼
Status opname berubah menjadi "Selesai" (siap finalisasi)

[POST] /gudang/finalizeStockOpname/{id}
        │   Cek: flag != 2 (belum disetujui), flag != 0 (sudah selesai)
        │
        │   Untuk setiap detail yang selisih != 0:
        │     DB UPDATE: bahan SET stok_onhand = stok_onhand + selisih
        │     DB INSERT: stock_adjustments (kode='OPNAME {kode}', jumlah=selisih, ...)
        │
        │   DB UPDATE: stok_opname SET flag=2
        ▼
Stok on-hand diperbarui sesuai hasil opname
Selisih tercatat di stock_adjustments sebagai audit trail

[GET] /gudang/export-excel/{id}
        │   Ambil stok_opname by id → kode
        │   Download via StockOpnameDetailExport class
        ▼
File: Stok-Opname-Detail-{kode}.xlsx
```

---

## 5. Alur & Endpoint — Stok Adjustment

```
[GET] /adjustment  →  render view gudang.stokadjustment
        │   Inject semua bahan (id, nama, satuan) ke view

[GET] /searchadjustment?q={keyword}
        │   DB: bahan WHERE nama LIKE %keyword%
        │   LIMIT 50, ORDER BY nama
        ▼
Return: { results: [{id, text, satuan}] }  ← format Select2

[POST] /stokadjust  →  buat pengajuan adjustment
        │   validate:
        │     tanggal, operator required
        │     keterangan: required, unique di stock_adjustments_temporary WHERE status=pengajuan
        │     details[].id_barang: exists di bahan
        │     details[].jumlah: required, not_in:0
        │
        │   Kode: 'ADJ' + format tanggal 'md' (contoh: ADJ0615)
        │
        │   DB INSERT: stock_adjustments_temporary per item detail
        │     status = 'pengajuan'
        ▼
Pengajuan tersimpan, menunggu persetujuan

[GET] /gudang/history-data  (DataTables)
        │   DB: stock_adjustments_temporary
        │   GROUP BY kode, status → tampil summary per kode
        │   Status: badge "Diajukan" (warning) / "Sudah Masuk Data" (success)
        │   Aksi: tombol "Setujui" hanya muncul jika status=pengajuan

[GET] /gudang/adjustment-details?kode={kode}
        │   DB UNION:
        │     stock_adjustments_temporary WHERE kode = {kode}
        │     UNION
        │     stock_adjustments WHERE kode = {kode}
        ▼
Return detail item adjustment (nama_barang, jumlah, satuan, keterangan)
```

> **Catatan:** Endpoint persetujuan adjustment (`setujuiStokAdjust`) ada di controller tapi tidak terdaftar di `web.php` yang disertakan. Kemungkinan di-expose via route API terpisah atau route lain.

---

## 6. Alur & Endpoint — Stok Awal

> Stok awal adalah fitur one-time untuk input stok pembuka sebelum sistem dipakai.  
> Data disimpan di `admin_lpb` dengan `flag=1` dan `id_lpb='STOKAWAL'`.

```
[GET] /gudang/stokawal  →  render view gudang.stokawal

[GET] /check-stok-awal
        │   DB: admin_lpb WHERE flag=1 EXISTS?
        ▼
Return: { exists: true/false }

[POST] /store-stok-awal
        │   Jika sudah ada (flag=1 exists) → return error 400
        │   DB INSERT: admin_lpb (id_lpb='STOKAWAL', flag=1, no_po='STOKAWAL', no_sj='STOKAWAL')
        ▼
Record header stok awal terbuat

[GET] /get-detail-stok-awal?id_lpb={id_lpb}&...  (DataTables)
        │   DB: admin_lpb_detail JOIN bahan JOIN kategori_bahan
        │   WHERE id_lpb = {id_lpb}

[POST] /store-lpb-detail
        │   DB INSERT: admin_lpb_detail (id_lpb, id_bahan, id_kategori, jumlah, lot_number, harga=0)
        │   DB UPDATE: bahan SET
        │     stokawal   = stokawal   + jumlah_barang_diterima
        │     stok_onhand = stok_onhand + jumlah_barang_diterima

[POST] /updateDetailStokAwal
        │   Jika column = jumlah_barang_diterima:
        │     difference = newValue - oldValue
        │     DB UPDATE: bahan SET stokawal += difference, stok_onhand += difference
        │   DB UPDATE: admin_lpb_detail SET {column} = {value}

[GET] /get-bahan-dan-kategori?q={keyword}&id_bahan={id}
        │   Jika ada q → search bahan LIKE %q% LIMIT 10
        │   Jika ada id_bahan → ambil kategori & satuan bahan tersebut
        ▼
Return: { results: [...], kategori, kategori_id, satuan }
```

---

## 7. Detail Setiap Endpoint

### `GET /gudang/lpb`
- **Controller:** `lpb()`
- **Yang dilakukan:** Render view, inject `user` dari session

---

### `GET /gudang/lpbData`
- **Controller:** `getLpbData(Request $request)`
- **Query utama:** `admin_lpb LEFT JOIN inv_po LEFT JOIN suppliers`
- **Filter wajib:** `flag = 0` (exclude stok awal)
- **Filter opsional:**
  - `filterMonthYear` → filter `created_at` berdasarkan bulan & tahun
  - `filterLpbType` → filter `id_lpb LIKE '{type}%'` (LPBPO / LPBPP)
  - `searchTerm` → search di `id_lpb`, `no_po`, `no_sj`, `suppliers.nama`
- **Kolom action:**
  - `kunci=1` → tombol disabled "Tidak Bisa Cetak" dengan ikon gembok
  - `kunci=0` → tombol "Cetak" dengan ikon PDF
- **Pagination:** manual (skip/take), bukan via DataTables helper

---

### `POST /gudang/updateLpbData`
- **Controller:** `updateLpbData(Request $request)`
- **Validasi:** `id_lpb` exists, `type` in `[tanggal, no_sj]`, `value` required
- **Logika tambahan:** Jika record sudah pernah dicetak (`kunci != 0` OR `ulang != 0` OR `cetakan != 0`) → set `cetak_ulang = 1` agar PDF cetak ulang ditandai

---

### `GET /lpb/detail`
- **Controller:** `getDetailLpb(Request $request)`
- **Query:** `admin_lpb_detail JOIN bahan JOIN kategori_bahan WHERE id_lpb = ?`
- **Support:** search, pagination, sort by `admin_lpb_detail.id ASC`
- **Response:** `{ draw, recordsTotal, recordsFiltered, data }`

---

### `POST /lpb/detail/update`
- **Controller:** `updateLpbDetail(Request $request)`
- **column=9 (jumlah_barang_diterima):**
  - Hitung `difference = newValue - oldValue`
  - Baca `stok_onpurchase` dari `bahan`
  - Jika `stok_onpurchase - difference < 0` → kap `difference` di nilai `stok_onpurchase` (tidak sampai negatif)
  - Update `stok_onhand += difference`, `stok_onpurchase -= difference`
- **column=10 (lot_number):** update langsung, tidak ada efek ke stok

---

### `DELETE /lpb/detail/{id}`
- **Controller:** `deleteLpbDetail(Request $request, $id)`
- **Efek stok:**
  - `stok_onhand -= jumlah_barang_diterima` (barang dikembalikan ke PO)
  - `stok_onpurchase += jumlah_barang_diterima` (PO dianggap belum diterima)
- **Catatan:** `id` diambil dari **request body**, bukan path parameter (meski ada `$id` di signature)

---

### `GET /getSuppliers`
- **Controller:** `getSuppliers()`
- **Filter user:**
  - `user.type = 5` → `inv_po.jenis = 2` (supplier khusus)
  - selain itu → `inv_po.jenis != 2` (supplier umum)
- **Kondisi:** `inv_po.status != 2` (PO belum selesai)

---

### `GET /gudang/no-po`
- **Controller:** `getNoPoBySupplier(Request $request)`
- **Filter:** `status != 2` dan `id_suplier = {id}`
- **Group by** `no_po, tanggal, no_order` untuk deduplicate jika ada multi-item PO

---

### `GET /getDetailByNoPo`
- **Controller:** `getDetailByNoPo(Request $request)`
- **Query:** `inv_podetail JOIN bahan JOIN kategori_bahan WHERE no_po = ?`
- **Return:** `harga`, `jumlah` (qty PO), `satuan`, `kategori`, `id_bahan`, `katid`

---

### `POST /gudang/save-lpb`
- **Controller:** `saveLpb(Request $request)`
- **Generate ID LPB:** Pakai 2 digit tahun dari **tanggal diterima** (bukan `date('y')` server), cari nomor urut terakhir, increment +1, pad 3 digit
- **Auto no. surat jalan:** Jika input kosong, generate `{sjPrefix}{yearLpb}{counter 4 digit}`
- **Pembaruan stok:** `stok_onpurchase` dikurangi dengan `GREATEST(0, ...)` agar tidak negatif

---

### `POST /cetak-lpb`
- **Controller:** `cetakLpb(Request $request)`
- **Efek samping saat cetak:**
  - LPB di-lock: `kunci = 1`
  - Counter cetakan diupdate:
    - `cetak_ulang=0` → `ulang++` (cetak pertama kali / ulang normal)
    - `cetak_ulang=1` → `cetakan++`, `ulang=0` (cetak setelah ada perubahan data)
- **PDF:** Dibuat via TCPDF, return sebagai binary stream (`Content-Type: application/pdf`)
- **Footer PDF:** "Cetakan ke X dan duplikasi ke Y"

---

## 8. Logika Bisnis Penting

### Tiga Jenis Stok di Tabel `bahan`
| Kolom | Keterangan | Berubah saat |
|---|---|---|
| `stok_onhand` | Stok fisik ada di gudang | LPB disimpan (+), detail dihapus (-), adjustment, opname |
| `stok_onpurchase` | Barang sudah di-PO tapi belum diterima | LPB disimpan (-), detail dihapus (+) |
| `stokawal` | Stok awal satu kali input | Hanya saat input/edit stok awal |

### Status Kunci LPB
| `kunci` | `cetak_ulang` | `ulang` | `cetakan` | Kondisi |
|---|---|---|---|---|
| 0 | 0 | 0 | 0 | LPB baru, belum pernah dicetak |
| 1 | 0 | N | 0 | Sudah dicetak N kali, tidak ada perubahan data |
| 1 | 1 | 0 | N | Sudah ada perubahan data setelah cetak, cetak ulang N kali |

LPB dengan `kunci=1` tidak bisa diedit inline (field `editable` tidak dirender).

### Validasi Overpayment Barang (Saat Input LPB)
```
totalKeranjang = sum(jumlah) untuk id_bahan yang sama di selectedLpbItems
totalAkhir = totalKeranjang + jumlahInput

Jika totalAkhir > jumlah_PO:
  Jika kategori = PACKING (katid=12 atau nama='PACKING') → BOLEH melebihi
  Selainnya → TOLAK, tampilkan sisa maksimal yang boleh diinput
```

### Penomoran LPB
- Format: `{PREFIX}{YY}{NNN}` contoh: `LPBPO25001`
- `YY` diambil dari **tanggal barang diterima**, bukan tanggal server
- Nomor urut berbasis `id_lpb LIKE '{PREFIX}{YY}%'`, bukan sequence DB

### Flag Stok Opname
| `flag` | Status |
|---|---|
| 0 | Sedang dikerjakan |
| 1 | Selesai (siap finalisasi) |
| 2 | Sudah disetujui & stok diperbarui |

### Harga di Stok Opname
Saat membuat sesi opname, harga per bahan diambil dari rata-rata 5 harga LPB terakhir (`admin_lpb_detail.harga`). Jika belum ada LPB → fallback ke `bahan.harga`.

---

## 9. Struktur Database

### Tabel yang Terlibat

#### `admin_lpb`
| Kolom | Keterangan |
|---|---|
| `id_lpb` | Primary key (string, generated, contoh: LPBPO25001) |
| `tanggal` | Tanggal barang diterima |
| `no_po` | Relasi ke `inv_po.no_po` |
| `no_sj` | Nomor surat jalan (unik) |
| `id_user` | User yang input |
| `jenis_lpb` | 1=PO, 2=PP, 3=MO |
| `flag` | 0=LPB normal, 1=stok awal |
| `kunci` | 0=bisa edit, 1=terkunci (sudah cetak) |
| `cetak_ulang` | 0=tidak ada perubahan, 1=ada perubahan setelah cetak |
| `ulang` | Counter cetak ulang normal |
| `cetakan` | Counter cetak setelah perubahan data |

#### `admin_lpb_detail`
| Kolom | Keterangan |
|---|---|
| `id` | Primary key |
| `id_lpb` | FK ke `admin_lpb.id_lpb` |
| `id_bahan` | FK ke `bahan.id` |
| `id_kategori` | FK ke `kategori_bahan.katid` |
| `jumlah_barang_diterima` | Jumlah yang diterima (editable) |
| `lot_number` | Nomor lot batch (nullable, editable) |
| `harga` | Harga saat diterima (untuk histori & opname) |

#### `inv_po`
| Kolom | Keterangan |
|---|---|
| `no_po` | Primary key |
| `id_suplier` | FK ke `suppliers.id` |
| `jenis` | 0=PO biasa, 1=PP, 2=khusus |
| `status` | 0=draft, 1=sebagian diterima, 2=selesai |

#### `inv_podetail`
| Kolom | Keterangan |
|---|---|
| `no_po` | FK ke `inv_po` |
| `id_bahan` | FK ke `bahan` |
| `jumlah` | Qty yang dipesan |
| `diterima` | Akumulasi qty yang sudah diterima via LPB |
| `harga` | Harga per unit |

#### `bahan`
| Kolom | Keterangan |
|---|---|
| `id` | Primary key |
| `nama` | Nama bahan |
| `kategori` | FK ke `kategori_bahan.katid` |
| `satuan` | Satuan ukur |
| `harga` | Harga default |
| `stok_onhand` | Stok fisik saat ini |
| `stok_onpurchase` | Stok dalam proses pembelian |
| `stokawal` | Stok awal (hanya diupdate via fitur Stok Awal) |

#### `stok_opname` & `stok_opname_detail`
| Kolom | Keterangan |
|---|---|
| `stok_opname.kode` | Kode sesi opname (STO-NK2501) |
| `stok_opname.flag` | 0=berlangsung, 1=selesai, 2=disetujui |
| `stok_opname_detail.stok_sistem` | Snapshot stok saat opname dibuat |
| `stok_opname_detail.stok_real` | Input stok fisik sebenarnya |
| `stok_opname_detail.selisih` | stok_real - stok_sistem |
| `stok_opname_detail.kerugian` | MAX(0, (sistem-real) × harga) |

#### `stock_adjustments` & `stock_adjustments_temporary`
| Kolom | Keterangan |
|---|---|
| `kode` | Kode adjustment (ADJ{mmdd} atau OPNAME {kode_opname}) |
| `status` | Hanya di temporary: 'pengajuan' / 'sudah masuk data' |
| `jumlah` | Positif = tambah stok, Negatif = kurangi stok |

---

## 10. Edge Cases & Validasi

### ✅ Sudah Ditangani
| Skenario | Penanganan |
|---|---|
| No. Surat Jalan duplikat | Cek real-time saat input (debounce 500ms) + validasi saat save |
| Jumlah barang melebihi PO | Ditolak frontend, kecuali kategori PACKING |
| Stok on-purchase tidak boleh negatif | `GREATEST(0, stok_onpurchase - jumlah)` saat save LPB |
| Stok on-purchase tidak negatif saat edit | Dicek & dikap saat `updateLpbDetail` column=9 |
| LPB yang sudah dicetak tidak bisa diedit di UI | Field `editable` tidak dirender jika `kunci=1` |
| Sesi opname tidak bisa di-finalisasi dua kali | Cek `flag == 2` sebelum finalisasi |
| Sesi opname tidak bisa difinalisasi sebelum selesai | Cek `flag == 0` sebelum finalisasi |
| Stok awal hanya bisa dibuat sekali | Cek `exists` sebelum insert |
| Error DB di semua operasi write | `DB::beginTransaction` + `rollBack` + return error |
| LPB terkunci tidak bisa dihapus detail-nya | Tombol "Hapus" tidak dirender jika `kunci=1` |

### ⚠️ Perlu Diperhatikan
| Skenario | Catatan |
|---|---|
| **`deleteLpbDetail` ambil `id` dari body, bukan path** | Method signature ada `$id` tapi `$validated['id']` dipakai. Bisa membingungkan. |
| **Penomoran LPB tidak atomic** | Generate nomor dengan `SELECT MAX` lalu `INSERT` tidak dibungkus lock. Jika dua request bersamaan bisa menghasilkan ID duplikat. |
| **Penomoran no. SJ auto juga tidak atomic** | Sama seperti ID LPB, hitung `COUNT + 1` bisa race condition. |
| **Filter `filterMonthYear` di `getLpbData` pakai `created_at`** | Sementara kolom yang tampil di tabel adalah `tanggal`. Jika user input tanggal berbeda bulan dari tanggal create, bisa tidak muncul di filter bulan yang diharapkan. |
| **`user.type` dari session dipakai untuk filter supplier** | Jika session expired/manipulasi, filter bisa salah. Tidak ada validasi middleware khusus per endpoint. |
| **Kode ADJ pakai format `md` (bulan+tanggal)** | Contoh: `ADJ0615`. Jika ada dua adjustment di hari yang sama, kode akan sama. Keunikan bergantung pada field `keterangan` yang di-unique di temporary table. |
| **`finalizeStockOpname` pakai `$request->user()`** | Di route web dengan session custom (`auth.custom`), `$request->user()` bisa return `null`. Sudah ada fallback `'API System'` tapi nama operator tidak akan tercatat dengan benar. |
