@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Master Tipe Pembebanan</h3>
                <p class="text-base-content/60">Kelola kategori tipe pembebanan akuntansi untuk bahan</p>
            </div>
            @can('create', App\Models\TipePembebanan::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('tipe-pembebanan.create') }}')">
                    <i class="fa-solid fa-plus"></i> Tambah Tipe Pembebanan
                </button>
            @endcan
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('tipe-pembebanan.index') }}',
                reportUrl: '{{ route('tipe-pembebanan.report.pdf') }}',
                columns: [
                    { data: 'nama_tipe' }, { data: 'keterangan' },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari tipe pembebanan..." x-model="search">
                </label>
                <a :href="buildReportUrl()" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Nama Tipe</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="4" class="py-6 text-center text-base-content/50">
                                    <span class="loading loading-spinner loading-sm"></span> Memuat data...
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="4" class="py-6 text-center text-base-content/50">Data tidak ditemukan</td>
                            </tr>
                        </template>
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr>
                                <td class="text-center" x-text="rangeStart + index"></td>
                                <td class="font-semibold text-primary" x-text="row.nama_tipe"></td>
                                <td x-text="row.keterangan || '-'"></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" x-show="row.can_update" class="btn btn-outline btn-warning btn-sm"
                                            @click="openAjaxModal(`{{ url('tipe-pembebanan') }}/${row.id}/edit`)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form :action="`{{ url('tipe-pembebanan') }}/${row.id}`" method="POST" x-show="row.can_delete" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline btn-error btn-sm"
                                                @click="confirmAjaxDelete($event, 'Hapus tipe pembebanan ini?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
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

    <div id="modal-container"></div>
@endsection
