@extends('layouts.app')

@section('content')
    <div class="content-page" x-data="{ komponen: [{ bahan_id: '', jumlah: 1 }] }">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Kitting &amp; Bundling</h3>
            <p class="text-base-content/60">
                Rakit beberapa bahan jadi satu barang jadi. Komponen dikonsumsi FIFO dan nilainya pindah utuh ke kit.
            </p>
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mb-4 shadow-sm">
                <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="alert alert-error mb-4 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>@foreach ($errors->all() as $pesan)<p class="text-sm">{{ $pesan }}</p>@endforeach</div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h5 class="font-bold"><i class="fa-solid fa-cubes text-primary"></i> Definisi Kit Baru</h5>
                </div>
                <form action="{{ route('kit.store') }}" method="POST" class="p-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Kode</span></label>
                            <input type="text" name="kode" class="input input-bordered input-sm" value="{{ old('kode') }}" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nama Kit</span></label>
                            <input type="text" name="nama" class="input input-bordered input-sm" value="{{ old('nama') }}" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Bahan Hasil</span></label>
                            <select name="bahan_hasil_id" class="select select-bordered select-sm" data-app-picker required>
                                <option value="">Pilih bahan jadi...</option>
                                @foreach ($bahans as $bahan)
                                    <option value="{{ $bahan->id }}">{{ $bahan->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Jumlah Hasil per Rakitan</span></label>
                            <input type="number" step="0.000001" name="jumlah_hasil" class="input input-bordered input-sm" value="{{ old('jumlah_hasil', 1) }}" required>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="font-semibold">Komponen</span>
                            <button type="button" class="btn btn-ghost btn-xs" @click="komponen.push({ bahan_id: '', jumlah: 1 })">
                                <i class="fa-solid fa-plus"></i> Tambah baris
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(baris, index) in komponen" :key="index">
                                <div class="flex flex-wrap items-center gap-2">
                                    <select :name="`komponen[${index}][bahan_id]`" class="select select-bordered select-sm flex-1" required>
                                        <option value="">Pilih komponen...</option>
                                        @foreach ($bahans as $bahan)
                                            <option value="{{ $bahan->id }}">{{ $bahan->nama }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" step="0.000001" :name="`komponen[${index}][jumlah]`" x-model="baris.jumlah"
                                        class="input input-bordered input-sm w-28" required>
                                    <button type="button" class="btn btn-ghost btn-sm btn-square text-error"
                                        x-show="komponen.length > 1" @click="komponen.splice(index, 1)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan Kit</button>
                    </div>
                </form>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h5 class="font-bold"><i class="fa-solid fa-screwdriver-wrench text-primary"></i> Daftar Kit</h5>
                </div>
                <div class="divide-y divide-base-300">
                    @forelse ($kits as $kit)
                        <div class="p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $kit->kode }} &middot; {{ $kit->nama }}</p>
                                    <p class="text-xs text-base-content/60">
                                        Menghasilkan {{ rtrim(rtrim(number_format((float) $kit->jumlah_hasil, 4, ',', '.'), '0'), ',') }}
                                        {{ $kit->bahanHasil->satuan ?? '' }} {{ $kit->bahanHasil->nama ?? '' }}
                                    </p>
                                    <p class="mt-1 text-xs text-base-content/50">
                                        {{ $kit->komponen->map(fn ($k) => ($k->bahan->nama ?? '?') . ' x' . rtrim(rtrim(number_format((float) $k->jumlah, 4, ',', '.'), '0'), ','))->implode(' + ') }}
                                    </p>
                                </div>
                                @if (!$kit->aktif)
                                    <span class="badge badge-ghost badge-sm">nonaktif</span>
                                @endif
                            </div>

                            <form action="{{ route('kit.rakit', $kit) }}" method="POST" class="mt-3 flex flex-wrap items-end gap-2">
                                @csrf
                                <select name="gudang_id" class="select select-bordered select-xs" required>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                                <select name="jenis" class="select select-bordered select-xs">
                                    <option value="RAKIT">Rakit</option>
                                    <option value="URAI">Urai</option>
                                </select>
                                <input type="date" name="tanggal" value="{{ today()->toDateString() }}" class="input input-bordered input-xs w-32" required>
                                <input type="number" step="0.000001" name="jumlah_kit" value="1" class="input input-bordered input-xs w-24" required>
                                <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-play"></i> Jalankan</button>
                            </form>
                        </div>
                    @empty
                        <div class="p-10 text-center text-base-content/50">Belum ada kit yang didefinisikan.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card mt-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h5 class="font-bold"><i class="fa-solid fa-clock-rotate-left text-info"></i> Riwayat Perakitan</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nomor</th><th>Kit</th><th>Gudang</th><th>Jenis</th>
                            <th>Tanggal</th><th class="text-end">Jumlah</th><th class="text-end">Nilai</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($perakitan as $row)
                            <tr>
                                <td class="font-semibold">{{ $row->nomor }}</td>
                                <td>{{ $row->kit->nama ?? '-' }}</td>
                                <td>{{ $row->gudang->nama ?? '-' }}</td>
                                <td><span class="badge badge-ghost badge-sm">{{ $row->jenis }}</span></td>
                                <td>{{ optional($row->tanggal)->format('d-m-Y') }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $row->jumlah_kit, 4, ',', '.'), '0'), ',') }}</td>
                                <td class="text-end">{{ number_format((float) $row->nilai_total, 2, ',', '.') }}</td>
                                <td><span class="badge badge-sm {{ $row->status === 'POSTED' ? 'badge-success' : 'badge-ghost' }}">{{ $row->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-base-content/50">Belum ada perakitan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
