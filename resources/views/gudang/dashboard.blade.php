@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Dashboard Gudang</h3>
                    <p class="text-muted mb-0">Ringkasan penerimaan, pemakaian, dan pemeriksaan stok hari ini.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('create', App\Models\Lpb::class)
                        <a href="{{ route('lpb.index', ['create' => 1]) }}" class="btn btn-primary">
                            <i class="fa-solid fa-box-open me-1"></i> Terima Barang
                        </a>
                    @endcan
                    @can('create', App\Models\ServiceBap::class)
                        <a href="{{ route('service-baps.create') }}" class="btn btn-outline-primary">
                            <i class="fa-solid fa-clipboard-check me-1"></i> Buat BAP Jasa
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach ([
                    ['label' => 'Master Bahan', 'value' => $warehouseMetrics['total_materials'], 'icon' => 'boxes-stacked', 'color' => 'primary'],
                    ['label' => 'Perlu Perhatian', 'value' => $warehouseMetrics['stock_attention'], 'icon' => 'triangle-exclamation', 'color' => 'warning'],
                    ['label' => 'Penerimaan Hari Ini', 'value' => $warehouseMetrics['receipts_today'], 'icon' => 'box-open', 'color' => 'success'],
                    ['label' => 'NPK Hari Ini', 'value' => $warehouseMetrics['issues_today'], 'icon' => 'arrow-right-from-bracket', 'color' => 'info'],
                    ['label' => 'Opname Aktif', 'value' => $warehouseMetrics['open_opnames'], 'icon' => 'clipboard-check', 'color' => 'primary'],
                    ['label' => 'BAP Jasa Hari Ini', 'value' => $warehouseMetrics['service_baps_today'], 'icon' => 'screwdriver-wrench', 'color' => 'secondary'],
                ] as $metric)
                    <div class="col-6 col-xl-2">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="text-muted small mb-2">{{ $metric['label'] }}</div>
                                        <div class="fs-3 fw-bold">{{ number_format($metric['value']) }}</div>
                                    </div>
                                    <span class="rounded-circle bg-{{ $metric['color'] }}-subtle text-{{ $metric['color'] }} p-3">
                                        <i class="fa-solid fa-{{ $metric['icon'] }}"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-4">
                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1">Penerimaan Terbaru</h5>
                                <small class="text-muted">Barang dan jasa yang terakhir diterima</small>
                            </div>
                            <a href="{{ route('lpb.index') }}" class="btn btn-sm btn-light border">Lihat semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Dokumen</th><th>Tanggal</th><th>Supplier</th><th>Jenis</th></tr></thead>
                                <tbody>
                                    @forelse ($recentReceipts as $receipt)
                                        <tr>
                                            <td><a class="fw-semibold" href="{{ route('lpb.show', $receipt) }}">{{ $receipt->id_lpb }}</a></td>
                                            <td>{{ $receipt->tanggal?->format('d-m-Y') }}</td>
                                            <td>{{ $receipt->pembelian->supplier->nama ?? '-' }}</td>
                                            <td><span class="badge bg-primary-subtle text-primary-emphasis">
                                                {{ $receipt->document_type === 'SERVICE_BAP' ? 'BAP Jasa' : 'LPB Barang' }}
                                            </span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada penerimaan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1">Pemakaian Terbaru</h5>
                                <small class="text-muted">Barang yang terakhir dikeluarkan melalui NPK</small>
                            </div>
                            <a href="{{ route('npk.index') }}" class="btn btn-sm btn-light border">Lihat semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>NPK</th><th>Tanggal</th><th>Barang</th><th class="text-end">Jumlah</th></tr></thead>
                                <tbody>
                                    @forelse ($recentIssues as $issue)
                                        <tr>
                                            <td class="fw-semibold text-primary">{{ $issue->kode }}</td>
                                            <td>{{ \Illuminate\Support\Carbon::parse($issue->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ $issue->barang->nama ?? '-' }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($issue->jumlah, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pemakaian.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.template.page-help', [
        'title' => 'Dashboard Gudang',
        'items' => [
            'Gunakan Penerimaan untuk mencatat LPB barang atau BAP jasa.',
            'Gunakan NPK untuk mencatat barang yang dipakai atau dikeluarkan.',
            'Stock Opname digunakan untuk membandingkan stok sistem dengan hasil hitung fisik.',
            'Dashboard gudang hanya menampilkan kuantitas dan aktivitas, tanpa harga atau nilai uang.',
        ],
    ])
@endsection
