@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Gelombang Pengambilan</h3>
            <p class="text-base-content/60">
                Gabungkan beberapa perintah pengambilan jadi satu gelombang supaya operator sekali jalan mengambil banyak baris.
            </p>
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mb-4 shadow-sm">
                <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="alert alert-error mb-4 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i><span>{{ $errors->first() }}</span>
            </div>
        @endif

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

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="flex items-center justify-between border-b border-base-300 p-4">
                    <h5 class="font-bold"><i class="fa-solid fa-square-plus text-primary"></i> Susun Gelombang Baru</h5>
                    <span class="badge badge-ghost">{{ $kandidat->count() }} kandidat</span>
                </div>
                <form action="{{ route('gelombang-pengambilan.store') }}" method="POST" class="p-4">
                    @csrf
                    <input type="hidden" name="gudang_id" value="{{ $gudangDipilih }}">

                    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Strategi Urutan</span></label>
                            <select name="strategi" class="select select-bordered select-sm" required>
                                @foreach ($strategi as $kode => $label)
                                    <option value="{{ $kode }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Catatan</span></label>
                            <input type="text" name="catatan" class="input input-bordered input-sm" placeholder="Opsional">
                        </div>
                    </div>

                    <div class="max-h-80 overflow-y-auto rounded-box border border-base-300">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th class="w-10"></th>
                                    <th>Perintah</th>
                                    <th class="text-end">Baris</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($kandidat as $pick)
                                    <tr>
                                        <td><input type="checkbox" name="pick_ids[]" value="{{ $pick->id }}" class="checkbox checkbox-sm"></td>
                                        <td class="font-semibold">{{ $pick->number }}</td>
                                        <td class="text-end">{{ $pick->lines->count() }}</td>
                                        <td><span class="badge badge-ghost badge-sm">{{ $pick->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-base-content/50">Tidak ada perintah pengambilan bebas di gudang ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="btn btn-primary btn-sm" @disabled($kandidat->isEmpty())>
                            <i class="fa-solid fa-layer-group"></i> Buat Gelombang
                        </button>
                    </div>
                </form>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h5 class="font-bold"><i class="fa-solid fa-list-ol text-primary"></i> Gelombang Terakhir</h5>
                </div>
                <div class="divide-y divide-base-300">
                    @forelse ($gelombang as $wave)
                        <div class="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $wave->nomor }}</p>
                                    <p class="text-xs text-base-content/60">
                                        {{ $wave->gudang->nama ?? '-' }} &middot; {{ $wave->pesanan_count }} perintah
                                        @if ($wave->petugas) &middot; {{ $wave->petugas->name }} @endif
                                    </p>
                                </div>
                                <span class="badge badge-sm {{ ['DIRENCANAKAN' => 'badge-ghost', 'DIRILIS' => 'badge-info', 'SELESAI' => 'badge-success', 'DIBATALKAN' => 'badge-error'][$wave->status] ?? 'badge-ghost' }}">
                                    {{ $wave->status }}
                                </span>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                @if ($wave->status === 'DIRENCANAKAN')
                                    <form action="{{ route('gelombang-pengambilan.release', $wave) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <select name="ditugaskan_ke" class="select select-bordered select-xs">
                                            <option value="">Tanpa petugas</option>
                                            @foreach ($petugas as $orang)
                                                <option value="{{ $orang->id }}">{{ $orang->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-paper-plane"></i> Rilis</button>
                                    </form>
                                @endif
                                @if ($wave->status === 'DIRILIS')
                                    <form action="{{ route('gelombang-pengambilan.complete', $wave) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-xs"><i class="fa-solid fa-check"></i> Selesai</button>
                                    </form>
                                @endif
                                @if (in_array($wave->status, ['DIRENCANAKAN', 'DIRILIS'], true))
                                    <form action="{{ route('gelombang-pengambilan.destroy', $wave) }}" method="POST"
                                        x-data @submit.prevent="AppAlert.confirm('Batalkan gelombang ini? Perintah pengambilannya dilepas kembali.').then(r => r.isConfirmed && $el.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-xmark"></i> Batal</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-10 text-center text-base-content/50">Belum ada gelombang.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
