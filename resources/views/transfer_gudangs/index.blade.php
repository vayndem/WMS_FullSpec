@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">Transfer Gudang</h3>
                <p class="text-base-content/60">Perpindahan stok dengan harga FIFO tetap</p>
            </div>
            @can('create', App\Models\TransferGudang::class)
                <a class="btn btn-primary" href="{{ route('transfer-gudangs.create') }}">Buat Transfer</a>
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
                            <th>Asal</th>
                            <th>Tujuan</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="font-semibold">{{ $r->nomor_transfer }}</td>
                                <td>{{ $r->tanggal->format('d-m-Y') }}</td>
                                <td>{{ $r->gudangAsal->nama }}</td>
                                <td>{{ $r->gudangTujuan->nama }}</td>
                                <td>{{ $r->status }}</td>
                                <td>
                                    @can('view', $r)
                                        <a href="{{ route('transfer-gudangs.show', $r) }}" class="btn btn-outline btn-primary btn-sm">Detail</a>
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
