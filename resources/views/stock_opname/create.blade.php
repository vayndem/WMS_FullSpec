@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold">Buat Stock Opname</h3>
                <p class="text-muted">Stok sistem akan disimpan sebagai snapshot dokumen.</p>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">@include('stock_opname._form')</div>
            </div>
        </div>
    </div>
@endsection
