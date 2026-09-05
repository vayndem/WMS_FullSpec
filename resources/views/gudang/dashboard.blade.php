@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Dashboard Gudang</h3>
                <p class="text-base-content/60">Ringkasan penerimaan, pemakaian, dan pemeriksaan stok hari ini.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('create', App\Models\PenerimaanBarang::class)
                    <a href="{{ route('penerimaan-barang.index', ['create' => 1]) }}" class="btn btn-primary">
                        <i class="fa-solid fa-box-open"></i> Terima Barang
                    </a>
                @endcan
                @can('create', App\Models\PenerimaanJasa::class)
                    <a href="{{ route('penerimaan-jasa.create') }}" class="btn btn-outline btn-primary">
                        <i class="fa-solid fa-clipboard-check"></i> Buat Penerimaan Jasa
                    </a>
                @endcan
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 xl:grid-cols-3">
            <div class="xl:col-span-2">
                <h5 class="mb-3 font-bold"><i class="fa-solid fa-list-check text-primary"></i> Semua Tugas Gudang</h5>
                @include('layouts.template.task-grid', ['tasks' => $tasks])
            </div>
            @include('layouts.template.task-chart', ['tasks' => $tasks, 'chartTitle' => 'Distribusi Tugas', 'chartType' => 'bar', 'horizontal' => true])
        </div>

        @php
            $colorClasses = [
                'primary' => 'bg-primary/10 text-primary',
                'warning' => 'bg-warning/10 text-warning',
                'success' => 'bg-success/10 text-success',
                'info' => 'bg-info/10 text-info',
                'secondary' => 'bg-secondary/10 text-secondary',
            ];
        @endphp
        <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-6">
            @foreach ([['label' => 'Master Bahan', 'value' => $warehouseMetrics['total_materials'], 'icon' => 'boxes-stacked', 'color' => 'primary'], ['label' => 'Perlu Perhatian', 'value' => $warehouseMetrics['stock_attention'], 'icon' => 'triangle-exclamation', 'color' => 'warning'], ['label' => 'Penerimaan Hari Ini', 'value' => $warehouseMetrics['receipts_today'], 'icon' => 'box-open', 'color' => 'success'], ['label' => 'Pemakaian Hari Ini', 'value' => $warehouseMetrics['issues_today'], 'icon' => 'arrow-right-from-bracket', 'color' => 'info'], ['label' => 'Opname Aktif', 'value' => $warehouseMetrics['open_opnames'], 'icon' => 'clipboard-check', 'color' => 'primary'], ['label' => 'Penerimaan Jasa Hari Ini', 'value' => $warehouseMetrics['service_baps_today'], 'icon' => 'screwdriver-wrench', 'color' => 'secondary']] as $metric)
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
                        <h5 class="font-bold">Penerimaan Terbaru</h5>
                        <p class="text-sm text-base-content/50">Barang dan jasa yang terakhir diterima</p>
                    </div>
                    <a href="{{ route('penerimaan-barang.index') }}" class="btn btn-sm btn-ghost border border-base-300">Lihat semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Tanggal</th>
                                <th>Supplier</th>
                                <th>Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentReceipts as $receipt)
                                <tr>
                                    <td><a class="font-semibold text-primary" href="{{ route('penerimaan-barang.show', $receipt) }}">{{ $receipt->id_lpb }}</a></td>
                                    <td>{{ $receipt->tanggal?->format('d-m-Y') }}</td>
                                    <td>{{ $receipt->pembelian->supplier->nama ?? '-' }}</td>
                                    <td><span class="badge badge-primary badge-outline">{{ $receipt->document_type === 'SERVICE_BAP' ? 'Penerimaan Jasa' : 'Penerimaan Barang' }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-base-content/50">Belum ada penerimaan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">Pemakaian Terbaru</h5>
                        <p class="text-sm text-base-content/50">Barang yang terakhir dikeluarkan melalui pemakaian barang</p>
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
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentIssues as $issue)
                                <tr>
                                    <td class="font-semibold text-primary">{{ $issue->kode }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($issue->tanggal)->format('d-m-Y') }}</td>
                                    <td>{{ $issue->barang->nama ?? '-' }}</td>
                                    <td class="text-end font-semibold">{{ number_format($issue->jumlah, 2, ',', '.') }}</td>
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

@endsection
