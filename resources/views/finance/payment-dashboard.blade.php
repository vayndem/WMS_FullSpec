@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Selamat datang, {{ $user['name'] ?? 'Finance' }}</h3>
                <p class="text-base-content/60">Kelola prioritas dan pencatatan pembayaran invoice supplier.</p>
            </div>
            <a href="{{ route('invoice-lpb.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-money-check-dollar"></i> Buka Daftar Invoice
            </a>
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
            @foreach ([['Invoice belum lunas', $metrics['unpaid_count'], 'dokumen', 'fa-file-invoice-dollar', 'primary'], ['Total sisa tagihan', 'Rp ' . number_format($metrics['outstanding_value'], 2, ',', '.'), null, 'fa-wallet', 'info'], ['Melewati jatuh tempo', $metrics['overdue_count'], 'invoice', 'fa-triangle-exclamation', $metrics['overdue_count'] ? 'danger' : 'success'], ['Jatuh tempo ≤ 7 hari', $metrics['due_soon_count'], 'invoice', 'fa-calendar-day', 'warning']] as [$label, $value, $suffix, $icon, $color])
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm text-base-content/50">{{ $label }}</p>
                                <p class="mt-1 {{ is_numeric($value) ? 'text-3xl' : 'text-xl' }} font-bold">
                                    {{ is_numeric($value) ? number_format($value) : $value }}</p>
                                @if ($suffix)
                                    <p class="text-sm text-base-content/50">{{ $suffix }}</p>
                                @endif
                            </div>
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl {{ $colorClasses[$color] }}">
                                <i class="fa-solid {{ $icon }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div role="alert" class="alert alert-info mb-4">
            <i class="fa-solid fa-chart-line"></i>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <strong>Bulan {{ now()->translatedFormat('F Y') }}</strong>
                <span>{{ number_format($metrics['payments_this_month']) }} transaksi · kas dibayarkan <strong>Rp {{ number_format($metrics['paid_this_month'], 2, ',', '.') }}</strong></span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
            <div class="card border border-base-300 bg-base-100 shadow-sm xl:col-span-7">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-base-300 p-4">
                    <div>
                        <h5 class="font-bold">Prioritas Pembayaran</h5>
                        <p class="text-sm text-base-content/50">Diurutkan dari jatuh tempo terdekat</p>
                    </div>
                    <a href="{{ route('invoice-lpb.index') }}" class="btn btn-sm btn-ghost">Lihat semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice / Supplier</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Sisa Tagihan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priorityInvoices as $invoice)
                                @php
                                    $overdue = $invoice->tgl_deadline_pembayaran && $invoice->tgl_deadline_pembayaran->isPast();
                                    $dueSoon = $invoice->tgl_deadline_pembayaran && !$overdue && $invoice->tgl_deadline_pembayaran->lte(today()->addDays(7));
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $invoice->no_invoice }}</strong>
                                        <span class="block text-sm text-base-content/50">{{ $invoice->supplier->nama ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $overdue ? 'badge-error' : ($dueSoon ? 'badge-warning' : 'badge-ghost') }}">
                                            {{ $invoice->tgl_deadline_pembayaran?->format('d-m-Y') ?? 'Tanpa deadline' }}
                                        </span>
                                    </td>
                                    <td class="text-end font-semibold">Rp {{ number_format((float) $invoice->sisa_tagihan, 2, ',', '.') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('invoice-lpb.index', ['invoice' => $invoice->id]) }}" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-money-bill-transfer"></i> Bayar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-5 text-center text-base-content/50">Tidak ada invoice yang perlu dibayar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm xl:col-span-5">
                <div class="border-b border-base-300 p-4">
                    <h5 class="font-bold">Pembayaran Terakhir</h5>
                    <p class="text-sm text-base-content/50">Riwayat transaksi terbaru</p>
                </div>
                <div>
                    @forelse($recentPayments as $payment)
                        <div class="border-b border-base-300 px-4 py-3 last:border-b-0">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ $payment->invoice->no_invoice ?? '-' }}</strong>
                                    <span class="block text-sm text-primary">{{ $payment->payment_number ?? '-' }}</span>
                                    <span class="block text-sm text-base-content/50">{{ $payment->invoice->supplier->nama ?? '-' }}</span>
                                    <span class="block text-sm text-base-content/50">{{ $payment->coaKasBank->nama_akun ?? $payment->metode_pembayaran }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="font-semibold text-success">Rp {{ number_format((float) $payment->jumlah_pembayaran, 2, ',', '.') }}</span>
                                    <span class="block text-sm text-base-content/50">{{ $payment->tanggal_pembayaran?->format('d-m-Y') }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-5 text-center text-base-content/50">Belum ada riwayat pembayaran.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
