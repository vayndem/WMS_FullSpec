# Dokumentasi Engineer — Modul Afval (Penjualan Limbah/Sisa Produksi)

---

## Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Arsitektur & Tabel Database](#2-arsitektur--tabel-database)
3. [Routes](#3-routes)
4. [Controller Methods](#4-controller-methods)
5. [Alur Data End-to-End](#5-alur-data-end-to-end)
6. [Frontend / Blade](#6-frontend--blade)
7. [Status Reference](#7-status-reference)
8. [ID Generation](#8-id-generation)
9. [Interaksi Antar Tabel](#9-interaksi-antar-tabel)
10. [Error & Edge Cases](#10-error--edge-cases)

---

## 1. Gambaran Umum

Modul Afval mencatat transaksi **penjualan limbah/sisa produksi** (kertas afval, laminasi, kardus, dll) kepada pembeli eksternal. Setiap transaksi terdiri dari satu header (siapa pembeli, kapan, alamat) dan satu atau lebih baris detail (tipe afval, berat, harga satuan).

Alur sederhana:

```
User buat transaksi afval baru (status: "waiting")
    └─► Transaksi tersimpan, menunggu proses faktur
        └─► Setelah difakturkan → status diubah ke "done faktur"
```

Modul ini tidak terhubung ke stok kertas (`admin_kertas`) maupun modul produksi. Berdiri sendiri sebagai pencatatan penjualan limbah.

---

## 2. Arsitektur & Tabel Database

### `afval` — Header Transaksi

| Kolom | Tipe | Keterangan |
|---|---|---|
| `kode_afval` | varchar | PK, format `AF{dd}{mm}{seq3}` misal `AF10062501` |
| `nama` | varchar | Nama pembeli |
| `alamat` | varchar | Alamat pembeli |
| `tanggal` | date | Tanggal transaksi |
| `notes` | text | Catatan tambahan, nullable |
| `status_faktur` | varchar | `'waiting'` atau `'done faktur'` |

### `afval_detail` — Detail Item per Transaksi

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | int | PK auto increment |
| `kode_afval` | varchar | FK → `afval.kode_afval` |
| `tipe` | varchar | Jenis afval — lihat [Tipe Afval yang Tersedia](#tipe-afval-yang-tersedia) |
| `berat` | decimal | Berat dalam KG (presisi 4 desimal) |
| `harga_satuan` | decimal | Harga per KG (presisi 4 desimal) |

### Tipe Afval yang Tersedia

Hardcoded di dropdown form (bukan dari tabel master):

| Label di Dropdown | Value yang Disimpan |
|---|---|
| Afval Kertas | `Afval Kertas` |
| Afval Laminasi | `Afval Laminasi` |
| Afval Kertas Coklat | `Afval Campuran` |
| Afval Kertas Kardus | `Afval Campuran` |
| Afval Campuran | `Afval Campuran` |

> ⚠️ Tiga pilihan berbeda (Coklat, Kardus, Campuran) menghasilkan `tipe = 'Afval Campuran'` yang sama. Setelah tersimpan, tidak bisa dibedakan dari mana asalnya. Ini tampaknya by design — ketiganya diperlakukan sama dalam pencatatan.

### Model Eloquent

| Model | Tabel | Relasi |
|---|---|---|
| `Afval` | `afval` | `hasMany(AfvalDetail, 'kode_afval', 'kode_afval')` sebagai `details` |
| `AfvalDetail` | `afval_detail` | `belongsTo(Afval, 'kode_afval', 'kode_afval')` |

---

## 3. Routes

| Method | URI | Controller Method | Nama Route | Keterangan |
|---|---|---|---|---|
| GET | `/afval` | `index()` | `afval.index` | View halaman utama |
| GET | `/readafval` | `readafval()` | `readafval` | DataTables server-side |
| POST | `/createafval` | `createafval()` | `createafval` | Buat transaksi baru |
| GET | `/afval/details/{kode_afval}` | `getDetailsafval()` | `afval.details` | Detail item per transaksi (expand row) |
| GET | `/afval/{afval}` | `detailAfvalid()` | `afval.show` | Detail lengkap satu transaksi (resource) |
| GET | `/afval-waiting` | `getafvalid()` | _(none)_ | List kode afval status waiting (dipakai modul faktur) |
| PUT/PATCH | `/afval/{kode_afval}/status` | `updateStatusAfval()` | _(none)_ | Ubah status ke "done faktur" |

> Route `afval.details` dan `afval.show` (resource) memiliki URI yang mirip (`/afval/details/{id}` vs `/afval/{afval}`). Laravel mencocokkan berdasarkan urutan definisi. Pastikan route `/afval/details/{kode_afval}` didefinisikan **sebelum** `Route::resource('afval', ...)` agar tidak tertangkap sebagai `{afval}` parameter resource.

---

## 4. Controller Methods

### 4.1 `index()`

Hanya mengembalikan view. Data user diambil dari `session('user_data')`.

---

### 4.2 `readafval(Request $request)`

**Query param:** `status` (default `'waiting'`)

**Query:**
```sql
SELECT
    afval.kode_afval, afval.nama, afval.alamat, afval.tanggal,
    afval.notes, afval.status_faktur,
    GROUP_CONCAT(afval_detail.tipe SEPARATOR " + ") as tipe,
    SUM(afval_detail.berat) as berat,
    SUM(afval_detail.harga_satuan * afval_detail.berat) as harga_satuan
FROM afval
JOIN afval_detail ON afval.kode_afval = afval_detail.kode_afval
WHERE afval.status_faktur = ?
GROUP BY afval.kode_afval, afval.nama, afval.alamat, afval.tanggal, afval.notes, afval.status_faktur
```

**Kolom hasil aggregasi:**
- `tipe`: semua jenis afval dalam satu transaksi digabung dengan `" + "` — misal `"Afval Kertas + Afval Laminasi"`
- `berat`: total berat semua item
- `harga_satuan`: total nilai (`SUM(harga_satuan * berat)`) — **nama kolom menyesatkan**, ini bukan harga per satuan tapi grand total nilai transaksi

> ⚠️ Kolom `harga_satuan` di response `readafval()` sebenarnya adalah **total nilai** (berat × harga) bukan harga satuan. Di Blade, kolom ini di-render dengan label "Harga Total" yang sudah benar, tapi nama variabel di response tidak konsisten dengan semantiknya.

---

### 4.3 `getDetailsafval($kode_afval)`

Mengembalikan semua baris `afval_detail` WHERE `kode_afval = ?` via model `AfvalDetail`.

Dipakai oleh expand row (child row) di DataTables. Jika tidak ada detail, return `404`.

---

### 4.4 `createafval(Request $request)`

**Request body (JSON):**
```json
{
  "nama": "CV. Maju Jaya",
  "alamat": "Jl. Industri No. 5",
  "tanggal": "2025-06-10",
  "status_faktur": "waiting",
  "notes": "Pembayaran tunai",
  "details": [
    { "tipe": "Afval Kertas", "berat": 150.5, "harga_satuan": 1200.0 },
    { "tipe": "Afval Laminasi", "berat": 75.25, "harga_satuan": 800.0 }
  ]
}
```

**Validasi:**
```php
'nama'                    => 'required|string|max:255'
'alamat'                  => 'required|string|max:255'
'tanggal'                 => 'required|date'
'status_faktur'           => 'required|string|max:50'
'details'                 => 'required|array|min:1'
'details.*.tipe'          => 'required|string|max:255'
'details.*.berat'         => 'required|numeric|min:0'
'details.*.harga_satuan'  => 'required|numeric|min:0'
```

**ID Generation:**
```php
$prefix   = 'AF' . date('d') . date('m');  // misal 'AF1006' (10 Juni)
$lastData = DB::table('afval')
    ->where('kode_afval', 'like', $prefix . '%')
    ->orderBy('kode_afval', 'desc')
    ->first();

$lastNumber = (int) substr($lastData->kode_afval, -3);
$nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
$newKodeAfval = $prefix . $nextNumber;  // AF1006001
```

**DB writes:**
```sql
INSERT INTO afval (kode_afval, nama, alamat, tanggal, notes, status_faktur) VALUES (...)

-- LOOP per item detail:
INSERT INTO afval_detail (kode_afval, tipe, berat, harga_satuan) VALUES (...)
```

Return `201` jika berhasil.

---

### 4.5 `detailAfvalid($kode_afval)`

Mengembalikan header `Afval` dengan relasi `details` (eager load). Berbeda dengan `getDetailsafval()` yang hanya mengembalikan detail — method ini mengembalikan header + detail sekaligus.

Dipakai oleh modul lain (kemungkinan modul faktur) yang membutuhkan data lengkap satu transaksi.

---

### 4.6 `getafvalid()`

Mengembalikan list transaksi afval dengan `status_faktur = 'waiting'`, hanya kolom `kode_afval` dan `tanggal`, diurutkan ascending.

Dipakai oleh **modul faktur** sebagai dropdown/picker — user memilih kode afval mana yang akan difakturkan.

---

### 4.7 `updateStatusAfval($kode_afval)`

Mengubah `status_faktur` dari `'waiting'` ke `'done faktur'`.

```sql
UPDATE afval SET status_faktur = 'done faktur' WHERE kode_afval = ?
```

Tidak ada validasi kondisi sebelumnya — bisa dipanggil berulang kali pada transaksi yang sudah `'done faktur'` tanpa error (idempoten secara efek, tapi tidak secara semantik).

---

## 5. Alur Data End-to-End

```
User klik "Tambah"
    └─► Modal #modalTambah terbuka
        └─► User isi nama pembeli, alamat, tanggal
            └─► User pilih tipe afval → isi berat + harga → klik Submit

Klik Submit (#submitformtambah)
    └─► Validasi frontend: nama_bahan wajib dipilih
        └─► Hitung: total = berat × harga
            └─► Append baris ke #tabeldetail (in-memory, belum ke server)
                └─► updateGrandTotal() → hitung ulang total, diskon, ongkir, grand total

User isi beberapa item → klik Simpan (.simpansemua)
    └─► Validasi frontend:
        ├─► nama_pembeli tidak boleh kosong
        └─► tabeldetail harus ada minimal 1 baris
    └─► Kumpulkan data dari DOM:
        ├─► Header: nama, alamat, tanggal, status_faktur, notes
        └─► Detail rows: tipe (col 0), harga_satuan (col 1), berat (col 2)
    └─► POST /createafval (JSON body)
        ├─► Validasi server-side
        ├─► Generate kode_afval (AF + ddmm + seq)
        ├─► INSERT afval (header)
        └─► LOOP: INSERT afval_detail per item
            └─► Response 201 → Swal sukses → reset form → reload DataTable

Toggle Status (btn-lihat-status)
    └─► Toggle currentStatus: 'waiting' ↔ 'done faktur'
        └─► table.ajax.reload() → DataTable fetch ulang dengan status baru

Expand row (td.dt-control)
    └─► GET /afval/details/{kode_afval}
        └─► Response: array detail → render tabel child row
            └─► Tampilkan: tipe, berat (4 desimal + " kg"), harga_satuan (4 desimal + Rp)
```

---

## 6. Frontend / Blade

### 6.1 Dua File Blade

| File | Isi |
|---|---|
| `afval/index.blade.php` | Halaman utama + DataTable + script JS |
| `afval/tambah.blade.php` | Modal `#modalTambah` (di-include di index) |

### 6.2 Toggle Status — Satu DataTable Dua Mode

```javascript
let currentStatus = 'waiting';  // state global

$('#btnToggleStatus').on('click', function() {
    if (currentStatus === 'waiting') {
        currentStatus = 'done faktur';
        // Ubah teks + warna tombol
    } else {
        currentStatus = 'waiting';
    }
    table.ajax.reload();  // reload dengan status baru
});
```

DataTable mengirim `status` ke server via:
```javascript
ajax: {
    data: function(d) {
        d.status = currentStatus;  // dikirim ke readafval()
    }
}
```

### 6.3 Item Detail — Disimpan di DOM, Bukan di State JS

Setiap klik `#submitformtambah` menambahkan baris HTML ke `#tabeldetail tbody`. Data tidak disimpan di array JavaScript — seluruh state ada di DOM.

Saat simpan, data dibaca kembali dari DOM:
```javascript
let details = $('#tabeldetail tbody tr').map(function() {
    const row = $(this).find('td');
    return {
        tipe:         row.eq(0).text(),
        harga_satuan: parseNumber(row.eq(1).text()),
        berat:        parseNumber(row.eq(2).text()),
    };
}).get();
```

**Index kolom tabel detail modal:**

| Index (`td.eq(N)`) | Isi | Visible |
|---|---|---|
| 0 (colspan=2) | Nama bahan/tipe | Visible |
| 1 | Harga satuan (formatted) | Visible |
| 2 | Berat/Qty (formatted) | Visible |
| 3 | Total (harga × berat, formatted) | Visible |
| 4 | Tombol Delete | Visible |

> ⚠️ Index yang dibaca saat simpan: `eq(0)` = tipe, `eq(1)` = harga, `eq(2)` = berat. Tapi di HTML, kolom pertama pakai `colspan="2"`. jQuery `eq()` menghitung per elemen `<td>` — `colspan` tidak memecah sel menjadi dua — jadi `eq(0)` = td colspan, `eq(1)` = harga, `eq(2)` = berat. Ini konsisten dan benar, tapi perlu diperhatikan jika struktur tabel berubah.

### 6.4 Kalkulasi Grand Total

```javascript
function updateGrandTotal() {
    let totalExclude = 0;
    let totalBerat = 0;

    $('#tabeldetail tbody tr').each(function() {
        totalBerat   += parseNumber($(this).find('td').eq(2).text());  // berat
        totalExclude += parseNumber($(this).find('td').eq(3).text());  // total per item
    });

    const diskon    = parseNumber($('#diskon').val());
    const ongkir    = parseNumber($('#ongkir').val());
    const grandTotal = (totalExclude - diskon) + ongkir;

    $('#GrandTotalPembelian').val(formatNumber(grandTotal, 4));
}
```

Dipanggil setiap kali:
- Item ditambahkan ke tabel
- Item dihapus dari tabel
- Nilai `#diskon` atau `#ongkir` berubah

> ⚠️ `diskon` dan `ongkir` **tidak dikirim ke server** saat `createafval()`. Payload hanya berisi `nama`, `alamat`, `tanggal`, `status_faktur`, `notes`, dan `details`. Grand total yang dihitung di frontend hanya untuk tampilan — tidak tersimpan di DB.

### 6.5 Expand Row — Child Row dengan AJAX

```javascript
$('#afvaltable tbody').on('click', 'td.dt-control', function() {
    var row = table.row(tr);

    if (row.child.isShown()) {
        row.child.hide();
        tr.removeClass('dt-shown');
    } else {
        row.child('<em>Loading...</em>').show();  // placeholder
        $.ajax({
            url: '/afval/details/' + kodeAfval,
            success: function(response) {
                row.child(format(response)).show();  // replace dengan tabel detail
            }
        });
    }
});
```

`format()` membangun HTML tabel dari array detail, dengan format angka 4 desimal.

---

## 7. Status Reference

### `afval.status_faktur`

| Nilai | Arti | Ditampilkan saat |
|---|---|---|
| `'waiting'` | Belum difakturkan | Toggle default (btn biru "Tampilkan Afval Selesai Faktur") |
| `'done faktur'` | Sudah difakturkan | Toggle aktif (btn hijau "Tampilkan Afval Waiting") |

Status awal saat create selalu `'waiting'` — field `status_faktur` ada di form modal tapi `readonly` dengan value hardcoded `"waiting"`.

Transisi status:
```
waiting → done faktur   (via updateStatusAfval() dari modul faktur)
done faktur → ?         Tidak ada mekanisme rollback — final
```

---

## 8. ID Generation

**Format:** `AF{dd}{mm}{seq3}`

| Komponen | Contoh | Keterangan |
|---|---|---|
| `AF` | `AF` | Prefix tetap |
| `{dd}` | `10` | Tanggal saat transaksi dibuat (bukan tanggal form) |
| `{mm}` | `06` | Bulan saat transaksi dibuat |
| `{seq3}` | `001` | Sequence 3 digit, reset per hari per bulan |

Contoh: Transaksi ke-5 pada 10 Juni → `AF1006005`

**Query generate:**
```php
$prefix = 'AF' . date('d') . date('m');  // tanggal server saat request
$lastData = DB::table('afval')
    ->where('kode_afval', 'like', $prefix . '%')
    ->orderBy('kode_afval', 'desc')
    ->first();
```

> ⚠️ Sequence di-reset setiap pergantian hari — tapi yang dimaksud "hari" di sini adalah tanggal **server saat request**, bukan tanggal yang diisi di form. Jika user mengisi `tanggal = 2025-06-09` tapi server saat itu sudah `2025-06-10`, kode yang dihasilkan tetap `AF1006XXX` (10 Juni).

> ⚠️ Tidak ada `lockForUpdate()`. Dua request simultan pada hari yang sama bisa menghasilkan kode duplikat.

---

## 9. Interaksi Antar Tabel

```
[afval]
    kode_afval ──────────────► FK → afval_detail.kode_afval
    status_faktur ← 'waiting' saat insert via createafval()
    status_faktur ← 'done faktur' saat updateStatusAfval() dari modul faktur

[afval_detail]
    kode_afval ──────────────► FK → afval.kode_afval
    ← INSERT via createafval() (loop per item)
    ← hanya dibaca via getDetailsafval() untuk expand row
    ← hanya dibaca via readafval() untuk aggregasi (GROUP_CONCAT, SUM)

[Modul Faktur] (controller lain)
    ← menggunakan getafvalid() untuk picker kode afval
    ← memanggil updateStatusAfval() setelah faktur dibuat
    ← menggunakan detailAfvalid() untuk data lengkap satu transaksi
```

---

## 10. Error & Edge Cases

### 10.1 Error yang Di-handle Eksplisit

| Skenario | Di mana | Response |
|---|---|---|
| Validasi gagal (field wajib, format) | `createafval()` | `422 + errors JSON` |
| DB error saat insert | `createafval()` | `rollBack() + 500 + message` |
| `kode_afval` tidak ditemukan di detail | `getDetailsafval()` | `404` |
| `kode_afval` tidak ditemukan di update status | `updateStatusAfval()` | `404` |
| Nama pembeli kosong | Frontend `.simpansemua` | Swal warning |
| Tabel detail kosong | Frontend `.simpansemua` | Swal warning |
| Tipe afval tidak dipilih | Frontend `#submitformtambah` | Swal warning |

### 10.2 Edge Cases yang Perlu Diperhatikan

**1. `diskon` dan `ongkir` tidak disimpan ke DB**

Frontend menghitung grand total dengan diskon dan ongkir, tapi kedua field ini tidak ada di payload `createafval()` dan tidak ada kolomnya di tabel `afval`. Setelah transaksi tersimpan, informasi diskon/ongkir hilang. Jika dibutuhkan untuk rekonsiliasi atau audit, perlu ditambahkan kolom dan dikirim ke server.

**2. Tiga label berbeda menghasilkan `tipe` yang sama**

"Afval Kertas Coklat", "Afval Kertas Kardus", dan "Afval Campuran" semuanya disimpan sebagai `tipe = 'Afval Campuran'`. Riwayat pemilihan tidak bisa ditelusuri setelah tersimpan.

**3. Kode afval berbasis tanggal server, bukan tanggal form**

Jika user mengisi transaksi untuk tanggal kemarin, kode tetap menggunakan tanggal server hari ini. Ini bisa membingungkan karena `kode_afval` menyiratkan tanggal yang bisa berbeda dari `tanggal` di datanya sendiri.

**4. Race condition di generate `kode_afval`**

Tidak ada `lockForUpdate()` — dua request bersamaan di hari yang sama bisa menghasilkan kode duplikat. Karena `kode_afval` adalah PK, salah satu request akan gagal dengan duplicate key error yang ditangkap oleh `try-catch` dan return `500`.

**5. Kolom `harga_satuan` di response `readafval()` adalah total nilai, bukan harga satuan**

`SUM(afval_detail.harga_satuan * afval_detail.berat)` di-alias sebagai `harga_satuan` tapi dirender di Blade dengan label "Harga Total". Jika ada kode lain yang membaca field `harga_satuan` dari response `readafval()` dan mengasumsikan nilainya adalah harga per satuan, hasilnya akan salah.

**6. `notes` default di textarea adalah `" - "`**

Di `tambah.blade.php`, textarea notes memiliki konten default `" - "` (dengan spasi). Jika user tidak menghapus ini, nilai `notes` yang tersimpan adalah `" - "` bukan `null`. Validasi di server menggunakan `nullable`, jadi ini lolos.

**7. Tidak ada mekanisme edit atau hapus transaksi**

`Route::resource('afval', ...)` mendaftarkan semua CRUD routes, tapi di controller hanya `index()`, `readafval()`, `createafval()`, `getDetailsafval()`, `detailAfvalid()`, `getafvalid()`, dan `updateStatusAfval()` yang diimplementasikan. Tidak ada `update()` atau `destroy()` — transaksi afval yang sudah dibuat tidak bisa diedit atau dihapus dari UI.

---

*Dokumentasi ini dibuat berdasarkan kode per Juni 2026. Update dokumentasi ini setiap kali ada perubahan pada `AfvalController.php`, routes afval, atau struktur tabel `afval` dan `afval_detail`.*