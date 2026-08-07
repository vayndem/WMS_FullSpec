@extends('layouts.app')

@section('content')
    @php($isService = $lpb->document_type === 'SERVICE_BAP')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="{{ route('lpb.index') }}" class="btn btn-sm btn-light border" title="Kembali">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <h3 class="fw-bold text-dark mb-0">
                            {{ $isService ? 'Detail BAP Jasa' : 'Detail Penerimaan Barang' }}
                        </h3>
                    </div>
                    <p class="text-muted mb-0">
                        {{ $isService ? 'Informasi pekerjaan jasa yang mulai dikerjakan; selesai ketika masuk invoice.' : 'Informasi barang yang telah diterima dari supplier.' }}
                    </p>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                        {{ $lpb->id_lpb }}
                    </span>
                    @if (!$lpb->no_invoice)
                        <div class="mt-2"><span class="badge bg-warning-subtle text-warning-emphasis">Belum ditagih</span></div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Nomor PO</small>
                            <div class="fw-semibold text-primary">{{ $lpb->no_po }}</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Tanggal</small>
                            <div class="fw-semibold">{{ $lpb->tanggal?->format('d-m-Y') ?? '-' }}</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">{{ $isService ? 'Dokumen/BA' : 'Surat Jalan' }}</small>
                            <div class="fw-semibold">{{ $lpb->no_sj ?: '-' }}</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Supplier</small>
                            <div class="fw-semibold">{{ $lpb->pembelian->supplier->nama ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-1">{{ $isService ? 'Detail Jasa Diterima' : 'Detail Barang Diterima' }}</h5>
                    <small class="text-muted">
                        {{ $isService ? $lpb->serviceDetails->count() : $lpb->details->count() }} detail
                    </small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if ($isService)
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Jasa</th>
                                        <th>Kategori</th>
                                        <th>Status Pekerjaan</th>
                                        <th>Cost Center/Alokasi</th>
                                        @if ($financial)<th class="text-end pe-4">Nilai BAP</th>@endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lpb->serviceDetails as $detail)
                                        <tr>
                                            <td class="ps-4 fw-semibold">
                                                {{ $detail->servicePoDetail->description ?? '-' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary">
                                                    {{ $detail->servicePoDetail->category->display_code ?? '-' }}
                                                </span>
                                                <small class="d-block text-muted mt-1">
                                                    {{ $detail->kategori->katnama ?? 'Belum dimapping' }}
                                                </small>
                                            </td>
                                            <td>{{ $lpb->no_invoice ? 'Selesai 100%' : 'Sedang dikerjakan' }}</td>
                                            <td>
                                                @if ($detail->allocations->isNotEmpty())
                                                    @foreach ($detail->allocations as $allocation)
                                                        <span class="badge bg-light text-dark border me-1 mb-1">
                                                            {{ $allocation->datapesanan_code }}
                                                            ({{ number_format((float) $allocation->percentage, 2, ',', '.') }}%)
                                                        </span>
                                                    @endforeach
                                                @else
                                                    {{ $detail->department_cost_center ?: '-' }}
                                                @endif
                                            </td>
                                            @if ($financial)
                                                <td class="text-end fw-semibold pe-4">
                                                    Rp {{ number_format((float) $detail->amount, 0, ',', '.') }}
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-5">Tidak ada detail jasa.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @else
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Barang</th>
                                        <th>Kategori</th>
                                        <th>Lot</th>
                                        <th class="text-end">Kuantitas</th>
                                        @if ($financial)<th class="text-end pe-4">Harga Satuan</th>@endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lpb->details as $detail)
                                        <tr>
                                            <td class="ps-4 fw-semibold">{{ $detail->bahan->nama ?? '-' }}</td>
                                            <td>{{ $detail->kategori->katnama ?? '-' }}</td>
                                            <td>{{ $detail->lot_number ?: '-' }}</td>
                                            <td class="text-end">
                                                {{ number_format((float) $detail->jumlah_barang_diterima, 2, ',', '.') }}
                                                {{ $detail->bahan->satuan ?? '' }}
                                            </td>
                                            @if ($financial)
                                                <td class="text-end fw-semibold pe-4">
                                                    Rp {{ number_format((float) $detail->harga, 0, ',', '.') }}
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-5">Tidak ada detail barang.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-white p-3 text-end">
                    <a href="{{ route('lpb.index') }}" class="btn btn-light border px-4">Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection
