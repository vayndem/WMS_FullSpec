@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="text-2xl font-bold">Pengaturan Bahan per Gudang</h3>
        @include('warehouse_partials.alerts')
        @can('create', App\Models\PengaturanBahanGudang::class)
            <form method="POST" action="{{ route('pengaturan-bahan-gudangs.store') }}" class="card mb-4 border border-base-300 bg-base-100 p-4 shadow-sm">
                @csrf
                <div class="grid grid-cols-1 gap-2 md:grid-cols-7">
                    <select name="gudang_id" class="select select-bordered md:col-span-2" required>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                    <select name="bahan_id" class="select select-bordered md:col-span-2" required>
                        @foreach ($bahans as $b)
                            <option value="{{ $b->id }}">{{ $b->nama }}</option>
                        @endforeach
                    </select>
                    @foreach (['stok_minimum', 'stok_maksimum', 'stok_pengaman', 'titik_pemesanan'] as $f)
                        <input type="number" step="any" min="0" name="{{ $f }}" class="input input-bordered" placeholder="{{ str_replace('_', ' ', $f) }}" value="0" required>
                    @endforeach
                    <input type="hidden" name="aktif" value="1">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        @endcan
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Gudang</th>
                            <th>Bahan</th>
                            <th>Min</th>
                            <th>Maks</th>
                            <th>Safety</th>
                            <th>Reorder</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->gudang->nama }}</td>
                                <td class="font-semibold">{{ $r->bahan->nama }}</td>
                                <td>{{ $r->stok_minimum }}</td>
                                <td>{{ $r->stok_maksimum }}</td>
                                <td>{{ $r->stok_pengaman }}</td>
                                <td>{{ $r->titik_pemesanan }}</td>
                                <td>
                                    @can('delete', $r)
                                        <form method="POST" action="{{ route('pengaturan-bahan-gudangs.destroy', $r) }}"
                                            @submit.prevent="AppAlert.confirm('Hapus pengaturan bahan gudang ini?').then(r => r.isConfirmed && $event.target.submit())">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline btn-error btn-sm">Hapus</button>
                                        </form>
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
