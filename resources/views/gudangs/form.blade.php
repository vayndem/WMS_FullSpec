@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4 class="mb-4">{{ $gudang->exists ? 'Edit' : 'Tambah' }} Gudang</h4>@include('warehouse_partials.alerts')
            <form method="POST" action="{{ $gudang->exists ? route('gudangs.update', $gudang) : route('gudangs.store') }}"
                class="card card-body">@csrf @if ($gudang->exists)
                    @method('PUT')
                @endif
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Kode</label><input name="kode" class="form-control" required
                            value="{{ old('kode', $gudang->kode) }}"></div>
                    <div class="col-md-4 mb-3"><label>Nama</label><input name="nama" class="form-control" required
                            value="{{ old('nama', $gudang->nama) }}"></div>
                    <div class="col-md-4 mb-3"><label>Jenis</label><select name="jenis" class="form-select" required>
                            @foreach (['NORMAL', 'CONSIDER', 'RUSAK'] as $j)
                                <option @selected(old('jenis', $gudang->jenis ?: 'NORMAL') === $j)>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mb-3"><label>Alamat</label>
                        <textarea name="alamat" class="form-control">{{ old('alamat', $gudang->alamat) }}</textarea>
                    </div>
                </div>
                <div class="row">
                    @foreach (['aktif' => 'Aktif', 'boleh_penerimaan' => 'Boleh LPB', 'boleh_npk' => 'Boleh NPK', 'boleh_transfer' => 'Boleh Transfer', 'boleh_opname' => 'Boleh Opname'] as $f => $l)
                        <div class="col-md-2 form-check ms-3 mb-3"><input type="checkbox" name="{{ $f }}"
                                value="1" class="form-check-input" @checked(old($f, $gudang->exists ? $gudang->$f : true))><label
                                class="form-check-label">{{ $l }}</label></div>
                    @endforeach
                </div>
                <div><button class="btn btn-primary">Simpan</button> <a href="{{ route('gudangs.index') }}"
                        class="btn btn-light">Batal</a></div>
            </form>
        </div>
    </div>
@endsection
