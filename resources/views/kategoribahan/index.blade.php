@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Kategori Bahan</h3>
                <p class="text-base-content/60">Mapping akun wajib untuk setiap alur persediaan.</p>
            </div>
            @can('create', App\Models\KategoriBahan::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('kategori-bahan.create') }}')">
                    <i class="fa-solid fa-plus"></i> Tambah Kategori
                </button>
            @endcan
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('kategori-bahan.index') }}',
                columns: [
                    { data: 'katnama' }, { data: 'tipe_pembebanan_nama' },
                    { data: 'coa_persediaan_label', orderable: false }, { data: 'coa_beban_label', orderable: false },
                    { data: 'coa_clearing_lpb_label', orderable: false }, { data: 'coa_beban_selisih_opname_label', orderable: false },
                    { data: 'coa_koreksi_opname_label', orderable: false }, { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex items-center justify-between gap-3 border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari kategori..." x-model="search">
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Kategori</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Tipe</th>
                            <th>Persediaan</th>
                            <th>Pemakaian</th>
                            <th>GRNI</th>
                            <th>Selisih -</th>
                            <th>Selisih +</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="8" class="py-6 text-center text-base-content/50">
                                    <span class="loading loading-spinner loading-sm"></span> Memuat data...
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="8" class="py-6 text-center text-base-content/50">Data tidak ditemukan</td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="row.id">
                            <tr>
                                <td class="font-semibold text-primary" x-text="row.katnama"></td>
                                <td x-text="row.tipe_pembebanan_nama || '-'"></td>
                                <td x-text="row.coa_persediaan_label || '-'"></td>
                                <td x-text="row.coa_beban_label || '-'"></td>
                                <td x-text="row.coa_clearing_lpb_label || '-'"></td>
                                <td x-text="row.coa_beban_selisih_opname_label || '-'"></td>
                                <td x-text="row.coa_koreksi_opname_label || '-'"></td>
                                <td class="text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" x-show="row.can_update" class="btn btn-outline btn-primary btn-sm"
                                            @click="openAjaxModal(`{{ url('kategori-bahan') }}/${row.id}/edit`)">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form :action="`{{ url('kategori-bahan') }}/${row.id}`" method="POST" x-show="row.can_delete" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline btn-error btn-sm"
                                                @click="confirmAjaxDelete($event, 'Hapus kategori yang belum dipakai?')">
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
