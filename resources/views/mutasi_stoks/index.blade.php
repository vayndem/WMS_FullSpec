@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4>Mutasi Stok</h4>
            <form class="row mb-3">
                <div class="col-md-4"><select name="gudang_id" class="form-select">
                        <option value="">Semua gudang</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}" @selected(request('gudang_id') == $g->id)>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col"><button class="btn btn-primary">Filter</button></div>
            </form>
            <div class="card table-responsive">
                <table class="table mb-0">
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
                                <td>{{ $r->nomor_mutasi }}</td>
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
            </div>{{ $rows->links() }}
        </div>
    </div>
@endsection
