@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Retur Pembelian</h3>
                <p class="text-base-content/60">Kelola pengembalian barang ke supplier sebelum LPB ditagih</p>
            </div>
            @can('create', App\Models\ReturPembelian::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('retur-pembelian.create') }}')">
                    <i class="fa-solid fa-plus"></i> Buat Retur Pembelian
                </button>
            @endcan
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('retur-pembelian.index') }}',
                columns: [
                    { data: 'no_retur' }, { data: 'tanggal' }, { data: 'id_lpb' },
                    { data: 'alasan' }, { data: 'total_nilai' }, { data: 'status_label' },
                ],
            })">
            <div class="flex items-center justify-end border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari retur..." x-model="search">
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">No. Retur</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Tanggal</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">No. LPB</th>
                            <th class="cursor-pointer select-none" @click="sortBy(3)">Alasan</th>
                            <th class="cursor-pointer select-none text-end" @click="sortBy(4)">Total Nilai</th>
                            <th class="cursor-pointer select-none text-center" @click="sortBy(5)">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="py-6 text-center text-base-content/50">
                                    <span class="loading loading-spinner loading-sm"></span> Memuat data...
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="6" class="py-6 text-center text-base-content/50">Data tidak ditemukan</td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="row.id">
                            <tr>
                                <td class="font-bold text-primary" x-text="row.no_retur"></td>
                                <td x-text="row.tanggal"></td>
                                <td x-text="row.id_lpb"></td>
                                <td x-text="row.alasan"></td>
                                <td class="text-end font-bold" x-text="formatRupiah(row.total_nilai)"></td>
                                <td class="text-center">
                                    <span class="badge" :class="row.status === 'POSTED' ? 'badge-success' : 'badge-ghost'" x-text="row.status === 'POSTED' ? 'Aktif' : 'Dibalik'"></span>
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

    <div id="modal-container"></div>
@endsection
