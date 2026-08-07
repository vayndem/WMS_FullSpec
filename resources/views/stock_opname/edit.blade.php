@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold">Perbaiki {{ $opname->number }}</h3>
                <p class="text-muted">Dokumen rejected akan kembali menjadi draft setelah disimpan.</p>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">@include('stock_opname._form')</div>
            </div>
        </div>
    </div>
@endsection
