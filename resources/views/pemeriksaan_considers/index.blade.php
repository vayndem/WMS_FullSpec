@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">Pemeriksaan Consider</h3>
                <p class="text-base-content/60">Keputusan baik/rusak sebelum barang masuk Gudang Rusak</p>
            </div>
            @can('create', App\Models\PemeriksaanConsider::class)
                <a href="{{ route('pemeriksaan-considers.create') }}" class="btn btn-primary">Buat Pemeriksaan</a>
            @endcan
        </div>

        @include('warehouse_partials.alerts')

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Consider</th>
                            <th>Gudang Baik</th>
                            <th>Gudang Rusak</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="font-semibold">{{ $r->nomor_pemeriksaan }}</td>
                                <td>{{ $r->tanggal->format('d-m-Y') }}</td>
                                <td>{{ $r->gudangConsider->nama }}</td>
                                <td>{{ $r->gudangBaik->nama }}</td>
                                <td>{{ $r->gudangRusak->nama }}</td>
                                <td>{{ $r->status }}</td>
                                <td>
                                    @can('view', $r)
                                        <a class="btn btn-outline btn-primary btn-sm" href="{{ route('pemeriksaan-considers.show', $r) }}">Detail</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </div>
@endsection
