@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Stock Opname</h3>
                <p class="text-base-content/60">Hitung fisik, approval accounting, lalu posting stok dan jurnal.</p>
            </div>
            <div class="flex items-center gap-2">
                @can('create', App\Models\StockOpname::class)
                    <a class="btn btn-primary" href="{{ route('stock-opname.create') }}"><i class="fa-solid fa-plus"></i> Buat Opname</a>
                @endcan
                <a class="btn btn-success" href="{{ route('stock-opname.export.excel') }}"><i class="fa-solid fa-file-excel"></i> Export Excel Opname</a>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="stockOpnameDataTable({
                url: '{{ route('stock-opname.index') }}',
                reportUrl: '{{ route('stock-opname.report.pdf') }}',
                defaultOrderColumn: 1, defaultOrderDir: 'desc',
                columns: [
                    { data: 'number' }, { data: 'cutoff_at' }, { data: 'warehouse_name' },
                    { data: 'details_count' }, { data: 'status' }, { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex items-center justify-end border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari opname..." x-model="search">
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Nomor</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Cut-off</th>
                            <th class="cursor-pointer select-none" @click="sortBy(2)">Gudang</th>
                            <th class="text-center">Item</th>
                            <th class="cursor-pointer select-none" @click="sortBy(4)">Status</th>
                            <th class="text-end">Aksi</th>
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
                                <td class="font-semibold text-primary" x-text="row.number"></td>
                                <td x-text="row.cutoff_at"></td>
                                <td x-text="row.warehouse_name"></td>
                                <td class="text-center" x-text="row.details_count"></td>
                                <td>
                                    <span class="badge"
                                        :class="{
                                            'badge-neutral': row.status === 'DRAFT',
                                            'badge-error': row.status === 'REJECTED',
                                            'badge-warning': row.status === 'SUBMITTED',
                                            'badge-info': row.status === 'APPROVED',
                                            'badge-success': row.status === 'POSTED',
                                        }" x-text="row.status"></span>
                                </td>
                                <td class="text-end">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <button type="button" class="btn btn-outline btn-primary btn-sm" title="Detail selisih" @click="showDetail(row.id)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <a class="btn btn-outline btn-sm" :href="`{{ url('stock-opname') }}/${row.id}`" title="Halaman lengkap">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        <a class="btn btn-outline btn-error btn-sm" target="_blank" :href="`{{ url('stock-opname') }}/${row.id}/pdf`">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                        <a x-show="row.can_update" class="btn btn-outline btn-warning btn-sm" :href="`{{ url('stock-opname') }}/${row.id}/edit`">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button type="button" x-show="row.can_submit" class="btn btn-primary btn-sm"
                                            @click="AppAlert.confirm('SUBMIT stock opname ini?').then(r => r.isConfirmed && runAction(row.id, 'submit'))">Submit</button>
                                        <a x-show="row.can_approve" class="btn btn-success btn-sm" :href="`{{ url('stock-opname') }}/${row.id}`">Isi Harga & Konfirmasi</a>
                                        <button type="button" x-show="row.can_approve" class="btn btn-outline btn-error btn-sm" @click="rejectWithNote(row.id)">Reject</button>
                                        <button type="button" x-show="row.can_post" class="btn btn-success btn-sm"
                                            @click="AppAlert.confirm('POST stock opname ini?').then(r => r.isConfirmed && runAction(row.id, 'post'))">Post</button>
                                        <button type="button" x-show="row.can_delete" class="btn btn-outline btn-error btn-sm"
                                            @click="AppAlert.confirm('DELETE stock opname ini?').then(r => r.isConfirmed && runAction(row.id, 'delete', true))">
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
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('stockOpnameDataTable', (config) => {
                const base = window.wmsDataTable(config);
                return {
                    ...base,
                    async runAction(id, action, isDelete = false) {
                        try {
                            const url = isDelete ? `{{ url('stock-opname') }}/${id}` : `{{ url('stock-opname') }}/${id}/${action}`;
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                                body: JSON.stringify(isDelete ? { _method: 'DELETE' } : {}),
                            });
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok) { window.AppAlert.error(data.message || 'Proses gagal.'); return; }
                            window.AppAlert.success(data.message);
                            this.fetchData();
                        } catch (error) {
                            window.AppAlert.error('Proses gagal.');
                        }
                    },
                    async rejectWithNote(id) {
                        const result = await window.Swal.fire({
                            title: 'Reject opname?', input: 'textarea', inputLabel: 'Catatan',
                            showCancelButton: true, confirmButtonText: 'Simpan',
                        });
                        if (!result.isConfirmed) return;
                        try {
                            const response = await fetch(`{{ url('stock-opname') }}/${id}/reject`, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                                body: JSON.stringify({ approval_note: result.value || '' }),
                            });
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok) { window.AppAlert.error(data.message || 'Proses gagal.'); return; }
                            window.AppAlert.success(data.message);
                            this.fetchData();
                        } catch (error) {
                            window.AppAlert.error('Proses gagal.');
                        }
                    },
                    async showDetail(id) {
                        try {
                            const response = await fetch(`{{ url('stock-opname') }}/${id}/detail-data`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                            const r = await response.json();
                            const esc = (value) => { const el = document.createElement('div'); el.textContent = value ?? ''; return el.innerHTML; };
                            const money = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(n || 0);
                            const rows = r.items.map((x, i) => {
                                const tone = x.direction === 'PLUS' ? 'text-success' : (x.direction === 'MINUS' ? 'text-error' : 'text-base-content/50');
                                return `<tr><td>${i + 1}</td><td class="font-semibold">${esc(x.name)}</td>
                                    <td class="text-end">${x.system_quantity} ${esc(x.unit)}</td>
                                    <td class="text-end">${x.physical_quantity} ${esc(x.unit)}</td>
                                    <td class="text-end font-bold ${tone}">${x.difference_quantity}</td>
                                    ${r.financial ? `<td class="text-end">${money(x.unit_cost)}</td><td class="text-end">${money(x.difference_value)}</td>` : ''}
                                    <td>${esc(x.reason || '-')}</td></tr>`;
                            }).join('');
                            window.Swal.fire({
                                title: `Detail ${esc(r.number)}`,
                                width: 'min(1100px, 96vw)',
                                html: `<div class="text-start text-sm text-gray-500 mb-3">${esc(r.warehouse)} · ${esc(r.status)}</div>
                                    <div style="overflow-x:auto"><table class="table table-sm" style="width:100%">
                                    <thead><tr><th>No.</th><th>Barang</th><th class="text-end">Sistem</th>
                                    <th class="text-end">Fisik</th><th class="text-end">Selisih</th>
                                    ${r.financial ? '<th class="text-end">Harga</th><th class="text-end">Nilai</th>' : ''}
                                    <th>Alasan</th></tr></thead><tbody>${rows}</tbody></table></div>`,
                                confirmButtonText: 'Tutup',
                            });
                        } catch (error) {
                            window.AppAlert.error('Gagal memuat detail opname.');
                        }
                    },
                };
            });
        });
    </script>
@endsection
