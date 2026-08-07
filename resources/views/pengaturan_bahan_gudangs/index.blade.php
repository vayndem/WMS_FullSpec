@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4>Pengaturan Bahan per Gudang</h4>@include('warehouse_partials.alerts')<form method="POST"
                action="{{ route('pengaturan-bahan-gudangs.store') }}" class="card card-body mb-3">@csrf<div class="row g-2">
                    <div class="col-md-3"><select name="gudang_id" class="form-select" required>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><select name="bahan_id" class="form-select" required>
                            @foreach ($bahans as $b)
                                <option value="{{ $b->id }}">{{ $b->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach (['stok_minimum', 'stok_maksimum', 'stok_pengaman', 'titik_pemesanan'] as $f)
                        <div class="col"><input type="number" step="any" min="0" name="{{ $f }}"
                                class="form-control" placeholder="{{ str_replace('_', ' ', $f) }}" value="0" required>
                        </div>
                    @endforeach
                    <div class="col">
                        <input type="hidden" name="aktif" value="1"><button class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
            <div class="card table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Gudang</th>
                            <th>Bahan</th>
                            <th>Min</th>
                            <th>Maks</th>
                            <th>Safety</th>
                            <th>Reorder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td>{{ $r->gudang->nama }}</td>
                                <td>{{ $r->bahan->nama }}</td>
                                <td>{{ $r->stok_minimum }}</td>
                                <td>{{ $r->stok_maksimum }}</td>
                                <td>{{ $r->stok_pengaman }}</td>
                                <td>{{ $r->titik_pemesanan }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>{{ $rows->links() }}
        </div>
    </div>
@endsection
