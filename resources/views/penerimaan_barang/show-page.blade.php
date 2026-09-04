@extends('layouts.app')

@section('content')
    @php($isService = $lpb->document_type === 'SERVICE_BAP')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="mb-1 flex items-center gap-2">
                    <a href="{{ route('penerimaan-barang.index') }}" class="btn btn-sm btn-ghost border border-base-300" title="Kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <h3 class="text-2xl font-bold">{{ $isService ? 'Detail Penerimaan Jasa' : 'Detail Penerimaan Barang' }}</h3>
                </div>
                <p class="text-base-content/60">
                    {{ $isService ? 'Informasi pekerjaan jasa yang mulai dikerjakan; selesai ketika masuk invoice.' : 'Informasi barang yang telah diterima dari supplier.' }}
                </p>
            </div>
            <div class="text-end">
                <span class="badge badge-lg badge-primary badge-outline">{{ $lpb->id_lpb }}</span>
                @if (!$lpb->no_invoice)
                    <div class="mt-2"><span class="badge badge-warning">Belum ditagih</span></div>
                @endif
            </div>
        </div>

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body grid grid-cols-2 gap-4 p-4 lg:grid-cols-4">
                <div>
                    <p class="mb-1 text-sm text-base-content/50">Nomor PO</p>
                    <p class="font-semibold text-primary">{{ $lpb->no_po }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-base-content/50">Tanggal</p>
                    <p class="font-semibold">{{ $lpb->tanggal?->format('d-m-Y') ?? '-' }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-base-content/50">{{ $isService ? 'Dokumen/BA' : 'Surat Jalan' }}</p>
                    <p class="font-semibold">{{ $lpb->no_sj ?: '-' }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-base-content/50">Supplier</p>
                    <p class="font-semibold">{{ $lpb->pembelian->supplier->nama ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h5 class="font-bold">{{ $isService ? 'Detail Jasa Diterima' : 'Detail Barang Diterima' }}</h5>
                <p class="text-sm text-base-content/50">{{ $isService ? $lpb->serviceDetails->count() : $lpb->details->count() }} detail</p>
            </div>
            <div class="overflow-x-auto">
                @if ($isService)
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Jasa</th>
                                <th>Kategori</th>
                                <th>Status Pekerjaan</th>
                                <th>Cost Center/Alokasi</th>
                                @if ($financial)<th class="text-end">Nilai Penerimaan Jasa</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lpb->serviceDetails as $detail)
                                <tr>
                                    <td class="font-semibold">{{ $detail->servicePoDetail->description ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-primary badge-outline">{{ $detail->servicePoDetail->category->display_code ?? '-' }}</span>
                                        <p class="mt-1 text-sm text-base-content/50">{{ $detail->kategori->katnama ?? 'Belum dimapping' }}</p>
                                    </td>
                                    <td>{{ $lpb->no_invoice ? 'Selesai 100%' : 'Sedang dikerjakan' }}</td>
                                    <td>
                                        @if ($detail->allocations->isNotEmpty())
                                            @foreach ($detail->allocations as $allocation)
                                                <span class="badge badge-ghost mb-1 mr-1">
                                                    {{ $allocation->datapesanan_code }}
                                                    ({{ number_format((float) $allocation->percentage, 2, ',', '.') }}%)
                                                </span>
                                            @endforeach
                                        @else
                                            {{ $detail->department_cost_center ?: '-' }}
                                        @endif
                                    </td>
                                    @if ($financial)
                                        <td class="text-end font-semibold">Rp {{ number_format((float) $detail->amount, 0, ',', '.') }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-5 text-center text-base-content/50">Tidak ada detail jasa.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Lot</th>
                                <th class="text-end">Kuantitas</th>
                                @if ($financial)<th class="text-end">Harga Satuan</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lpb->details as $detail)
                                <tr>
                                    <td class="font-semibold">{{ $detail->bahan->nama ?? '-' }}</td>
                                    <td>{{ $detail->kategori->katnama ?? '-' }}</td>
                                    <td>{{ $detail->lot_number ?: '-' }}</td>
                                    <td class="text-end">
                                        {{ number_format((float) $detail->jumlah_barang_diterima, 2, ',', '.') }} {{ $detail->bahan->satuan ?? '' }}
                                    </td>
                                    @if ($financial)
                                        <td class="text-end font-semibold">Rp {{ number_format((float) $detail->harga, 0, ',', '.') }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-5 text-center text-base-content/50">Tidak ada detail barang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="flex justify-end border-t border-base-300 p-4">
                <a href="{{ route('penerimaan-barang.index') }}" class="btn btn-ghost border border-base-300">Kembali</a>
            </div>
        </div>
    </div>
@endsection
