@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Daftar Jurnal</h3>
                <p class="text-base-content/60">Kelola header jurnal transaksi dan penyesuaian akuntansi</p>
            </div>
            @can('create', App\Models\Jurnal::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('jurnal.create') }}')">
                    <i class="fa-solid fa-plus"></i> Buat Jurnal Baru
                </button>
            @endcan
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="jurnalDataTable({
                url: '{{ route('jurnal.index') }}',
                reportUrl: '{{ route('jurnal.report.pdf') }}',
                defaultOrderColumn: 1, defaultOrderDir: 'desc',
                extraParams: { sumber_transaksi: '', status: '', date_from: '', date_to: '' },
                columns: [
                    { data: 'no_jurnal' }, { data: 'tanggal' }, { data: 'sumber_transaksi' }, { data: 'keterangan' },
                    { data: 'total_debit' }, { data: 'total_kredit' }, { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="grid grid-cols-1 gap-3 border-b border-base-300 p-4 md:grid-cols-5">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Sumber Transaksi</span></label>
                    <select class="select select-bordered select-sm" x-model="extraParams.sumber_transaksi">
                        <option value="">Semua sumber</option>
                        <option value="MANUAL">Manual</option>
                        <option value="LPB">LPB</option>
                        <option value="NPK">NPK</option>
                        <option value="INVOICE_SUPPLIER">Invoice Supplier</option>
                        <option value="PELUNASAN_HUTANG">Pelunasan Hutang</option>
                        <option value="REVERSAL">Reversal</option>
                        <option value="ASSET_ACQUISITION">Perolehan Aset</option>
                        <option value="ASSET_DEPRECIATION">Penyusutan Aset</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Status</span></label>
                    <select class="select select-bordered select-sm" x-model="extraParams.status">
                        <option value="">Semua status</option>
                        <option value="DRAFT">Draft</option>
                        <option value="POSTED">Posted</option>
                        <option value="REVERSED">Reversed</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Dari Tanggal</span></label>
                    <input type="date" class="input input-bordered input-sm" x-model="extraParams.date_from">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Sampai Tanggal</span></label>
                    <input type="date" class="input input-bordered input-sm" x-model="extraParams.date_to">
                </div>
                <div class="flex items-end gap-2">
                    <button type="button" class="btn btn-outline btn-sm flex-1" @click="extraParams = { sumber_transaksi: '', status: '', date_from: '', date_to: '' }">Reset Filter</button>
                    <a :href="buildReportUrl()" target="_blank" rel="noopener" class="btn btn-error btn-sm"><i class="fa-solid fa-file-pdf"></i></a>
                </div>
            </div>

            <div class="flex items-center justify-end border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari jurnal..." x-model="search">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">No. Jurnal</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Tanggal</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">Sumber Transaksi</th>
                            <th class="cursor-pointer select-none" @click="sortBy(3)">Keterangan</th>
                            <th class="cursor-pointer select-none text-end" @click="sortBy(4)">Total Debit</th>
                            <th class="cursor-pointer select-none text-end" @click="sortBy(5)">Total Kredit</th>
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
                                <td class="font-bold text-primary" x-text="row.no_jurnal"></td>
                                <td x-text="row.tanggal"></td>
                                <td><span class="badge badge-ghost" x-text="String(row.sumber_transaksi || '-').replaceAll('_', ' ')"></span></td>
                                <td x-text="row.keterangan || '-'"></td>
                                <td class="text-end font-bold text-success" x-text="formatRupiah(row.total_debit)"></td>
                                <td class="text-end font-bold text-error" x-text="formatRupiah(row.total_kredit)"></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" class="btn btn-outline btn-info btn-sm" @click="openShow(row.id, $refs.jurnalShowDialog)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button type="button" x-show="row.can_post" class="btn btn-success btn-sm" title="Posting"
                                            @click="AppAlert.confirm('Setelah diposting jurnal akan terkunci. Lanjutkan?').then(r => r.isConfirmed && postJurnal(row.id))">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button type="button" x-show="row.can_reverse" class="btn btn-outline btn-error btn-sm" title="Balik jurnal"
                                            @click="AppAlert.confirm('Buat jurnal pembalik? Transaksi sumber tidak otomatis dibatalkan.').then(r => r.isConfirmed && reverseJurnal(row.id))">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                        <button type="button" x-show="row.can_update" class="btn btn-outline btn-warning btn-sm"
                                            @click="openAjaxModal(`{{ url('jurnal') }}/${row.id}/edit`)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" x-show="row.can_delete" class="btn btn-outline btn-error btn-sm"
                                            @click="AppAlert.confirm('Hapus jurnal draft ini beserta detailnya?').then(r => { if (r.isConfirmed) fetch(`{{ url('jurnal') }}/${row.id}`, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }).then(r => r.json()).then(d => { AppAlert.auto(d); fetchData(); }).catch(() => AppAlert.error('Gagal menghapus data.')) })">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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

            <dialog x-ref="jurnalShowDialog" class="modal">
                <div class="modal-box max-w-3xl" x-show="jurnal">
                    <h3 class="mb-4 text-lg font-bold"><i class="fa-solid fa-book"></i> Jurnal: <span x-text="jurnal?.no_jurnal"></span></h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div><p class="text-sm text-base-content/50">Tanggal</p><p class="text-lg font-bold" x-text="jurnal?.tanggal"></p></div>
                        <div><p class="text-sm text-base-content/50">Sumber Transaksi</p><p class="text-lg font-bold" x-text="jurnal?.sumber_transaksi"></p></div>
                        <div><p class="text-sm text-base-content/50">Reff ID</p><p class="text-lg font-bold" x-text="jurnal?.reff_id ?? '-'"></p></div>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div><p class="text-sm text-base-content/50">Total Debit</p><p class="text-lg font-bold text-success" x-text="formatRupiah(jurnal?.total_debit || 0)"></p></div>
                        <div><p class="text-sm text-base-content/50">Total Kredit</p><p class="text-lg font-bold text-error" x-text="formatRupiah(jurnal?.total_kredit || 0)"></p></div>
                    </div>
                    <div class="mt-3">
                        <p class="text-sm text-base-content/50">Keterangan Header</p>
                        <p class="font-bold" x-text="jurnal?.keterangan ?? '-'"></p>
                    </div>
                    <div class="divider"></div>
                    <h6 class="mb-3 font-bold"><i class="fa-solid fa-list-ol text-info"></i> Rincian Entri Jurnal</h6>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Akun (COA)</th>
                                    <th>Keterangan Baris</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Kredit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="!jurnal?.details || jurnal.details.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-3 text-center text-base-content/50">Belum ada item rincian jurnal.</td>
                                    </tr>
                                </template>
                                <template x-for="(item, i) in (jurnal?.details || [])" :key="item.id">
                                    <tr>
                                        <td class="text-center" x-text="i + 1"></td>
                                        <td class="font-bold" x-text="item.coa ? `${item.coa.kode_akun} - ${item.coa.nama_akun}` : '-'"></td>
                                        <td x-text="item.keterangan || '-'"></td>
                                        <td class="text-end font-bold text-success" x-text="formatRupiah(item.debit)"></td>
                                        <td class="text-end font-bold text-error" x-text="formatRupiah(item.kredit)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" class="btn btn-ghost border border-base-300" @click="$refs.jurnalShowDialog.close()">Tutup</button>
                    </div>
                </div>
                <div class="modal-backdrop" @click="$refs.jurnalShowDialog.close()"></div>
            </dialog>
        </div>
    </div>

    <div id="modal-container"></div>

    <script>
        // wmsDataTable plus jurnal-specific show/post/reverse actions for this page only.
        document.addEventListener('alpine:init', () => {
            Alpine.data('jurnalDataTable', (config) => {
                const base = window.wmsDataTable(config);
                return {
                    ...base,
                    jurnal: null,
                    async openShow(id, dialogEl) {
                        try {
                            const response = await fetch(`{{ url('jurnal') }}/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                            const res = await response.json();
                            if (!res.success) return;
                            this.jurnal = res.data;
                            dialogEl.showModal();
                        } catch (error) {
                            window.AppAlert.error('Gagal mengambil data detail jurnal.');
                        }
                    },
                    async postJurnal(id) {
                        try {
                            const response = await fetch(`{{ url('jurnal') }}/${id}/post`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                            const data = await response.json();
                            if (!response.ok) { window.AppAlert.error(data.message || 'Gagal memposting jurnal.'); return; }
                            window.AppAlert.success(data.message);
                            this.fetchData();
                        } catch (error) {
                            window.AppAlert.error('Gagal memposting jurnal.');
                        }
                    },
                    async reverseJurnal(id) {
                        try {
                            const response = await fetch(`{{ url('jurnal') }}/${id}/reverse`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                            const data = await response.json();
                            if (!response.ok) { window.AppAlert.error(data.message || 'Gagal membalik jurnal.'); return; }
                            window.AppAlert.success(data.message);
                            this.fetchData();
                        } catch (error) {
                            window.AppAlert.error('Gagal membalik jurnal.');
                        }
                    },
                };
            });
        });
    </script>
@endsection
