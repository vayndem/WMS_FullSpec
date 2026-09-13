@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Audit Login</h3>
                <p class="text-base-content/60">Jejak masuk, keluar, dan percobaan login yang gagal atau ditolak.</p>
            </div>
            <a href="{{ route('user.index') }}" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Pengguna
            </a>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body flex-row items-center gap-3 p-4">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-success/10 text-success">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </span>
                    <div>
                        <p class="text-xs text-base-content/60">Login Berhasil (24 jam)</p>
                        <p class="text-2xl font-bold">{{ number_format($ringkasan['login_24_jam'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body flex-row items-center gap-3 p-4">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-error/10 text-error">
                        <i class="fa-solid fa-user-lock"></i>
                    </span>
                    <div>
                        <p class="text-xs text-base-content/60">Gagal / Ditolak (24 jam)</p>
                        <p class="text-2xl font-bold">{{ number_format($ringkasan['gagal_24_jam'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('user.login-audit') }}',
                defaultOrderColumn: 0,
                defaultOrderDir: 'desc',
                columns: [
                    { data: 'created_at' }, { data: 'event' }, { data: 'email', searchable: false },
                    { data: 'ip_address' },
                ],
            })">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari IP atau peristiwa..." x-model="search">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Waktu</th>
                            <th>Peristiwa</th>
                            <th>Pengguna</th>
                            <th>Email</th>
                            <th>IP</th>
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
                                <td colspan="5" class="py-6 text-center text-base-content/50">Belum ada catatan</td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="row.id">
                            <tr>
                                <td class="whitespace-nowrap text-sm" x-text="row.waktu"></td>
                                <td>
                                    <span class="badge badge-sm" :class="{
                                            'badge-success': row.event === 'login',
                                            'badge-ghost': row.event === 'logout',
                                            'badge-error': row.event === 'login_gagal',
                                            'badge-warning': row.event === 'login_ditolak',
                                        }" x-text="row.event"></span>
                                </td>
                                <td class="font-semibold" x-text="row.pengguna"></td>
                                <td class="text-sm text-base-content/70" x-text="row.email"></td>
                                <td class="font-mono text-xs" x-text="row.ip_address || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-base-300 p-4 text-sm">
                <span class="text-base-content/60">
                    Menampilkan <span x-text="rangeStart"></span>–<span x-text="rangeEnd"></span> dari
                    <span x-text="recordsFiltered"></span> catatan
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
@endsection
