@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Daftar Supplier</h3>
                <p class="text-base-content/60">Kelola data vendor dan supplier perusahaan</p>
            </div>
            @can('create', App\Models\Supplier::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('supplier.create') }}')">
                    <i class="fa-solid fa-plus"></i> Tambah Supplier
                </button>
            @endcan
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mb-4 shadow-sm">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('supplier.index') }}',
                reportUrl: '{{ route('supplier.report.pdf') }}',
                columns: [
                    { data: 'nama' }, { data: 'alamat' }, { data: 'npwp' },
                    { data: 'telp' }, { data: 'up' }, { data: 'pembayaran' },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari supplier..." x-model="search">
                </label>
                <a :href="buildReportUrl()" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Nama</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Alamat</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">NPWP</th>
                            <th class="cursor-pointer select-none" @click="sortBy(3)">Telp</th>
                            <th class="cursor-pointer select-none" @click="sortBy(4)">UP</th>
                            <th class="cursor-pointer select-none" @click="sortBy(5)">Pembayaran</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="py-6 text-center text-base-content/50">
                                    <span class="loading loading-spinner loading-sm"></span> Memuat data...
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="7" class="py-6 text-center text-base-content/50">Data tidak ditemukan</td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="row.id">
                            <tr>
                                <td class="font-semibold text-primary" x-text="row.nama"></td>
                                <td x-text="row.alamat || '-'"></td>
                                <td x-text="row.npwp || '-'"></td>
                                <td x-text="row.telp || '-'"></td>
                                <td x-text="row.up || '-'"></td>
                                <td x-text="row.pembayaran || '-'"></td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" x-show="row.can_update" title="Edit Supplier"
                                            class="btn btn-ghost btn-sm btn-square text-warning"
                                            @click="openAjaxModal(`{{ url('supplier') }}/${row.id}/edit`)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form :action="`{{ url('supplier') }}/${row.id}`" method="POST" x-show="row.can_delete"
                                            @submit.prevent="AppAlert.confirm('Yakin ingin menghapus supplier ini?').then(r => r.isConfirmed && $event.target.submit())">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Supplier" class="btn btn-ghost btn-sm btn-square text-error">
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
