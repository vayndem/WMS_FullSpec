@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Daftar Transaksi Pembelian</h3>
                <p class="text-base-content/60">Kelola seluruh riwayat dan pengajuan Purchase Order (PO)</p>
            </div>
            @can('create', App\Models\PesananPembelian::class)
                <button type="button" class="btn btn-primary" onclick="Alpine.$data(pembelianDialog).openCreate()">
                    <i class="fa-solid fa-plus"></i> Tambah Pembelian
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
                url: '{{ route('pembelian.index') }}',
                reportUrl: '{{ route('pembelian.report.pdf') }}',
                extraParams: { bulan: '{{ date('m') }}', tahun: '{{ date('Y') }}' },
                columns: [
                    { data: 'no_po' }, { data: 'tanggal' }, { data: 'nama' },
                    { data: 'grand_total' }, { data: 'status' }, { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="grid grid-cols-1 gap-3 border-b border-base-300 p-4 md:grid-cols-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Filter Bulan</span></label>
                    <select class="select select-bordered select-sm" x-model="extraParams.bulan">
                        <option value="0">Semua Bulan</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ sprintf('%02d', $m) }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Filter Tahun</span></label>
                    <input type="number" class="input input-bordered input-sm" x-model="extraParams.tahun">
                </div>
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Cari</span></label>
                    <label class="input input-bordered input-sm flex items-center gap-2">
                        <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                        <input type="search" class="grow" placeholder="Cari PO / supplier..." x-model="search">
                    </label>
                </div>
                <div>
                    <a :href="buildReportUrl()" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-8"></th>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">No PO</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Tanggal</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">Supplier</th>
                            <th class="cursor-pointer select-none text-end" @click="sortBy(3)">Grand Total</th>
                            <th class="cursor-pointer select-none text-center" @click="sortBy(4)">Status</th>
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
                    </tbody>
                    <template x-for="row in rows" :key="row.no_po">
                        <tbody>
                            <tr>
                                <td>
                                    <button type="button" class="btn btn-outline btn-primary btn-xs btn-circle" @click="toggleExpand(row.no_po)">
                                        <i class="fa-solid" :class="expanded[row.no_po] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    </button>
                                </td>
                                <td class="font-bold text-primary" x-text="row.no_po"></td>
                                <td x-text="row.tanggal"></td>
                                <td x-text="row.nama"></td>
                                <td class="text-end font-bold" x-text="formatRupiah(row.grand_total || 0)"></td>
                                <td class="text-center">
                                    <span class="badge" :class="row.status == 2 ? 'badge-error' : 'badge-success'" x-text="row.status == 2 ? 'Closed' : 'Open'"></span>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a :href="`{{ url('pembelian') }}/${row.no_po}/cetak`" target="_blank" class="btn btn-outline btn-info btn-sm" title="Cetak PO">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <template x-if="row.kunci == 0">
                                            <span class="inline-flex gap-1">
                                                <button type="button" class="btn btn-outline btn-warning btn-sm" title="Edit PO"
                                                    @click="Alpine.$data(pembelianDialog).openEdit(row.no_po)">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline btn-error btn-sm" title="Hapus PO"
                                                    @click="Alpine.$data(pembelianDialog).deletePo(row.no_po)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </span>
                                        </template>
                                        <span class="badge badge-ghost" x-show="row.kunci != 0"><i class="fa-solid fa-lock"></i> Locked</span>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="expanded[row.no_po]" x-cloak>
                                <td></td>
                                <td colspan="6" class="bg-base-200/40">
                                    <div class="my-2 rounded-lg border border-base-300 bg-base-100 p-4">
                                        <h6 class="mb-2 font-bold"><i class="fa-solid fa-boxes-packing"></i> Detail Item yang Dibeli (No PO: <span x-text="row.no_po"></span>)</h6>
                                        <div class="overflow-x-auto">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Nama Barang / Bahan</th>
                                                        <th class="text-center">Jumlah Beli</th>
                                                        <th class="text-end">Harga Satuan</th>
                                                        <th class="text-end">Subtotal</th>
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
                                                            <td x-text="item.bahan ? item.bahan.nama : ('Bahan #' + item.bahan_id)"></td>
                                                            <td class="text-center font-bold" x-text="item.jumlah"></td>
                                                            <td class="text-end" x-text="formatRupiah(item.harga || 0)"></td>
                                                            <td class="text-end font-bold text-primary" x-text="formatRupiah(item.include || (item.jumlah * item.harga) || 0)"></td>
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

    <dialog id="pembelianDialog" class="modal" x-data="pembelianForm({ documentNumber: '{{ $documentNumber }}' })">
        <div class="modal-box max-w-6xl p-0 overflow-hidden">
            <div class="flex items-center gap-3 bg-primary px-6 py-4 text-primary-content">
                <i class="fa-solid fa-cart-shopping"></i>
                <h3 class="text-lg font-bold" x-text="editMode ? `Edit Transaksi Pembelian (${editNoPo})` : 'Buat Transaksi Pembelian Baru'"></h3>
            </div>
            <form @submit.prevent="submit()" class="flex flex-col">
                <div class="max-h-[75vh] overflow-y-auto p-6">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nomor PO</span></label>
                            <input type="text" class="input input-bordered bg-base-200" :value="form.no_po" readonly>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Tanggal</span></label>
                            <input type="date" x-model="form.tanggal" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Supplier</span></label>
                            <div class="join w-full">
                                <input type="text" :value="form.supplier_nama" class="input input-bordered join-item flex-1 bg-base-200" placeholder="Pilih Supplier..." readonly required>
                                <button type="button" class="join-item btn btn-outline" @click="openSupplierPicker()">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">No. SO / Order</span></label>
                            <input type="text" x-model="form.no_order" class="input input-bordered">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Untuk Perhatian (ATTN)</span></label>
                            <input type="text" x-model="form.untuk_perhatian" class="input input-bordered">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Term Pembayaran</span></label>
                            <input type="text" x-model="form.term" class="input input-bordered">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Pilihan PPN (11%)</span></label>
                            <select x-model="form.is_ppn" class="select select-bordered">
                                <option value="0">Non-PPN</option>
                                <option value="1">Gunakan PPN (11%)</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Diskon (Rp)</span></label>
                            <input type="number" step="any" min="0" x-model="form.diskon" data-money-input class="input input-bordered">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Ongkir / Handling (Rp)</span></label>
                            <input type="number" step="any" min="0" x-model="form.ongkir" data-money-input class="input input-bordered">
                        </div>
                        <div class="form-control md:col-span-4">
                            <label class="label"><span class="label-text font-semibold">Catatan / Notes</span></label>
                            <textarea x-model="form.notes" class="textarea textarea-bordered" rows="2"></textarea>
                        </div>
                    </div>

                    <div x-show="supplierPickerOpen" x-cloak class="mt-4 rounded-lg border border-base-300 bg-base-200/40 p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h6 class="font-bold"><i class="fa-solid fa-truck-field text-primary"></i> Pilih Supplier</h6>
                                <p class="text-sm text-base-content/50">Cari berdasarkan nama, telepon, atau alamat supplier.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline" @click="supplierPickerOpen = false">
                                <i class="fa-solid fa-xmark"></i> Tutup
                            </button>
                        </div>
                        <label class="input input-bordered input-sm mb-3 flex w-full max-w-xs items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                            <input type="search" class="grow" placeholder="Cari supplier..." x-model="supplierSearchTerm" @input.debounce.350ms="fetchSuppliers()">
                        </label>
                        <div class="max-h-72 overflow-y-auto overflow-x-auto">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama Supplier</th>
                                        <th>Telepon / HP</th>
                                        <th>Alamat</th>
                                        <th class="w-24 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in supplierResults" :key="item.id">
                                        <tr>
                                            <td x-text="item.nama"></td>
                                            <td x-text="item.telepon || '-'"></td>
                                            <td x-text="item.alamat || '-'"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-success btn-xs" @click="pickSupplier(item)">
                                                    <i class="fa-solid fa-check"></i> Pilih
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Gudang Tujuan <span class="text-error">*</span></span></label>
                            <select x-model="form.gudang_id" class="select select-bordered" required>
                                <option value="">-- Pilih Gudang --</option>
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}">{{ $gudang->kode }} - {{ $gudang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div x-show="requestPickerOpen" x-cloak class="mb-4 rounded-lg border border-base-300 bg-base-200/40 p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h6 class="font-bold"><i class="fa-solid fa-boxes-stacked text-primary"></i> Pilih Item Permintaan (Request ACC)</h6>
                                <p class="text-sm text-base-content/50">Cari berdasarkan kode request atau nama barang, lalu pilih item yang akan dibeli.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline" @click="requestPickerOpen = false">
                                <i class="fa-solid fa-xmark"></i> Tutup
                            </button>
                        </div>
                        <label class="input input-bordered input-sm mb-3 flex w-full max-w-xs items-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                            <input type="search" class="grow" placeholder="Cari permintaan..." x-model="requestSearchTerm" @input.debounce.350ms="fetchRequests()">
                        </label>
                        <div class="max-h-72 overflow-y-auto overflow-x-auto">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Kode Request</th>
                                        <th>Nama Barang / Bahan</th>
                                        <th>Target ACC</th>
                                        <th>Realisasi</th>
                                        <th>Referensi Harga</th>
                                        <th>Sisa Kuota</th>
                                        <th class="w-24 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in requestResults" :key="item.id_permintaan + '-' + item.id_bahan">
                                        <tr>
                                            <td class="font-bold text-primary" x-text="item.no_request"></td>
                                            <td x-text="item.bahan"></td>
                                            <td x-text="item.jumlah_order"></td>
                                            <td x-text="item.realisasi"></td>
                                            <td>
                                                <span x-text="formatRupiah(item.harga_referensi || 0)"></span>
                                                <small class="block text-base-content/50">Rata-rata 5 penerimaan barang terakhir</small>
                                            </td>
                                            <td x-text="item.jumlah_order - item.realisasi"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-success btn-xs" @click="addItemFromRequest(item)">
                                                    <i class="fa-solid fa-check"></i> Pilih
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                        <div class="lg:col-span-8">
                            <div class="mb-3 flex items-center justify-between">
                                <h6 class="font-bold"><i class="fa-solid fa-list"></i> Detail Item Pembelian</h6>
                                <button type="button" class="btn btn-info btn-sm" @click="openRequestPicker()">
                                    <i class="fa-solid fa-list-check"></i> Cari dari Permintaan (Request)
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item / Bahan</th>
                                            <th class="w-32">Jumlah Beli</th>
                                            <th class="w-40">Harga Satuan (Rp)</th>
                                            <th class="w-40 text-end">Subtotal (Rp)</th>
                                            <th class="w-12 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="items.length === 0">
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-base-content/50">Belum ada item dipilih.</td>
                                            </tr>
                                        </template>
                                        <template x-for="(item, idx) in items" :key="idx">
                                            <tr>
                                                <td>
                                                    <strong x-text="item.nama"></strong>
                                                    <small x-show="item.max" class="block text-base-content/50" x-text="'Maksimal Beli: ' + item.max + ' unit'"></small>
                                                </td>
                                                <td>
                                                    <input type="number" step="any" min="0.01" :max="item.max || null" x-model.number="item.jumlah" class="input input-bordered input-sm w-full" required>
                                                </td>
                                                <td>
                                                    <input type="number" step="any" min="0" x-model.number="item.harga" data-money-input class="input input-bordered input-sm w-full" required>
                                                </td>
                                                <td class="text-end font-bold" x-text="formatRupiah((item.jumlah || 0) * (item.harga || 0))"></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-error btn-xs" @click="items.splice(idx, 1)">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <div class="card h-full border border-base-300 bg-base-200/40">
                                <div class="card-body p-4">
                                    <h6 class="mb-3 border-b border-base-300 pb-2 font-bold"><i class="fa-solid fa-calculator"></i> Ringkasan Totals</h6>
                                    <div class="flex justify-between py-1"><span class="text-base-content/60">Total Exclude (DPP):</span><span class="font-bold" x-text="'Rp ' + totalExclude.toLocaleString('id-ID')"></span></div>
                                    <div class="flex justify-between py-1"><span class="text-base-content/60">PPN (11%):</span><span class="font-bold text-info" x-text="'Rp ' + totalPpn.toLocaleString('id-ID')"></span></div>
                                    <div class="flex justify-between py-1"><span class="text-base-content/60">Total Include PPN:</span><span class="font-bold" x-text="'Rp ' + totalInclude.toLocaleString('id-ID')"></span></div>
                                    <div class="flex justify-between py-1"><span class="text-base-content/60">Diskon:</span><span class="font-bold text-error" x-text="'- Rp ' + Number(form.diskon || 0).toLocaleString('id-ID')"></span></div>
                                    <div class="flex justify-between py-1"><span class="text-base-content/60">Ongkir / Handling:</span><span class="font-bold text-success" x-text="'+ Rp ' + Number(form.ongkir || 0).toLocaleString('id-ID')"></span></div>
                                    <div class="divider my-2"></div>
                                    <div class="flex items-center justify-between rounded-lg bg-primary p-3 text-primary-content">
                                        <span class="font-bold">Grand Total:</span>
                                        <span class="text-lg font-bold" x-text="'Rp ' + grandTotal.toLocaleString('id-ID')"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                    <button type="button" class="btn btn-ghost" @click="$el.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span x-show="submitting" class="loading loading-spinner loading-sm"></span>
                        <i class="fa-solid fa-floppy-disk" x-show="!submitting"></i>
                        <span x-text="editMode ? 'Update Transaksi' : 'Simpan Transaksi'"></span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="$el.closest('dialog').close()"></div>
    </dialog>

    <script>
        function pembelianForm(config) {
            return {
                editMode: false,
                editNoPo: null,
                submitting: false,
                supplierPickerOpen: false,
                supplierSearchTerm: '',
                supplierResults: [],
                requestPickerOpen: false,
                requestSearchTerm: '',
                requestResults: [],
                form: {},
                items: [],

                init() {
                    this.form = this.defaultForm(config.documentNumber);
                },

                defaultForm(noPo) {
                    return {
                        no_po: noPo, tanggal: new Date().toISOString().substring(0, 10),
                        supplier_id: '', supplier_nama: '', gudang_id: '',
                        no_order: '-', untuk_perhatian: '-', term: '-',
                        is_ppn: '0', diskon: 0, ongkir: 0, notes: '-',
                    };
                },

                get totalExclude() {
                    return this.items.reduce((sum, item) => sum + (Number(item.jumlah) || 0) * (Number(item.harga) || 0), 0);
                },
                get totalPpn() {
                    return this.form.is_ppn == '1' ? this.totalExclude * 0.11 : 0;
                },
                get totalInclude() {
                    return this.totalExclude + this.totalPpn;
                },
                get grandTotal() {
                    const total = (this.totalInclude - (Number(this.form.diskon) || 0)) + (Number(this.form.ongkir) || 0);
                    return Math.max(0, total);
                },

                openCreate() {
                    this.editMode = false;
                    this.editNoPo = null;
                    this.form = this.defaultForm(config.documentNumber);
                    this.items = [];
                    this.$el.showModal();
                },

                async openEdit(noPo) {
                    try {
                        const response = await fetch(`{{ url('pembelian') }}/${noPo}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) { window.AppAlert.ajaxError(payload); return; }
                        const data = payload.data;
                        if (data.kunci != 0) { window.AppAlert.auto('Data tidak dapat diubah karena sudah dikunci.'); return; }

                        this.editMode = true;
                        this.editNoPo = data.no_po;
                        this.form = {
                            no_po: data.no_po, tanggal: data.tanggal, supplier_id: data.supplier_id,
                            supplier_nama: data.supplier ? data.supplier.nama : '-', gudang_id: data.gudang_id,
                            no_order: data.no_order, untuk_perhatian: data.untuk_perhatian, term: data.term,
                            is_ppn: data.ppn > 0 ? '1' : '0', diskon: data.diskon, ongkir: data.ongkir, notes: data.notes,
                        };
                        this.items = (data.details || []).map((detail) => {
                            let max = detail.jumlah;
                            if (detail.request_detail) {
                                max = (detail.request_detail.jumlah_acc - detail.request_detail.realisasi) + Number(detail.jumlah);
                            }
                            return {
                                request_detail_id: detail.request_detail_id || '', bahan_id: detail.bahan_id,
                                nama: detail.bahan ? detail.bahan.nama : `Bahan #${detail.bahan_id}`,
                                jumlah: Number(detail.jumlah), harga: Number(detail.harga),
                                max: detail.request_detail_id ? max : null,
                            };
                        });
                        this.$el.showModal();
                    } catch (error) {
                        window.AppAlert.error('Gagal mengambil data pembelian.');
                    }
                },

                async deletePo(noPo) {
                    const result = await window.AppAlert.confirm(`Hapus transaksi PO (${noPo})?`);
                    if (!result.isConfirmed) return;
                    try {
                        const response = await fetch(`{{ url('pembelian') }}/${noPo}`, {
                            method: 'DELETE',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) { window.AppAlert.ajaxError(data); return; }
                        window.AppAlert.auto(data);
                        window.dispatchEvent(new CustomEvent('wms:table-refresh'));
                    } catch (error) {
                        window.AppAlert.error('Gagal menghapus data pembelian.');
                    }
                },

                openSupplierPicker() {
                    this.supplierPickerOpen = true;
                    this.supplierSearchTerm = '';
                    this.fetchSuppliers();
                },

                async fetchSuppliers() {
                    const params = new URLSearchParams({ draw: '1', start: '0', length: '20', 'search[value]': this.supplierSearchTerm });
                    const response = await fetch(`{{ route('supplier.dataTable') }}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                    const data = await response.json().catch(() => ({ data: [] }));
                    this.supplierResults = data.data || [];
                },

                pickSupplier(item) {
                    this.form.supplier_id = item.id;
                    this.form.supplier_nama = item.nama;
                    this.supplierPickerOpen = false;
                },

                openRequestPicker() {
                    this.requestPickerOpen = true;
                    this.requestSearchTerm = '';
                    this.fetchRequests();
                },

                async fetchRequests() {
                    const params = new URLSearchParams({ draw: '1', start: '0', length: '20', 'search[value]': this.requestSearchTerm });
                    const response = await fetch(`{{ route('requestdetail.index') }}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                    const data = await response.json().catch(() => ({ data: [] }));
                    this.requestResults = data.data || [];
                },

                addItemFromRequest(item) {
                    const sisa = item.jumlah_order - item.realisasi;
                    this.items.push({
                        request_detail_id: item.id_permintaan, bahan_id: item.id_bahan, nama: item.bahan,
                        jumlah: sisa, harga: Number(item.harga_referensi || 0), max: sisa,
                    });
                },

                async submit() {
                    if (!this.form.supplier_id) { window.AppAlert.auto('Harap pilih supplier terlebih dahulu.'); return; }
                    if (this.items.length === 0) { window.AppAlert.auto('Harap pilih minimal 1 item detail dari permintaan.'); return; }

                    this.submitting = true;
                    try {
                        const url = this.editMode ? `{{ url('pembelian') }}/${this.editNoPo}` : '{{ route('pembelian.store') }}';
                        const payload = {
                            ...this.form,
                            details: this.items.map((item) => ({
                                bahan_id: item.bahan_id, jumlah: item.jumlah, harga: item.harga,
                                request_detail_id: item.request_detail_id || null,
                            })),
                        };
                        const response = await fetch(url, {
                            method: this.editMode ? 'PUT' : 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) { window.AppAlert.ajaxError(data); return; }

                        window.AppAlert.auto(data);
                        window.dispatchEvent(new CustomEvent('wms:table-refresh'));
                        if (!this.editMode && data.next_document_number) {
                            this.form = this.defaultForm(data.next_document_number);
                            this.items = [];
                        }
                        this.$el.closest('dialog').close();
                    } catch (error) {
                        window.AppAlert.error('Terjadi kesalahan saat menyimpan data.');
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>

    <div id="modal-container"></div>
@endsection
