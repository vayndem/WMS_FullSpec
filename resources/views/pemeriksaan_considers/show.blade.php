@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between">
                <h4>{{ $pemeriksaan->nomor_pemeriksaan }}</h4>
                <div>
                    @can('update', $pemeriksaan)
                        <a class="btn btn-warning" href="{{ route('pemeriksaan-considers.edit', $pemeriksaan) }}">Edit</a>
                    @endcan
                    @can('confirm', $pemeriksaan)
                        <form class="d-inline" method="POST" action="{{ route('pemeriksaan-considers.confirm', $pemeriksaan) }}">
                            @csrf
                            <button class="btn btn-danger"
                                onclick="return confirm('Konfirmasi keputusan? Barang rusak tidak dapat kembali ke gudang aktif.')">
                                Konfirmasi Final
                            </button>
                        </form>
                    @endcan
                    @can('delete', $pemeriksaan)
                        <form class="d-inline" method="POST"
                            action="{{ route('pemeriksaan-considers.destroy', $pemeriksaan) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger"
                                onclick="return confirm('Hapus draft pemeriksaan ini?')">Hapus</button>
                        </form>
                    @endcan
                </div>
            </div>

            @include('warehouse_partials.alerts')

            <div class="alert alert-warning mt-3">
                Setelah dikonfirmasi, barang berstatus rusak bersifat final dan tidak dapat ditransfer kembali.
            </div>
            <div class="card table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th>Diperiksa</th>
                            <th>Baik</th>
                            <th>Rusak</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pemeriksaan->details as $d)
                            <tr>
                                <td>{{ $d->bahan->nama }}</td>
                                <td>{{ $d->jumlah_diperiksa }}</td>
                                <td>{{ $d->jumlah_baik }}</td>
                                <td>{{ $d->jumlah_rusak }}</td>
                                <td>{{ $d->alasan ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
