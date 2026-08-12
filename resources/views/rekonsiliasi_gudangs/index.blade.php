@extends('layouts.app')
@section('content')
<div class="content-page"><div class="container-fluid">
<h4>Rekonsiliasi Inventory</h4><p class="text-muted">Standar kontrol: master bahan = saldo gudang + transit; saldo gudang = layer; nilai layer = General Ledger.</p>
<div class="row g-3 mb-3">
@foreach([['Master Qty',$master_quantity],['Gudang Qty',$warehouse_quantity],['In Transit',$transit_quantity],['Selisih Global',$global_quantity_difference]] as [$label,$value])<div class="col-md-3"><div class="card card-body"><small>{{ $label }}</small><h4 class="mb-0 {{ abs($value)>0.000001 && str_contains($label,'Selisih')?'text-danger':'' }}">{{ number_format($value,6,',','.') }}</h4></div></div>@endforeach
@if($financial)@foreach([['Nilai Layer',$layer_value],['Persediaan GL',$inventory_gl_value],['Selisih Nilai',$value_difference]] as [$label,$value])<div class="col-md-4"><div class="card card-body"><small>{{ $label }}</small><h4 class="mb-0 {{ str_contains($label,'Selisih') && abs($value)>.01?'text-danger':'' }}">Rp {{ number_format($value,2,',','.') }}</h4></div></div>@endforeach @endif
</div>
<div class="alert {{ $quantity_exceptions || abs($global_quantity_difference)>.000001 || ($financial && abs($value_difference)>.01) ? 'alert-danger':'alert-success' }}">{{ $quantity_exceptions || abs($global_quantity_difference)>.000001 || ($financial && abs($value_difference)>.01) ? 'Ditemukan ketidaksesuaian yang harus diselesaikan sebelum closing.':'Seluruh kontrol inventory sesuai.' }}</div>
<div class="card table-responsive"><table class="table mb-0"><thead><tr><th>Gudang</th><th>Bahan</th><th>Saldo Gudang</th><th>Saldo Layer</th><th>Selisih</th>@if($financial)<th>Nilai Layer</th>@endif<th>Status</th></tr></thead><tbody>@foreach($rows as $r)<tr class="{{ abs($r->selisih)>0.000001?'table-danger':'' }}"><td>{{ $r->gudang_nama }}</td><td>{{ $r->bahan_nama }}</td><td>{{ $r->stok_tersedia }}</td><td>{{ $r->layer_quantity }}</td><td>{{ $r->selisih }}</td>@if($financial)<td>Rp {{ number_format($r->layer_value,2,',','.') }}</td>@endif<td>{{ abs($r->selisih)<=0.000001?'SESUAI':'SELISIH' }}</td></tr>@endforeach</tbody></table></div>
</div></div>
@endsection
