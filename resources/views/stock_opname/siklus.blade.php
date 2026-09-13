@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Cycle Count Berbasis ABC</h3>
                <p class="text-base-content/60">
                    Kelas A dihitung tiap bulan, B tiap 3 bulan, C tiap tahun. Kelas ditentukan dari nilai pemakaian 12 bulan terakhir.
                </p>
            </div>
            <a href="{{ route('stock-opname.index') }}" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Daftar Opname
            </a>
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

        @forelse ($jadwal as $baris)
            @php $gudang = $gudangs->firstWhere('id', $baris['gudang_id']); @endphp
            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h5 class="font-bold"><i class="fa-solid fa-warehouse text-primary"></i> {{ $gudang->nama ?? 'Gudang #' . $baris['gudang_id'] }}</h5>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th class="text-end">Jumlah Bahan</th>
                                <th>Siklus</th>
                                <th>Hitung Terakhir</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($baris['kelas'] as $kelas)
                                <tr>
                                    <td>
                                        <span class="badge {{ $kelas['kelas'] === 'A' ? 'badge-error' : ($kelas['kelas'] === 'B' ? 'badge-warning' : 'badge-ghost') }}">
                                            Kelas {{ $kelas['kelas'] }}
                                        </span>
                                    </td>
                                    <td class="text-end font-semibold">{{ number_format($kelas['bahan'], 0, ',', '.') }}</td>
                                    <td>tiap {{ $kelas['siklus_bulan'] }} bulan</td>
                                    <td>
                                        {{ $kelas['terakhir'] ? \Illuminate\Support\Carbon::parse($kelas['terakhir'])->translatedFormat('d M Y') : 'Belum pernah' }}
                                    </td>
                                    <td>
                                        @if ($kelas['jatuh_tempo'])
                                            <span class="badge badge-warning badge-sm">Jatuh tempo</span>
                                        @else
                                            <span class="badge badge-success badge-sm">Dalam siklus</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('stock-opname.siklus.store') }}" method="POST"
                                            class="flex flex-wrap items-center justify-center gap-2">
                                            @csrf
                                            <input type="hidden" name="warehouse_id" value="{{ $baris['gudang_id'] }}">
                                            <input type="hidden" name="kelas_abc" value="{{ $kelas['kelas'] }}">
                                            <input type="date" name="cutoff_at" value="{{ today()->toDateString() }}"
                                                class="input input-bordered input-sm w-36" required>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fa-solid fa-play"></i> Mulai
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body text-center text-base-content/60">
                    <i class="fa-solid fa-circle-info text-2xl"></i>
                    <p>Belum ada klasifikasi ABC. Jalankan <code>php artisan wms:hitung-abc</code> atau tunggu jadwal mingguan.</p>
                </div>
            </div>
        @endforelse
    </div>
@endsection
