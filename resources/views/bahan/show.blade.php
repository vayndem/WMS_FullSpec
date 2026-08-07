@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('bahan.index') }}" class="btn btn-light" aria-label="Kembali"><i
                            class="fa-solid fa-arrow-left"></i></a>
                    <div>
                        <h3 class="mb-1">{{ $bahan->nama }}</h3>
                        <p class="text-muted mb-0">{{ $bahan->keterangan_bahan ?: 'Detail persediaan bahan' }}</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @can('update', $bahan)
                        <a href="{{ route('bahan.edit', $bahan) }}" class="btn btn-outline-warning">
                            <i class="fa-solid fa-pen me-1"></i>Edit Master
                        </a>
                    @endcan
                    @unless ($financial)
                        <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2"><i
                                class="fa-solid fa-lock me-1"></i>Harga dilindungi policy Accounting</span>
                    @endunless
                </div>
            </div>

            @if ($bahan->hasSmallUnit())
                <div class="rounded-3 bg-info-subtle text-info-emphasis border border-info-subtle shadow-sm p-3 mb-4">
                    <i class="fa-solid fa-scale-balanced me-2"></i>
                    <strong>Konversi:</strong> 1 {{ $bahan->satuan }} =
                    {{ number_format((float) $bahan->berat_kecil, 2, ',', '.') }} {{ $bahan->satuan_kecil }}.
                    Stok saat ini setara dengan
                    <strong>{{ number_format((float) $bahan->smallUnitEquivalent((float) $bahan->stok_onhand), 2, ',', '.') }}
                        {{ $bahan->satuan_kecil }}</strong>.
                </div>
            @endif

            <div class="row g-3 mb-4">
                @foreach ([['Stok on hand', $bahan->stok_onhand, 'fa-boxes-stacked', 'primary'], ['On purchase', $bahan->stok_onpurchase, 'fa-cart-shopping', 'info'], ['Planning', $bahan->planning, 'fa-list-check', 'warning'], ['Jumlah layer', $layers->count(), 'fa-layer-group', 'success']] as [$label, $value, $icon, $color])
                    <div class="col-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-2">
                                    <div><small class="text-muted">{{ $label }}</small>
                                        <div class="fs-5 fw-bold mt-1">
                                            {{ number_format((float) $value, 2, ',', '.') }}{{ $label !== 'Jumlah layer' ? ' ' . $bahan->satuan : '' }}
                                        </div>
                                    </div><i class="fa-solid {{ $icon }} text-{{ $color }}"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="mb-3">Informasi Master</h5>
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted">Kategori</dt>
                                <dd class="col-7">{{ $bahan->kategoriBahan->katnama ?? '-' }}</dd>
                                <dt class="col-5 text-muted">Tipe barang</dt>
                                <dd class="col-7">{{ $bahan->tipeBarang->katnama ?? '-' }}</dd>
                                <dt class="col-5 text-muted">Gudang utama</dt>
                                <dd class="col-7">{{ $bahan->gudang->nama ?? '-' }}</dd>
                                <dt class="col-5 text-muted">Satuan</dt>
                                <dd class="col-7">{{ $bahan->satuan }}</dd>
                                <dt class="col-5 text-muted">Satuan kecil</dt>
                                <dd class="col-7">{{ $bahan->berat_kecil ?: '-' }} {{ $bahan->satuan_kecil ?: '' }}</dd>
                                <dt class="col-5 text-muted">Stok awal</dt>
                                <dd class="col-7">{{ number_format((float) $bahan->stokawal, 2, ',', '.') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="mb-3">Kesehatan Layer</h5>
                            @php
                                $layerQuantity = (float) $layers->sum('remaining_quantity');
                                $difference = (float) $bahan->stok_onhand - $layerQuantity;
                            @endphp
                            <div class="row g-3">
                                <div class="col-sm-4"><small class="text-muted">Total layer aktif</small>
                                    <div class="fw-bold fs-5">{{ number_format($layerQuantity, 2, ',', '.') }}
                                        {{ $bahan->satuan }}</div>
                                </div>
                                <div class="col-sm-4"><small class="text-muted">Selisih kuantitas</small>
                                    <div
                                        class="fw-bold fs-5 {{ abs($difference) <= 0.000001 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($difference, 2, ',', '.') }}</div>
                                </div>
                                <div class="col-sm-4"><small class="text-muted">Status</small>
                                    <div class="mt-1"><span
                                            class="badge {{ abs($difference) <= 0.000001 ? 'bg-success' : 'bg-danger' }}">{{ abs($difference) <= 0.000001 ? 'VALID' : 'SELISIH' }}</span>
                                    </div>
                                </div>
                                @if ($financial)
                                    <div class="col-sm-6"><small class="text-muted">Harga rata-rata layer aktif</small>
                                        <div class="fw-bold fs-5">Rp
                                            {{ number_format($layerQuantity > 0 ? $layers->sum(fn($layer) => $layer->remaining_quantity * $layer->unit_cost) / $layerQuantity : 0, 2, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="col-sm-6"><small class="text-muted">Total nilai persediaan</small>
                                        <div class="fw-bold fs-5 text-primary">Rp
                                            {{ number_format($layers->sum(fn($layer) => $layer->remaining_quantity * $layer->unit_cost), 2, ',', '.') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Detail Layer Persediaan</h5>
                    <div class="table-responsive">
                        <table id="layer-table" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Sumber</th>
                                    <th>Referensi</th>
                                    <th class="text-end">Jumlah awal</th>
                                    <th class="text-end">Sisa</th>
                                    @if ($financial)
                                        <th class="text-end">Harga satuan</th>
                                        <th class="text-end">Nilai tersisa</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($layers as $layer)
                                    <tr>
                                        <td>{{ $layer->transaction_date?->format('d-m-Y') }}</td>
                                        <td><span
                                                class="badge bg-primary-subtle text-primary">{{ $layer->source_type }}</span>
                                        </td>
                                        <td>#{{ $layer->source_id }}</td>
                                        <td class="text-end">
                                            {{ number_format((float) $layer->initial_quantity, 2, ',', '.') }}</td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format((float) $layer->remaining_quantity, 2, ',', '.') }}</td>
                                        @if ($financial)
                                            <td class="text-end">Rp
                                                {{ number_format((float) $layer->unit_cost, 2, ',', '.') }}</td>
                                            <td class="text-end fw-semibold">Rp
                                                {{ number_format($layer->remaining_quantity * $layer->unit_cost, 2, ',', '.') }}
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $financial ? 7 : 5 }}" class="text-center text-muted py-4">Belum
                                            ada layer persediaan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if ($('#layer-table tbody tr').length > 1) $('#layer-table').DataTable({
                pageLength: 25,
                order: [
                    [0, 'desc']
                ]
            });
        });
    </script>
@endpush
