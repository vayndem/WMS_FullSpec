@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">Mutasi Stok</h3>
        <form class="mb-4 grid grid-cols-1 gap-2 md:grid-cols-5">
            <select name="gudang_id" class="select select-bordered md:col-span-4">
                <option value="">Semua gudang</option>
                @foreach ($gudangs as $g)
                    <option value="{{ $g->id }}" @selected(request('gudang_id') == $g->id)>{{ $g->nama }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Filter</button>
        </form>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nomor</th>
                            <th>Gudang</th>
                            <th>Bahan</th>
                            <th>Jenis</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Saldo</th>
                            <th>Referensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->tanggal }}</td>
                                <td class="font-semibold">{{ $r->nomor_mutasi }}</td>
                                <td>{{ $r->gudang->nama }}</td>
                                <td>{{ $r->bahan->nama }}</td>
                                <td>{{ $r->jenis_mutasi }}</td>
                                <td>{{ $r->jumlah_masuk }}</td>
                                <td>{{ $r->jumlah_keluar }}</td>
                                <td>{{ $r->saldo_setelah }}</td>
                                <td>{{ $r->jenis_referensi }} #{{ $r->referensi_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </div>
@endsection
