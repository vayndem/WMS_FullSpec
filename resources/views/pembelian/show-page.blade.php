@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="{{ route('pembelian.index') }}" class="btn btn-sm btn-light border" title="Kembali">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <h3 class="fw-bold text-dark mb-0">Detail Purchase Order</h3>
                    </div>
                    <p class="text-muted mb-0">Informasi pemesanan dan realisasi penerimaan barang.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                    {{ $pembelian->no_po }}
                </span>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Tanggal PO</small>
                            <div class="fw-semibold">
                                {{ $pembelian->tanggal ? \Illuminate\Support\Carbon::parse($pembelian->tanggal)->format('d-m-Y') : '-' }}
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Supplier</small>
                            <div class="fw-semibold">{{ $pembelian->supplier->nama ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Nomor Request</small>
                            <div class="fw-semibold">{{ $pembelian->no_order ?: '-' }}</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Status</small>
                            <span
                                class="badge {{ (int) $pembelian->status === 2 ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ (int) $pembelian->status === 2 ? 'Selesai' : 'Aktif' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-1">Detail Item PO</h5>
                    <small class="text-muted">{{ $pembelian->details->count() }} item</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Barang</th>
                                    <th class="text-end">Dipesan</th>
                                    <th class="text-end">Diterima</th>
                                    <th class="text-end">Sisa</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end pe-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pembelian->details as $detail)
                                    @php
                                        $ordered = (float) $detail->jumlah;
                                        $received = (float) $detail->diterima;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold">{{ $detail->bahan->nama ?? '-' }}</div>
                                            <small class="text-muted">{{ $detail->bahan->satuan ?? '' }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($ordered, 2, ',', '.') }}</td>
                                        <td class="text-end text-success fw-semibold">
                                            {{ number_format($received, 2, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format(max(0, $ordered - $received), 2, ',', '.') }}
                                        </td>
                                        <td class="text-end">Rp {{ number_format((float) $detail->harga, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end fw-semibold pe-4">
                                            Rp
                                            {{ number_format((float) ($detail->exclude ?? $ordered * $detail->harga), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">Tidak ada detail PO.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Grand Total</th>
                                    <th class="text-end pe-4 text-primary">
                                        Rp {{ number_format((float) $pembelian->grand_total, 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-end gap-2">
                    <a href="{{ route('pembelian.index') }}" class="btn btn-light border px-4">Kembali</a>
                    <form method="POST" action="{{ route('pembelian.cetak', $pembelian->no_po) }}" target="_blank"
                        class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-file-pdf me-2"></i>Cetak PO
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
