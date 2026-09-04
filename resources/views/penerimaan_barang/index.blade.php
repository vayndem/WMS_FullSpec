@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Daftar Penerimaan (Barang &amp; Jasa)</h3>
                <p class="text-base-content/60">Kelola penerimaan barang dan dimulainya pekerjaan jasa dari supplier</p>
            </div>
            @can('create', App\Models\PenerimaanBarang::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('penerimaan-barang.create') }}')">
                    <i class="fa-solid fa-plus"></i> Buat Penerimaan Barang Baru
                </button>
            @endcan
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('penerimaan-barang.index') }}',
                reportUrl: '{{ route('penerimaan-barang.report.pdf') }}',
                extraParams: { jenis_lpb: '1' },
                columns: [
                    { data: 'id_lpb' }, { data: 'jenis_lpb_label' }, { data: 'tanggal' }, { data: 'no_po' },
                    { data: 'supplier_nama' }, { data: 'gudang_nama' }, { data: 'no_sj' }, { data: 'user_nama' },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 p-4">
                <div role="tablist" class="tabs tabs-boxed">
                    <a role="tab" class="tab" :class="extraParams.jenis_lpb === '' && 'tab-active'" @click="extraParams.jenis_lpb = ''">
                        <i class="fa-solid fa-layer-group"></i>&nbsp;Semua
                    </a>
                    <a role="tab" class="tab" :class="extraParams.jenis_lpb === '1' && 'tab-active'" @click="extraParams.jenis_lpb = '1'">
                        <i class="fa-solid fa-box"></i>&nbsp;Penerimaan Barang
                    </a>
                    <a role="tab" class="tab" :class="extraParams.jenis_lpb === '3' && 'tab-active'" @click="extraParams.jenis_lpb = '3'">
                        <i class="fa-solid fa-screwdriver-wrench"></i>&nbsp;Penerimaan Jasa
                    </a>
                </div>
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari Penerimaan Barang..." x-model="search">
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
                            <th class="cursor-pointer select-none" @click="sortBy(0)">No Penerimaan Barang</th>
                            <th>Jenis</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">Tanggal</th>
                            <th class="cursor-pointer select-none" @click="sortBy(3)">No PO</th>
                            <th class="cursor-pointer select-none" @click="sortBy(4)">Supplier</th>
                            <th class="cursor-pointer select-none" @click="sortBy(5)">Gudang</th>
                            <th class="cursor-pointer select-none" @click="sortBy(6)">No SJ</th>
                            <th class="cursor-pointer select-none" @click="sortBy(7)">Petugas</th>
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
                    </tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tbody>
                            <tr>
                                <td>
                                    <button type="button" class="btn btn-outline btn-primary btn-xs btn-circle" @click="toggleExpand(row.id)">
                                        <i class="fa-solid" :class="expanded[row.id] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    </button>
                                </td>
                                <td class="font-bold text-primary" x-text="row.id_lpb"></td>
                                <td>
                                    <span class="badge" :class="row.document_type === 'SERVICE_BAP' ? 'badge-info' : 'badge-primary'" x-text="row.jenis_lpb_label"></span>
                                </td>
                                <td x-text="row.tanggal"></td>
                                <td class="font-bold" x-text="row.no_po"></td>
                                <td x-text="row.supplier_nama"></td>
                                <td x-text="row.gudang_nama"></td>
                                <td x-text="row.no_sj"></td>
                                <td x-text="row.user_nama"></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" class="btn btn-outline btn-primary btn-sm" @click="toggleExpand(row.id)">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </button>
                                        <button type="button" x-show="row.can_delete" class="btn btn-outline btn-error btn-sm"
                                            @click="AppAlert.confirm('Hapus penerimaan barang draft ini? Penerimaan yang sudah diposting tidak dapat dihapus.').then(r => { if (r.isConfirmed) fetch(`{{ url('penerimaan-barang') }}/${row.id}`, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).then(d => { AppAlert.auto(d.message ? d : 'Data berhasil dihapus.'); fetchData(); }).catch(() => AppAlert.error('Gagal menghapus data.')) })">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="expanded[row.id]" x-cloak>
                                <td></td>
                                <td colspan="9" class="bg-base-200/40">
                                    <div class="my-2 rounded-lg border border-base-300 bg-base-100 p-4">
                                        <h6 class="mb-2 font-bold">
                                            <i class="fa-solid fa-list-check"></i>
                                            <span x-text="row.document_type === 'SERVICE_BAP' ? 'Detail Penerimaan Jasa' : 'Detail Item Penerimaan Barang'"></span>
                                            (<span x-text="row.id_lpb"></span>)
                                        </h6>
                                        <div class="overflow-x-auto">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th x-text="row.document_type === 'SERVICE_BAP' ? 'Pekerjaan Jasa' : 'Nama Barang / Bahan'"></th>
                                                        <th class="text-center">Kategori</th>
                                                        <th x-text="row.document_type === 'SERVICE_BAP' ? 'Cost Center / Datapesanan' : 'Lot Number'"></th>
                                                        <th class="text-center" x-text="row.document_type === 'SERVICE_BAP' ? 'Status Pekerjaan' : 'Qty Diterima'"></th>
                                                        @if ($financial)
                                                            <th class="text-end" x-text="row.document_type === 'SERVICE_BAP' ? 'Nilai Penerimaan Jasa' : 'Harga Satuan'"></th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-if="row.document_type === 'SERVICE_BAP'">
                                                        <template x-if="!row.service_details || row.service_details.length === 0">
                                                            <tr>
                                                                <td colspan="5" class="text-center text-base-content/50">Tidak ada detail item penerimaan.</td>
                                                            </tr>
                                                        </template>
                                                    </template>
                                                    <template x-if="row.document_type === 'SERVICE_BAP'">
                                                        <template x-for="item in (row.service_details || [])" :key="item.id">
                                                            <tr>
                                                                <td x-text="item.service_po_detail?.description || '-'"></td>
                                                                <td class="text-center">
                                                                    <span class="badge badge-primary badge-outline" x-text="item.service_po_detail?.category?.display_code || '-'"></span>
                                                                    <span x-text="item.service_po_detail?.category?.name || ''"></span>
                                                                </td>
                                                                <td>
                                                                    <template x-if="item.allocations && item.allocations.length > 0">
                                                                        <span>
                                                                            <template x-for="alloc in item.allocations" :key="alloc.datapesanan_code">
                                                                                <span class="badge badge-ghost mr-1 mb-1" x-text="`${alloc.datapesanan_code} (${Number(alloc.percentage).toLocaleString('id-ID')}%)`"></span>
                                                                            </template>
                                                                        </span>
                                                                    </template>
                                                                    <span x-show="!item.allocations || item.allocations.length === 0" x-text="item.department_cost_center || '-'"></span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge" :class="row.no_invoice ? 'badge-success' : 'badge-warning'" x-text="row.no_invoice ? 'Selesai 100%' : 'Sedang dikerjakan'"></span>
                                                                </td>
                                                                @if ($financial)
                                                                    <td class="text-end font-semibold" x-text="formatRupiah(item.amount || 0)"></td>
                                                                @endif
                                                            </tr>
                                                        </template>
                                                    </template>
                                                    <template x-if="row.document_type !== 'SERVICE_BAP'">
                                                        <template x-if="!row.details || row.details.length === 0">
                                                            <tr>
                                                                <td colspan="5" class="text-center text-base-content/50">Tidak ada detail item penerimaan.</td>
                                                            </tr>
                                                        </template>
                                                    </template>
                                                    <template x-if="row.document_type !== 'SERVICE_BAP'">
                                                        <template x-for="item in (row.details || [])" :key="item.id">
                                                            <tr>
                                                                <td x-text="item.bahan ? item.bahan.nama : '-'"></td>
                                                                <td class="text-center" x-text="item.kategori ? item.kategori.katnama : '-'"></td>
                                                                <td class="text-center" x-text="item.lot_number ?? '-'"></td>
                                                                <td class="text-center font-bold text-success" x-text="item.jumlah_barang_diterima"></td>
                                                                @if ($financial)
                                                                    <td class="text-end" x-text="item.harga ? formatRupiah(item.harga) : '-'"></td>
                                                                @endif
                                                            </tr>
                                                        </template>
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
