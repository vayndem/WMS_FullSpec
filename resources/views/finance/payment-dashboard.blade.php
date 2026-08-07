@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Selamat datang, {{ $user['name'] ?? 'Finance' }}</h3>
                    <p class="text-muted mb-0">Kelola prioritas dan pencatatan pembayaran invoice supplier.</p>
                </div>
                <a href="{{ route('invoice-lpb.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-money-check-dollar me-1"></i>Buka Daftar Invoice
                </a>
            </div>

            <div class="row g-3 mb-4">
                @foreach ([['Invoice belum lunas', $metrics['unpaid_count'], 'dokumen', 'fa-file-invoice-dollar', 'primary'], ['Total sisa tagihan', 'Rp ' . number_format($metrics['outstanding_value'], 2, ',', '.'), null, 'fa-wallet', 'info'], ['Melewati jatuh tempo', $metrics['overdue_count'], 'invoice', 'fa-triangle-exclamation', $metrics['overdue_count'] ? 'danger' : 'success'], ['Jatuh tempo ≤ 7 hari', $metrics['due_soon_count'], 'invoice', 'fa-calendar-day', 'warning']] as [$label, $value, $suffix, $icon, $color])
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <small class="text-muted">{{ $label }}</small>
                                        <div class="{{ is_numeric($value) ? 'display-6' : 'fs-4' }} fw-bold mt-1">
                                            {{ is_numeric($value) ? number_format($value) : $value }}</div>
                                        @if ($suffix)
                                            <small class="text-muted">{{ $suffix }}</small>
                                        @endif
                                    </div>
                                    <span
                                        class="payment-metric-icon bg-{{ $color }}-subtle text-{{ $color }}"><i
                                            class="fa-solid {{ $icon }}"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-3 border border-primary-subtle bg-primary-subtle text-primary-emphasis p-3 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div><i class="fa-solid fa-chart-line me-2"></i><strong>Bulan
                            {{ now()->translatedFormat('F Y') }}</strong></div>
                    <div>{{ number_format($metrics['payments_this_month']) }} transaksi · kas dibayarkan <strong>Rp
                            {{ number_format($metrics['paid_this_month'], 2, ',', '.') }}</strong></div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-xl-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="mb-1">Prioritas Pembayaran</h5><small class="text-muted">Diurutkan dari jatuh
                                    tempo terdekat</small>
                            </div>
                            <a href="{{ route('invoice-lpb.index') }}" class="btn btn-sm btn-light">Lihat semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Invoice / Supplier</th>
                                            <th>Jatuh tempo</th>
                                            <th class="text-end">Sisa tagihan</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($priorityInvoices as $invoice)
                                            @php
                                                $overdue =
                                                    $invoice->tgl_deadline_pembayaran &&
                                                    $invoice->tgl_deadline_pembayaran->isPast();
                                                $dueSoon =
                                                    $invoice->tgl_deadline_pembayaran &&
                                                    !$overdue &&
                                                    $invoice->tgl_deadline_pembayaran->lte(today()->addDays(7));
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $invoice->no_invoice }}</strong><small
                                                        class="d-block text-muted">{{ $invoice->supplier->nama ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $overdue ? 'bg-danger' : ($dueSoon ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                        {{ $invoice->tgl_deadline_pembayaran?->format('d-m-Y') ?? 'Tanpa deadline' }}
                                                    </span>
                                                </td>
                                                <td class="text-end fw-semibold">Rp
                                                    {{ number_format((float) $invoice->sisa_tagihan, 2, ',', '.') }}</td>
                                                <td class="text-end"><a
                                                        href="{{ route('invoice-lpb.index', ['invoice' => $invoice->id]) }}"
                                                        class="btn btn-sm btn-success"><i
                                                            class="fa-solid fa-money-bill-transfer me-1"></i>Bayar</a></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-5">Tidak ada invoice
                                                    yang perlu dibayar.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-1">Pembayaran Terakhir</h5><small class="text-muted">Riwayat transaksi
                                terbaru</small>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($recentPayments as $payment)
                                <div class="list-group-item px-4 py-3">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <strong>{{ $payment->invoice->no_invoice ?? '-' }}</strong>
                                            <small class="d-block text-primary">{{ $payment->payment_number ?? '-' }}</small>
                                            <small
                                                class="d-block text-muted">{{ $payment->invoice->supplier->nama ?? '-' }}</small>
                                            <small
                                                class="d-block text-muted">{{ $payment->coaKasBank->nama_akun ?? $payment->metode_pembayaran }}</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-semibold text-success">Rp
                                                {{ number_format((float) $payment->jumlah_pembayaran, 2, ',', '.') }}</span>
                                            <small
                                                class="d-block text-muted">{{ $payment->tanggal_pembayaran?->format('d-m-Y') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-5">Belum ada riwayat pembayaran.</div>
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
        .payment-metric-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
    </style>
@endpush
