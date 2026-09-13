@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Profil Saya</h3>
            <p class="text-base-content/60">Perbarui identitas akun dan ganti password Anda sendiri.</p>
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
                <div>
                    @foreach ($errors->all() as $pesan)
                        <p class="text-sm">{{ $pesan }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body items-center text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 text-3xl font-bold text-primary">
                        {{ strtoupper(substr($pengguna->name, 0, 1)) }}
                    </div>
                    <h4 class="mt-2 text-lg font-bold">{{ $pengguna->name }}</h4>
                    <p class="text-sm text-base-content/60">{{ $pengguna->email }}</p>
                    <span class="badge badge-primary badge-outline">{{ $pengguna->role_name }}</span>

                    <div class="mt-3 w-full space-y-2 text-left text-sm">
                        <div class="flex justify-between gap-2">
                            <span class="text-base-content/60">Status</span>
                            <span class="badge badge-sm {{ $pengguna->is_active ? 'badge-success' : 'badge-error' }}">
                                {{ $pengguna->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-base-content/60">Login terakhir</span>
                            <span class="font-semibold">{{ $pengguna->last_login_at?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-base-content/60">IP terakhir</span>
                            <span class="font-mono text-xs">{{ $pengguna->last_login_ip ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4 xl:col-span-2">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="border-b border-base-300 p-4">
                        <h5 class="font-bold"><i class="fa-solid fa-id-card text-primary"></i> Identitas Akun</h5>
                    </div>
                    <form action="{{ route('profil.update') }}" method="POST" class="p-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Nama Lengkap</span></label>
                                <input type="text" name="name" value="{{ old('name', $pengguna->name) }}" class="input input-bordered" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Email</span></label>
                                <input type="email" name="email" value="{{ old('email', $pengguna->email) }}" class="input input-bordered" required>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Identitas
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="border-b border-base-300 p-4">
                        <h5 class="font-bold"><i class="fa-solid fa-key text-warning"></i> Ganti Password</h5>
                    </div>
                    <form action="{{ route('profil.password') }}" method="POST" class="p-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Password Lama</span></label>
                                <input type="password" name="password_lama" class="input input-bordered" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Password Baru</span></label>
                                <input type="password" name="password" class="input input-bordered" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Ulangi Password Baru</span></label>
                                <input type="password" name="password_confirmation" class="input input-bordered" required>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs text-base-content/60">Minimal 8 karakter, memuat huruf dan angka.</span>
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fa-solid fa-key"></i> Ganti Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="border-b border-base-300 p-4">
                        <h5 class="font-bold"><i class="fa-solid fa-clock-rotate-left text-info"></i> Aktivitas Login Terakhir</h5>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Peristiwa</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($riwayatLogin as $baris)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ $baris->created_at->translatedFormat('d M Y H:i:s') }}</td>
                                        <td>
                                            <span class="badge badge-sm {{ $baris->event === 'login' ? 'badge-success' : 'badge-ghost' }}">
                                                {{ $baris->event }}
                                            </span>
                                        </td>
                                        <td class="font-mono text-xs">{{ $baris->ip_address ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-base-content/50">Belum ada catatan login.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
