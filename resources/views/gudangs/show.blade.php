@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-4">
                <h4>{{ $gudang->kode }} - {{ $gudang->nama }}</h4>
                @can('update', $gudang)
                    <a href="{{ route('gudangs.edit', $gudang) }}" class="btn btn-warning">Edit</a>
                @endcan
            </div>@include('warehouse_partials.alerts')<div class="card card-body">
                <dl class="row">
                    <dt class="col-md-3">Jenis</dt>
                    <dd class="col-md-9">{{ $gudang->jenis }}</dd>
                    <dt class="col-md-3">Alamat</dt>
                    <dd class="col-md-9">{{ $gudang->alamat ?: '-' }}</dd>
                    <dt class="col-md-3">Status</dt>
                    <dd class="col-md-9">{{ $gudang->aktif ? 'Aktif' : 'Nonaktif' }}</dd>
                    <dt class="col-md-3">Jumlah item stok</dt>
                    <dd class="col-md-9">{{ $gudang->stok_count }}</dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
