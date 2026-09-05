@php
    $routeName = request()->route()?->getName() ?? '';
    $userType = (int) (auth()->user()->type ?? 0);
    $roleName = auth()->user()?->role_name ?? 'Unknown';
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
                $userType === \App\Models\User::ROLE_PURCHASING
                    ? 'Dashboard Purchasing merangkum pekerjaan dari request sampai invoice supplier.'
                    : ($userType === \App\Models\User::ROLE_FINANCE
                        ? 'Dashboard pembayaran merangkum invoice supplier yang perlu dilunasi.'
                        : 'Dashboard menampilkan pintasan dan ringkasan sesuai hak akses Anda.'),
            'steps' =>
                $userType === \App\Models\User::ROLE_PURCHASING
                    ? [
                        'Periksa request yang masih pending.',
                        'Pantau PO yang belum diterima penuh.',
                        'Tindak lanjuti penerimaan barang yang belum memiliki invoice.',
                        'Perhatikan invoice jatuh tempo dan bahan di bawah planning.',
                    ]
                    : ($userType === \App\Models\User::ROLE_FINANCE
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
                $userType === \App\Models\User::ROLE_ACCOUNTING
                    ? 'Accounting dapat melihat harga rata-rata dan nilai persediaan.'
                    : 'Harga satuan dan nilai persediaan hanya dapat dilihat role Accounting.',
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
        'penerimaan-barang.' => [
            'title' => 'Panduan Penerimaan Barang',
            'intro' => 'Penerimaan Barang (LPB) mencatat barang yang benar-benar diterima berdasarkan Purchase Order.',
            'steps' => [
                'Pilih PO dan nomor surat jalan.',
                'Masukkan kuantitas fisik serta lot yang diterima.',
                'Harga diambil dari PO; periksa seluruh item sebelum menyimpan.',
                'Penerimaan barang tersimpan akan menambah stok, membuat layer, dan memposting jurnal persediaan–GRNI.',
            ],
            'note' =>
                'Penerimaan barang yang sudah diposting terkunci. Koreksi tidak dilakukan dengan mengubah jurnal secara langsung.',
        ],
        'pemakaian-barang.' => [
            'title' => 'Panduan Pemakaian Barang',
            'intro' => 'Pemakaian Barang (NPK) mencatat barang yang digunakan atau keluar dari gudang.',
            'steps' => [
                'Pilih barang dan gudang asal.',
                'Masukkan jumlah pemakaian serta referensi pesanan.',
                'Simpan draft bila belum final atau pilih keluar untuk posting.',
                'Sistem menghitung harga rata-rata lima layer aktif dan mengurangi layer secara FIFO.',
            ],
            'note' => 'Pemakaian barang akan ditolak bila stok on hand atau layer persediaan tidak mencukupi.',
        ],
        'faktur-pembelian.' => [
            'title' => 'Panduan Faktur Pembelian',
            'intro' => 'Faktur pembelian menggabungkan satu atau beberapa penerimaan barang dari supplier yang sama.',
            'steps' => [
                'Pilih seluruh penerimaan barang yang tercantum dalam faktur.',
                'Periksa PPN, diskon, ongkir, dan nilai tagihan.',
                'Simpan faktur untuk memindahkan GRNI menjadi hutang supplier.',
                'Catat pembayaran menggunakan akun yang ditandai sebagai Kas/Bank.',
            ],
            'note' =>
                $userType === \App\Models\User::ROLE_FINANCE
                    ? 'Anda hanya dapat mencatat atau membatalkan pembayaran. Header faktur dikelola Purchasing.'
                    : 'PPh (23/22/4(2) Final) diakui pada saat pembayaran, bukan saat faktur diterima.',
        ],
        'bagan-akun.' => [
            'title' => 'Panduan Bagan Akun',
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
                $userType === \App\Models\User::ROLE_ACCOUNTING
                    ? 'Sebagai Accounting, isi harga selisih positif dan konfirmasi valuasi setelah Gudang mengunci hasil fisik.'
                    : 'Sebagai Gudang, buat opname, isi hasil fisik, lalu konfirmasikan kepada Accounting tanpa melihat harga.',
            'steps' =>
                $userType === \App\Models\User::ROLE_ACCOUNTING
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
                $userType === \App\Models\User::ROLE_ACCOUNTING
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
                'Penerimaan barang, pemakaian barang, invoice, pembayaran, opname, dan jurnal manual dalam periode terkunci akan ditolak oleh server.',
        ],
        'tax-rate.' => [
            'title' => 'Panduan Tarif Pajak',
            'intro' => 'Kelola tarif PPN dan PPh (23, 22, 4(2) Final) berdasarkan tanggal efektif.',
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

<div x-data="{ open: false }">
    <button type="button" class="page-help-button" @click="open = true"
        title="Cara menggunakan halaman ini" aria-label="Buka panduan halaman">
        <i class="fa-solid fa-question"></i>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-black/40" @click="open = false"
        x-transition.opacity></div>

    <div x-show="open" x-cloak
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col bg-base-100 shadow-2xl"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
        <div class="flex items-start justify-between border-b border-base-300 p-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wide text-primary">Pusat Bantuan</span>
                <h5 class="mt-1 text-lg font-bold">{{ $help['title'] }}</h5>
            </div>
            <button type="button" class="btn btn-ghost btn-sm btn-circle" @click="open = false" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
            <p class="text-base-content/60">{{ $help['intro'] }}</p>
            <ol class="page-help-steps mt-3 flex flex-col gap-2 list-decimal pl-5">
                @foreach ($help['steps'] as $step)
                    <li><span>{{ $step }}</span></li>
                @endforeach
            </ol>
            @if (!empty($help['note']))
                <div class="mt-4 flex items-start gap-2 rounded-lg bg-info/10 p-3 text-sm text-base-content">
                    <i class="fa-solid fa-circle-info mt-0.5 text-info"></i>
                    <span>{{ $help['note'] }}</span>
                </div>
            @endif
            <div class="mt-4 flex items-center justify-between rounded-lg bg-base-200 p-3 text-sm">
                <span class="text-base-content/50">Hak akses aktif</span>
                <strong>{{ $roleName }} ({{ $userType }})</strong>
            </div>
        </div>
    </div>
</div>
