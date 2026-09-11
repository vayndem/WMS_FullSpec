@php
    $reminders = collect($reminders ?? []);
    $judul = $judul ?? 'Pengingat & Deadline';
@endphp
<div class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="flex items-center justify-between border-b border-base-300 p-4">
        <h5 class="font-bold"><i class="fa-solid fa-bell text-warning"></i> {{ $judul }}</h5>
        @if ($reminders->isNotEmpty())
            <span class="badge badge-warning">{{ $reminders->count() }}</span>
        @endif
    </div>
    <div class="divide-y divide-base-300">
        @forelse ($reminders as $item)
            @php
                $hari = (int) $item['hari'];
                [$tone, $teks] = match (true) {
                    $hari < 0 => ['error', 'Lewat ' . abs($hari) . ' hari'],
                    $hari === 0 => ['error', 'Hari ini'],
                    $hari <= 7 => ['warning', $hari . ' hari lagi'],
                    default => ['info', $hari . ' hari lagi'],
                };
            @endphp
            <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-3 p-3 transition hover:bg-base-200">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ $item['label'] }}</p>
                    <p class="text-xs text-base-content/60">
                        {{ $item['konteks'] }} &middot; {{ $item['tanggal']->format('d-m-Y') }}
                        @if (!is_null($item['nilai']))
                            &middot; {{ rtrim(rtrim(number_format((float) $item['nilai'], 2, ',', '.'), '0'), ',') }}
                        @endif
                    </p>
                </div>
                <span class="badge badge-{{ $tone }} shrink-0 whitespace-nowrap">{{ $teks }}</span>
            </a>
        @empty
            <div class="p-6 text-center text-base-content/50">
                <i class="fa-solid fa-circle-check text-2xl text-success"></i>
                <p class="mt-2 text-sm">Tidak ada yang mendesak. Semua aman.</p>
            </div>
        @endforelse
    </div>
</div>
