@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Daftar Request</h3>
                <p class="text-base-content/60">Kelola seluruh pengajuan request barang perusahaan</p>
            </div>
            @can('create', App\Models\Request::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('request.create') }}')">
                    <i class="fa-solid fa-plus"></i> Buat Request Baru
                </button>
            @endcan
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('request.index') }}',
                reportUrl: '{{ route('request.report.pdf') }}',
                extraParams: { status: 'PENDING' },
                columns: [
                    { data: 'no_request' }, { data: 'status' },
                    { data: 'created_at' }, { data: 'aksi', orderable: false, searchable: false },
                ],
            })"
            @wms:table-refresh.window="fetchData()"
            x-init="if (new URLSearchParams(window.location.search).get('create') === '1') { openAjaxModal('{{ route('request.create') }}'); }">
            <div class="flex flex-wrap items-end gap-3 border-b border-base-300 bg-base-200/40 p-4">
                <label class="form-control w-full max-w-xs">
                    <span class="label-text font-semibold text-xs uppercase">Filter Status</span>
                    <select class="select select-bordered select-sm mt-1" x-model="extraParams.status">
                        <option value="">Semua Status</option>
                        <option value="PENDING">Pending</option>
                        <option value="APPROVED">Approved</option>
                        <option value="REJECTED">Rejected</option>
                    </select>
                </label>
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari request..." x-model="search">
                </label>
                <a :href="buildReportUrl()" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-8"></th>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">No Request</th>
                            <th class="cursor-pointer select-none text-center" @click="sortBy(1)">Status</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">Tgl Request</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="5" class="py-6 text-center text-base-content/50">
                                    <span class="loading loading-spinner loading-sm"></span> Memuat data...
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="5" class="py-6 text-center text-base-content/50">Data tidak ditemukan</td>
                            </tr>
                        </template>
                    </tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tbody>
                            <tr>
                                <td>
                                    <button type="button" class="btn btn-outline btn-primary btn-xs btn-circle" @click="toggleExpand(row.id)">
                                        <i class="fa-solid" :class="expanded[row.id] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    </button>
                                </td>
                                <td class="font-bold text-primary" x-text="row.no_request"></td>
                                <td class="text-center">
                                    <span class="badge text-white"
                                        :class="{
                                            'badge-warning': row.status === 'PENDING',
                                            'badge-success': row.status === 'APPROVED',
                                            'badge-error': row.status === 'REJECTED',
                                        }"
                                        x-text="row.status === 'PENDING' ? 'Pending' : (row.status === 'APPROVED' ? 'Approved' : 'Rejected')"></span>
                                </td>
                                <td x-text="row.formatted_date"></td>
                                <td class="text-center">
                                    <template x-if="row.can_approve">
                                        <a class="btn btn-success btn-sm" :href="`{{ url('request') }}/${row.id}/approve`">
                                            <i class="fa-solid fa-check"></i> ACC Request
                                        </a>
                                    </template>
                                    <template x-if="!row.can_approve">
                                        <button type="button" class="btn btn-ghost btn-sm border border-base-300" @click="toggleExpand(row.id)">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </button>
                                    </template>
                                </td>
                            </tr>
                            <tr x-show="expanded[row.id]" x-cloak>
                                <td></td>
                                <td colspan="4" class="bg-base-200/40">
                                    <div class="my-2 rounded-lg border-l-4 border-primary bg-base-100 p-4 shadow-sm">
                                        <h6 class="mb-3 font-bold">
                                            <i class="fa-solid fa-list-check text-primary"></i>
                                            Detail Item Request (<span x-text="row.no_request"></span>)
                                        </h6>
                                        <div class="overflow-x-auto">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Nama Barang</th>
                                                        <th class="text-center">Jumlah Minta</th>
                                                        <th class="text-center">Jumlah ACC</th>
                                                        <th>Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-if="!row.details || row.details.length === 0">
                                                        <tr>
                                                            <td colspan="4" class="text-center text-base-content/50">Tidak ada detail item.</td>
                                                        </tr>
                                                    </template>
                                                    <template x-for="item in row.details" :key="item.id">
                                                        <tr>
                                                            <td class="font-semibold" x-text="item.nama_barang"></td>
                                                            <td class="text-center font-bold text-primary" x-text="item.jumlah_minta"></td>
                                                            <td class="text-center font-bold text-success" x-text="item.jumlah_acc ?? '-'"></td>
                                                            <td x-text="item.keterangan ?? '-'"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </template>
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
