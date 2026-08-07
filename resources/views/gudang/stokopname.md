# Dokumentasi Engineer — Modul Stok Opname Non-Kertas

---

## Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Perbedaan dengan Stok Opname Kertas](#2-perbedaan-dengan-stok-opname-kertas)
3. [Arsitektur & Tabel Database](#3-arsitektur--tabel-database)
4. [Routes](#4-routes)
5. [Controller Methods](#5-controller-methods)
6. [Alur Data End-to-End](#6-alur-data-end-to-end)
7. [Frontend / Blade](#7-frontend--blade)
8. [Access Control (User Type)](#8-access-control-user-type)
9. [Flag & Status Reference](#9-flag--status-reference)
10. [Kalkulasi Harga & Kerugian](#10-kalkulasi-harga--kerugian)
11. [Interaksi Antar Tabel](#11-interaksi-antar-tabel)
12. [Error & Edge Cases](#12-error--edge-cases)

---

## 1. Gambaran Umum

Modul ini mengelola stok opname untuk bahan non-kertas (tinta, lem, bahan kimia, dll). Alurnya:

```
Gudang (type 14) buat sesi opname baru
    └─► Sistem snapshot stok saat ini dari tabel bahan
        └─► Gudang isi stok fisik (stok_real) per item
            └─► Inventory (type 11) ubah harga jika perlu
                └─► Inventory kirim pengajuan (flag 0 → 1)
                    └─► Manager/API setujui (flag 1 → 2)
                        └─► Sistem update stok_onhand di tabel bahan
                            └─► Sistem catat ke stock_adjustments
```

Satu sesi opname mencakup **semua bahan** dengan `jenis IN (0,1)` dan `kategori != 17` yang tidak archived. Tidak bisa memilih bahan tertentu saja.

---

## 2. Perbedaan dengan Stok Opname Kertas

| Aspek | Non-Kertas (modul ini) | Kertas (modul lain) |
|---|---|---|
| Tabel master | `bahan` | `admin_kertas` |
| Tabel opname | `stok_opname` + `stok_opname_detail` | `stok_opname` + `stok_opname_detail` |
| Kode format | `STO-NK{yy}{seq2}` | `STO{yy}{seq2}` |
| Yang bisa edit | Gudang edit `stok_real`, Inventory edit `harga` | Gudang edit `real_semarang` + `real_boja` |
| Saat finalize | Update `bahan.stok_onhand` + insert `stock_adjustments` | Update `admin_kertas` (per lokasi) + buat NPK/LPB |
| Who finalizes | API endpoint (dipanggil dari `GudangController` lokal — di-route sebagai `finalizeStockOpname`) | `completeStockOpname()` di controller kertas |
| Controller | `GudangController` di sistem ini (bukan API Inventory eksternal) | `GudangController` juga tapi method berbeda |

> ⚠️ Walaupun tabel `stok_opname` dan `stok_opname_detail` namanya sama antara kertas dan non-kertas, **isinya berbeda** — kolom `stok_opname_detail` untuk non-kertas pakai `id_bahan`, sedangkan untuk kertas pakai `id_kertas` dengan kolom terpisah per lokasi (`sistem_semarang`, `real_semarang`, dll).

---

## 3. Arsitektur & Tabel Database

### `stok_opname` — Header Sesi Opname

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | int | PK auto increment |
| `kode` | varchar | Format `STO-NK{yy}{seq2}` misal `STO-NK2501` |
| `tanggal` | date | Tanggal sesi opname |
| `user_id` | int | FK → user yang membuat sesi |
| `flag` | tinyint | Lihat [Flag Reference](#9-flag--status-reference) |
| `created_at`, `updated_at` | timestamp | - |

### `stok_opname_detail` — Detail Item per Sesi

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | int | PK |
| `kode` | varchar | FK → `stok_opname.kode` |
| `id_bahan` | int | FK → `bahan.id` |
| `id_kategori` | int | FK → `kategori_bahan.katid` (snapshot saat buat sesi) |
| `stok_sistem` | decimal | Snapshot `bahan.stok_onhand` saat sesi dibuat — **tidak berubah** |
| `stok_real` | decimal | Hasil hitung fisik — bisa diedit oleh Gudang (type 14) |
| `harga` | decimal | Harga per satuan — bisa diedit oleh Inventory (type 11) |
| `selisih` | decimal | `stok_real - stok_sistem` (auto-kalkulasi) |
| `kerugian` | decimal | `max(0, (stok_sistem - stok_real) * harga)` (auto-kalkulasi) |

### `bahan` — Master Bahan Non-Kertas

| Kolom | Keterangan |
|---|---|
| `id` | PK |
| `nama` | Nama bahan |
| `satuan` | Satuan (pcs, kg, liter, dll) |
| `stok_onhand` | Stok fisik saat ini ← diupdate saat finalize |
| `harga` | Harga default ← dipakai sebagai fallback harga di sesi opname |
| `jenis` | `0` atau `1` → diikutkan opname; selain itu tidak |
| `kategori` | FK → `kategori_bahan.katid`; `17` dikecualikan dari opname |

### `stock_adjustments` — Audit Trail Perubahan Stok

Dibuat saat finalize untuk setiap bahan yang punya `selisih != 0`.

| Kolom | Keterangan |
|---|---|
| `kode` | Format `'OPNAME ' + kode_opname` |
| `tanggal` | Waktu finalize |
| `keterangan` | `'Hasil Opname ' + kode_opname` |
| `operator` | Nama user yang melakukan finalize |
| `id_barang` | FK → `bahan.id` |
| `nama_barang` | Nama bahan (snapshot) |
| `jumlah` | `selisih` (bisa negatif jika stok berkurang) |
| `satuan` | Satuan bahan (snapshot) |

### Tabel Lookup (Read-only di modul ini)

| Tabel | Dipakai untuk |
|---|---|
| `kategori_bahan` | Nama kategori di tabel detail |
| `admin_lpb_detail` | Ambil 5 harga terakhir untuk kalkulasi harga rata-rata saat buat sesi |

---

## 4. Routes

| Method | URI | Controller Method | Nama Route | Keterangan |
|---|---|---|---|---|
| GET | `/stokopname` | `stokOpname()` | `gudang.stokopname` | View halaman opname |
| GET | `/gudang/generateStockOpnameCode` | `generateStockOpnameCode()` | `gudang.generateStockOpnameCode` | Generate kode otomatis |
| GET | `/gudang/getStockOpnameData` | `getStockOpnameData()` | `gudang.getStockOpnameData` | DataTables server-side |
| POST | `/gudang/storeStockOpname` | `storeStockOpname()` | `gudang.storeStockOpname` | Buat sesi opname baru |
| GET | `/gudang/getDetailStockOpname` | `getDetailStockOpname()` | `gudang.getDetailStockOpname` | Detail item per sesi (DataTables) |
| GET | `/gudang/getStockOpname/{id}` | `getStockOpnameById()` | `gudang.getStockOpname` | Ambil data satu sesi (untuk modal edit) |
| POST | `/gudang/updateStockOpname/{id}` | `updateStockOpname()` | `gudang.updateStockOpname` | Update tanggal sesi |
| DELETE | `/gudang/deleteStockOpname/{id}` | `deleteStockOpname()` | `gudang.deleteStockOpname` | Hapus sesi + semua detail |
| POST | `/gudang/completeStockOpname/{id}` | `completeStockOpname()` | `gudang.completeStockOpname` | Kirim pengajuan (flag 0 → 1) |
| POST | `/gudang/updateDetailStockOpname` | `updateDetailStockOpname()` | `gudang.updateDetailStockOpname` | Edit stok_real atau harga inline |
| GET | `/gudang/export-excel/{id}` | `exportStockOpnameDetail()` | `gudang.exportStockOpnameDetail` | Export detail ke Excel |
| POST | `/gudang/finalizeStockOpname/{id}` | `finalizeStockOpname()` | `gudang.finalizeStockOpname` | Setujui & update stok (flag 1 → 2) |

---

## 5. Controller Methods

### 5.1 `stokOpname()`

Hanya mengembalikan view. Data user diambil dari `session('user_data')` (bukan `Auth::user()`).

> ⚠️ Sistem ini menggunakan `session('user_data')` alih-alih Laravel Auth standar. Pastikan session ini selalu terisi — jika session habis, `$userType` bisa null dan semua guard user type akan gagal.

---

### 5.2 `generateStockOpnameCode()`

**Format kode:** `STO-NK{yy}{seq2}` — contoh `STO-NK2501`, `STO-NK2502`

```php
$lastOpname = DB::table('stok_opname')
    ->whereYear('tanggal', $currentYear)
    ->orderBy('kode', 'desc')
    ->first();

$lastNumber = (int) substr($lastOpname->kode, -2);  // 2 digit terakhir
$newNumber  = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
$newCode    = 'STO-NK' . $shortYear . $newNumber;
```

Kode ini hanya di-generate untuk modal create. Tidak menggunakan lock — ada potensi race condition jika dua user buka modal bersamaan.

Jika kode sudah dipakai (race condition), `storeStockOpname()` memiliki mekanisme `while` loop via `incrementKode()` untuk mencoba kode berikutnya.

---

### 5.3 `getStockOpnameData(Request $request)`

DataTables server-side untuk tabel utama. Query tanpa filter — menampilkan **semua sesi** diurutkan `updated_at DESC`.

Tombol di kolom action dikontrol kondisi:

```php
if ($row->flag == 0 && $userType == 14) {
    // Tampilkan tombol Edit + Hapus
}
// Export Excel selalu tampil untuk semua
```

> ⚠️ `userType` diambil dari `session('user_data')` di dalam callback `addColumn`. Jika session habis di tengah request DataTables (edge case), `$userType` akan null dan tombol edit/hapus tidak akan muncul.

---

### 5.4 `storeStockOpname(Request $request)`

**Validasi:**
- `kode`: unik di tabel `stok_opname`
- `tanggal`: format date
- `userType` harus `14` (Gudang) — validasi tambahan di luar validator

**Alur pembuatan detail:**

```
1. INSERT header ke stok_opname

2. SELECT bahan WHERE jenis IN (0,1) AND kategori != 17

3. UNTUK SETIAP bahan:
   a. Ambil 5 harga terakhir dari admin_lpb_detail WHERE id_bahan = ?
      ORDER BY id DESC LIMIT 5
      → Hitung rata-rata (avg())
      → Jika tidak ada harga di LPB → pakai bahan.harga sebagai fallback

   b. Snapshot stok_onhand saat ini → stok_sistem = stok_real = stok_onhand
   c. selisih = 0, kerugian = 0 (karena stok_real = stok_sistem saat awal)

4. BULK INSERT semua detail sekaligus (satu query)
```

> ⚠️ Snapshot dibuat saat sesi dibuat. Jika ada transaksi bahan (masuk/keluar) antara sesi dibuat dan sesi diselesaikan, `stok_sistem` di detail sudah tidak mencerminkan kondisi terkini. Ini adalah trade-off yang umum di opname — stok_sistem adalah "kondisi saat opname dimulai".

---

### 5.5 `getDetailStockOpname(Request $request)`

**Query param:** `kode`

DataTables manual (tidak pakai package Yajra secara langsung) dengan implementasi pagination, search, dan sort sendiri.

**Join yang dipakai:**
```sql
stok_opname_detail
JOIN bahan ON id_bahan = bahan.id
JOIN kategori_bahan ON bahan.kategori = kategori_bahan.katid
WHERE stok_opname_detail.kode = ?
```

**Search:** Hanya pada `bahan.nama` dan `kategori_bahan.katnama`.

**Sort:** Menggunakan `columnsMap` array untuk memetakan nama kolom frontend ke kolom DB:

```php
$columnsMap = [
    'id_detail'     => 'stok_opname_detail.id',
    'nama_bahan'    => 'bahan.nama',
    'nama_kategori' => 'kategori_bahan.katnama',
    'harga'         => 'stok_opname_detail.harga',
    'stok_sistem'   => 'stok_opname_detail.stok_sistem',
    'stok_real'     => 'stok_opname_detail.stok_real',
    'selisih'       => 'stok_opname_detail.selisih',
    'kerugian'      => 'stok_opname_detail.kerugian',
];
```

---

### 5.6 `updateDetailStockOpname(Request $request)`

**Validasi:**
- `id_detail`: harus ada di `stok_opname_detail`
- `column`: hanya boleh `'stok_real'` atau `'harga'` (whitelist)
- `value`: numeric, min 0

**Kalkulasi otomatis setelah update:**

```php
$stok_real_baru = $request->column == 'stok_real' ? $request->value : $detail->stok_real;
$harga_baru     = $request->column == 'harga'     ? $request->value : $detail->harga;

$selisih  = $stok_real_baru - $detail->stok_sistem;
$kerugian = max(0, ($detail->stok_sistem - $stok_real_baru) * $harga_baru);
```

Kolom `selisih` dan `kerugian` selalu dihitung ulang dan disimpan ke DB setiap kali ada perubahan.

---

### 5.7 `completeStockOpname($id)`

Mengubah `flag = 1` (Menunggu Persetujuan). Tidak ada validasi kondisi `flag` sebelumnya — bisa mengubah flag dari `2` ke `1` jika dipanggil ulang.

```sql
UPDATE stok_opname SET flag = 1, updated_at = NOW() WHERE id = ?
```

> ⚠️ Tidak ada guard `if flag == 0`. Artinya sesi yang sudah disetujui (`flag=2`) bisa dikembalikan ke "Menunggu Persetujuan" (`flag=1`) jika endpoint ini dipanggil lagi.

---

### 5.8 `finalizeStockOpname(Request $request, $id)`

Endpoint persetujuan final. Dipanggil oleh manager/admin.

**Validasi pre-condition:**
```php
if ($opname->flag == 2) → 400 "sudah pernah disetujui"
if ($opname->flag == 0) → 400 "belum selesai dikerjakan"
// Hanya flag == 1 yang bisa diproses
```

**DB writes per item detail (hanya jika `selisih != 0`):**

```sql
-- 1. Update stok_onhand bahan
UPDATE bahan
SET stok_onhand = stok_onhand + {selisih}
WHERE id = {id_bahan}

-- 2. Catat ke audit trail
INSERT INTO stock_adjustments (kode, tanggal, keterangan, operator, id_barang, nama_barang, jumlah, satuan, ...)
VALUES ('OPNAME STO-NK2501', NOW(), 'Hasil Opname STO-NK2501', 'Nama User', ...)
```

`selisih` bisa negatif jika stok fisik lebih sedikit dari sistem → `stok_onhand` akan berkurang.

**Setelah semua item diproses:**
```sql
UPDATE stok_opname SET flag = 2, updated_at = NOW() WHERE id = ?
```

**Nama operator:** Diambil dari `$request->user()->name`. Jika tidak ada auth user (dipanggil via API tanpa auth), fallback ke `'API System'`.

---

### 5.9 `deleteStockOpname(Request $request, $id)`

Hard delete header + semua detail. Hanya tersedia jika `flag == 0` (validasi ada di frontend, tidak ada guard di server).

```sql
DELETE FROM stok_opname_detail WHERE kode = ?
DELETE FROM stok_opname WHERE id = ?
```

---

### 5.10 `updateStockOpname(Request $request, $id)`

Hanya update kolom `tanggal`. Tidak ada perubahan lain.

```sql
UPDATE stok_opname SET tanggal = ?, updated_at = NOW() WHERE id = ?
```

---

## 6. Alur Data End-to-End

### 6.1 Alur Lengkap dari Buat hingga Setujui

```
Gudang (type 14) klik "Buat Sesi Opname Baru"
    └─► GET /gudang/generateStockOpnameCode
        └─► Response: { kode: 'STO-NK2501' } → isi field kode di modal (readonly)

Gudang isi tanggal → Submit
    └─► POST /gudang/storeStockOpname { kode, tanggal }
        ├─► Validasi: kode unik, tanggal valid, userType == 14
        ├─► INSERT stok_opname
        ├─► SELECT bahan WHERE jenis IN (0,1) AND kategori != 17
        ├─► LOOP per bahan:
        │   ├─► SELECT avg(harga) FROM admin_lpb_detail WHERE id_bahan = ? LIMIT 5
        │   └─► Prepare detail row (stok_sistem = stok_real = stok_onhand, selisih=0)
        └─► BULK INSERT stok_opname_detail
            └─► Swal sukses → reload DataTable

Gudang expand baris (btn-lihat-detail)
    └─► GET /gudang/getDetailStockOpname?kode=STO-NK2501
        └─► DataTables render tabel detail di child row
            ├─► Kolom stok_real: editable (klik untuk edit) — hanya jika flag=0 dan userType=14
            └─► Kolom harga: editable — hanya jika flag=0 dan userType=11

Gudang klik sel stok_real → input muncul → Enter/blur
    └─► POST /gudang/updateDetailStockOpname
        { id_detail, column: 'stok_real', value: 1250 }
        ├─► Hitung: selisih = 1250 - stok_sistem
        ├─► Hitung: kerugian = max(0, (stok_sistem - 1250) * harga)
        └─► UPDATE stok_opname_detail SET stok_real, selisih, kerugian
            └─► DataTables detail reload (null, false)

Inventory (type 11) edit harga jika perlu
    └─► POST /gudang/updateDetailStockOpname { column: 'harga', value: 85000 }
        ├─► Hitung ulang kerugian dengan harga baru
        └─► UPDATE stok_opname_detail SET harga, selisih, kerugian

Inventory klik "Kirim Pengajuan"
    └─► POST /gudang/completeStockOpname/{id}
        └─► UPDATE stok_opname SET flag = 1
            └─► Badge berubah ke "Menunggu Persetujuan"

Manager setujui (dari halaman persetujuan opname)
    └─► POST /gudang/finalizeStockOpname/{id}
        ├─► Validasi: flag harus == 1
        ├─► SELECT stok_opname_detail WHERE kode = ?
        ├─► LOOP per item WHERE selisih != 0:
        │   ├─► UPDATE bahan SET stok_onhand += selisih
        │   └─► INSERT stock_adjustments
        └─► UPDATE stok_opname SET flag = 2
            └─► Selesai — stok bahan sudah disesuaikan
```

---

## 7. Frontend / Blade

### 7.1 Session vs Auth

Blade mengambil userType dari session, **bukan** dari Laravel Auth:

```php
// Blade PHP section
$userData = session('user_data');
$userType = $userData['type'] ?? null;

// Blade Blade section (inject ke JavaScript)
const userType = {{ $userType }};
```

Jika session expired, `$userType` akan null → `const userType = ;` yang menghasilkan JavaScript error. Semua kondisi berbasis `userType` di frontend akan gagal.

### 7.2 Expandable Child Row — DataTable di dalam DataTable

```javascript
$('#stockOpnameTable tbody').on('click', '.btn-lihat-detail', function() {
    var row = table.row(tr);

    if (row.child.isShown()) {
        row.child.hide();
    } else {
        // Inject HTML tabel detail ke child row
        row.child(detailTableHtml).show();

        // Init DataTable baru di dalam child row
        var detailTable = $(`#detailTable-${kode}`).DataTable({
            ajax: { url: "...", data: { kode: kode } },
            columns: detailColumns  // kolom berbeda per userType
        });
    }
});
```

Setiap kali baris di-expand, DataTable baru di-init. **Tidak ada destroy** jika baris di-collapse lalu di-expand lagi — tapi karena `row.child.hide()` hanya menyembunyikan DOM, bukan menghapusnya, DataTable yang sudah ada akan tetap aktif. DataTable baru hanya dibuat saat pertama kali expand.

### 7.3 Inline Edit — Editable Cell

Kolom `stok_real` (Gudang) dan `harga` (Inventory) menggunakan pola editable cell:

```javascript
$(`#detailTable-${kode} tbody`).on('click', '.editable-container', function() {
    var valueSpan = container.find('.editable-value');

    // Guard: jangan buat input baru jika sudah ada input
    if (valueSpan.find('input').length) return;

    // Replace span teks dengan input
    var input = $('<input type="number" ...>');
    valueSpan.html(input);
    input.focus().select();

    // Save on blur atau Enter
    input.on('blur keydown', function(e) {
        if (e.type === 'blur' || (e.key === 'Enter')) {
            // Jika nilai tidak berubah → restore tanpa AJAX
            if (newValue === originalValue) {
                valueSpan.text(originalValue);
                return;
            }
            // AJAX POST ke updateDetailStockOpname
            $.ajax({ url: "...", ... });
        }
    });
});
```

Kolom yang editable dikontrol oleh kombinasi `flag` dan `userType`:

| Kolom | Editable oleh | Kondisi |
|---|---|---|
| `stok_real` | Gudang (type 14) | `flag == 0` |
| `harga` | Inventory (type 11) | `flag == 0` |
| Kedua kolom | Siapapun | `flag != 0` → tampil text saja, tidak bisa diedit |

### 7.4 Kolom Detail Berbeda per UserType

DataTable detail di child row membangun `detailColumns` array secara dinamis:

```javascript
// Base columns (semua user type)
var detailColumns = [id_detail, nama_bahan, nama_kategori]

// Tambahan untuk type 11 (Inventory)
if (userType == 11) {
    detailColumns.push(harga_editable)
}

// stok_sistem, stok_real (editable jika gudang), selisih
detailColumns.push(stok_sistem, stok_real, selisih)

// Total kerugian hanya untuk type 11
if (userType == 11) {
    detailColumns.push(kerugian_currency)
}
```

### 7.5 Modal Create/Edit — Satu Modal, Dua Mode

Modal `#addOpnameModal` dipakai untuk create dan edit:

```javascript
$('#addOpnameModal').on('show.bs.modal', function(event) {
    var opnameId = $(event.relatedTarget).data('id');

    if (opnameId) {
        // MODE EDIT: load data existing
        $.get('/gudang/getStockOpname/' + opnameId, ...)
    } else {
        // MODE CREATE: generate kode baru
        $.ajax({ url: route('gudang.generateStockOpnameCode'), ... })
    }
});
```

Form submit menentukan URL berdasarkan ada/tidaknya `opname_id`:

```javascript
var url = id
    ? '/gudang/updateStockOpname/' + id   // POST update
    : route('gudang.storeStockOpname');    // POST create
```

---

## 8. Access Control (User Type)

| User Type | Bisa Buat Sesi | Edit Stok Fisik | Edit Harga | Kirim Pengajuan | Setujui |
|---|---|---|---|---|---|
| `14` (Gudang) | ✅ | ✅ | ❌ | ❌ | ❌ |
| `11` (Inventory) | ❌ | ❌ | ✅ | ✅ | ❌ |
| Manager/Admin | ❌ | ❌ | ❌ | ❌ | ✅ (via halaman persetujuan) |

**Kontrol di Controller:**

- `storeStockOpname()`: cek `session('user_data')['type'] == 14` → 403 jika tidak
- `finalizeStockOpname()`: tidak ada cek user type — siapapun bisa memanggil endpoint ini jika tahu URL-nya

**Kontrol di Frontend:**

- Tombol "Buat Sesi Opname" hanya tampil jika `$userType == 14` (Blade `@if`)
- Tombol Edit/Hapus di tabel hanya di-render jika `flag==0 AND userType==14`
- Tombol "Kirim Pengajuan" di child row hanya tampil jika `userType==11 AND flag==0`
- Kolom editable di-render berbeda per `userType`

---

## 9. Flag & Status Reference

### `stok_opname.flag`

| Nilai | Label di Badge | Arti | Siapa yang Set |
|---|---|---|---|
| `0` | "Dalam Pengerjaan" | Sesi aktif, bisa diedit | `storeStockOpname()` saat insert |
| `1` | "Menunggu Persetujuan" | Dikirim, tidak bisa diedit | `completeStockOpname()` |
| `2` | "Disetujui" | Final, stok sudah diupdate | `finalizeStockOpname()` |

**Transisi yang valid:**
```
0 → 1  (via completeStockOpname)
1 → 2  (via finalizeStockOpname — ada guard flag==0 dan flag==2)
0 → 1 → 0  ⚠️ TIDAK DICEGAH (completeStockOpname bisa dipanggil ulang)
2 → 1  ⚠️ TIDAK DICEGAH (completeStockOpname tidak mengecek flag sebelumnya)
```

---

## 10. Kalkulasi Harga & Kerugian

### Harga Awal Saat Buat Sesi

```php
// Ambil rata-rata 5 harga terakhir dari LPB
$lpbPrices = DB::table('admin_lpb_detail')
    ->where('id_bahan', $bahan->id)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->pluck('harga');

$price = $lpbPrices->isNotEmpty()
    ? $lpbPrices->avg()      // rata-rata 5 harga terakhir
    : $bahan->harga;         // fallback ke harga master bahan
```

Ini adalah **harga estimasi awal**. Inventory bisa mengubahnya saat pengisian opname.

### Kalkulasi Selisih & Kerugian

```
selisih  = stok_real - stok_sistem
           positif → stok lebih dari sistem (keuntungan)
           negatif → stok kurang dari sistem (kerugian)

kerugian = max(0, (stok_sistem - stok_real) * harga)
           Hanya dihitung jika stok_real < stok_sistem
           Jika stok_real >= stok_sistem → kerugian = 0
```

Kalkulasi ini dijalankan **setiap kali** `updateDetailStockOpname()` dipanggil, baik untuk perubahan `stok_real` maupun `harga`.

### Update Stok saat Finalize

```
bahan.stok_onhand = bahan.stok_onhand + selisih

Contoh:
  stok_sistem = 100, stok_real = 85 → selisih = -15
  stok_onhand baru = stok_onhand_lama + (-15) = stok_onhand_lama - 15
```

---

## 11. Interaksi Antar Tabel

```
[bahan]
    id ───────────────────────────► FK → stok_opname_detail.id_bahan
    stok_onhand ← diupdate saat finalizeStockOpname() SET += selisih
    harga ← dipakai sebagai fallback harga di storeStockOpname() jika tidak ada riwayat LPB

[kategori_bahan]
    katid ────────────────────────► FK → bahan.kategori
    ← hanya dibaca untuk tampilan nama kategori di detail

[stok_opname]
    kode ─────────────────────────► FK → stok_opname_detail.kode
    flag ← 0 saat create
    flag ← 1 saat completeStockOpname()
    flag ← 2 saat finalizeStockOpname()

[stok_opname_detail]
    id_bahan ─────────────────────► FK → bahan.id
    id_kategori ──────────────────► FK → kategori_bahan.katid (snapshot saat insert)
    stok_sistem ← snapshot bahan.stok_onhand saat sesi dibuat (immutable setelah itu)
    stok_real ← diedit oleh Gudang via updateDetailStockOpname()
    harga ← diedit oleh Inventory via updateDetailStockOpname()
    selisih, kerugian ← auto-kalkulasi setiap kali update

[admin_lpb_detail]
    id_bahan ─────────────────────► dipakai saat storeStockOpname() untuk ambil avg harga
    ← hanya dibaca, tidak diupdate

[stock_adjustments]
    ← INSERT oleh finalizeStockOpname() untuk setiap item yang punya selisih != 0
    id_barang ───────────────────► FK → bahan.id
```

---

## 12. Error & Edge Cases

### 12.1 Error yang Di-handle Eksplisit

| Skenario | Di mana | Response |
|---|---|---|
| Kode duplikat saat create | `storeStockOpname()` | Validator gagal → `success:false` |
| Kode collision (race) | `storeStockOpname()` | While loop `incrementKode()` sampai kode unik |
| User bukan type 14 saat create | `storeStockOpname()` | `403 + message` |
| ID detail tidak ditemukan | `updateDetailStockOpname()` | `404` |
| Kolom tidak di whitelist | `updateDetailStockOpname()` | `422 ValidationException` |
| ID opname tidak ditemukan | `getStockOpnameById()`, `finalizeStockOpname()`, `deleteStockOpname()` | `404` |
| Opname sudah disetujui (flag=2) | `finalizeStockOpname()` | `400` |
| Opname belum selesai (flag=0) | `finalizeStockOpname()` | `400` |
| DB error umum | semua method write | `rollBack()` + `response 500` |

### 12.2 Edge Cases yang Perlu Diperhatikan

**1. `completeStockOpname()` tidak mengecek flag sebelumnya**

Bisa dipanggil berulang kali — termasuk pada sesi yang sudah `flag=2`. Ini akan memundurkan status dari "Disetujui" ke "Menunggu Persetujuan", yang memungkinkan sesi yang sudah final untuk di-finalize ulang. Perlu ditambahkan guard `if ($opname->flag != 0) return 400`.

**2. `finalizeStockOpname()` tidak ada cek user type**

Siapapun dengan akses POST ke endpoint ini bisa menyetujui opname. Otentikasi tergantung middleware route, tapi tidak ada validasi `userType` di dalam method.

**3. Session-based auth bisa null jika session expired**

Semua method yang membaca `session('user_data')` akan menghasilkan perilaku tak terduga jika session habis. Di controller, `storeStockOpname()` sudah menghandle dengan guard eksplisit. Tapi `getStockOpnameData()` dan beberapa method lain bergantung pada session tanpa guard — hasilnya bisa berupa tampilan yang salah atau tombol yang tidak muncul.

**4. Stok_sistem adalah snapshot immutable — tidak sinkron jika ada transaksi bahan**

Setelah sesi dibuat, `stok_sistem` tidak berubah. Jika ada penerimaan atau pengeluaran bahan selama opname berlangsung, `stok_sistem` yang tercatat tidak mencerminkan kondisi "real-time". Selisih yang dihitung bisa tidak akurat jika ada aktivitas bahan di tengah proses opname.

**5. Bulk insert tanpa batching**

```php
DB::table('stok_opname_detail')->insert($detailData);
```

Jika jumlah bahan sangat banyak (misalnya ribuan item), satu bulk insert bisa melebihi batas `max_allowed_packet` MySQL atau timeout. Tidak ada batching (chunk insert).

**6. Tidak ada mekanisme unlock**

Setelah `flag=1` (Menunggu Persetujuan), tidak ada route untuk mengembalikan ke `flag=0` kecuali memanggil `completeStockOpname()` yang memang tidak ada guardnya. Ini berarti jika Inventory salah kirim pengajuan terlalu cepat, tidak ada tombol "batalkan pengajuan" yang tersedia.

**7. Export Excel tersedia di semua status**

Tombol export di kolom action selalu tampil tanpa batasan `flag` atau `userType`. Ini by design (semua orang boleh export), tapi perlu dicatat jika ada kebutuhan membatasi akses di kemudian hari.

---

*Dokumentasi ini dibuat berdasarkan kode per Juni 2026. Update dokumentasi ini setiap kali ada perubahan pada method stok opname di `GudangController.php`, routes opname, atau struktur tabel `stok_opname`, `stok_opname_detail`, atau `stock_adjustments`.*