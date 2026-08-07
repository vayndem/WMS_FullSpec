@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4>Pembagian Gudang</h4>
            <p class="text-muted">Disiapkan untuk pembatasan per gudang; saat ini seluruh type 14 tetap dapat mengakses semua
                gudang.</p>@include('warehouse_partials.alerts')<form method="POST" action="{{ route('pembagian-gudangs.store') }}"
                class="card card-body mb-3">@csrf<div class="row">
                    <div class="col-md-4"><select name="user_id" class="form-select" required>
                            <option value="">Pilih admin gudang</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
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
                    <div class="col"><button class="btn btn-primary">Simpan Pembagian</button></div>
                </div>
                @foreach (['boleh_menerima', 'boleh_npk', 'boleh_transfer', 'boleh_opname'] as $f)
                    <input type="hidden" name="{{ $f }}" value="1">
                @endforeach
            </form>
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
                                <td>Terima, NPK, Transfer, Opname</td>
                                <td>
                                    <form method="POST" action="{{ route('pembagian-gudangs.destroy', $r) }}">@csrf
                                        @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>{{ $rows->links() }}
        </div>
    </div>
@endsection
