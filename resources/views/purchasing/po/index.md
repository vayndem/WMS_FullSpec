# Dokumentasi Engineer — Modul Purchasing (PO Non-Kertas)

---

## Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Tiga Jenis PO (`jenis`)](#2-tiga-jenis-po-jenis)
3. [Arsitektur & Tabel Database](#3-arsitektur--tabel-database)
4. [Routes](#4-routes)
5. [Controller Methods — PO](#5-controller-methods--po)
6. [Controller Methods — Invoice LPB](#6-controller-methods--invoice-lpb)
7. [Controller Methods — Export](#7-controller-methods--export)
8. [Alur Data End-to-End](#8-alur-data-end-to-end)
9. [Frontend / Blade](#9-frontend--blade)
10. [Cetak PDF Purchase Order](#10-cetak-pdf-purchase-order)
11. [Status Reference](#11-status-reference)
12. [ID Generation](#12-id-generation)
13. [Interaksi Antar Tabel](#13-interaksi-antar-tabel)
14. [Error & Edge Cases](#14-error--edge-cases)

---

## 1. Gambaran Umum

Modul Purchasing mengelola **pembuatan dan pengelolaan Purchase Order (PO)** untuk bahan non-kertas, serta **pencatatan Invoice LPB** yang mengaitkan LPB (Laporan Penerimaan Barang) dengan invoice supplier.

Dua sub-modul utama:

```
Sub-modul 1: Purchase Order (PO)
    Purchasing buat PO → cetak PDF → supplier kirim barang
    → LPB dibuat (modul terpisah) → status LPB dihubungkan ke invoice

Sub-modul 2: Invoice LPB
    Purchasing buat invoice dari LPB yang sudah diterima
    → Invoice tercatat → pembayaran diproses (modul terpisah)
    → LPB Return jika ada barang bermasalah
```

---

## 2. Tiga Jenis PO (`jenis`)

Kolom `jenis` di `inv_po` dan `inv_podetail` menentukan tipe pembelian:

| `jenis` | Label | Keterangan |
|---|---|---|
| `1` | PP (Pembelian Penolong) | Bahan penolong produksi — judul PDF "PEMBELIAN BAHAN PENOLONG (PP)" |
| `2` | Non PO/PP | Pembelian tanpa PO formal |
| `3` | PO (Pembelian Penunjang) | Default — bahan penunjang produksi — judul PDF "PEMBELIAN BAHAN PENUNJANG (PO)" |

Filter `jenis` dikirim ke semua request DataTables dari Blade: `d.jenis = {{ $jenis }}`. Nilai `$jenis` diinjeksi dari controller/route yang membuka halaman.

---

## 3. Arsitektur & Tabel Database

### `inv_po` — Header Purchase Order

| Kolom | Tipe | Keterangan |
|---|---|---|
| `no_po` | varchar | PK, generated via `POModel::generatePONumber()` |
| `id_suplier` | int | FK → `suppliers.id` |
| `tanggal` | date | Tanggal PO |
| `no_order` | varchar | Nomor SO dari supplier |
| `untukperhatian` | varchar | "UP: ..." di header PDF |
| `term` | varchar | Term pembayaran |
| `notes` | text | Catatan tambahan |
| `ppn` | decimal | Nilai persentase PPN (`konstanta_ppn` atau 0) |
| `totalexclude` | decimal | Total harga sebelum PPN |
| `totalppn` | decimal | Nominal PPN |
| `totalinclude` | decimal | Total setelah PPN |
| `diskon` | decimal | Nominal diskon |
| `ongkir` | decimal | Biaya freight handling |
| `inputlabel` | varchar | Label baris ongkir — default `'Freight Handling'`, disimpan sebagai `'-'` jika default |
| `GrandTotalPembelian` | decimal | Grand total akhir |
| `jenis` | tinyint | Tipe PO — lihat [Section 2](#2-tiga-jenis-po-jenis) |
| `status` | tinyint | `0`=aktif, `1`=partial close, `2`=closed |
| `kunci` | tinyint | `1`=locked (tidak bisa cetak ulang tanpa archive) |
| `cetak` | int | Counter cetak — `0`=belum pernah, `1`=cetak asli, `>1`=cetak ulang |
| `counter_asli` | varchar | Timestamp/label cetakan pertama |
| `cetak_ulang` | tinyint | `1` jika sudah pernah diedit setelah dicetak |
| `user_id` | int | FK → `users.id` atau session `user_data.id` |

### `inv_podetail` — Detail Item PO

| Kolom | Keterangan |
|---|---|
| `id` | PK |
| `no_po` | FK → `inv_po.no_po` |
| `id_bahan` | FK → `bahan.id` |
| `jumlah` | Qty yang dipesan |
| `harga` | Harga per satuan |
| `exclude` | `jumlah × harga` |
| `ppn` | Nominal PPN per item |
| `include` | `exclude + ppn` |
| `id_permintaan` | FK → `permintaan.id` (opsional, `0` jika tidak dari permintaan) |
| `diterima` | Qty yang sudah diterima (diupdate modul LPB) |
| `jenis` | Sama dengan `inv_po.jenis` |

### `permintaan` — Permintaan Pembelian

| Kolom | Keterangan |
|---|---|
| `id` | PK |
| `id_bahan` | FK → `bahan.id` |
| `jumlah_order` | Jumlah yang diminta |
| `realisasi` | Jumlah yang sudah di-PO |
| `finish` | `1` jika `realisasi >= jumlah_order` |

### `invoice_lpb` — Invoice dari LPB

| Kolom | Keterangan |
|---|---|
| `id` | PK |
| `no_invoice` | Nomor invoice supplier (unik) |
| `kode_supplier` | FK → `suppliers.id` |
| `tanggal` | Tanggal invoice |
| `tgl_deadline_pembayaran` | Jatuh tempo pembayaran |
| `sub_total` | Total sebelum adjustment |
| `ppn` | Nominal PPN |
| `diskon` | Nominal diskon |
| `pph` | PPh — bisa negatif (`-` operator) |
| `ongkir` | Biaya pengiriman |
| `grand_total` | Total akhir |
| `total_pembayaran` | Sudah dibayar berapa |
| `sisa_tagihan` | `grand_total - total_pembayaran` |
| `status_pembayaran` | `'Belum Dibayar'` / `'Proses Pembayaran'` / `'Proses'` / `'Dibayar Sebagian'` / `'Lunas'` |
| `note` | Catatan |

### `admin_lpb_return` — Header Return Barang Bermasalah

| Kolom | Keterangan |
|---|---|
| `id_return` | PK, format `RT{mmdd}{seq3}` |
| `id_lpb` | FK → `admin_lpb.id_lpb` |
| `no_po`, `no_sj` | Disalin dari LPB asal |
| `no_invoice` | FK → `invoice_lpb.no_invoice` |
| `status`, `flag` | Status return |
| `jenis_lpb` | Disalin dari LPB asal |

### `admin_lpb_detail_return` — Detail Return

Isi bahan yang dikembalikan, dengan `jumlah_barang_diterima` = qty return, bukan qty asli.

### Tabel Lookup (Read-only di modul ini)

| Tabel | Dipakai untuk |
|---|---|
| `suppliers` | Nama supplier di header PO & invoice |
| `bahan` | Nama bahan, satuan, harga default, `keterangan_bahan` |
| `admin_lpb` | Status LPB, relasi ke invoice |
| `admin_lpb_detail` | Qty diterima, harga per item untuk invoice |

---

## 4. Routes

### Routes PO

| Method | URI | Controller Method | Nama Route | Keterangan |
|---|---|---|---|---|
| GET | `/purchasing` | `index()` | `purchasing.index` | DataTables permintaan pembelian (AJAX only) |
| GET | `/purchasing/create` | `create()` | `purchasing.create` | Load modal form tambah PO (AJAX, inject view) |
| POST | `/purchasing` | `store()` | `purchasing.store` | Simpan PO baru / edit PO / tambah detail |
| GET | `/purchasing/{no_po}` | `show()` | `purchasing.show` | Detail item satu PO (untuk expand row) |
| GET | `/purchasing/{no_po}/edit` | `edit()` | `purchasing.edit` | Load modal tambah detail ke PO existing |
| PUT | `/purchasing/{no_po}` | `update()` | `purchasing.update` | Update no_order / diskon-ongkir / close PO |
| DELETE | `/purchasing/{id}` | `destroy()` | `purchasing.destroy` | Hapus satu item detail PO |
| GET | `/getDataPO` | `getData()` | `getDataPO` | DataTables header PO (tabel utama) |
| GET | `/getdetailPO` | `getDatadetail()` | `getDataPOdetail` | Load modal detail PO summary |
| GET | `/showDetail` | `showDetail()` | `showDetail` | DataTables detail item per periode |
| POST | `/cetakpembelian` | `cetakpembelian()` | _(none)_ | Generate & stream PDF PO |
| POST | `/lihatcetak` | _(method lain)_ | _(none)_ | Preview HTML PO di modal |

### Routes Invoice LPB

| Method | URI | Controller Method | Nama Route | Keterangan |
|---|---|---|---|---|
| GET | `/invoicelpb` | `invoiceLpb()` | `invoicelpb.index` | Halaman invoice LPB (type 5 only) |
| GET | `/lpbreturn` | `lpbreturn()` | `invoicelpb.lpbreturn` | Halaman LPB return (type 5 only) |
| GET | `/purchasing/invoice/data` | `getInvoiceData()` | `purchasing.invoice.data` | DataTables invoice & return |
| GET | `/purchasing/invoice/available-pos` | `getAvailablePurchaseOrders()` | `purchasing.invoice.available_pos` | List PO yang LPB-nya siap diinvoice |
| GET | `/purchasing/lpb/by-po/{no_po}` | `getLpbByPo()` | `purchasing.lpb.by_po` | LPB + detail per PO untuk form invoice |
| POST | `/invoice-lpb/store` | `storeInvoice()` | `invoice.lpb.store` | Simpan invoice baru |
| GET | `/invoice/detail/{id}` | `getInvoiceDetail()` | `invoice.detail` | Detail invoice atau return |
| GET | `/purchasing/getInvoiceItems/{invoiceId}` | `getInvoiceItems()` | `invoice.getInvoiceItems` | Item LPB dalam satu invoice |
| POST | `/purchasing/updateInvoiceItems` | `updateInvoiceItems()` | `invoice.updateInvoiceItems` | Catat LPB return (barang bermasalah) |
| DELETE | `/purchasing/invoice/{id}` | `destroyInvoice()` | `invoice.lpb.destroy` | Hapus invoice (hanya jika belum diproses bayar) |

### Routes Export

| Method | URI | Controller Method | Nama Route | Keterangan |
|---|---|---|---|---|
| GET | `/export-po` | `exportPO()` | `export.po` | Export PO ke XLSX |
| GET | `/laporan-opname/export` | `exportExcel()` | `laporan.opname` | Export laporan opname (type 11 only) |
| GET | `/laporan-barang/export` | `exportLaporan()` | `laporan.barang` | Export laporan stok barang (type 11 only) |

> ⚠️ **Route conflict potensial:** `GET /purchasing/{no_po}/edit` adalah route resource standar, tapi `GET /purchasing/invoice/data`, `GET /purchasing/invoice/available-pos`, dll. menggunakan prefix `/purchasing/...` yang bisa tertangkap sebagai `{no_po}` parameter jika urutannya salah. Pastikan routes spesifik dengan path literal didefinisikan **sebelum** `Route::resource('purchasing', ...)`.

---

## 5. Controller Methods — PO

### 5.1 `index(Request $request)` — DataTables Permintaan

DataTables untuk list `permintaan` yang belum selesai (`jumlah_order > realisasi`).

**Filter:** `jenis` (dari request AJAX), `search.value`

Jika `jenis != 3`, filter bahan berdasarkan `bahan.jenis`. Jika `jenis = 3`, semua bahan ditampilkan.

Dipakai di modal tambah PO untuk user memilih permintaan yang akan di-PO.

---

### 5.2 `getData(Request $request)` — DataTables Header PO

DataTables utama halaman. **Implementasi manual** (bukan package Yajra):

```php
return response()->json([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $po_bahan,
]);
```

**Filter:** `bulan`, `tahun`, `jenis`, `search.value` (mencari `no_po` atau `nama` supplier)

`recordsTotal` dihitung tanpa filter search (hanya filter bulan/tahun/jenis), `recordsFiltered` dihitung dengan filter search.

---

### 5.3 `store(Request $request)` — Tiga Mode dalam Satu Method

Mode ditentukan dari parameter `edit`:

| `edit` | Mode | Keterangan |
|---|---|---|
| `0` | Create PO baru | Buat header + semua detail sekaligus |
| `2` | Edit PO existing | Buat archive jika sudah pernah dicetak, update header + detail via `updateOrCreate` |
| Selain itu (biasanya `1`) | Tambah satu detail ke PO | Hitung ulang total header |

**Mode 0 — Create baru:**
```
1. Validate id_suplier
2. POModel::generatePONumber($tanggal, $jenis)
3. INSERT inv_po (header)
4. LOOP: INSERT inv_podetail per item
5. Permintaan::whereColumn('realisasi', '>=', 'jumlah_order')->update(['finish' => 1])
```

**Mode 2 — Edit PO:**
```
1. Jika po.cetak >= 1: CALL SP_ArchivePo(?) → set cetak_ulang=1
2. UPDATE inv_po (header)
3. LOOP items: DetailPOModel::updateOrCreate([no_po, id_bahan], [...])
4. DetailPOModel WHERE no_po AND id_bahan NOT IN (kept) → DELETE
5. Update finish permintaan
```

**Mode 1 (tambah satu detail):**
```
1. Jika po.cetak >= 1: CALL SP_ArchivePo(?) → set cetak_ulang=1
2. INSERT inv_podetail (satu baris)
3. Hitung ulang header:
   newexclude = po.totalexclude + exclude
   newbruto = newexclude - po.diskon
   newnilaippn = (newbruto * ppn) / 100
   newinclude = newexclude + newnilaippn
4. UPDATE inv_po (totalexclude, totalppn, totalinclude, GrandTotalPembelian)
5. Update finish permintaan
```

> **SP_ArchivePo:** Stored procedure yang di-call sebelum edit jika PO sudah dicetak. Tidak ada kode SP di controller — hanya pemanggilan via `DB::statement('CALL SP_ArchivePo(?)', [$po->no_po])`.

---

### 5.4 `show(string $no_po)` — Detail Item PO

Mengembalikan array detail item untuk expand row di frontend.

```sql
SELECT inv_podetail.id as unique, id_bahan, jumlah, inv_podetail.harga as harga_bahan,
       exclude, ppn, include, nama, satuan
FROM inv_podetail
JOIN bahan ON id_bahan = bahan.id
WHERE no_po = ?
```

Alias `id as unique` dipakai sebagai identifier di tombol hapus/edit per baris (`hapusdetail(d.unique)`, `editdetail(d.unique)`).

---

### 5.5 `update(Request $request, $no_po)` — Tiga Mode Update

Mode ditentukan dari `closestatus`:

| `closestatus` | Mode | Yang diupdate |
|---|---|---|
| `0` | Update no_order & term | `no_order`, `term` |
| `99` | Update diskon, ongkir, inputlabel | `diskon`, `ongkir`, `totalppn`, `totalinclude`, `GrandTotalPembelian`, `inputlabel` |
| Selain itu (misal `2`) | Close PO | `status = closestatus` + kurangi `bahan.stok_onpurchase` untuk qty belum diterima |

**Mode Close PO (`closestatus = 2`):**
```php
$details = DetailPOModel::where('no_po', $no_po)
    ->whereColumn('jumlah', '>', 'diterima')
    ->get();

foreach ($details as $detail) {
    $selisih = $detail->jumlah - $detail->diterima;
    Bahan::where('id', $detail->id_bahan)->decrement('stok_onpurchase', $selisih);
}
```

Untuk setiap item yang belum fully diterima, selisih `jumlah - diterima` dikurangi dari `bahan.stok_onpurchase`.

Guard: jika `po.status == 2`, tolak close ulang dengan response 500.

**Mode Update Diskon (`closestatus = 99`):**

```php
// Guard: PO yang sudah dikunci tidak bisa diupdate
if ($pokertas->kunci != 0) return 403;

// Hitung ulang semua nilai dari totalexclude
$excludeminusdiskon = $exclude - $diskonedit;
$newtotalppn = ($excludeminusdiskon * $acuanppn) / 100;
$newinclude = $excludeminusdiskon + $newtotalppn;
$GrandTotal = $newinclude + $ongkiredit;
```

---

### 5.6 `destroy(string $id)` — Hapus Satu Detail PO

`$id` adalah `inv_podetail.id` (bukan `no_po`).

**Logika:**
```
1. Cari detail WHERE id = $id
2. Cari header WHERE no_po = detail.no_po
3. Cek jumlah detail yang tersisa:
   JIKA hanya 1 sisa:
       DELETE detail + DELETE header (PO ikut terhapus)
   JIKA lebih dari 1:
       Hitung ulang header tanpa exclude item yang dihapus:
       newtotalexclude = po.totalexclude - detail.exclude
       newtotalppn = ((newtotalexclude - po.diskon) * po.ppn) / 100
       newtotalinclude = newtotalexclude + newtotalppn
       GrandTotal = newtotalinclude - po.diskon + po.ongkir
       UPDATE inv_po
       DELETE inv_podetail WHERE id = $id
```

> ⚠️ Formula `GrandTotal = newtotalinclude - po.diskon + po.ongkir` mengurangi diskon dua kali — pertama di `newtotalinclude` (sudah dihitung dari `newtotalexclude - diskon`), kemudian dikurangi lagi di sini. Ini kemungkinan bug kalkulasi.

---

### 5.7 `cetakpembelian(Request $request)` — Generate PDF PO

**Request:** POST, body `nomorpo`

**Data yang dibutuhkan:**
- `POModel::headernomorpo($nomorpo)` — header PO + supplier
- `DetailPOModel::detail($nomorpo)` — detail item + nama bahan + keterangan

**Konten PDF (TCPDF, A4 portrait):**
- Header perusahaan (logo kiri, logo kanan, nama, alamat)
- Judul "PURCHASE ORDER" + nomor PO
- Info supplier (kepada yth, UP, term, no_order)
- Tabel item: No, Nama + keterangan_bahan, QTY, Satuan, Harga, Jumlah Harga
- Footer tabel: Total, Diskon, PPN, Freight Handling/inputlabel, Total Order
- Penutup + nama penandatangan hardcoded `Roy Mulyono`
- Footer bawah: info cetakan asli atau duplikat ke-N

**Logika footer cetakan:**
```php
if ($header->cetak == 1) {
    $footer_text = "Cetakan asli $header->counter_asli, dicetak $tanggal_cetak";
} else {
    $cetakanke = $header->cetak - 1;
    $footer_text = "Cetakan duplikat ke-$cetakanke dari asli $header->counter_asli, dicetak $tanggal_cetak";
}
```

**Output:** `$pdf->Output('nama_file.pdf', 'I')` — stream inline ke browser.

> ⚠️ `$pdf->Output('nama_file.pdf', 'I')` menggunakan nama file literal `'nama_file.pdf'` — nama file tidak dinamis berdasarkan nomor PO.

> ⚠️ Nama penandatangan `Roy Mulyono` di-hardcode di HTML, tidak diambil dari DB.

---

## 6. Controller Methods — Invoice LPB

### 6.1 `invoiceLpb()` & `lpbreturn()`

Guard akses: hanya user type `5`. Jika bukan, `abort(403)`.

Menggunakan `session('user_data')` bukan `Auth::user()`.

---

### 6.2 `getInvoiceData(Request $request)` — DataTables Invoice & Return

Method ini menangani **dua mode** via parameter `return`:

**Mode `return=false` (default) — Invoice LPB:**
```sql
SELECT invoice_lpb.*, suppliers.nama as supplier_nama,
       GROUP_CONCAT(DISTINCT admin_lpb.no_po) as no_po,
       MIN(admin_lpb.jenis_lpb) as jenis_lpb
FROM invoice_lpb
JOIN suppliers ON kode_supplier = suppliers.id
LEFT JOIN admin_lpb ON no_invoice = no_invoice
GROUP BY invoice_lpb.id, ...
```

Data juga menambahkan flag `is_overdue` di PHP:
```php
$item->is_overdue = Carbon::now()->startOfDay()->gt(Carbon::parse($item->tgl_deadline_pembayaran));
```

**Mode `return=true` — LPB Return:**
```sql
SELECT admin_lpb_return.*, SUM(aldr.jumlah_barang_diterima * aldr.harga) as grand_total
FROM admin_lpb_return
LEFT JOIN admin_lpb_detail_return ON id_return
JOIN inv_po ON no_po
JOIN suppliers ON id_suplier
GROUP BY admin_lpb_return.id, ...
```

**Filter status invoice:**

| `status` | Filter |
|---|---|
| `'0'` | `status_pembayaran IN ('Siap Bayar', 'Belum Dibayar')` |
| `'1'` | `status_pembayaran IN ('Proses Pembayaran', 'Proses')` |
| `'2'` | `status_pembayaran = 'Lunas'` |
| `'3'` | `tgl_deadline_pembayaran < TODAY AND status != 'Lunas'` (overdue) |
| `'all'` | Tanpa filter status |

---

### 6.3 `getAvailablePurchaseOrders()` — PO Siap Diinvoice

```sql
SELECT DISTINCT al.no_po, ip.id_suplier, s.nama as nama_supplier, ip.ppn
FROM admin_lpb al
JOIN inv_po ip ON al.no_po = ip.no_po
JOIN suppliers s ON ip.id_suplier = s.id
WHERE al.status = 0 AND al.flag = 0
ORDER BY s.nama ASC, al.no_po ASC
```

`status=0, flag=0` = LPB yang belum diinvoice.

---

### 6.4 `getLpbByPo($no_po)` — LPB + Detail per PO

Mengambil semua LPB yang belum diinvoice untuk satu PO, beserta detail item-nya.

**Harga yang dipakai:**
```sql
COALESCE(ipd.harga, ald.harga) as harga_final
```

Prioritas harga dari `inv_podetail` (harga yang disepakati di PO), fallback ke harga di `admin_lpb_detail` (harga penerimaan).

Response diformat dengan PHP untuk menambahkan `total_qty` dan `sub_total_lpb` per header LPB.

---

### 6.5 `storeInvoice(Request $request)` — Simpan Invoice Baru

**Validasi lengkap:**
- `no_invoice`: unik di `invoice_lpb`
- `kode_supplier`: exists di `suppliers`
- `tgl_deadline_pembayaran`: harus >= `tanggal_nota`
- `selected_lpb_ids`: wajib (minimal satu LPB dipilih)

**Parsing currency:** Semua nilai nominal (`sub_total`, `ppn`, `diskon`, `grand_total`, dll.) diparse via helper private:
```php
private function parseCurrency($value) {
    $cleanedValue = preg_replace('/[^\d,]/', '', $value);
    $floatValue = str_replace(',', '.', $cleanedValue);
    return (float) $floatValue;
}
```

**PPh bisa negatif:** `$pph_operator` dari request (`+` atau `-`) menentukan tanda nilai PPh.

**DB writes:**
```sql
INSERT INTO invoice_lpb (no_invoice, kode_supplier, tanggal, ..., status_pembayaran='Belum Dibayar')

UPDATE admin_lpb
SET no_invoice = ?, status = 1
WHERE id_lpb IN (selected_lpb_ids)
```

---

### 6.6 `updateInvoiceItems(Request $request)` — Catat Barang Bermasalah (LPB Return)

Untuk setiap item yang di-return (qty > 0), method ini:

1. Cek apakah sudah ada header return untuk `id_lpb` yang sama dalam batch ini
2. Jika belum, buat header baru di `admin_lpb_return` dengan kode `RT{mmdd}{seq3}`
3. Insert detail ke `admin_lpb_detail_return`

**Generate kode return:**
```php
$todayPrefix = 'RT' . now()->format('md');  // misal RT1210
$lastReturn = DB::table('admin_lpb_return')
    ->where('id_return', 'LIKE', $todayPrefix . '%')
    ->orderBy('id_return', 'desc')
    ->first();

$nextIncrement = $lastReturn ? (int)substr($lastReturn->id_return, 6) + 1 : 1;
$newReturnId = $todayPrefix . str_pad($nextIncrement, 3, '0', STR_PAD_LEFT);
// Contoh: RT1210001
```

Dalam satu request, beberapa item dari LPB yang sama menggunakan satu header return yang sama (tracking via `$createdReturnHeaders[$id_lpb]`).

---

### 6.7 `destroyInvoice($id)` — Hapus Invoice

**Guard status:**
```php
$protectedStatuses = ['Dibayar Sebagian', 'Proses Pembayaran', 'Proses', 'Lunas'];
if (in_array($invoice->status_pembayaran, $protectedStatuses)) {
    return response()->json(['...'], 403);
}
```

**DB writes jika diizinkan:**
```sql
UPDATE admin_lpb
SET status = 0, flag = 0, no_invoice = NULL
WHERE no_invoice = ?

DELETE FROM invoice_lpb WHERE id = ?
```

LPB dikembalikan ke status "belum diinvoice" dan bisa dipilih lagi untuk invoice baru.

---

## 7. Controller Methods — Export

### 7.1 `exportPO(Request $request)` — Export PO ke XLSX

Menggunakan `PoKertasExport` (Maatwebsite Excel). Filter: `bulan`, `tahun`, `jenis`.

Nama file: `Laporan_PO_NonKertas_{namaBulan}_{tahun}.xlsx`

Jika `bulan = '0'`, nama bulan menjadi `'SemuaBulan'`.

### 7.2 `exportExcel(Request $request)` — Export Laporan Opname

Guard: hanya user type `11` (via session).

Data periode: dari `stok_opname.tanggal` terbaru sampai `tanggal_akhir` (dari request, default hari ini).

Menggunakan `LaporanOpnameExport`.

### 7.3 `exportLaporan(Request $request)` — Export Laporan Stok Barang

Guard: hanya user type `11` (via session).

Membutuhkan `tgl_awal` dan `tgl_akhir` di query params. Jika tidak ada, redirect back dengan error.

Menggunakan `ExportLaporan`.

---

## 8. Alur Data End-to-End

### 8.1 Buat PO Baru

```
Purchasing klik "+" (btnAdd)
    └─► GET /purchasing/create?jenis={jenis}
        └─► Render view formtambah → inject ke .viewmodal
            └─► Modal #exampleModal terbuka
                └─► User pilih supplier, isi tanggal, term, notes
                    └─► User pilih dari daftar permintaan (DataTable)
                        └─► Item ditambah ke tabel form (in-memory)

User klik Simpan
    └─► POST /purchasing (edit=0)
        ├─► POModel::generatePONumber() → nomor PO baru
        ├─► INSERT inv_po (header)
        ├─► LOOP: INSERT inv_podetail per item
        └─► UPDATE permintaan.finish jika realisasi >= jumlah_order
            └─► Response: no_po baru → Swal sukses → reload DataTable
```

### 8.2 Tambah Detail ke PO Existing

```
Purchasing expand baris → klik ikon wrench di header tabel detail
    └─► GET /purchasing/{no_po}/edit?jenis={jenis}
        └─► Render view tambahkekurangan → inject ke .viewmodal
            └─► Modal #Modaltambah terbuka

User pilih bahan, isi qty & harga → Simpan
    └─► POST /purchasing (edit!=0 dan edit!=2, misal edit=1)
        ├─► Jika po.cetak >= 1: CALL SP_ArchivePo()
        ├─► INSERT inv_podetail (satu baris)
        └─► UPDATE inv_po (totalexclude, totalppn, totalinclude, GrandTotalPembelian)
```

### 8.3 Close PO

```
Purchasing klik nomor PO (clickable-po)
    └─► Swal tiga pilihan: Edit nomor SO / Close PO / Edit (diskon/ongkir)

Pilih "Close PO"
    └─► Swal konfirmasi dengan input nomor acak (4 digit)
        └─► User masukkan nomor yang benar
            └─► PUT /purchasing/{no_po} { closestatus: 2 }
                ├─► Guard: status != 2 (tidak bisa close ulang)
                ├─► UPDATE inv_po SET status = 2
                └─► LOOP detail yang belum diterima:
                    Bahan::decrement('stok_onpurchase', selisih)
```

### 8.4 Buat Invoice dari LPB

```
Purchasing buka /invoicelpb (type 5 only)
    └─► GET /purchasing/invoice/available-pos → list PO dengan LPB siap invoice
        └─► User pilih PO → GET /purchasing/lpb/by-po/{no_po}
            └─► Response: header LPB + detail item + subtotal per LPB
                └─► User isi no_invoice, tanggal, deadline, nominal

User submit
    └─► POST /invoice-lpb/store
        ├─► Validasi: no_invoice unik, deadline >= tanggal, selected_lpb_ids wajib
        ├─► INSERT invoice_lpb (status='Belum Dibayar')
        └─► UPDATE admin_lpb SET no_invoice=?, status=1 (LPB terkait)
```

### 8.5 Catat Barang Return

```
Purchasing pilih invoice → klik "Catat Barang Bermasalah"
    └─► GET /purchasing/getInvoiceItems/{invoiceId} → list bahan dalam invoice

User isi qty return per item → Submit
    └─► POST /purchasing/updateInvoiceItems
        ├─► LOOP per item dengan qty > 0:
        │   ├─► Jika belum ada header return untuk id_lpb ini:
        │   │   ├─► Generate kode RT{mmdd}{seq3}
        │   │   └─► INSERT admin_lpb_return
        │   └─► INSERT admin_lpb_detail_return
        └─► Commit
```

---

## 9. Frontend / Blade

### 9.1 ViewModal Pattern — Tiga Modal via AJAX Inject

Div `.viewmodal` berfungsi sebagai container injectable untuk modal form:

```javascript
$.ajax({ url: '/purchasing/create', ... })
    .done(response => {
        $('.viewmodal').html(response.data).show();
        $('#exampleModal').modal('show');
    });
```

Tiga trigger berbeda menggunakan pattern yang sama:
- `btnAdd` → form tambah PO baru → `#exampleModal`
- `btndetail` → modal detail summary → `#detailModal`
- `tambahDetail(noPo)` → form tambah detail ke PO → `#Modaltambah`

### 9.2 Expand Row — Dibuat Dinamis via JavaScript

Tidak menggunakan DataTables child row API. Expand row dibuat via `$(this).closest('tr').after(...)`:

```javascript
$('#poKertasTable').on('click', '.btn-detail', function() {
    const noPo = $(this).data('no-po');
    let detailRow = $(`#detail-${noPo}`);

    if (!detailRow.length) {
        // Buat baris baru dengan tabel di dalamnya
        parentRow.after(`<tr id="detail-${noPo}">...</tr>`);
        detailRow = $(`#detail-${noPo}`);
    }

    if (!detailRow.hasClass('loaded')) {
        $.ajax({ url: '/purchasing/' + noPo })  // GET show()
            .done(data => {
                // Populate tbody
                detailRow.addClass('loaded');
            });
    }

    detailRow.collapse('toggle');
});
```

`loaded` class mencegah AJAX berulang jika baris sudah pernah di-load. Tapi data tidak di-refresh — jika ada perubahan setelah expand pertama, data di expand row bisa stale sampai halaman di-refresh.

### 9.3 Close PO — Konfirmasi dengan Nomor Acak

```javascript
let randomNumber = Math.floor(1000 + Math.random() * 9000);
Swal.fire({
    html: `Masukkan nomor berikut: <b>${randomNumber}</b>...`,
    preConfirm: () => {
        if (userInput != randomNumber) {
            Swal.showValidationMessage("Nomor tidak sesuai!");
        }
        return userInput == randomNumber;
    }
});
```

Pattern ini mencegah close PO yang tidak sengaja. Nomor acak 4 digit (1000-9999) di-generate di frontend — tidak ada validasi di server bahwa konfirmasi ini benar-benar dilakukan.

### 9.4 Tiga Mode di `clickable-po` (Klik Nomor PO)

Satu Swal dengan tiga tombol mengarah ke tiga alur berbeda:

| Tombol | Alur |
|---|---|
| `confirmButtonText: "Edit nomor SO"` | Buka `#poModal` untuk update `no_order` + `term` |
| `denyButtonText: "Close PO"` | Swal konfirmasi nomor → PUT closestatus=2 |
| `cancelButtonText: "Edit"` | Buka `#Modaleditdiskon` untuk update diskon/ongkir/inputlabel |

### 9.5 AutoNumeric untuk Field Numerik

```javascript
$('#jumlah_edit').autoNumeric('init', { aSep: ',', mDec: 2 });
$('#harga_edit').autoNumeric('init', { aSep: ',', mDec: 2 });
```

Library `autoNumeric.js` dipakai untuk format input numerik dengan separator koma dan 2 desimal. Saat membaca nilai, dipakai `.autoNumeric('get')` yang mengembalikan string tanpa separator.

---

## 10. Cetak PDF Purchase Order

**Trigger:** Klik ikon print di baris → `POST /cetakpembelian`

**Guard Cetak:**
- Jika `kunci = 1` → tombol cetak disabled (tampil ikon lock)
- Jika `kunci = 0` → tombol cetak aktif

**Proses setelah cetak:**
- `cetak` counter di `inv_po` diincrement (di luar kode yang ditampilkan, kemungkinan di SP atau via observer)
- Cetakan pertama (`cetak = 1`) → footer "Cetakan asli..."
- Cetakan selanjutnya (`cetak > 1`) → footer "Cetakan duplikat ke-{cetak-1}..."

**Edit setelah cetak:**
- Jika PO sudah dicetak (`cetak >= 1`) dan ada edit → `CALL SP_ArchivePo(?)` terlebih dahulu
- `cetak_ulang` di-set ke `'1'`

---

## 11. Status Reference

### `inv_po.status`

| Nilai | Arti | Set oleh |
|---|---|---|
| `0` | Aktif / Buka | Default saat create |
| `1` | Partial (sebagian LPB diterima) | Kemungkinan diupdate modul LPB |
| `2` | Closed | `update()` closestatus=2 |

Warna tombol nomor PO di tabel:
- `status=0` → hijau (`btn-success`)
- `status=1` → kuning (`btn-warning`)
- `status=2` → merah (`btn-danger`)

### `invoice_lpb.status_pembayaran`

| Nilai | Keterangan |
|---|---|
| `'Belum Dibayar'` | Default saat invoice dibuat |
| `'Siap Bayar'` | Siap untuk diproses pembayaran |
| `'Proses Pembayaran'` / `'Proses'` | Sedang diproses |
| `'Dibayar Sebagian'` | Pembayaran parsial |
| `'Lunas'` | Sudah lunas |

Status yang diproteksi (tidak bisa hapus invoice): `Dibayar Sebagian`, `Proses Pembayaran`, `Proses`, `Lunas`.

---

## 12. ID Generation

### `no_po` — via `POModel::generatePONumber($tanggal, $jenis)`

Implementasi di model `POModel` — tidak ditampilkan di controller. Dipanggil dengan tanggal PO dan jenis.

### `id_return` — Format `RT{mmdd}{seq3}`

```php
$todayPrefix = 'RT' . now()->format('md');  // misal RT1210 (12 Oktober)
$lastReturn = DB::table('admin_lpb_return')
    ->where('id_return', 'LIKE', $todayPrefix . '%')
    ->orderBy('id_return', 'desc')
    ->first();

$nextIncrement = $lastReturn ? (int)substr($lastReturn->id_return, 6) + 1 : 1;
$newReturnId = $todayPrefix . str_pad($nextIncrement, 3, '0', STR_PAD_LEFT);
```

Sequence reset setiap hari. Tidak ada lock — potensi race condition jika dua request bersamaan.

---

## 13. Interaksi Antar Tabel

```
[inv_po]
    no_po ──────────────────────► FK → inv_podetail.no_po
    no_po ──────────────────────► FK → admin_lpb.no_po (via modul LPB)
    status ← 2 saat close, disertai decrement stok_onpurchase
    totalexclude, totalppn, totalinclude, GrandTotalPembelian
        ← dihitung ulang saat tambah/hapus detail (mode 1 dan destroy)
    cetak ← diupdate saat cetak PDF (kemungkinan via SP atau observer)
    kunci ← dikontrol dari luar (modul LPB atau manajemen PO)

[inv_podetail]
    no_po ──────────────────────► FK → inv_po.no_po
    id_bahan ───────────────────► FK → bahan.id
    id_permintaan ──────────────► FK → permintaan.id (nullable/0)
    diterima ← diupdate oleh modul LPB saat penerimaan barang

[permintaan]
    realisasi ← diupdate oleh modul LPB saat ada PO yang dikaitkan
    finish ← 1 jika realisasi >= jumlah_order
        UPDATE dilakukan di store() setelah setiap save PO/detail

[bahan]
    stok_onpurchase ← decrement saat close PO (selisih jumlah - diterima)
    stok_onhand ← diupdate oleh modul LPB (bukan modul ini)

[admin_lpb]
    no_invoice ← diisi saat storeInvoice()
    status ← 1 saat diinvoice, 0 saat invoice dihapus
    flag ← 0 reset saat destroyInvoice()

[invoice_lpb]
    no_invoice ──────────────────► FK referensi dari admin_lpb.no_invoice
    status_pembayaran ← diupdate oleh modul pembayaran (di luar controller ini)

[admin_lpb_return]
    id_return ──────────────────► FK → admin_lpb_detail_return.id_return
    id_lpb ─────────────────────► FK → admin_lpb.id_lpb
    no_invoice ─────────────────► FK → invoice_lpb.no_invoice (via no_invoice LPB)

[suppliers]
    id ─────────────────────────► FK → inv_po.id_suplier, invoice_lpb.kode_supplier
```

---

## 14. Error & Edge Cases

### 14.1 Error yang Di-handle Eksplisit

| Skenario | Di mana | Response |
|---|---|---|
| `id_suplier` tidak valid | `store()` edit=0 | `400 + error message` |
| Exception DB umum | `store()`, `destroy()`, `storeInvoice()`, dll. | `rollBack() + 400/500` |
| Detail PO tidak ditemukan | `destroy()` | `404` |
| Header PO tidak ditemukan | `destroy()`, `update()` | `404` / exception |
| PO sudah dikunci | `update()` closestatus=99 | `403` |
| PO sudah closed | `update()` close | `500` |
| Invoice tidak ditemukan | `destroyInvoice()`, `getInvoiceDetail()` | `404` |
| Invoice sudah diproses bayar | `destroyInvoice()` | `403` |
| Header LPB tidak ditemukan saat return | `updateInvoiceItems()` | Exception → rollback + 500 |
| Validasi gagal | `storeInvoice()`, `updateInvoiceItems()` | `422 + errors` |

### 14.2 Edge Cases yang Perlu Diperhatikan

**1. Bug kalkulasi di `destroy()` — diskon dikurangi dua kali**

```php
// Di destroy():
$newtotalinclude = $newtotalexclude + $newtotalppn;
// ... newtotalppn sudah dihitung dari (newtotalexclude - diskon)

$pokertas->update([
    'GrandTotalPembelian' => $newtotalinclude - $pokertas->diskon + $pokertas->ongkir,
    // ↑ diskon dikurangi lagi padahal sudah ikut dalam newtotalppn
]);
```

Formula ini inkonsisten dengan `update()` closestatus=99 yang menggunakan:
```php
$GrandTotal = $newinclude + $ongkiredit;  // tanpa kurangi diskon lagi
```

**2. Nama penandatangan PDF hardcoded**

`Roy Mulyono` di `cetakpembelian()` tidak bisa diubah tanpa edit kode. Perlu diambil dari DB (kemungkinan user type tertentu).

**3. Nama file PDF hardcoded `'nama_file.pdf'`**

`$pdf->Output('nama_file.pdf', 'I')` — nama file tidak menyertakan nomor PO.

**4. Expand row data tidak refresh**

Data expand row di-cache via class `.loaded`. Jika ada perubahan setelah pertama kali di-expand, user perlu refresh halaman untuk melihat data terbaru.

**5. Konfirmasi close PO hanya di frontend**

Nomor acak untuk konfirmasi close PO divalidasi hanya di JavaScript. Server tidak memverifikasi bahwa konfirmasi ini dilakukan — request PUT dengan `closestatus=2` bisa dikirim langsung via API tanpa konfirmasi.

**6. `getDatadetail()` hanya response jika `$request->ajax()`**

Jika diakses langsung via browser, `return response()->json(...)` di dalam `if ($request->ajax())` tidak dieksekusi — method mengembalikan null.

**7. Race condition di generate `id_return`**

`RT{mmdd}{seq3}` tidak menggunakan lock. Dua request bersamaan bisa menghasilkan kode return yang sama.

**8. `stok_onpurchase` tidak diperbarui saat hapus detail PO**

`destroy()` mengurangi total header tapi tidak mengurangi `bahan.stok_onpurchase`. Jika detail dihapus sebelum PO di-close, stok `onpurchase` tidak akurat.

---

*Dokumentasi ini dibuat berdasarkan kode per Juni 2026. Update dokumentasi ini setiap kali ada perubahan pada `PurchasingController.php`, model `POModel`/`DetailPOModel`, routes purchasing, atau struktur tabel `inv_po`, `inv_podetail`, `invoice_lpb`, dan tabel return.*