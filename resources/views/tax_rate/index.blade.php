@extends('layouts.app')

@section('content')
    <div class="content-page"
        x-data="{
                ...wmsDataTable({
                    url: '{{ route('tax-rate.index') }}',
                    columns: [
                        { data: 'tax_type' }, { data: 'rate' }, { data: 'effective_from' },
                        { data: 'effective_until' }, { data: 'is_active' }, { data: 'description' },
                        { data: 'aksi', orderable: false, searchable: false },
                    ],
                }),
                editingId: null,
                form: { tax_type: 'PPN', rate: '', effective_from: '', effective_until: '', description: '', is_active: true },
                saving: false,
                openCreate() {
                    this.editingId = null;
                    this.form = { tax_type: 'PPN', rate: '', effective_from: '', effective_until: '', description: '', is_active: true };
                    this.$refs.rateDialog.showModal();
                },
                openEdit(row) {
                    this.editingId = row.id;
                    this.form = {
                        tax_type: row.tax_type, rate: row.rate,
                        effective_from: (row.effective_from || '').substring(0, 10),
                        effective_until: (row.effective_until || '').substring(0, 10),
                        description: row.description || '', is_active: !!row.is_active,
                    };
                    this.$refs.rateDialog.showModal();
                },
                async save() {
                    this.saving = true;
                    try {
                        const url = this.editingId ? `{{ url('tax-rate') }}/${this.editingId}` : '{{ route('tax-rate.store') }}';
                        const response = await fetch(url, {
                            method: this.editingId ? 'PUT' : 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ...this.form, is_active: this.form.is_active ? 1 : 0 }),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) { window.AppAlert.ajaxError(data); return; }
                        window.AppAlert.success(data.message || 'Tarif pajak berhasil disimpan.');
                        this.$refs.rateDialog.close();
                        this.fetchData();
                    } catch (error) {
                        window.AppAlert.error('Gagal menyimpan tarif pajak.');
                    } finally {
                        this.saving = false;
                    }
                },
            }">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Master Tarif Pajak</h3>
                <p class="text-base-content/60">Tarif bertanggal efektif; transaksi menyimpan snapshot.</p>
            </div>
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i class="fa-solid fa-plus"></i> Tambah Tarif
            </button>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Tarif</th>
                            <th>Berlaku Mulai</th>
                            <th>Berlaku Sampai</th>
                            <th>Status</th>
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
                        <template x-for="row in rows" :key="row.id">
                            <tr>
                                <td class="font-semibold" x-text="row.tax_type"></td>
                                <td x-text="`${Number(row.rate).toLocaleString('id-ID')}%`"></td>
                                <td x-text="row.effective_from"></td>
                                <td x-text="row.effective_until || '-'"></td>
                                <td>
                                    <span class="badge" :class="row.is_active ? 'badge-success' : 'badge-ghost'"
                                        x-text="row.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                </td>
                                <td x-text="row.description || '-'"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline btn-primary btn-sm" @click="openEdit(row)">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
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

            <dialog x-ref="rateDialog" class="modal">
                <div class="modal-box max-w-md">
                    <h3 class="mb-4 text-lg font-bold" x-text="editingId ? 'Edit Tarif' : 'Tambah Tarif'"></h3>
                    <form @submit.prevent="save()" class="flex flex-col gap-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Jenis</span></label>
                            <select x-model="form.tax_type" class="select select-bordered">
                                <option value="PPN">PPN</option>
                                <option value="PPH23">PPH23</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Tarif (%)</span></label>
                            <input type="number" min="0" max="100" step="0.0001" x-model="form.rate" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Berlaku Mulai</span></label>
                            <input type="date" x-model="form.effective_from" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Berlaku Sampai</span></label>
                            <input type="date" x-model="form.effective_until" class="input input-bordered">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                            <textarea x-model="form.description" class="textarea textarea-bordered" rows="2"></textarea>
                        </div>
                        <label class="label cursor-pointer justify-start gap-2">
                            <input type="checkbox" x-model="form.is_active" class="checkbox">
                            <span class="label-text">Aktif</span>
                        </label>
                        <div class="mt-2 flex justify-end gap-2">
                            <button type="button" class="btn btn-ghost" @click="$refs.rateDialog.close()">Batal</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span x-show="saving" class="loading loading-spinner loading-sm"></span> Simpan
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-backdrop" @click="$refs.rateDialog.close()"></div>
            </dialog>
    </div>
@endsection
