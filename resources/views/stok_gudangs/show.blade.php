@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="text-2xl font-bold">Kartu Stok: {{ $stok->bahan->nama }}</h3>
        <p class="mb-4 text-base-content/60">{{ $stok->gudang->nama }} · Saldo {{ number_format($stok->stok_tersedia, 6, ',', '.') }}</p>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
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
                                <td class="font-semibold">{{ $m->nomor_mutasi }}</td>
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
        </div>
        <div class="mt-4">{{ $mutasi->links() }}</div>
    </div>
@endsection
