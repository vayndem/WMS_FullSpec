@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="mb-1 flex items-center gap-2">
                    <a href="{{ route('pembelian.index') }}" class="btn btn-sm btn-ghost border border-base-300" title="Kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <h3 class="text-2xl font-bold">Detail Purchase Order</h3>
                </div>
                <p class="text-base-content/60">Informasi pemesanan dan realisasi penerimaan barang.</p>
            </div>
            <span class="badge badge-lg badge-primary badge-outline">{{ $pembelian->no_po }}</span>
        </div>

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body grid grid-cols-2 gap-4 p-4 lg:grid-cols-4">
                <div>
                    <p class="mb-1 text-sm text-base-content/50">Tanggal PO</p>
                    <p class="font-semibold">{{ $pembelian->tanggal ? \Illuminate\Support\Carbon::parse($pembelian->tanggal)->format('d-m-Y') : '-' }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-base-content/50">Supplier</p>
                    <p class="font-semibold">{{ $pembelian->supplier->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-base-content/50">Nomor Request</p>
                    <p class="font-semibold">{{ $pembelian->no_order ?: '-' }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-base-content/50">Status</p>
                    <span class="badge {{ $pembelian->status === \App\Models\PesananPembelian::CLOSED ? 'badge-success' : 'badge-warning' }}">
                        {{ $pembelian->status === \App\Models\PesananPembelian::CLOSED ? 'Selesai' : 'Aktif' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h5 class="font-bold">Detail Item PO</h5>
                <p class="text-sm text-base-content/50">{{ $pembelian->details->count() }} item</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th class="text-end">Dipesan</th>
                            <th class="text-end">Diterima</th>
                            <th class="text-end">Sisa</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pembelian->details as $detail)
                            @php
                                $ordered = (float) $detail->jumlah;
                                $received = (float) $detail->diterima;
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $detail->bahan->nama ?? '-' }}</p>
                                    <p class="text-sm text-base-content/50">{{ $detail->bahan->satuan ?? '' }}</p>
                                </td>
                                <td class="text-end">{{ number_format($ordered, 2, ',', '.') }}</td>
                                <td class="text-end font-semibold text-success">{{ number_format($received, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format(max(0, $ordered - $received), 2, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $detail->harga, 0, ',', '.') }}</td>
                                <td class="text-end font-semibold">Rp {{ number_format((float) ($detail->exclude ?? $ordered * $detail->harga), 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-base-content/50">Tidak ada detail PO.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Grand Total</th>
                            <th class="text-end text-primary">Rp {{ number_format((float) $pembelian->grand_total, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 p-4">
                <a href="{{ route('pembelian.index') }}" class="btn btn-ghost border border-base-300">Kembali</a>
                <form method="POST" action="{{ route('pembelian.cetak', $pembelian->no_po) }}" target="_blank">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-file-pdf"></i> Cetak PO
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
