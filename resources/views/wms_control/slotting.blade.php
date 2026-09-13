@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Saran Penataan Bin (Slotting)</h3>
            <p class="text-base-content/60">
                Membandingkan kelas ABC bahan dengan kelas bin tempatnya berada. Barang cepat bergerak sebaiknya di bin dekat jalur ambil.
            </p>
        </div>

        <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
            <div class="form-control">
                <label class="label"><span class="label-text font-semibold">Gudang</span></label>
                <select name="gudang_id" class="select select-bordered select-sm" onchange="this.form.submit()">
                    @foreach ($gudangs as $gudang)
                        <option value="{{ $gudang->id }}" @selected($gudangDipilih === $gudang->id)>{{ $gudang->nama }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="flex items-center justify-between border-b border-base-300 p-4">
                <h5 class="font-bold"><i class="fa-solid fa-arrows-turn-right text-primary"></i> Bahan yang Salah Tempat</h5>
                <span class="badge badge-ghost">{{ $saran->count() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th>Kelas Bahan</th>
                            <th>Lokasi Sekarang</th>
                            <th class="text-end">Jumlah</th>
                            <th>Saran Pindah Ke</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($saran as $row)
                            <tr>
                                <td class="font-semibold">{{ $row['bahan'] }}</td>
                                <td>
                                    <span class="badge badge-sm {{ $row['kelas_bahan'] === 'A' ? 'badge-error' : ($row['kelas_bahan'] === 'B' ? 'badge-warning' : 'badge-ghost') }}">
                                        {{ $row['kelas_bahan'] }}
                                    </span>
                                </td>
                                <td>
                                    {{ $row['lokasi_sekarang'] }}
                                    <span class="badge badge-ghost badge-xs">kelas {{ $row['kelas_lokasi_sekarang'] }}</span>
                                </td>
                                <td class="text-end">{{ rtrim(rtrim(number_format($row['jumlah'], 4, ',', '.'), '0'), ',') }}</td>
                                <td>
                                    @if ($row['lokasi_saran'])
                                        <span class="badge badge-primary badge-sm">{{ $row['lokasi_saran'] }}</span>
                                    @else
                                        <span class="text-sm text-base-content/50">Tidak ada bin kelas {{ $row['kelas_bahan'] }} yang muat</span>
                                    @endif
                                </td>
                                <td class="text-xs text-base-content/60">{{ $row['alasan'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-base-content/50">
                                    <i class="fa-solid fa-circle-check text-2xl text-success"></i>
                                    <p class="mt-2">Tidak ada bahan yang salah tempat, atau kelas ABC bin belum diisi.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div role="alert" class="alert alert-info mt-4">
            <i class="fa-solid fa-circle-info"></i>
            <span>Saran ini hanya rekomendasi. Perpindahan fisik antar bin tetap dicatat lewat Transfer Gudang atau penyesuaian putaway.</span>
        </div>
    </div>
@endsection
