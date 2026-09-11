@php
    $progress = collect($progress ?? []);
    $judul = $judul ?? 'Progres Pekerjaan';
@endphp
@if ($progress->isNotEmpty())
    <div class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="border-b border-base-300 p-4">
            <h5 class="font-bold"><i class="fa-solid fa-bars-progress text-primary"></i> {{ $judul }}</h5>
        </div>
        <div class="space-y-4 p-4">
            @foreach ($progress as $item)
                @php
                    $persen = (float) $item['persen'];
                    $tone = match (true) {
                        $persen >= 80 => 'progress-success',
                        $persen >= 50 => 'progress-info',
                        $persen >= 25 => 'progress-warning',
                        default => 'progress-error',
                    };
                @endphp
                <div>
                    <div class="mb-1 flex items-end justify-between gap-3">
                        <span class="text-sm font-semibold">{{ $item['label'] }}</span>
                        <span class="text-sm font-bold">{{ rtrim(rtrim(number_format($persen, 1, ',', '.'), '0'), ',') }}%</span>
                    </div>
                    <progress class="progress {{ $tone }} w-full" value="{{ $persen }}" max="100"></progress>
                    <p class="mt-1 text-xs text-base-content/60">
                        {{ rtrim(rtrim(number_format((float) $item['selesai'], 2, ',', '.'), '0'), ',') }}
                        dari
                        {{ rtrim(rtrim(number_format((float) $item['total'], 2, ',', '.'), '0'), ',') }}
                        @if ($item['catatan']) &middot; {{ $item['catatan'] }} @endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@endif
