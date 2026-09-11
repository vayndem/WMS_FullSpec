@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Selamat datang, {{ $user['name'] ?? 'Accounting' }}</h3>
                <p class="text-base-content/60">Pusat kontrol keuangan dan persediaan WMS.</p>
            </div>
            <span class="badge badge-lg badge-primary badge-outline">
                <i class="fa-solid fa-user-shield"></i> {{ $user->role_name ?? 'Accounting' }} ({{ $user->type ?? \App\Models\User::ROLE_ACCOUNTING }})
            </span>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 xl:grid-cols-3">
            <div class="xl:col-span-2">
                <h5 class="mb-3 font-bold"><i class="fa-solid fa-list-check text-primary"></i> Semua Tugas Accounting</h5>
                @include('layouts.template.task-grid', ['tasks' => $tasks])
            </div>
            @include('layouts.template.task-chart', ['tasks' => $tasks, 'chartTitle' => 'Distribusi Tugas', 'chartType' => 'bar', 'horizontal' => true])
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
            @include('layouts.template.reminder-list', ['reminders' => $reminders ?? [], 'judul' => 'Menunggu Tindakan Accounting'])
            @include('layouts.template.progress-list', ['progress' => $progress ?? []])
        </div>

        <h5 class="mb-3 font-bold"><i class="fa-solid fa-compass text-primary"></i> Navigasi Cepat</h5>
        @php
            $colorClasses = [
                'primary' => 'bg-primary/10 text-primary',
                'info' => 'bg-info/10 text-info',
                'success' => 'bg-success/10 text-success',
                'warning' => 'bg-warning/10 text-warning',
                'danger' => 'bg-error/10 text-error',
                'secondary' => 'bg-secondary/10 text-secondary',
            ];
        @endphp
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
        ['Rekonsiliasi WMS', 'Periksa stok, layer, GRNI, hutang supplier, invoice, dan jurnal.', 'reconciliation.index', 'fa-scale-balanced', 'primary'],
        ['Master Bahan', 'Lihat posisi stok, harga rata-rata, nilai persediaan, dan detail layer.', 'bahan.index', 'fa-boxes-stacked', 'info'],
        ['Jurnal Umum', 'Periksa jurnal otomatis dan kelola jurnal manual.', 'jurnal.index', 'fa-book', 'success'],
        ['Bagan Akun', 'Kelola akun dan mapping jurnal otomatis WMS.', 'bagan-akun.index', 'fa-sitemap', 'warning'],
        ['Kunci Periode', 'Tutup periode agar transaksi lama tidak berubah.', 'period-lock.index', 'fa-calendar-xmark', 'danger'],
        ['Tarif Pajak', 'Kelola tarif PPN dan PPh berdasarkan tanggal efektif.', 'tax-rate.index', 'fa-percent', 'secondary'],
        ['Faktur Pembelian', 'Periksa invoice penerimaan barang, pembayaran, PPh, dan sisa tagihan.', 'faktur-pembelian.index', 'fa-file-invoice-dollar', 'primary'],
        ['Stock Opname', 'Periksa dan approve hasil penghitungan fisik gudang.', 'stock-opname.index', 'fa-clipboard-check', 'success'],
    ] as [$title, $description, $route, $icon, $color])
                <a href="{{ route($route) }}" class="card border border-base-300 bg-base-100 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="card-body p-4">
                        <span class="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl {{ $colorClasses[$color] }}">
                            <i class="fa-solid {{ $icon }}"></i>
                        </span>
                        <h5 class="mb-2 font-bold">{{ $title }}</h5>
                        <p class="mb-3 text-sm text-base-content/50">{{ $description }}</p>
                        <span class="text-sm font-semibold text-primary">Buka fitur <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </a>
            @endforeach
        </div>

        <div role="alert" class="alert alert-info mt-4">
            <i class="fa-solid fa-circle-info"></i>
            <span>Mulai dari Rekonsiliasi WMS. Periksa setiap status yang tidak valid sebelum melakukan closing periode.</span>
        </div>
    </div>
@endsection
