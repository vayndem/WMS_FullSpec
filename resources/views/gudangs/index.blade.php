@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">Master Gudang</h3>
                <p class="text-base-content/60">Gudang Normal, Consider, dan Rusak</p>
            </div>
            @can('create', App\Models\Gudang::class)
                <a href="{{ route('gudangs.create') }}" class="btn btn-primary">Tambah Gudang</a>
            @endcan
        </div>
        @include('warehouse_partials.alerts')
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Kemampuan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gudangs as $g)
                            <tr>
                                <td>{{ $g->kode }}</td>
                                <td class="font-semibold">{{ $g->nama }}</td>
                                <td><span class="badge badge-ghost">{{ $g->jenis }}</span></td>
                                <td>{{ $g->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                                <td>{{ collect(['LPB' => $g->boleh_penerimaan, 'NPK' => $g->boleh_npk, 'Transfer' => $g->boleh_transfer, 'Opname' => $g->boleh_opname])->filter()->keys()->join(', ') }}</td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        @can('view', $g)
                                            <a href="{{ route('gudangs.show', $g) }}" class="btn btn-outline btn-primary btn-sm">Detail</a>
                                        @endcan
                                        @can('update', $g)
                                            <a href="{{ route('gudangs.edit', $g) }}" class="btn btn-outline btn-warning btn-sm">Edit</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-base-content/50">Belum ada gudang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
