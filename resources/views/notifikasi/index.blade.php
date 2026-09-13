@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Notifikasi</h3>
                <p class="text-base-content/60">
                    Antrean persetujuan dan pengingat tenggat yang ditujukan kepada peran Anda.
                </p>
            </div>
            @if ($belumDibaca > 0)
                <form action="{{ route('notifikasi.baca-semua') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-check-double"></i> Tandai semua dibaca ({{ $belumDibaca }})
                    </button>
                </form>
            @endif
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mb-4 shadow-sm">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @php
            $toneBadge = [
                'error' => 'bg-error/10 text-error',
                'warning' => 'bg-warning/10 text-warning',
                'info' => 'bg-info/10 text-info',
                'success' => 'bg-success/10 text-success',
            ];
        @endphp

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="divide-y divide-base-300">
                @forelse ($notifikasi as $item)
                    @php
                        $data = $item->data;
                        $warna = $toneBadge[$data['warna'] ?? 'info'] ?? $toneBadge['info'];
                    @endphp
                    <div class="flex flex-wrap items-start gap-3 p-4 {{ $item->read_at ? '' : 'bg-base-200/60' }}">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $warna }}">
                            <i class="fa-solid {{ $data['ikon'] ?? 'fa-bell' }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">{{ $data['judul'] ?? 'Notifikasi' }}</p>
                                @unless ($item->read_at)
                                    <span class="badge badge-primary badge-xs">baru</span>
                                @endunless
                            </div>
                            <p class="text-sm text-base-content/70">{{ $data['ringkasan'] ?? '' }}</p>
                            <p class="mt-1 text-xs text-base-content/50">
                                {{ $data['konteks'] ?? '' }} &middot; {{ $item->created_at->diffForHumans() }}
                            </p>

                            @if (!empty($data['rincian']))
                                <div class="collapse-arrow collapse mt-2 border border-base-300">
                                    <input type="checkbox">
                                    <div class="collapse-title min-h-0 py-2 text-sm font-semibold">
                                        Lihat {{ count($data['rincian']) }} rincian
                                    </div>
                                    <div class="collapse-content">
                                        <ul class="space-y-1 text-sm">
                                            @foreach ($data['rincian'] as $rincian)
                                                <li class="flex flex-wrap items-center justify-between gap-2">
                                                    <span class="min-w-0 truncate">{{ $rincian['label'] }}</span>
                                                    <span class="badge badge-sm {{ $rincian['hari'] < 0 ? 'badge-error' : 'badge-ghost' }}">
                                                        {{ $rincian['hari'] < 0 ? 'Lewat ' . abs($rincian['hari']) . ' hari' : $rincian['hari'] . ' hari lagi' }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <form action="{{ route('notifikasi.baca', $item->id) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                Buka <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="p-10 text-center text-base-content/50">
                        <i class="fa-solid fa-bell-slash text-3xl"></i>
                        <p class="mt-2">Belum ada notifikasi.</p>
                    </div>
                @endforelse
            </div>
        </div>

        @if ($notifikasi->hasPages())
            <div class="mt-4">{{ $notifikasi->links() }}</div>
        @endif
    </div>
@endsection
