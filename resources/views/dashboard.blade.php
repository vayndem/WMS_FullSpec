@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Selamat datang, {{ $user['name'] }}</h3>
            <p class="text-base-content/60">Ringkasan lintas divisi: tugas terbuka, tenggat terdekat, dan progres pekerjaan.</p>
        </div>

        @if (!empty($tasks))
            <div class="mb-4 grid grid-cols-1 gap-3 xl:grid-cols-3">
                <div class="xl:col-span-2">
                    <h5 class="mb-3 font-bold"><i class="fa-solid fa-list-check text-primary"></i> Seluruh Tugas Terbuka</h5>
                    @include('layouts.template.task-grid', ['tasks' => $tasks])
                </div>
                @include('layouts.template.task-chart', ['tasks' => $tasks, 'chartTitle' => 'Distribusi Tugas', 'chartType' => 'bar', 'horizontal' => true])
            </div>

            <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
                @include('layouts.template.reminder-list', ['reminders' => $reminders ?? [], 'judul' => 'Tenggat Terdekat Lintas Divisi'])
                @include('layouts.template.progress-list', ['progress' => $progress ?? []])
            </div>
        @else
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body text-center text-base-content/60">
                    Belum ada data untuk ditampilkan.
                </div>
            </div>
        @endif
    </div>
@endsection
