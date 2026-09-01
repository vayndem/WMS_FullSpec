@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="card bg-base-100 shadow-sm border border-base-300">
            <div class="flex items-center justify-between border-b border-base-300 p-4">
                <h4 class="text-lg font-bold">Selamat Datang: <strong>{{ $user['name'] }}</strong></h4>
            </div>
            <div class="card-body">
            </div>
        </div>
    </div>
@endsection
