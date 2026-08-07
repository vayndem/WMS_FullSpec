@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4>Kartu Stok: {{ $stok->bahan->nama }}</h4>
            <p>{{ $stok->gudang->nama }} · Saldo {{ number_format($stok->stok_tersedia, 6, ',', '.') }}</p>
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Dokumen</th>
                                <th>Jenis</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th class="text-end">Saldo</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mutasi as $m)
                                <tr>
                                    <td>{{ $m->tanggal }}</td>
                                    <td>{{ $m->nomor_mutasi }}</td>
                                    <td>{{ $m->jenis_mutasi }}</td>
                                    <td class="text-end">{{ $m->jumlah_masuk }}</td>
                                    <td class="text-end">{{ $m->jumlah_keluar }}</td>
                                    <td class="text-end">{{ $m->saldo_setelah }}</td>
                                    <td>{{ $m->user->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>{{ $mutasi->links() }}
        </div>
    </div>
@endsection
