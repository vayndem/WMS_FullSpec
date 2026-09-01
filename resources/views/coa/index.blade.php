@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Chart of Accounts (COA)</h3>
                <p class="text-base-content/60">Kelola daftar akun dan struktur pengkodean akuntansi</p>
            </div>
            @can('create', App\Models\ChartOfAccount::class)
                <button type="button" class="btn btn-primary" onclick="openAjaxModal('{{ route('chart-of-accounts.create') }}')">
                    <i class="fa-solid fa-plus"></i> Tambah Akun COA
                </button>
            @endcan
        </div>

        @can('updateMapping', App\Models\ChartOfAccount::class)
            <div class="collapse collapse-arrow mb-4 border border-base-300 bg-base-100 shadow-sm">
                <input type="checkbox">
                <div class="collapse-title flex items-center justify-between font-bold">
                    <div>
                        <h5 class="mb-1">Mapping Akuntansi WMS</h5>
                        <p class="text-sm font-normal text-base-content/50">Sistem memakai mapping ini, bukan ID atau nama akun.</p>
                    </div>
                </div>
                <div class="collapse-content">
                    <form action="{{ route('chart-of-accounts.mapping.update') }}" method="POST"
                        @submit.prevent="submitAjaxForm($event, { onSuccess: () => {} })">
                        @csrf
                        @method('PUT')
                        <h6 class="mb-3 font-bold text-primary">Mapping Global</h6>
                        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach (['HUTANG_USAHA' => 'Hutang Supplier', 'PPN_MASUKAN' => 'PPN Masukan', 'HUTANG_PPH23' => 'Hutang PPh 23', 'BIAYA_BANK' => 'Biaya Bank', 'BEBAN_MATERAI' => 'Beban Materai', 'SELISIH_BAYAR' => 'Selisih Bayar', 'BIAYA_ONGKIR' => 'Biaya Angkut', 'DISKON_PEMBELIAN' => 'Diskon Pembelian'] as $key => $label)
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-semibold">{{ $label }}</span></label>
                                    <select class="select select-bordered" name="global[{{ $key }}]" required>
                                        <option value="">Pilih akun</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(($settings[$key] ?? null) == $account->id)>
                                                {{ $account->kode_akun }} — {{ $account->nama_akun }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <h6 class="mb-3 font-bold text-primary">Mapping per Kategori Bahan</h6>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th>Persediaan</th>
                                        <th>Pemakaian/Beban</th>
                                        <th>GRNI</th>
                                        <th>Selisih Opname (-)</th>
                                        <th>Koreksi Opname (+)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td class="font-semibold">{{ $category->katnama }}</td>
                                            @foreach (['coa_persediaan_id', 'coa_beban_id', 'coa_clearing_lpb_id', 'coa_beban_selisih_opname_id', 'coa_koreksi_opname_id'] as $field)
                                                <td>
                                                    <select class="select select-bordered select-sm w-full" name="categories[{{ $category->id }}][{{ $field }}]" required>
                                                        <option value="">Pilih akun</option>
                                                        @foreach ($accounts as $account)
                                                            <option value="{{ $account->id }}" @selected($category->{$field} == $account->id)>
                                                                {{ $account->kode_akun }} — {{ $account->nama_akun }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-end">
                            <button class="btn btn-primary" type="submit">Simpan Mapping</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('chart-of-accounts.index') }}',
                reportUrl: '{{ route('chart-of-accounts.report.pdf') }}',
                columns: [
                    { data: 'kode_akun' }, { data: 'nama_akun' }, { data: 'kategori_akun' },
                    { data: 'posisi_normal' }, { data: 'keterangan' }, { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari akun..." x-model="search">
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
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Kode Akun</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Nama Akun</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">Kategori</th>
                            <th class="cursor-pointer select-none text-center" @click="sortBy(3)">Posisi Normal</th>
                            <th>Keterangan</th>
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
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr>
                                <td class="text-center" x-text="rangeStart + index"></td>
                                <td class="font-bold text-primary" x-text="row.kode_akun"></td>
                                <td class="font-semibold" x-text="row.nama_akun"></td>
                                <td>
                                    <span class="badge"
                                        :class="{
                                            'badge-info': row.kategori_akun === 'ASET',
                                            'badge-warning': row.kategori_akun === 'LIABILITAS',
                                            'badge-primary': row.kategori_akun === 'EKUITAS',
                                            'badge-success': row.kategori_akun === 'PENDAPATAN',
                                            'badge-error': row.kategori_akun === 'BEBAN',
                                        }" x-text="row.kategori_akun"></span>
                                </td>
                                <td class="text-center font-bold">
                                    <span :class="row.posisi_normal === 'DEBIT' ? 'text-success' : 'text-error'" x-text="row.posisi_normal"></span>
                                </td>
                                <td x-text="row.keterangan || '-'"></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" x-show="row.can_update" class="btn btn-outline btn-warning btn-sm"
                                            @click="openAjaxModal(`{{ url('chart-of-accounts') }}/${row.id}/edit`)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form :action="`{{ url('chart-of-accounts') }}/${row.id}`" method="POST" x-show="row.can_delete" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline btn-error btn-sm"
                                                @click="confirmAjaxDelete($event, 'Akun akan dinonaktifkan dan tidak bisa dipakai transaksi baru. Lanjutkan?')">
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
