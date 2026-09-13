@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Manajemen Pengguna</h3>
                <p class="text-base-content/60">Kelola akun, peran, dan status aktif seluruh pengguna sistem.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('user.login-audit') }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-shield-halved"></i> Audit Login
                </a>
                @can('create', App\Models\User::class)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openAjaxModal('{{ route('user.create') }}')">
                        <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
                    </button>
                @endcan
            </div>
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mb-4 shadow-sm">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="alert alert-error mb-4 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @php
            $toneClasses = [
                'primary' => 'bg-primary/10 text-primary',
                'success' => 'bg-success/10 text-success',
                'error' => 'bg-error/10 text-error',
                'warning' => 'bg-warning/10 text-warning',
            ];
        @endphp
        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Total Pengguna', $ringkasan['total'], 'fa-users', 'primary'],
                ['Akun Aktif', $ringkasan['aktif'], 'fa-user-check', 'success'],
                ['Akun Nonaktif', $ringkasan['nonaktif'], 'fa-user-slash', 'error'],
                ['Belum Pernah Login', $ringkasan['belum_pernah_login'], 'fa-user-clock', 'warning'],
            ] as [$label, $nilai, $ikon, $warna])
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body flex-row items-center gap-3 p-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $toneClasses[$warna] }}">
                            <i class="fa-solid {{ $ikon }}"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs text-base-content/60">{{ $label }}</p>
                            <p class="text-2xl font-bold">{{ number_format($nilai, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm"
            x-data="wmsDataTable({
                url: '{{ route('user.index') }}',
                extraParams: { status: '' },
                columns: [
                    { data: 'name' }, { data: 'email' }, { data: 'role_label' },
                    { data: 'status_label' }, { data: 'login_terakhir' },
                    { data: 'aksi', orderable: false, searchable: false },
                ],
            })">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-300 p-4">
                <label class="input input-bordered flex w-full max-w-xs items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                    <input type="search" class="grow" placeholder="Cari nama atau email..." x-model="search">
                </label>
                <select class="select select-bordered select-sm w-full max-w-[12rem]" x-model="extraParams.status">
                    <option value="">Semua status</option>
                    <option value="AKTIF">Hanya aktif</option>
                    <option value="NONAKTIF">Hanya nonaktif</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="cursor-pointer select-none" @click="sortBy(0)">Nama</th>
                            <th class="cursor-pointer select-none" @click="sortBy(1)">Email</th>
                            <th>Peran</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                            <th class="text-center">Aksi</th>
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
                                <td class="font-semibold" x-text="row.name"></td>
                                <td class="text-base-content/70" x-text="row.email"></td>
                                <td><span class="badge badge-ghost badge-sm" x-text="row.role_label"></span></td>
                                <td>
                                    <span class="badge badge-sm"
                                        :class="row.is_active ? 'badge-success' : 'badge-error'"
                                        x-text="row.status_label"></span>
                                </td>
                                <td class="text-sm text-base-content/60" x-text="row.login_terakhir"></td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" title="Edit pengguna"
                                            class="btn btn-ghost btn-sm btn-square text-warning"
                                            @click="openAjaxModal(`{{ url('pengguna') }}/${row.id}/edit`)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form :action="`{{ url('pengguna') }}/${row.id}/nonaktifkan`" method="POST"
                                            x-show="row.is_active && row.can_deactivate"
                                            @submit.prevent="AppAlert.confirm('Nonaktifkan akun ini? Sesi aktifnya akan dicabut.').then(r => r.isConfirmed && $event.target.submit())">
                                            @csrf
                                            <button type="submit" title="Nonaktifkan akun" class="btn btn-ghost btn-sm btn-square text-error">
                                                <i class="fa-solid fa-user-slash"></i>
                                            </button>
                                        </form>
                                        <form :action="`{{ url('pengguna') }}/${row.id}/aktifkan`" method="POST" x-show="!row.is_active">
                                            @csrf
                                            <button type="submit" title="Aktifkan kembali" class="btn btn-ghost btn-sm btn-square text-success">
                                                <i class="fa-solid fa-user-check"></i>
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
