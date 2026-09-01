@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Selamat datang, {{ $user['name'] ?? 'Purchasing' }}</h3>
                <p class="text-base-content/60">Pantau permintaan, pemesanan, penerimaan, dan invoice supplier.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('create', App\Models\MaterialRequest::class)
                    <a href="{{ route('request.index', ['create' => 1]) }}" class="btn btn-outline btn-primary">
                        <i class="fa-solid fa-file-circle-plus"></i> Buat Request
                    </a>
                @endcan
                @can('create', App\Models\Pembelian::class)
                    <a href="{{ route('pembelian.index', ['create' => 1]) }}" class="btn btn-primary">
                        <i class="fa-solid fa-cart-plus"></i> Buat Purchase Order
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
                'danger' => 'bg-error/10 text-error',
            ];
        @endphp
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Request menunggu', $metrics['pending_requests'], 'Perlu diperiksa/approve', 'request.index', 'fa-file-circle-question', 'primary'], ['PO aktif', $metrics['open_purchase_orders'], $metrics['awaiting_receipt'] . ' belum diterima penuh', 'pembelian.index', 'fa-cart-shopping', 'info'], ['LPB belum ditagih', $metrics['unbilled_receipts'], 'Menunggu invoice supplier', 'lpb.index', 'fa-box-open', 'warning'], ['Invoice belum lunas', $metrics['unpaid_invoices'], $metrics['overdue_invoices'] . ' melewati jatuh tempo', 'invoice-lpb.index', 'fa-file-invoice-dollar', $metrics['overdue_invoices'] ? 'danger' : 'success']] as [$label, $value, $note, $route, $icon, $color])
                <a href="{{ route($route) }}" class="card border border-base-300 bg-base-100 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-base-content/50">{{ $label }}</p>
                                <p class="mt-1 text-3xl font-bold">{{ number_format($value) }}</p>
                            </div>
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl {{ $colorClasses[$color] }}">
                                <i class="fa-solid {{ $icon }}"></i>
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-base-content/50">{{ $note }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($metrics['approved_unrealized'] || $metrics['stock_attention'] || $metrics['overdue_invoices'])
            <div role="alert" class="alert alert-warning mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div class="flex flex-wrap items-center gap-3">
                    <strong>Perlu perhatian</strong>
                    <span>{{ $metrics['approved_unrealized'] }} item request approved belum direalisasikan</span>
                    <span class="hidden md:inline">·</span>
                    <span>{{ $metrics['stock_attention'] }} bahan di bawah planning</span>
                    <span class="hidden md:inline">·</span>
                    <span>{{ $metrics['overdue_invoices'] }} invoice melewati jatuh tempo</span>
                </div>
            </div>
        @endif

        <div class="mb-4 grid grid-cols-1 gap-3 xl:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">Request Menunggu Tindakan</h5>
                        <p class="text-sm text-base-content/50">Request terbaru berstatus pending</p>
                    </div>
                    <a href="{{ route('request.index') }}" class="btn btn-sm btn-ghost">Lihat semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No Request</th>
                                <th>Tanggal</th>
                                <th class="text-end">Item</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingRequests as $requestItem)
                                <tr>
                                    <td class="font-semibold">{{ $requestItem->no_request }}</td>
                                    <td>{{ $requestItem->created_at?->format('d-m-Y H:i') }}</td>
                                    <td class="text-end">{{ $requestItem->details_count }}</td>
                                    <td class="text-end"><a href="{{ route('request.approveForm', $requestItem) }}" class="btn btn-outline btn-primary btn-sm">Periksa</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-base-content/50">Tidak ada request yang menunggu.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">Progress Purchase Order</h5>
                        <p class="text-sm text-base-content/50">PO aktif dan realisasi penerimaannya</p>
                    </div>
                    <a href="{{ route('pembelian.index') }}" class="btn btn-sm btn-ghost">Lihat semua</a>
                </div>
                <div class="p-4">
                    @forelse($openPurchaseOrders as $purchaseOrder)
                        @php
                            $ordered = (float) ($purchaseOrder->ordered_quantity ?? 0);
                            $received = (float) ($purchaseOrder->received_quantity ?? 0);
                            $progress = $ordered > 0 ? min(100, round(($received / $ordered) * 100)) : 0;
                        @endphp
                        <a href="{{ route('pembelian.show', $purchaseOrder->no_po) }}" class="block {{ !$loop->last ? 'mb-3 border-b border-base-300 pb-3' : '' }}">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <div>
                                    <span class="font-semibold">{{ $purchaseOrder->no_po }}</span>
                                    <span class="block text-sm text-base-content/50">{{ $purchaseOrder->supplier->nama ?? '-' }}</span>
                                </div>
                                <span class="text-sm font-semibold">{{ $progress }}%</span>
                            </div>
                            <progress class="progress {{ $progress >= 100 ? 'progress-success' : 'progress-primary' }} w-full" value="{{ $progress }}" max="100"></progress>
                            <span class="text-sm text-base-content/50">{{ number_format($received, 2, ',', '.') }} dari {{ number_format($ordered, 2, ',', '.') }} unit diterima</span>
                        </a>
                    @empty
                        <div class="py-4 text-center text-base-content/50">Tidak ada PO aktif.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">LPB Belum Ditagih</h5>
                        <p class="text-sm text-base-content/50">Barang sudah diterima, invoice belum masuk</p>
                    </div>
                    <a href="{{ route('lpb.index') }}" class="btn btn-sm btn-ghost">Lihat LPB</a>
                </div>
                <div>
                    @forelse($unbilledReceipts as $receipt)
                        <a href="{{ route('lpb.show', $receipt) }}" class="block border-b border-base-300 px-4 py-3 last:border-b-0 hover:bg-base-200/50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ $receipt->id_lpb }}</strong>
                                    <span class="block text-sm text-base-content/50">{{ $receipt->pembelian->supplier->nama ?? '-' }} · PO {{ $receipt->no_po }}</span>
                                </div>
                                <span class="text-sm text-base-content/50">{{ $receipt->tanggal }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="py-4 text-center text-base-content/50">Semua LPB sudah memiliki invoice.</div>
                    @endforelse
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">Jatuh Tempo Invoice</h5>
                        <p class="text-sm text-base-content/50">Prioritas pembayaran supplier</p>
                    </div>
                    <a href="{{ route('invoice-lpb.index') }}" class="btn btn-sm btn-ghost">Lihat invoice</a>
                </div>
                <div>
                    @forelse($dueInvoices as $invoice)
                        @php $overdue = $invoice->tgl_deadline_pembayaran && $invoice->tgl_deadline_pembayaran->isPast(); @endphp
                        <a href="{{ route('invoice-lpb.index', ['invoice' => $invoice->id]) }}" class="block border-b border-base-300 px-4 py-3 last:border-b-0 hover:bg-base-200/50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ $invoice->no_invoice }}</strong>
                                    <span class="block text-sm text-base-content/50">{{ $invoice->supplier->nama ?? '-' }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="badge {{ $overdue ? 'badge-error' : 'badge-warning' }}">{{ $overdue ? 'Terlambat' : 'Belum lunas' }}</span>
                                    <span class="mt-1 block text-sm text-base-content/50">{{ $invoice->tgl_deadline_pembayaran?->format('d-m-Y') ?? 'Tanpa deadline' }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="py-4 text-center text-base-content/50">Tidak ada invoice yang belum lunas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
