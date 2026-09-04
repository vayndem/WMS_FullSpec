@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">{{ $gudang->exists ? 'Edit' : 'Tambah' }} Gudang</h3>
        @include('warehouse_partials.alerts')
        @php($ability = $gudang->exists ? 'update' : 'create')
        @php($subject = $gudang->exists ? $gudang : App\Models\Gudang::class)
        @can($ability, $subject)
            <form method="POST" action="{{ $gudang->exists ? route('gudangs.update', $gudang) : route('gudangs.store') }}"
                class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                @csrf
                @if ($gudang->exists)
                    @method('PUT')
                @endif
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Kode</span></label>
                        <input name="kode" class="input input-bordered" required value="{{ old('kode', $gudang->kode) }}">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Nama</span></label>
                        <input name="nama" class="input input-bordered" required value="{{ old('nama', $gudang->nama) }}">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Jenis</span></label>
                        <select name="jenis" class="select select-bordered" required>
                            @foreach (['NORMAL', 'CONSIDER', 'RUSAK'] as $j)
                                <option @selected(old('jenis', $gudang->jenis ?: 'NORMAL') === $j)>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control md:col-span-3">
                        <label class="label"><span class="label-text font-semibold">Alamat</span></label>
                        <textarea name="alamat" class="textarea textarea-bordered">{{ old('alamat', $gudang->alamat) }}</textarea>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-6">
                    @foreach (['aktif' => 'Aktif', 'boleh_penerimaan' => 'Boleh Penerimaan Barang', 'boleh_npk' => 'Boleh Pemakaian Barang', 'boleh_transfer' => 'Boleh Transfer', 'boleh_opname' => 'Boleh Opname'] as $f => $l)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="{{ $f }}" value="1" class="checkbox" @checked(old($f, $gudang->exists ? $gudang->$f : true))>
                            <span>{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-4 flex gap-2">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('gudangs.index') }}" class="btn btn-ghost border border-base-300">Batal</a>
                </div>
            </form>
        @endcan
    </div>
@endsection
