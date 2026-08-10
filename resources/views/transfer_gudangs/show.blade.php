@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between">
                <h4>{{ $transfer->nomor_transfer }}</h4>
                <div>
                    @can('update', $transfer)
                        <a href="{{ route('transfer-gudangs.edit', $transfer) }}" class="btn btn-warning">Edit</a>
                    @endcan
                    @can('submit', $transfer)
                        <form class="d-inline" method="POST" action="{{ route('transfer-gudangs.submit', $transfer) }}">
                            @csrf<button class="btn btn-info">Ajukan</button></form>
                    @endcan
                    @can('confirm', $transfer)
                        <form class="d-inline" method="POST" action="{{ route('transfer-gudangs.confirm', $transfer) }}">
                            @csrf<button class="btn btn-success"
                                onclick="return confirm('Konfirmasi transfer dan pindahkan stok?')">Konfirmasi</button></form>
                    @endcan
                    @can('delete', $transfer)
                        <form class="d-inline" method="POST" action="{{ route('transfer-gudangs.destroy', $transfer) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger"
                                onclick="return confirm('Hapus draft transfer ini?')">Hapus</button>
                        </form>
                    @endcan
                </div>
            </div>@include('warehouse_partials.alerts')<div class="card card-body my-3">
                <p><strong>{{ $transfer->gudangAsal->nama }}</strong> → <strong>{{ $transfer->gudangTujuan->nama }}</strong>
                </p>
                <p>Status: {{ $transfer->status }} · Tanggal: {{ $transfer->tanggal->format('d-m-Y') }}</p>
            </div>
            <div class="card table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th class="text-end">Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfer->details as $d)
                            <tr>
                                <td>{{ $d->bahan->nama }}</td>
                                <td class="text-end">{{ $d->jumlah }}</td>
                                <td>{{ $d->keterangan ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
