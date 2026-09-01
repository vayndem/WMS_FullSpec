@extends('layouts.app')

@section('content')
    <div class="content-page" x-data="{
        submitOrConfirm(event, message) {
            AppAlert.confirm(message).then(r => { if (r.isConfirmed) event.target.submit(); });
        },
    }">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-bold">{{ $pemeriksaan->nomor_pemeriksaan }}</h3>
            <div class="flex items-center gap-2">
                @can('update', $pemeriksaan)
                    <a class="btn btn-warning" href="{{ route('pemeriksaan-considers.edit', $pemeriksaan) }}">Edit</a>
                @endcan
                @can('confirm', $pemeriksaan)
                    <form method="POST" action="{{ route('pemeriksaan-considers.confirm', $pemeriksaan) }}"
                        @submit.prevent="submitOrConfirm($event, 'Konfirmasi keputusan? Barang rusak tidak dapat kembali ke gudang aktif.')">
                        @csrf
                        <button class="btn btn-error">Konfirmasi Final</button>
                    </form>
                @endcan
                @can('delete', $pemeriksaan)
                    <form method="POST" action="{{ route('pemeriksaan-considers.destroy', $pemeriksaan) }}"
                        @submit.prevent="submitOrConfirm($event, 'Hapus draft pemeriksaan ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline btn-error">Hapus</button>
                    </form>
                @endcan
            </div>
        </div>

        @include('warehouse_partials.alerts')

        <div role="alert" class="alert alert-warning my-4">
            <span>Setelah dikonfirmasi, barang berstatus rusak bersifat final dan tidak dapat ditransfer kembali.</span>
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th>Diperiksa</th>
                            <th>Baik</th>
                            <th>Rusak</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pemeriksaan->details as $d)
                            <tr>
                                <td class="font-semibold">{{ $d->bahan->nama }}</td>
                                <td>{{ $d->jumlah_diperiksa }}</td>
                                <td>{{ $d->jumlah_baik }}</td>
                                <td>{{ $d->jumlah_rusak }}</td>
                                <td>{{ $d->alasan ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
