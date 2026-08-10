@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4>Pembagian Gudang</h4>
            <p class="text-muted">Atur gudang mana saja yang boleh diakses oleh user Warehouse atau Produksi.</p>@include('warehouse_partials.alerts')
            @can('create', App\Models\PembagianGudang::class)
            <form method="POST" action="{{ route('pembagian-gudangs.store') }}"
                class="card card-body mb-3">@csrf<div class="row g-3">
                    <div class="col-md-4"><select name="user_id" class="form-select" required>
                            <option value="">Pilih user gudang/produksi</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><select name="gudang_id" class="form-select" required>
                            <option value="">Pilih gudang</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-center"><button class="btn btn-primary">Simpan Pembagian</button></div>
                </div>
                <div class="row g-3 mt-1">
                    @foreach (['boleh_menerima' => 'Terima', 'boleh_npk' => 'NPK', 'boleh_transfer' => 'Transfer', 'boleh_opname' => 'Opname'] as $f => $label)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="hidden" name="{{ $f }}" value="0">
                                <input class="form-check-input" type="checkbox" name="{{ $f }}" value="1" id="{{ $f }}" checked>
                                <label class="form-check-label" for="{{ $f }}">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>
            @endcan
            <div class="card">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Gudang</th>
                            <th>Akses</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->user->name }}</td>
                                <td>{{ $r->gudang->nama }}</td>
                                <td>
                                    {{ collect([
                                        'Terima' => $r->boleh_menerima,
                                        'NPK' => $r->boleh_npk,
                                        'Transfer' => $r->boleh_transfer,
                                        'Opname' => $r->boleh_opname,
                                    ])->filter()->keys()->join(', ') ?: '-' }}
                                </td>
                                <td>
                                    @can('delete', $r)
                                    <form method="POST" action="{{ route('pembagian-gudangs.destroy', $r) }}">@csrf
                                        @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>{{ $rows->links() }}
        </div>
    </div>
@endsection
