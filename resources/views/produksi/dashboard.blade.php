@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Dashboard Produksi</h3>
                    <p class="text-muted mb-0">Ringkasan pemakaian, transfer, dan opname untuk gudang produksi.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('create', App\Models\Npk::class)
                        <a href="{{ route('npk.index', ['create' => 1]) }}" class="btn btn-primary">
                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Buat NPK
                        </a>
                    @endcan
                    @can('create', App\Models\TransferGudang::class)
                        <a href="{{ route('transfer-gudangs.create') }}" class="btn btn-outline-primary">
                            <i class="fa-solid fa-right-left me-1"></i> Buat Transfer
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach ([['label' => 'Gudang Tugas', 'value' => $productionMetrics['assigned_warehouses'], 'icon' => 'warehouse', 'color' => 'primary'], ['label' => 'NPK Hari Ini', 'value' => $productionMetrics['issues_today'], 'icon' => 'arrow-right-from-bracket', 'color' => 'info'], ['label' => 'Transfer Aktif', 'value' => $productionMetrics['transfers_in_progress'], 'icon' => 'right-left', 'color' => 'warning'], ['label' => 'Opname Aktif', 'value' => $productionMetrics['open_opnames'], 'icon' => 'clipboard-check', 'color' => 'success']] as $metric)
                    <div class="col-6 col-xl-3">
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
                                <h5 class="fw-bold mb-1">Transfer Terbaru</h5>
                                <small class="text-muted">Perpindahan stok yang melibatkan gudang produksi</small>
                            </div>
                            <a href="{{ route('transfer-gudangs.index') }}" class="btn btn-sm btn-light border">Lihat semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                            <td class="fw-semibold text-primary">{{ $transfer->nomor_transfer }}</td>
                                            <td>{{ \Illuminate\Support\Carbon::parse($transfer->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ $transfer->asal_nama ?? '-' }} -> {{ $transfer->tujuan_nama ?? '-' }}</td>
                                            <td><span class="badge bg-primary-subtle text-primary-emphasis">{{ $transfer->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada transfer.</td>
                                        </tr>
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
                                <h5 class="fw-bold mb-1">NPK Terbaru</h5>
                                <small class="text-muted">Pemakaian bahan dari gudang produksi</small>
                            </div>
                            <a href="{{ route('npk.index') }}" class="btn btn-sm btn-light border">Lihat semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                            <td class="fw-semibold text-primary">{{ $issue->kode }}</td>
                                            <td>{{ \Illuminate\Support\Carbon::parse($issue->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ $issue->barang->nama ?? '-' }}</td>
                                            <td>{{ $issue->gudangAsal->nama ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada pemakaian.</td>
                                        </tr>
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
        'title' => 'Dashboard Produksi',
        'items' => [
            'Transfer dari gudang utama ke gudang produksi dicatat melalui transfer gudang.',
            'NPK dari gudang produksi menjadi titik mulai pengurangan stok dan pembebanan biaya.',
            'Stock opname tetap dilakukan per gudang agar saldo produksi tetap akurat.',
            'Dashboard produksi hanya menampilkan aktivitas gudang yang memang di-assign ke user ini.',
        ],
    ])
@endsection
