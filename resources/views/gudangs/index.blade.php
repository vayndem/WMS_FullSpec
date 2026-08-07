@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <h4>Master Gudang</h4>
                    <p class="text-muted">Gudang Normal, Consider, dan Rusak</p>
                </div><a href="{{ route('gudangs.create') }}" class="btn btn-primary">Tambah Gudang</a>
            </div>
            @include('warehouse_partials.alerts')
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
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
                                    <td>{{ $g->nama }}</td>
                                    <td><span class="badge bg-secondary">{{ $g->jenis }}</span></td>
                                    <td>{{ $g->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                                    <td>{{ collect(['LPB' => $g->boleh_penerimaan, 'NPK' => $g->boleh_npk, 'Transfer' => $g->boleh_transfer, 'Opname' => $g->boleh_opname])->filter()->keys()->join(', ') }}
                                    </td>
                                    <td><a href="{{ route('gudangs.show', $g) }}"
                                            class="btn btn-sm btn-outline-primary">Detail</a> <a
                                            href="{{ route('gudangs.edit', $g) }}"
                                            class="btn btn-sm btn-outline-warning">Edit</a></td>
                            </tr>@empty<tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada gudang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
