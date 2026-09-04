@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Dashboard Produksi</h3>
                <p class="text-base-content/60">Ringkasan pemakaian, transfer, dan opname untuk gudang produksi.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('create', App\Models\PemakaianBarang::class)
                    <a href="{{ route('pemakaian-barang.index', ['create' => 1]) }}" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Buat Pemakaian Barang
                    </a>
                @endcan
                @can('create', App\Models\TransferGudang::class)
                    <a href="{{ route('transfer-gudangs.create') }}" class="btn btn-outline btn-primary">
                        <i class="fa-solid fa-right-left"></i> Buat Transfer
                    </a>
                @endcan
            </div>
        </div>

        @php
            $colorClasses = [
                'primary' => 'bg-primary/10 text-primary',
                'info' => 'bg-info/10 text-info',
                'warning' => 'bg-warning/10 text-warning',
                'success' => 'bg-success/10 text-success',
            ];
        @endphp
        <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([['label' => 'Gudang Tugas', 'value' => $productionMetrics['assigned_warehouses'], 'icon' => 'warehouse', 'color' => 'primary'], ['label' => 'Pemakaian Hari Ini', 'value' => $productionMetrics['issues_today'], 'icon' => 'arrow-right-from-bracket', 'color' => 'info'], ['label' => 'Transfer Aktif', 'value' => $productionMetrics['transfers_in_progress'], 'icon' => 'right-left', 'color' => 'warning'], ['label' => 'Opname Aktif', 'value' => $productionMetrics['open_opnames'], 'icon' => 'clipboard-check', 'color' => 'success']] as $metric)
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="mb-2 text-sm text-base-content/50">{{ $metric['label'] }}</p>
                                <p class="text-2xl font-bold">{{ number_format($metric['value']) }}</p>
                            </div>
                            <span class="rounded-full p-3 {{ $colorClasses[$metric['color']] }}">
                                <i class="fa-solid fa-{{ $metric['icon'] }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">Transfer Terbaru</h5>
                        <p class="text-sm text-base-content/50">Perpindahan stok yang melibatkan gudang produksi</p>
                    </div>
                    <a href="{{ route('transfer-gudangs.index') }}" class="btn btn-sm btn-ghost border border-base-300">Lihat semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nomor</th>
                                <th>Tanggal</th>
                                <th>Rute</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTransfers as $transfer)
                                <tr>
                                    <td class="font-semibold text-primary">{{ $transfer->nomor_transfer }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($transfer->tanggal)->format('d-m-Y') }}</td>
                                    <td>{{ $transfer->asal_nama ?? '-' }} &rarr; {{ $transfer->tujuan_nama ?? '-' }}</td>
                                    <td><span class="badge badge-primary badge-outline">{{ $transfer->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-base-content/50">Belum ada transfer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">Pemakaian Barang Terbaru</h5>
                        <p class="text-sm text-base-content/50">Pemakaian bahan dari gudang produksi</p>
                    </div>
                    <a href="{{ route('pemakaian-barang.index') }}" class="btn btn-sm btn-ghost border border-base-300">Lihat semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NPK</th>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th>Gudang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentIssues as $issue)
                                <tr>
                                    <td class="font-semibold text-primary">{{ $issue->kode }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($issue->tanggal)->format('d-m-Y') }}</td>
                                    <td>{{ $issue->barang->nama ?? '-' }}</td>
                                    <td>{{ $issue->gudangAsal->nama ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-base-content/50">Belum ada pemakaian.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.template.page-help', [
        'title' => 'Dashboard Produksi',
        'items' => [
            'Transfer dari gudang utama ke gudang produksi dicatat melalui transfer gudang.',
            'Pemakaian barang dari gudang produksi menjadi titik mulai pengurangan stok dan pembebanan biaya.',
            'Stock opname tetap dilakukan per gudang agar saldo produksi tetap akurat.',
            'Dashboard produksi hanya menampilkan aktivitas gudang yang memang di-assign ke user ini.',
        ],
    ])
@endsection
