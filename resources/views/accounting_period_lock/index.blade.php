@extends('layouts.app')

@section('content')
    <div class="content-page"
        x-data="{
            ...wmsDataTable({
                url: '{{ route('period-lock.index') }}',
                columns: [
                    { data: 'period_start' }, { data: 'period_end' }, { data: 'status' },
                    { data: 'reason' }, { data: 'locked_by_name' }, { data: 'aksi', orderable: false, searchable: false },
                ],
            }),
            async unlock(row) {
                const result = await window.Swal.fire({
                    title: 'Buka kembali periode?',
                    input: 'textarea',
                    inputLabel: 'Alasan wajib diisi',
                    showCancelButton: true,
                    confirmButtonText: 'Buka periode',
                    cancelButtonText: 'Batal',
                    inputValidator: v => !v?.trim() ? 'Alasan wajib diisi' : undefined,
                });
                if (!result.isConfirmed) return;
                try {
                    const response = await fetch(`{{ url('period-lock') }}/${row.id}/unlock`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ unlock_reason: result.value }),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) { window.AppAlert.ajaxError(data); return; }
                    window.AppAlert.success('Periode berhasil dibuka.');
                    this.fetchData();
                } catch (error) {
                    window.AppAlert.error('Gagal membuka periode.');
                }
            },
        }">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Kunci Periode Akuntansi</h3>
                <p class="text-base-content/60">Cegah transaksi mengubah periode yang sudah ditutup.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="lockPeriodDialog.showModal()">
                <i class="fa-solid fa-lock"></i> Kunci Periode
            </button>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mulai</th>
                            <th>Sampai</th>
                            <th>Status</th>
                            <th>Alasan</th>
                            <th>Dikunci Oleh</th>
                            <th>Aksi</th>
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
                                <td x-text="row.period_start"></td>
                                <td x-text="row.period_end"></td>
                                <td>
                                    <span class="badge" :class="row.status === 'LOCKED' ? 'badge-error' : 'badge-success'" x-text="row.status"></span>
                                </td>
                                <td x-text="row.reason"></td>
                                <td x-text="row.locked_by_name || '-'"></td>
                                <td>
                                    <button type="button" x-show="row.can_unlock" class="btn btn-outline btn-primary btn-sm" @click="unlock(row)">
                                        <i class="fa-solid fa-lock-open"></i> Buka
                                    </button>
                                    <span x-show="!row.can_unlock">-</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <dialog id="lockPeriodDialog" class="modal">
        <div class="modal-box">
            <h3 class="mb-4 text-lg font-bold">Kunci Periode</h3>
            <form action="{{ route('period-lock.store') }}" method="POST" @submit.prevent="submitAjaxForm($event, { onSuccess: () => $event.target.reset() })">
                @csrf
                <div role="alert" class="alert alert-warning mb-4 text-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Semua transaksi bertanggal dalam rentang ini akan ditolak.</span>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Tanggal Mulai</span></label>
                        <input name="period_start" type="date" class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Tanggal Akhir</span></label>
                        <input name="period_end" type="date" class="input input-bordered" required>
                    </div>
                    <div class="form-control sm:col-span-2">
                        <label class="label"><span class="label-text font-semibold">Alasan Closing</span></label>
                        <textarea name="reason" class="textarea textarea-bordered" rows="3" required></textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" onclick="lockPeriodDialog.close()">Batal</button>
                    <button type="submit" class="btn btn-primary">Kunci</button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" onclick="lockPeriodDialog.close()"></div>
    </dialog>
@endsection
