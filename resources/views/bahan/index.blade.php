@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Master Bahan</h3>
                <p class="text-base-content/60">Lihat posisi stok, layer persediaan, gudang, dan informasi bahan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @can('create', App\Models\Bahan::class)
                    <a href="{{ route('request.index', ['create' => 1]) }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Ajukan Barang Baru
                    </a>
                @endcan
                @unless ($financial)
                    <span class="badge badge-primary badge-outline"><i class="fa-solid fa-shield-halved"></i> Harga hanya untuk Accounting</span>
                @endunless
            </div>
        </div>

        <div
            x-data="wmsDataTable({
                url: '{{ route('bahan.index') }}',
                extraParams: { kategori_id: '{{ $kategoris->first()->id ?? '' }}', gudang_id: '{{ $gudangs->first()->id ?? '' }}' },
                columns: [
                    { data: 'nama' }, { data: 'kategori_nama' }, { data: 'gudang_nama' },
                    { data: 'stok_onhand' }, { data: 'stok_onpurchase' }, { data: 'layer_quantity' },
                    @if ($financial) { data: 'average_cost' }, { data: 'inventory_value' }, @endif
                    { data: 'stock_status', orderable: false, searchable: false },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="card mb-3 border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body grid grid-cols-1 items-end gap-3 p-4 md:grid-cols-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Kategori</span></label>
                        <select class="select select-bordered" x-model="extraParams.kategori_id">
                            <option value="">Semua kategori</option>
                            @foreach ($kategoris as $kategori)
                                <option value="{{ $kategori->id }}">{{ $kategori->katnama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Posisi Gudang</span></label>
                        <select class="select select-bordered" x-model="extraParams.gudang_id">
                            <option value="">Semua gudang</option>
                            @foreach ($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline"
                        @click="extraParams.kategori_id = '{{ $kategoris->first()->id ?? '' }}'; extraParams.gudang_id = '{{ $gudangs->first()->id ?? '' }}'; search = ''">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </button>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-base-300 p-4">
                    <p class="text-sm text-base-content/50"><i class="fa-solid fa-circle-info"></i> Klik Detail untuk melihat seluruh layer stok barang.</p>
                    <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                        <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                        <input type="search" class="grow" placeholder="Cari bahan..." x-model="search">
                    </label>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="cursor-pointer select-none" @click="sortBy(0)">Nama Bahan</th>
                                <th class="cursor-pointer select-none" @click="sortBy(1)">Kategori</th>
                                <th class="cursor-pointer select-none" @click="sortBy(2)">Gudang</th>
                                <th class="cursor-pointer select-none text-end" @click="sortBy(3)">On Hand</th>
                                <th class="cursor-pointer select-none text-end" @click="sortBy(4)">On Purchase</th>
                                <th class="cursor-pointer select-none text-end" @click="sortBy(5)">Total Layer</th>
                                @if ($financial)
                                    <th class="text-end">Harga Rata-rata</th>
                                    <th class="text-end">Nilai Persediaan</th>
                                @endif
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="10" class="py-6 text-center text-base-content/50">
                                        <span class="loading loading-spinner loading-sm"></span> Memuat data...
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="10" class="py-6 text-center text-base-content/50">Data tidak ditemukan</td>
                                </tr>
                            </template>
                            <template x-for="row in rows" :key="row.id">
                                <tr>
                                    <td class="font-semibold" x-text="row.nama"></td>
                                    <td x-text="row.kategori_nama"></td>
                                    <td x-text="row.gudang_nama"></td>
                                    <td class="text-end">
                                        <span x-text="`${Number(row.stok_onhand || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })} ${row.satuan || ''}`"></span>
                                        <template x-if="row.satuan_kecil && Number(row.berat_kecil || 1) > 1">
                                            <small class="block text-base-content/50"
                                                x-text="`= ${Number((row.stok_onhand || 0) * row.berat_kecil).toLocaleString('id-ID', { maximumFractionDigits: 2 })} ${row.satuan_kecil}`"></small>
                                        </template>
                                    </td>
                                    <td class="text-end" x-text="Number(row.stok_onpurchase || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })"></td>
                                    <td class="text-end" x-text="Number(row.layer_quantity || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })"></td>
                                    @if ($financial)
                                        <td class="text-end" x-text="'Rp ' + Number(row.average_cost || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></td>
                                        <td class="text-end font-semibold" x-text="'Rp ' + Number(row.inventory_value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></td>
                                    @endif
                                    <td>
                                        <span class="badge" :class="row.stock_status === 'VALID' ? 'badge-success' : 'badge-error'" x-text="row.stock_status"></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="flex items-center justify-end gap-1">
                                            <a :href="`{{ url('bahan') }}/${row.id}`" class="btn btn-outline btn-primary btn-sm">
                                                <i class="fa-solid fa-eye"></i> Detail
                                            </a>
                                            <a x-show="row.can_update" :href="`{{ url('bahan') }}/${row.id}/edit`" class="btn btn-outline btn-warning btn-sm">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-base-300 p-4 text-sm">
                    <span class="text-base-content/60">
                        Menampilkan <span x-text="rangeStart"></span>–<span x-text="rangeEnd"></span> dari
                        <span x-text="recordsFiltered"></span> data
                    </span>
                    <div class="join">
                        <button type="button" class="join-item btn btn-sm" :disabled="currentPage === 0" @click="goToPage(currentPage - 1)">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button type="button" class="join-item btn btn-sm btn-disabled" x-text="`${currentPage + 1} / ${pageCount}`"></button>
                        <button type="button" class="join-item btn btn-sm" :disabled="currentPage >= pageCount - 1" @click="goToPage(currentPage + 1)">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
