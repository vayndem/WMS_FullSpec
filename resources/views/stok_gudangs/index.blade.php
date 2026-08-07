@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4>Saldo Stok per Gudang</h4>
            <form class="row g-2 mb-3">
                <div class="col-md-4"><select name="gudang_id" class="form-select">
                        <option value="">Semua gudang</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}" @selected(request('gudang_id') == $g->id)>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><input name="q" class="form-control" value="{{ request('q') }}"
                        placeholder="Cari bahan"></div>
                <div class="col"><button class="btn btn-primary">Filter</button></div>
            </form>
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Gudang</th>
                                <th>Bahan</th>
                                <th class="text-end">Tersedia</th>
                                <th class="text-end">Reservasi</th>
                                <th class="text-end">Bebas</th>
                                <th class="text-end">Dipesan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td>{{ $r->gudang->nama }}</td>
                                    <td>{{ $r->bahan->nama }}</td>
                                    <td class="text-end">{{ number_format($r->stok_tersedia, 6, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($r->stok_direservasi, 6, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($r->stok_bebas, 6, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($r->stok_dipesan, 6, ',', '.') }}</td>
                                    <td><a href="{{ route('stok-gudangs.show', $r) }}"
                                            class="btn btn-sm btn-outline-primary">Kartu</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>{{ $rows->links() }}
        </div>
    </div>
@endsection
