@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Selamat datang, {{ $user['name'] ?? 'Purchasing' }}</h3>
                    <p class="text-muted mb-0">Pantau permintaan, pemesanan, penerimaan, dan invoice supplier.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('create', App\Models\Request::class)
                        <a href="{{ route('request.index', ['create' => 1]) }}" class="btn btn-outline-primary"><i
                                class="fa-solid fa-file-circle-plus me-1"></i>Buat Request</a>
                    @endcan
                    @can('create', App\Models\Pembelian::class)
                        <a href="{{ route('pembelian.index', ['create' => 1]) }}" class="btn btn-primary"><i
                                class="fa-solid fa-cart-plus me-1"></i>Buat Purchase Order</a>
                    @endcan
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach ([['Request menunggu', $metrics['pending_requests'], 'Perlu diperiksa/approve', 'request.index', 'fa-file-circle-question', 'primary'], ['PO aktif', $metrics['open_purchase_orders'], $metrics['awaiting_receipt'] . ' belum diterima penuh', 'pembelian.index', 'fa-cart-shopping', 'info'], ['LPB belum ditagih', $metrics['unbilled_receipts'], 'Menunggu invoice supplier', 'lpb.index', 'fa-box-open', 'warning'], ['Invoice belum lunas', $metrics['unpaid_invoices'], $metrics['overdue_invoices'] . ' melewati jatuh tempo', 'invoice-lpb.index', 'fa-file-invoice-dollar', $metrics['overdue_invoices'] ? 'danger' : 'success']] as [$label, $value, $note, $route, $icon, $color])
                    <div class="col-12 col-sm-6 col-xl-3">
                        <a href="{{ route($route) }}"
                            class="card dashboard-metric border-0 shadow-sm h-100 text-reset text-decoration-none">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-3">
                                    <div><small class="text-muted">{{ $label }}</small>
                                        <div class="display-6 fw-bold mt-1">{{ number_format($value) }}</div>
                                    </div>
                                    <span class="metric-icon bg-{{ $color }}-subtle text-{{ $color }}"><i
                                            class="fa-solid {{ $icon }}"></i></span>
                                </div>
                                <div class="small text-muted mt-3">{{ $note }}</div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            @if ($metrics['approved_unrealized'] || $metrics['stock_attention'] || $metrics['overdue_invoices'])
                <div class="rounded-3 border border-warning-subtle bg-warning-subtle text-warning-emphasis p-3 mb-4">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <strong><i class="fa-solid fa-triangle-exclamation me-2"></i>Perlu perhatian</strong>
                        <span>{{ $metrics['approved_unrealized'] }} item request approved belum direalisasikan</span>
                        <span class="vr d-none d-md-block"></span>
                        <span>{{ $metrics['stock_attention'] }} bahan di bawah planning</span>
                        <span class="vr d-none d-md-block"></span>
                        <span>{{ $metrics['overdue_invoices'] }} invoice melewati jatuh tempo</span>
                    </div>
                </div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Request Menunggu Tindakan</h5><small class="text-muted">Request terbaru
                                    berstatus pending</small>
                            </div>
                            <a href="{{ route('request.index') }}" class="btn btn-sm btn-light">Lihat semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>No request</th>
                                            <th>Tanggal</th>
                                            <th class="text-end">Item</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pendingRequests as $requestItem)
                                            <tr>
                                                <td class="fw-semibold">{{ $requestItem->no_request }}</td>
                                                <td>{{ $requestItem->created_at?->format('d-m-Y H:i') }}</td>
                                                <td class="text-end">{{ $requestItem->details_count }}</td>
                                                <td class="text-end"><a
                                                        href="{{ route('request.approveForm', $requestItem) }}"
                                                        class="btn btn-sm btn-outline-primary">Periksa</a></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">Tidak ada request
                                                    yang menunggu.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Progress Purchase Order</h5><small class="text-muted">PO aktif dan
                                    realisasi penerimaannya</small>
                            </div>
                            <a href="{{ route('pembelian.index') }}" class="btn btn-sm btn-light">Lihat semua</a>
                        </div>
                        <div class="card-body">
                            @forelse($openPurchaseOrders as $purchaseOrder)
                                @php
                                    $ordered = (float) ($purchaseOrder->ordered_quantity ?? 0);
                                    $received = (float) ($purchaseOrder->received_quantity ?? 0);
                                    $progress = $ordered > 0 ? min(100, round(($received / $ordered) * 100)) : 0;
                                @endphp
                                <a href="{{ route('pembelian.show', $purchaseOrder->no_po) }}"
                                    class="d-block text-reset text-decoration-none {{ !$loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                                    <div class="d-flex justify-content-between gap-3 mb-2">
                                        <div><span class="fw-semibold">{{ $purchaseOrder->no_po }}</span><small
                                                class="text-muted d-block">{{ $purchaseOrder->supplier->nama ?? '-' }}</small>
                                        </div>
                                        <span class="small fw-semibold">{{ $progress }}%</span>
                                    </div>
                                    <div class="progress" role="progressbar" aria-valuenow="{{ $progress }}"
                                        aria-valuemin="0" aria-valuemax="100" style="height:7px">
                                        <div class="progress-bar {{ $progress >= 100 ? 'bg-success' : 'bg-primary' }}"
                                            style="width:{{ $progress }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ number_format($received, 2, ',', '.') }} dari
                                        {{ number_format($ordered, 2, ',', '.') }} unit diterima</small>
                                </a>
                            @empty
                                <div class="text-center text-muted py-4">Tidak ada PO aktif.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">LPB Belum Ditagih</h5><small class="text-muted">Barang sudah diterima,
                                    invoice belum masuk</small>
                            </div><a href="{{ route('lpb.index') }}" class="btn btn-sm btn-light">Lihat LPB</a>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($unbilledReceipts as $receipt)
                                <a href="{{ route('lpb.show', $receipt) }}"
                                    class="list-group-item list-group-item-action px-4 py-3">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div><strong>{{ $receipt->id_lpb }}</strong><small
                                                class="d-block text-muted">{{ $receipt->pembelian->supplier->nama ?? '-' }}
                                                · PO {{ $receipt->no_po }}</small></div><span
                                            class="small text-muted">{{ $receipt->tanggal }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center text-muted py-4">Semua LPB sudah memiliki invoice.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Jatuh Tempo Invoice</h5><small class="text-muted">Prioritas pembayaran
                                    supplier</small>
                            </div><a href="{{ route('invoice-lpb.index') }}" class="btn btn-sm btn-light">Lihat invoice</a>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($dueInvoices as $invoice)
                                @php $overdue = $invoice->tgl_deadline_pembayaran && $invoice->tgl_deadline_pembayaran->isPast(); @endphp
                                <a href="{{ route('invoice-lpb.index', ['invoice' => $invoice->id]) }}"
                                    class="list-group-item list-group-item-action px-4 py-3">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div><strong>{{ $invoice->no_invoice }}</strong><small
                                                class="d-block text-muted">{{ $invoice->supplier->nama ?? '-' }}</small>
                                        </div>
                                        <div class="text-end"><span
                                                class="badge {{ $overdue ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $overdue ? 'Terlambat' : 'Belum lunas' }}</span><small
                                                class="d-block text-muted mt-1">{{ $invoice->tgl_deadline_pembayaran?->format('d-m-Y') ?? 'Tanpa deadline' }}</small>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center text-muted py-4">Tidak ada invoice yang belum lunas.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-metric {
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .dashboard-metric:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(37, 99, 235, .12) !important;
        }

        .metric-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .progress {
            background: #e9eff8;
        }
    </style>
@endpush
