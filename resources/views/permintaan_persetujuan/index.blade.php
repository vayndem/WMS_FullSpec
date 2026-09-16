@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Antrean Persetujuan</h3>
            <p class="text-base-content/60">Pembalikan dokumen dan perubahan bagan akun menunggu keputusan Accounting Manager sebelum benar-benar dijalankan.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success mb-4">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div role="tablist" class="tabs tabs-boxed m-4">
                @foreach (['PENDING' => 'Menunggu', 'APPROVED' => 'Disetujui', 'REJECTED' => 'Ditolak'] as $kunci => $label)
                    <a role="tab" href="{{ route('permintaan-persetujuan.index', ['status' => $kunci]) }}" class="tab {{ $status === $kunci ? 'tab-active' : '' }}">
                        {{ $label }}
                        @if ($kunci === 'PENDING' && $jumlahPending > 0)
                            <span class="badge badge-warning badge-sm ml-2">{{ $jumlahPending }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="overflow-x-auto px-4 pb-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Jenis</th>
                            <th>Ringkasan</th>
                            <th>Alasan</th>
                            <th>Pemohon</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permintaan as $item)
                            <tr>
                                <td class="font-mono text-xs">{{ $item->nomor }}</td>
                                <td>
                                    <div>{{ \App\Models\PermintaanPersetujuan::LABEL_JENIS[$item->jenis] ?? $item->jenis }}</div>
                                    <div class="text-xs text-base-content/50">{{ $item->sub_jenis }}</div>
                                </td>
                                <td>{{ $item->ringkasan }}</td>
                                <td class="max-w-xs text-xs text-base-content/70">{{ $item->alasan }}</td>
                                <td>
                                    <div>{{ $item->pemohon?->name ?? '-' }}</div>
                                    <div class="text-xs text-base-content/50">{{ $item->created_at?->format('d-m-Y H:i') }}</div>
                                </td>
                                <td>
                                    @if ($item->status === 'PENDING')
                                        <span class="badge badge-warning">Menunggu</span>
                                    @elseif ($item->status === 'APPROVED')
                                        <span class="badge badge-success">Disetujui</span>
                                    @else
                                        <span class="badge badge-error">Ditolak</span>
                                    @endif
                                    @if ($item->pemutus)
                                        <div class="text-xs text-base-content/50">
                                            {{ $item->pemutus->name }} &middot; {{ $item->diputuskan_pada?->format('d-m-Y H:i') }}
                                        </div>
                                    @endif
                                    @if ($item->catatan_checker)
                                        <div class="text-xs italic text-base-content/50">{{ $item->catatan_checker }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('decide', $item)
                                        <div class="flex justify-end gap-1">
                                            <form method="POST" action="{{ route('permintaan-persetujuan.approve', $item) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-xs">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('permintaan-persetujuan.reject', $item) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-error btn-xs">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-base-content/40">&mdash;</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-base-content/50">Tidak ada permintaan pada status ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-4">
                {{ $permintaan->links() }}
            </div>
        </div>

        <div class="mt-4 alert alert-info">
            <i class="fa-solid fa-circle-info"></i>
            <span>Pemohon tidak bisa menyetujui permintaannya sendiri. Aturan itu ditegakkan di service, bukan hanya di policy, sehingga Super Admin pun tidak bisa melewatinya.</span>
        </div>
    </div>
@endsection
