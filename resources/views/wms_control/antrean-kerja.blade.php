@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Antrean Kerja Saya</h3>
            <p class="text-base-content/60">Seluruh pekerjaan gudang yang menunggu Anda, dibatasi pada gudang yang boleh Anda akses.</p>
        </div>

        @if (empty($gudang_ids))
            <div role="alert" class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Anda belum ditugaskan ke gudang manapun. Hubungi administrator untuk mengatur Pembagian Gudang.</span>
            </div>
        @else
            @php
                $toneKartu = [
                    'warning' => 'bg-warning/10 text-warning',
                    'info' => 'bg-info/10 text-info',
                    'primary' => 'bg-primary/10 text-primary',
                    'secondary' => 'bg-secondary/10 text-secondary',
                    'accent' => 'bg-accent/10 text-accent',
                ];
                $ikon = ['QC' => 'fa-microscope', 'PUTAWAY' => 'fa-dolly', 'PICK' => 'fa-hand-holding-box',
                         'TRANSFER' => 'fa-truck-arrow-right', 'OPNAME' => 'fa-clipboard-check'];
                $labelJenis = ['QC' => 'Pemeriksaan QC', 'PUTAWAY' => 'Penempatan', 'PICK' => 'Pengambilan',
                               'TRANSFER' => 'Terima Transfer', 'OPNAME' => 'Stock Opname'];
            @endphp

            <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
                @foreach (['QC', 'PUTAWAY', 'PICK', 'TRANSFER', 'OPNAME'] as $jenis)
                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body items-center p-4 text-center">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $toneKartu[['QC'=>'warning','PUTAWAY'=>'info','PICK'=>'primary','TRANSFER'=>'secondary','OPNAME'=>'accent'][$jenis]] }}">
                                <i class="fa-solid {{ $ikon[$jenis] }}"></i>
                            </span>
                            <p class="text-2xl font-bold">{{ $ringkasan[$jenis] ?? 0 }}</p>
                            <p class="text-xs text-base-content/60">{{ $labelJenis[$jenis] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="xl:col-span-2">
                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="flex items-center justify-between border-b border-base-300 p-4">
                            <h5 class="font-bold"><i class="fa-solid fa-list-check text-primary"></i> Tugas Menunggu</h5>
                            <span class="badge badge-ghost">{{ $antrean->count() }}</span>
                        </div>
                        <div class="divide-y divide-base-300">
                            @forelse ($antrean as $tugas)
                                <a href="{{ $tugas['url'] }}" class="flex flex-wrap items-center gap-3 p-3 transition hover:bg-base-200">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $toneKartu[$tugas['tone']] ?? '' }}">
                                        <i class="fa-solid {{ $ikon[$tugas['jenis']] ?? 'fa-list-check' }}"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold">{{ $tugas['judul'] }}</p>
                                        <p class="truncate text-xs text-base-content/60">{{ $tugas['label'] }} &middot; {{ $tugas['detail'] }}</p>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-base-content/30"></i>
                                </a>
                            @empty
                                <div class="p-10 text-center text-base-content/50">
                                    <i class="fa-solid fa-mug-hot text-3xl text-success"></i>
                                    <p class="mt-2">Tidak ada tugas menunggu. Semua beres.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="border-b border-base-300 p-4">
                            <h5 class="font-bold"><i class="fa-solid fa-layer-group text-primary"></i> Gelombang Pengambilan</h5>
                        </div>
                        <div class="divide-y divide-base-300">
                            @forelse ($gelombang_saya as $wave)
                                <a href="{{ route('gelombang-pengambilan.index', ['gudang_id' => $wave->gudang_id]) }}"
                                    class="block p-3 transition hover:bg-base-200">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold">{{ $wave->nomor }}</span>
                                        <span class="badge badge-sm {{ $wave->status === 'DIRILIS' ? 'badge-success' : 'badge-ghost' }}">{{ $wave->status }}</span>
                                    </div>
                                    <p class="text-xs text-base-content/60">
                                        {{ $wave->gudang->nama ?? '-' }} &middot; {{ $wave->pesanan_count }} perintah
                                    </p>
                                </a>
                            @empty
                                <div class="p-6 text-center text-sm text-base-content/50">Belum ada gelombang aktif.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="border-b border-base-300 p-4">
                            <h5 class="font-bold"><i class="fa-solid fa-calendar-check text-warning"></i> Cycle Count Jatuh Tempo</h5>
                        </div>
                        <div class="divide-y divide-base-300">
                            @php $adaJatuhTempo = false; @endphp
                            @foreach ($jadwal_siklus as $baris)
                                @foreach ($baris['kelas'] as $kelas)
                                    @if ($kelas['jatuh_tempo'])
                                        @php $adaJatuhTempo = true; @endphp
                                        <div class="flex items-center justify-between gap-2 p-3">
                                            <div class="min-w-0">
                                                <p class="font-semibold">Kelas {{ $kelas['kelas'] }}</p>
                                                <p class="text-xs text-base-content/60">
                                                    {{ $kelas['bahan'] }} bahan &middot; siklus {{ $kelas['siklus_bulan'] }} bulan
                                                </p>
                                            </div>
                                            <span class="badge badge-warning badge-sm shrink-0">
                                                {{ $kelas['terakhir'] ? 'Lewat' : 'Belum pernah' }}
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                            @unless ($adaJatuhTempo)
                                <div class="p-6 text-center text-sm text-base-content/50">Semua kelas masih dalam siklus.</div>
                            @endunless
                        </div>
                        <a href="{{ route('stock-opname.siklus') }}" class="btn btn-ghost btn-sm rounded-t-none">Buka papan cycle count</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
