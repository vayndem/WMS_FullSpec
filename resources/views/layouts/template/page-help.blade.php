@php
    $routeName = request()->route()?->getName() ?? '';
    $userType = (int) (auth()->user()->type ?? 0);
    $help = [
        'title' => 'Panduan Halaman',
        'intro' => 'Gunakan halaman ini sesuai hak akses Anda. Tombol yang tersedia mengikuti policy sistem.',
        'steps' => [
            'Gunakan pencarian untuk menemukan data.',
            'Gunakan filter pada kolom untuk mempersempit hasil.',
            'Klik PDF untuk mencetak data sesuai pencarian dan filter aktif.',
        ],
        'note' => 'Jika tombol tidak muncul, akun Anda tidak memiliki izin untuk tindakan tersebut.',
    ];

    $guides = [
        'dashboard' => [
            'title' => 'Panduan Dashboard',
            'intro' =>
                $userType === 5
                    ? 'Dashboard Purchasing merangkum pekerjaan dari request sampai invoice supplier.'
                    : ($userType === 13
                        ? 'Dashboard pembayaran merangkum invoice supplier yang perlu dilunasi.'
                        : 'Dashboard menampilkan pintasan dan ringkasan sesuai hak akses Anda.'),
            'steps' =>
                $userType === 5
                    ? [
                        'Periksa request yang masih pending.',
                        'Pantau PO yang belum diterima penuh.',
                        'Tindak lanjuti LPB yang belum memiliki invoice.',
                        'Perhatikan invoice jatuh tempo dan bahan di bawah planning.',
                    ]
                    : ($userType === 13
                        ? [
                            'Dahulukan invoice yang terlambat atau segera jatuh tempo.',
                            'Klik Bayar pada invoice yang dipilih.',
                            'Pilih akun Kas/Bank dan isi komponen pembayaran.',
                            'Periksa riwayat pembayaran setelah transaksi berhasil.',
                        ]
                        : [
                            'Pilih modul melalui sidebar.',
                            'Perhatikan indikator transaksi yang memerlukan tindakan.',
                            'Gunakan menu profil untuk keluar dengan aman.',
                        ]),
        ],
        'supplier.' => [
            'title' => 'Panduan Supplier',
            'intro' => 'Kelola identitas dan ketentuan pembayaran supplier yang digunakan pada proses pembelian.',
            'steps' => [
                'Tambah supplier dan lengkapi alamat serta kontak.',
                'Gunakan Edit untuk memperbaiki data.',
                'Supplier yang sudah dipakai transaksi sebaiknya tidak dihapus.',
            ],
        ],
        'bahan.' => [
            'title' => 'Panduan Master Bahan',
            'intro' => 'Lihat posisi stok dan detail layer persediaan setiap bahan.',
            'steps' => [
                'Cari atau filter bahan berdasarkan kategori dan gudang.',
                'Bandingkan stok on hand dengan total layer.',
                'Klik Detail untuk melihat asal dan sisa setiap layer.',
                'Laporkan status SELISIH melalui halaman Rekonsiliasi WMS.',
            ],
            'note' =>
                $userType === 33
                    ? 'Accounting dapat melihat harga rata-rata dan nilai persediaan.'
                    : 'Harga satuan dan nilai persediaan hanya dapat dilihat Accounting type 33.',
        ],
        'request.' => [
            'title' => 'Panduan Request Barang',
            'intro' => 'Buat permintaan barang dan pantau proses persetujuannya.',
            'steps' => [
                'Buat request dan tambahkan barang yang diperlukan.',
                'Periksa jumlah dan keterangan sebelum mengirim.',
                'Buka detail untuk melihat status dan hasil approval.',
            ],
        ],
        'pembelian.' => [
            'title' => 'Panduan Purchase Order',
            'intro' => 'Kelola pemesanan barang kepada supplier sebelum barang diterima gudang.',
            'steps' => [
                'Pilih supplier dan tambahkan item PO.',
                'Pastikan harga, jumlah, termin, dan pajak benar.',
                'Tutup PO hanya setelah penerimaan atau penyelesaian transaksi selesai.',
            ],
        ],
        'lpb.' => [
            'title' => 'Panduan LPB',
            'intro' => 'LPB mencatat barang yang benar-benar diterima berdasarkan Purchase Order.',
            'steps' => [
                'Pilih PO dan nomor surat jalan.',
                'Masukkan kuantitas fisik serta lot yang diterima.',
                'Harga diambil dari PO; periksa seluruh item sebelum menyimpan.',
                'LPB tersimpan akan menambah stok, membuat layer, dan memposting jurnal persediaan–GRNI.',
            ],
            'note' =>
                'LPB yang sudah diposting terkunci. Koreksi tidak dilakukan dengan mengubah jurnal secara langsung.',
        ],
        'npk.' => [
            'title' => 'Panduan NPK',
            'intro' => 'NPK mencatat barang yang digunakan atau keluar dari gudang.',
            'steps' => [
                'Pilih barang dan gudang asal.',
                'Masukkan jumlah pemakaian serta referensi pesanan.',
                'Simpan draft bila belum final atau pilih keluar untuk posting.',
                'Sistem menghitung harga rata-rata lima layer aktif dan mengurangi layer secara FIFO.',
            ],
            'note' => 'NPK akan ditolak bila stok on hand atau layer persediaan tidak mencukupi.',
        ],
        'invoice-lpb.' => [
            'title' => 'Panduan Invoice Supplier',
            'intro' => 'Invoice supplier menggabungkan satu atau beberapa LPB dari supplier yang sama.',
            'steps' => [
                'Pilih seluruh LPB yang tercantum dalam invoice.',
                'Periksa PPN, diskon, ongkir, dan nilai tagihan.',
                'Simpan invoice untuk memindahkan GRNI menjadi hutang supplier.',
                'Catat pembayaran menggunakan akun yang ditandai sebagai Kas/Bank.',
            ],
            'note' =>
                $userType === 13
                    ? 'Anda hanya dapat mencatat atau membatalkan pembayaran. Header invoice dikelola Purchasing.'
                    : 'PPh 23 diakui pada saat pembayaran, bukan saat invoice diterima.',
        ],
        'chart-of-accounts.' => [
            'title' => 'Panduan Chart of Accounts',
            'intro' => 'Accountant mengelola nama akun dan mapping yang dipakai jurnal otomatis.',
            'steps' => [
                'Buat akun dan tentukan kategori serta posisi normal.',
                'Tandai akun Kas/Bank hanya untuk rekening pembayaran.',
                'Lengkapi mapping global dan mapping setiap kategori bahan.',
                'Nonaktifkan akun yang tidak digunakan; jangan menghapus histori akun.',
            ],
            'note' => 'Transaksi akan ditolak bila mapping akun yang diperlukan belum lengkap.',
        ],
        'kategori-bahan.' => [
            'title' => 'Panduan Kategori & Mapping',
            'intro' => 'Setiap kategori menentukan akun persediaan, pemakaian, GRNI, dan selisih opname.',
            'steps' => [
                'Buat kategori bahan.',
                'Pilih akun persediaan dan pemakaian.',
                'Pilih akun GRNI.',
                'Pilih akun selisih opname negatif dan koreksi positif.',
            ],
        ],
        'jurnal.' => [
            'title' => 'Panduan Jurnal',
            'intro' => 'Gunakan jurnal manual hanya untuk penyesuaian yang tidak berasal dari dokumen WMS.',
            'steps' => [
                'Buat minimal dua baris jurnal.',
                'Isi tepat salah satu sisi debit atau kredit per baris.',
                'Pastikan total debit dan kredit seimbang.',
                'Posting untuk mengunci jurnal; gunakan jurnal pembalik bila terjadi kesalahan.',
            ],
            'note' => 'Jurnal otomatis harus dikoreksi melalui dokumen sumbernya.',
        ],
        'tipe-pembebanan.' => [
            'title' => 'Panduan Tipe Pembebanan',
            'intro' => 'Tipe pembebanan membantu mengelompokkan perlakuan biaya setiap kategori bahan.',
            'steps' => [
                'Buat tipe sesuai kebijakan perusahaan.',
                'Hubungkan tipe ke kategori bahan.',
                'Jangan menghapus tipe yang masih digunakan.',
            ],
        ],
        'stock-opname.' => [
            'title' => 'Panduan Stock Opname',
            'intro' =>
                $userType === 33
                    ? 'Sebagai Accounting, isi harga selisih positif dan konfirmasi valuasi setelah Gudang mengunci hasil fisik.'
                    : 'Sebagai Gudang, buat opname, isi hasil fisik, lalu konfirmasikan kepada Accounting tanpa melihat harga.',
            'steps' =>
                $userType === 33
                    ? [
                        'Buka detail opname berstatus SUBMITTED.',
                        'Harga selisih negatif dihitung otomatis dari layer FIFO.',
                        'Isi harga per satuan untuk selisih positif, lalu konfirmasi atau reject dengan catatan.',
                        'Setelah kedua konfirmasi lengkap, post stok dan jurnal.',
                    ]
                    : [
                        'Buat opname, pilih gudang dan waktu cut-off.',
                        'Pilih barang lalu masukkan hasil hitung fisik dan alasan selisih.',
                        'Submit untuk mengunci konfirmasi fisik dan meneruskannya kepada Accounting.',
                        'Harga dan nilai tidak ditampilkan kepada role Gudang.',
                    ],
            'note' =>
                'Posting ditolak bila stok berubah setelah penghitungan. Lakukan penghitungan ulang agar cut-off tetap valid.',
        ],
        'reconciliation.' => [
            'title' => 'Panduan Rekonsiliasi WMS',
            'intro' => 'Periksa konsistensi stok, layer persediaan, invoice, GRNI, hutang supplier, dan jurnal.',
            'steps' => [
                'Pastikan seluruh kartu berstatus VALID.',
                'Klik kartu untuk melihat baris penyebab selisih.',
                'Perbaiki dokumen sumber; jangan mengubah jurnal otomatis langsung.',
                'Jalankan pemeriksaan kembali setelah koreksi.',
            ],
            'note' =>
                $userType === 33
                    ? 'Accounting dapat melihat kuantitas dan nilai rupiah.'
                    : 'Nilai keuangan disembunyikan; Anda tetap dapat memeriksa kuantitas stok dan layer.',
        ],
        'period-lock.' => [
            'title' => 'Panduan Kunci Periode',
            'intro' => 'Accounting mengunci rentang tanggal yang laporan keuangannya sudah ditutup.',
            'steps' => [
                'Pilih tanggal awal dan akhir periode.',
                'Isi alasan closing.',
                'Kunci periode setelah rekonsiliasi valid.',
                'Buka kembali hanya dengan alasan koreksi yang dapat diaudit.',
            ],
            'note' =>
                'LPB, NPK, invoice, pembayaran, opname, dan jurnal manual dalam periode terkunci akan ditolak oleh server.',
        ],
        'tax-rate.' => [
            'title' => 'Panduan Tarif Pajak',
            'intro' => 'Kelola tarif PPN dan PPh 23 berdasarkan tanggal efektif.',
            'steps' => [
                'Tambahkan tarif dan tanggal mulai berlaku.',
                'Tutup tarif lama dengan tanggal akhir sebelum mengaktifkan tarif baru.',
                'Pastikan rentang aktif tidak tumpang tindih.',
                'Invoice akan menyimpan snapshot tarif yang berlaku pada tanggalnya.',
            ],
        ],
        'debit.' => [
            'title' => 'Panduan Debit',
            'intro' => 'Kelola transaksi debit sesuai dokumen dan akun yang telah dipetakan.',
            'steps' => [
                'Periksa tanggal dan referensi.',
                'Pilih akun yang sesuai.',
                'Pastikan nominal serta keterangannya benar sebelum menyimpan.',
            ],
        ],
        'kredit.' => [
            'title' => 'Panduan Kredit',
            'intro' => 'Kelola transaksi kredit sesuai dokumen dan akun yang telah dipetakan.',
            'steps' => [
                'Periksa tanggal dan referensi.',
                'Pilih akun yang sesuai.',
                'Pastikan nominal serta keterangannya benar sebelum menyimpan.',
            ],
        ],
    ];

    foreach ($guides as $prefix => $guide) {
        if (str_starts_with($routeName, $prefix)) {
            $help = array_merge($help, $guide);
            break;
        }
    }
