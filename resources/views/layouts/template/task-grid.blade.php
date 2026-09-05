@php
    $toneClasses = [
        'error' => 'bg-error/10 text-error',
        'warning' => 'bg-warning/10 text-warning',
        'info' => 'bg-info/10 text-info',
        'success' => 'bg-success/10 text-success',
        'neutral' => 'bg-base-200 text-base-content/60',
    ];
@endphp
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @forelse ($tasks as $task)
        <a href="{{ $task['url'] }}" class="card border border-base-300 bg-base-100 p-4 shadow-sm transition hover:border-primary hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $toneClasses[$task['tone'] ?? 'neutral'] }}">
                    <i class="fa-solid {{ $task['icon'] ?? 'fa-list-check' }}"></i>
                </span>
                <span class="text-2xl font-bold">{{ $task['count'] }}</span>
            </div>
            <p class="mt-2 text-sm font-semibold text-base-content/70">{{ $task['label'] }}</p>
        </a>
    @empty
        <div class="col-span-full py-4 text-center text-base-content/50">Tidak ada tugas untuk ditampilkan.</div>
    @endforelse
</div>
