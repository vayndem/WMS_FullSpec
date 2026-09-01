@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="text-2xl font-bold">Rekonsiliasi Inventory</h3>
        <p class="mb-4 text-base-content/60">Standar kontrol: master bahan = saldo gudang + transit; saldo gudang = layer; nilai layer = General Ledger.</p>

        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
            @foreach ([['Master Qty', $master_quantity], ['Gudang Qty', $warehouse_quantity], ['In Transit', $transit_quantity], ['Selisih Global', $global_quantity_difference]] as [$label, $value])
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <p class="text-sm text-base-content/50">{{ $label }}</p>
                    <p class="text-xl font-bold {{ abs($value) > 0.000001 && str_contains($label, 'Selisih') ? 'text-error' : '' }}">{{ number_format($value, 6, ',', '.') }}</p>
                </div>
            @endforeach
        </div>
        @if ($financial)
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ([['Nilai Layer', $layer_value], ['Persediaan GL', $inventory_gl_value], ['Selisih Nilai', $value_difference]] as [$label, $value])
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <p class="text-sm text-base-content/50">{{ $label }}</p>
                        <p class="text-xl font-bold {{ str_contains($label, 'Selisih') && abs($value) > .01 ? 'text-error' : '' }}">Rp {{ number_format($value, 2, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @php($hasException = $quantity_exceptions || abs($global_quantity_difference) > .000001 || ($financial && abs($value_difference) > .01))
        <div role="alert" class="alert {{ $hasException ? 'alert-error' : 'alert-success' }} mb-4">
            <span>{{ $hasException ? 'Ditemukan ketidaksesuaian yang harus diselesaikan sebelum closing.' : 'Seluruh kontrol inventory sesuai.' }}</span>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Gudang</th>
                            <th>Bahan</th>
                            <th>Saldo Gudang</th>
                            <th>Saldo Layer</th>
                            <th>Selisih</th>
                            @if ($financial)<th>Nilai Layer</th>@endif
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr class="{{ abs($r->selisih) > 0.000001 ? 'bg-error/10' : '' }}">
                                <td>{{ $r->gudang_nama }}</td>
                                <td class="font-semibold">{{ $r->bahan_nama }}</td>
                                <td>{{ $r->stok_tersedia }}</td>
                                <td>{{ $r->layer_quantity }}</td>
                                <td>{{ $r->selisih }}</td>
                                @if ($financial)<td>Rp {{ number_format($r->layer_value, 2, ',', '.') }}</td>@endif
                                <td>
                                    <span class="badge {{ abs($r->selisih) <= 0.000001 ? 'badge-success' : 'badge-error' }}">
                                        {{ abs($r->selisih) <= 0.000001 ? 'SESUAI' : 'SELISIH' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
