@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('bahan.index') }}" class="btn btn-ghost border border-base-300" aria-label="Kembali">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h3 class="text-2xl font-bold">{{ $bahan->nama }}</h3>
                    <p class="text-base-content/60">{{ $bahan->keterangan_bahan ?: 'Detail persediaan bahan' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('update', $bahan)
                    <a href="{{ route('bahan.edit', $bahan) }}" class="btn btn-outline btn-warning">
                        <i class="fa-solid fa-pen"></i> Edit Master
                    </a>
                @endcan
                @unless ($financial)
                    <span class="badge badge-primary badge-outline"><i class="fa-solid fa-lock"></i> Harga dilindungi policy Accounting</span>
                @endunless
            </div>
        </div>

        @if ($bahan->hasSmallUnit())
            <div role="alert" class="alert alert-info mb-4 text-sm">
                <i class="fa-solid fa-scale-balanced"></i>
                <span>
                    <strong>Konversi:</strong> 1 {{ $bahan->satuan }} =
                    {{ number_format((float) $bahan->berat_kecil, 2, ',', '.') }} {{ $bahan->satuan_kecil }}.
                    Stok saat ini setara dengan
                    <strong>{{ number_format((float) $bahan->smallUnitEquivalent((float) $bahan->stok_onhand), 2, ',', '.') }} {{ $bahan->satuan_kecil }}</strong>.
                </span>
            </div>
        @endif

        <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([['Stok on hand', $bahan->stok_onhand, 'fa-boxes-stacked', 'text-primary'], ['On purchase', $bahan->stok_onpurchase, 'fa-cart-shopping', 'text-info'], ['Planning', $bahan->planning, 'fa-list-check', 'text-warning'], ['Jumlah layer', $layers->count(), 'fa-layer-group', 'text-success']] as [$label, $value, $icon, $color])
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm text-base-content/50">{{ $label }}</p>
                                <p class="mt-1 text-lg font-bold">
                                    {{ number_format((float) $value, 2, ',', '.') }}{{ $label !== 'Jumlah layer' ? ' ' . $bahan->satuan : '' }}
                                </p>
                            </div>
                            <i class="fa-solid {{ $icon }} {{ $color }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3 font-bold">Posisi per Gudang</h5>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Gudang</th>
                                <th>Jenis</th>
                                <th class="text-end">Tersedia</th>
                                <th class="text-end">Reservasi</th>
                                <th class="text-end">Bebas</th>
                                <th class="text-end">Dipesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bahan->stokGudangs as $stok)
                                <tr>
                                    <td>{{ $stok->gudang->nama ?? '-' }}</td>
                                    <td>{{ $stok->gudang->jenis ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $stok->stok_tersedia, 6, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $stok->stok_direservasi, 6, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $stok->stok_bebas, 6, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $stok->stok_dipesan, 6, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-base-content/50">Belum memiliki saldo gudang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="card border border-base-300 bg-base-100 shadow-sm lg:col-span-5">
                <div class="card-body p-4">
                    <h5 class="mb-3 font-bold">Informasi Master</h5>
                    <dl class="grid grid-cols-1 gap-y-2 text-sm sm:grid-cols-2">
                        <dt class="text-base-content/50">Kategori</dt>
                        <dd>{{ $bahan->kategoriBahan->katnama ?? '-' }}</dd>
                        <dt class="text-base-content/50">Tipe barang</dt>
                        <dd>{{ $bahan->tipeBarang->katnama ?? '-' }}</dd>
                        <dt class="text-base-content/50">Gudang utama</dt>
                        <dd>{{ $bahan->gudang->nama ?? '-' }}</dd>
                        <dt class="text-base-content/50">Satuan</dt>
                        <dd>{{ $bahan->satuan }}</dd>
                        <dt class="text-base-content/50">Satuan kecil</dt>
                        <dd>{{ $bahan->berat_kecil ?: '-' }} {{ $bahan->satuan_kecil ?: '' }}</dd>
                        <dt class="text-base-content/50">Stok awal</dt>
                        <dd>{{ number_format((float) $bahan->stokawal, 2, ',', '.') }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card border border-base-300 bg-base-100 shadow-sm lg:col-span-7">
                <div class="card-body p-4">
                    <h5 class="mb-3 font-bold">Kesehatan Layer</h5>
                    @php
                        $layerQuantity = (float) $layers->sum('remaining_quantity');
                        $difference = (float) $bahan->stok_onhand - $layerQuantity;
                        $isValid = abs($difference) <= 0.000001;
                    @endphp
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <p class="text-sm text-base-content/50">Total layer aktif</p>
                            <p class="text-lg font-bold">{{ number_format($layerQuantity, 2, ',', '.') }} {{ $bahan->satuan }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/50">Selisih kuantitas</p>
                            <p class="text-lg font-bold {{ $isValid ? 'text-success' : 'text-error' }}">{{ number_format($difference, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-base-content/50">Status</p>
                            <span class="badge {{ $isValid ? 'badge-success' : 'badge-error' }} mt-1">{{ $isValid ? 'VALID' : 'SELISIH' }}</span>
                        </div>
                        @if ($financial)
                            <div class="col-span-1">
                                <p class="text-sm text-base-content/50">Harga rata-rata layer aktif</p>
                                <p class="text-lg font-bold">Rp {{ number_format($layerQuantity > 0 ? $layers->sum(fn($layer) => $layer->remaining_quantity * $layer->unit_cost) / $layerQuantity : 0, 2, ',', '.') }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-base-content/50">Total nilai persediaan</p>
                                <p class="text-lg font-bold text-primary">Rp {{ number_format($layers->sum(fn($layer) => $layer->remaining_quantity * $layer->unit_cost), 2, ',', '.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3 font-bold">Detail Layer Persediaan</h5>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>Sumber</th>
                                <th>Referensi</th>
                                <th class="text-end">Jumlah Awal</th>
                                <th class="text-end">Sisa</th>
                                @if ($financial)
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-end">Nilai Tersisa</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($layers as $layer)
                                <tr>
                                    <td>{{ $layer->transaction_date?->format('d-m-Y') }}</td>
                                    <td>{{ $layer->gudang->nama ?? '-' }}</td>
                                    <td><span class="badge badge-primary badge-outline">{{ $layer->source_type }}</span></td>
                                    <td>#{{ $layer->source_id }}</td>
                                    <td class="text-end">{{ number_format((float) $layer->initial_quantity, 2, ',', '.') }}</td>
                                    <td class="text-end font-semibold">{{ number_format((float) $layer->remaining_quantity, 2, ',', '.') }}</td>
                                    @if ($financial)
                                        <td class="text-end">Rp {{ number_format((float) $layer->unit_cost, 2, ',', '.') }}</td>
                                        <td class="text-end font-semibold">Rp {{ number_format($layer->remaining_quantity * $layer->unit_cost, 2, ',', '.') }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $financial ? 8 : 6 }}" class="py-4 text-center text-base-content/50">Belum ada layer persediaan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
