@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-2xl font-bold">{{ $gudang->kode }} - {{ $gudang->nama }}</h3>
            @can('update', $gudang)
                <a href="{{ route('gudangs.edit', $gudang) }}" class="btn btn-warning">Edit</a>
            @endcan
        </div>
        @include('warehouse_partials.alerts')
        <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
            <dl class="grid grid-cols-1 gap-y-3 md:grid-cols-4">
                <dt class="font-semibold text-base-content/50 md:col-span-1">Jenis</dt>
                <dd class="md:col-span-3">{{ $gudang->jenis }}</dd>
                <dt class="font-semibold text-base-content/50 md:col-span-1">Alamat</dt>
                <dd class="md:col-span-3">{{ $gudang->alamat ?: '-' }}</dd>
                <dt class="font-semibold text-base-content/50 md:col-span-1">Status</dt>
                <dd class="md:col-span-3">{{ $gudang->aktif ? 'Aktif' : 'Nonaktif' }}</dd>
                <dt class="font-semibold text-base-content/50 md:col-span-1">Jumlah Item Stok</dt>
                <dd class="md:col-span-3">{{ $gudang->stok_count }}</dd>
            </dl>
        </div>
    </div>
@endsection
