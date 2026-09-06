@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Daftar Pengeluaran Barang (NPK)</h3>
                <p class="text-base-content/60">Kelola seluruh transaksi pengeluaran barang gudang</p>
            </div>
            @can('create', App\Models\PemakaianBarang::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('pemakaian-barang.create') }}')">
                    <i class="fa-solid fa-plus"></i> Buat Pemakaian Barang Baru
                </button>
            @endcan
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('pemakaian-barang.index') }}',
                reportUrl: '{{ route('pemakaian-barang.report.pdf') }}',
                extraParams: { status: 'POSTED' },
                columns: [
                    { data: 'kode' }, { data: 'kode_datapesanan' }, { data: 'tanggal' }, { data: 'nama_barang', name: 'barang.nama' }, { data: 'jumlah_display', name: 'jumlah' },
                    @if ($financial) { data: 'harga_satuan' }, { data: 'total_nilai' }, @endif
                    { data: 'status' }, { data: 'operator' }, { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex flex-wrap items-end gap-3 border-b border-base-300 bg-base-200/40 p-4">
                <label class="form-control w-full max-w-xs">
                    <span class="label-text font-semibold text-xs uppercase">Filter Status</span>
                    <select class="select select-bordered select-sm mt-1" x-model="extraParams.status">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">Draft</option>
                        <option value="POSTED">Keluar</option>
                        <option value="REVERSED">Reversed</option>
                    </select>
                </label>
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari NPK..." x-model="search">
                </label>
                <a :href="buildReportUrl()" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a :href="buildReportUrl('excel')" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Kode NPK</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Kode Pesanan</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">Tanggal</th>
                            <th class="cursor-pointer select-none" @click="sortBy(3)">Nama Barang</th>
                            <th class="cursor-pointer select-none text-center" @click="sortBy(4)">Jumlah</th>
                            @if ($financial)
                                <th class="text-end">Harga Rata-rata</th>
                                <th class="text-end">Nilai Pemakaian</th>
                            @endif
                            <th class="text-center">Status</th>
                            <th>Operator</th>
                            <th class="text-center">Aksi</th>
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
                                <td class="font-bold text-primary" x-text="row.kode"></td>
                                <td x-text="row.kode_datapesanan || '-'"></td>
                                <td x-text="row.tanggal"></td>
                                <td class="font-semibold" x-text="row.nama_barang"></td>
                                <td class="text-center font-bold text-primary" x-text="row.jumlah_display"></td>
                                @if ($financial)
                                    <td class="text-end" x-text="formatRupiah(row.harga_satuan || 0)"></td>
                                    <td class="text-end font-semibold" x-text="formatRupiah(row.total_nilai || 0)"></td>
                                @endif
                                <td class="text-center">
                                    <span class="badge"
                                        :class="{ 'badge-success': row.status === 'POSTED', 'badge-error': row.status === 'REVERSED', 'badge-warning': row.status === 'DRAFT' }"
                                        x-text="row.status === 'POSTED' ? 'Keluar' : (row.status === 'REVERSED' ? 'Reversed' : 'Draft')"></span>
                                </td>
                                <td x-text="row.operator || '-'"></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" x-show="row.can_update" class="btn btn-outline btn-info btn-sm"
                                            @click="openAjaxModal(`{{ url('pemakaian-barang') }}/${row.id}/edit`)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" x-show="row.can_delete" class="btn btn-outline btn-error btn-sm"
                                            @click="AppAlert.confirm('Hapus draft pengeluaran barang ini?').then(r => { if (r.isConfirmed) fetch(`{{ url('pemakaian-barang') }}/${row.id}`, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).then(d => { AppAlert.auto(d); fetchData(); }).catch(() => AppAlert.error('Gagal menghapus data.')) })">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <span x-show="!row.can_update && !row.can_delete">-</span>
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
