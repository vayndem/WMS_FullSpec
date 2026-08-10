@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <h4>Pemeriksaan Consider</h4>
                    <p class="text-muted">Keputusan baik/rusak sebelum barang masuk Gudang Rusak</p>
                </div>
                @can('create', App\Models\PemeriksaanConsider::class)
                    <a href="{{ route('pemeriksaan-considers.create') }}" class="btn btn-primary">Buat Pemeriksaan</a>
                @endcan
            </div>

            @include('warehouse_partials.alerts')

            <div class="card table-responsive">
                <table class="table mb-0">
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
                                <td>{{ $r->nomor_pemeriksaan }}</td>
                                <td>{{ $r->tanggal->format('d-m-Y') }}</td>
                                <td>{{ $r->gudangConsider->nama }}</td>
                                <td>{{ $r->gudangBaik->nama }}</td>
                                <td>{{ $r->gudangRusak->nama }}</td>
                                <td>{{ $r->status }}</td>
                                <td>
                                    @can('view', $r)
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('pemeriksaan-considers.show', $r) }}">Detail</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    </div>
@endsection
