@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">Saldo Stok per Gudang</h3>
        <form class="mb-4 grid grid-cols-1 gap-2 md:grid-cols-9">
            <select name="gudang_id" class="select select-bordered md:col-span-4">
                <option value="">Semua gudang</option>
                @foreach ($gudangs as $g)
                    <option value="{{ $g->id }}" @selected(request('gudang_id') == $g->id)>{{ $g->nama }}</option>
                @endforeach
            </select>
            <input name="q" class="input input-bordered md:col-span-4" value="{{ request('q') }}" placeholder="Cari bahan">
            <button class="btn btn-primary md:col-span-1">Filter</button>
        </form>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
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
                                <td class="font-semibold">{{ $r->bahan->nama }}</td>
                                <td class="text-end">{{ number_format($r->stok_tersedia, 6, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r->stok_direservasi, 6, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r->stok_bebas, 6, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r->stok_dipesan, 6, ',', '.') }}</td>
                                <td>
                                    @can('view', $r)
                                        <a href="{{ route('stok-gudangs.show', $r) }}" class="btn btn-outline btn-primary btn-sm">Kartu</a>
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