@endphp

<button type="button" class="page-help-button" data-bs-toggle="offcanvas" data-bs-target="#pageHelpCanvas"
    aria-controls="pageHelpCanvas" title="Cara menggunakan halaman ini" aria-label="Buka panduan halaman">
    <i class="fa-solid fa-question"></i>
</button>

<div class="offcanvas offcanvas-end page-help-canvas" tabindex="-1" id="pageHelpCanvas" aria-labelledby="pageHelpTitle">
    <div class="offcanvas-header border-bottom">
        <div>
            <span class="page-help-kicker">PUSAT BANTUAN</span>
            <h5 class="offcanvas-title mt-1" id="pageHelpTitle">{{ $help['title'] }}</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <p class="text-muted">{{ $help['intro'] }}</p>
        <ol class="page-help-steps">
            @foreach ($help['steps'] as $step)
                <li><span>{{ $step }}</span></li>
            @endforeach
        </ol>
        @if (!empty($help['note']))
            <div class="page-help-note">
                <i class="fa-solid fa-circle-info"></i>
                <span>{{ $help['note'] }}</span>
            </div>
        @endif
        <div class="page-help-role mt-4">
            <span>Hak akses aktif</span>
            <strong>Type {{ $userType ?: '-' }}</strong>
        </div>
    </div>
</div>
