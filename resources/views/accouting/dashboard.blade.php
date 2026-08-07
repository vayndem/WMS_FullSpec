@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Selamat datang, {{ $user['name'] ?? 'Accounting' }}</h3>
                    <p class="text-muted mb-0">Pusat kontrol keuangan dan persediaan WMS.</p>
                </div>
                <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                    <i class="fa-solid fa-user-shield me-1"></i>Accounting · Type {{ $user['type'] ?? 33 }}
                </span>
            </div>

            <div class="row g-3">
                @foreach ([
            ['Rekonsiliasi WMS', 'Periksa stok, layer, GRNI, hutang supplier, invoice, dan jurnal.', 'reconciliation.index', 'fa-scale-balanced', 'primary'],
            ['Master Bahan', 'Lihat posisi stok, harga rata-rata, nilai persediaan, dan detail layer.', 'bahan.index', 'fa-boxes-stacked', 'info'],
            ['Jurnal Umum', 'Periksa jurnal otomatis dan kelola jurnal manual.', 'jurnal.index', 'fa-book', 'success'],
            ['Chart of Accounts', 'Kelola akun dan mapping jurnal otomatis WMS.', 'chart-of-accounts.index', 'fa-sitemap', 'warning'],
            ['Kunci Periode', 'Tutup periode agar transaksi lama tidak berubah.', 'period-lock.index', 'fa-calendar-xmark', 'danger'],
            ['Tarif Pajak', 'Kelola tarif PPN dan PPh berdasarkan tanggal efektif.', 'tax-rate.index', 'fa-percent', 'secondary'],
            ['Invoice Supplier', 'Periksa invoice LPB, pembayaran, PPh, dan sisa tagihan.', 'invoice-lpb.index', 'fa-file-invoice-dollar', 'primary'],
            ['Stock Opname', 'Periksa dan approve hasil penghitungan fisik gudang.', 'stock-opname.index', 'fa-clipboard-check', 'success'],
        ] as [$title, $description, $route, $icon, $color])
                    <div class="col-12 col-md-6 col-xl-3">
                        <a href="{{ route($route) }}"
                            class="card border-0 shadow-sm h-100 text-reset text-decoration-none dashboard-link-card">
                            <div class="card-body">
                                <span
                                    class="d-inline-flex align-items-center justify-content-center rounded-3 bg-{{ $color }}-subtle text-{{ $color }} mb-3"
                                    style="width:46px;height:46px">
                                    <i class="fa-solid {{ $icon }}"></i>
                                </span>
                                <h5 class="mb-2">{{ $title }}</h5>
                                <p class="text-muted small mb-3">{{ $description }}</p>
                                <span class="small fw-semibold text-primary">Buka fitur <i
                                        class="fa-solid fa-arrow-right ms-1"></i></span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="rounded-3 border border-info-subtle bg-info-subtle text-info-emphasis p-3 mt-4">
                <i class="fa-solid fa-circle-info me-2"></i>
                Mulai dari Rekonsiliasi WMS. Periksa setiap status yang tidak valid sebelum melakukan closing periode.
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-link-card {
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .dashboard-link-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(37, 99, 235, .12) !important;
        }
    </style>
@endpush
