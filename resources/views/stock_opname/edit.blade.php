@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Perbaiki {{ $opname->number }}</h3>
            <p class="text-base-content/60">Dokumen rejected akan kembali menjadi draft setelah disimpan.</p>
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body p-4">@include('stock_opname._form')</div>
        </div>
    </div>
@endsection
