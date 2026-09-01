@extends('layouts.app')
@section('content')
    <div class="content-page" x-data="{
        submitOrConfirm(event, message) {
            AppAlert.confirm(message).then(r => { if (r.isConfirmed) event.target.submit(); });
        },
    }">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-bold">{{ $transfer->nomor_transfer }}</h3>
            <div class="flex items-center gap-2">
                @can('update', $transfer)
                    <a href="{{ route('transfer-gudangs.edit', $transfer) }}" class="btn btn-warning">Edit</a>
                @endcan
                @can('submit', $transfer)
                    <form method="POST" action="{{ route('transfer-gudangs.submit', $transfer) }}">
                        @csrf
                        <button class="btn btn-info">Ajukan</button>
                    </form>
                @endcan
                @can('confirm', $transfer)
                    <form method="POST" action="{{ route('transfer-gudangs.confirm', $transfer) }}"
                        @submit.prevent="submitOrConfirm($event, 'Kirim barang dan pindahkan ke stok in-transit?')">
                        @csrf
                        <button class="btn btn-success">Kirim</button>
                    </form>
                @endcan
                @can('receive', $transfer)
                    <form method="POST" action="{{ route('transfer-gudangs.receive', $transfer) }}"
                        @submit.prevent="submitOrConfirm($event, 'Terima seluruh barang transfer?')">
                        @csrf
                        <button class="btn btn-success">Terima</button>
                    </form>
                @endcan
                @can('delete', $transfer)
                    <form method="POST" action="{{ route('transfer-gudangs.destroy', $transfer) }}"
                        @submit.prevent="submitOrConfirm($event, 'Hapus draft transfer ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline btn-error">Hapus</button>
                    </form>
                @endcan
            </div>
        </div>
        @include('warehouse_partials.alerts')
        <div class="card my-4 border border-base-300 bg-base-100 p-4 shadow-sm">
            <p><strong>{{ $transfer->gudangAsal->nama }}</strong> &rarr; <strong>{{ $transfer->gudangTujuan->nama }}</strong></p>
            <p class="text-base-content/60">Status: {{ $transfer->status }} · Tanggal: {{ $transfer->tanggal->format('d-m-Y') }}</p>
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Dikirim</th>
                            <th class="text-end">Diterima</th>
                            <th class="text-end">Selisih</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfer->details as $d)
                            <tr>
                                <td class="font-semibold">{{ $d->bahan->nama }}</td>
                                <td class="text-end">{{ $d->jumlah }}</td>
                                <td class="text-end">{{ $d->jumlah_dikirim }}</td>
                                <td class="text-end">{{ $d->jumlah_diterima }}</td>
                                <td class="text-end">{{ $d->jumlah_selisih }}</td>
                                <td>{{ $d->keterangan ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
