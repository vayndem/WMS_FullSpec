@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Pesanan Penjualan</h3>
                <p class="text-base-content/60">Pesanan dari pelanggan; pengiriman dilakukan lewat surat jalan.</p>
            </div>
            @can('create', App\Models\PesananPenjualan::class)
                <a href="{{ route('pesanan-penjualan.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Pesanan Baru</a>
            @endcan
        </div>
        @if (session('success'))
            <div class="alert alert-success mb-4"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="list-inside list-disc">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Status</span></label>
                    <select name="status" class="select select-bordered select-sm">
                        <option value="">Semua</option>
                        @foreach (['OPEN' => 'Open', 'CLOSED' => 'Closed', 'DIBATALKAN' => 'Dibatalkan'] as $kode => $label)
                            <option value="{{ $kode }}" @selected($status === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Nomor</th><th>Tanggal</th><th>Pelanggan</th><th>Sales</th><th>Gudang</th><th class="text-end">DPP</th><th class="text-end">PPN</th><th class="text-end">Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($pesanan as $baris)
                            <tr>
                                <td><a href="{{ route('pesanan-penjualan.show', $baris) }}" class="link link-primary font-mono text-xs">{{ $baris->nomor }}</a></td>
                                <td>{{ $baris->tanggal?->format('d-m-Y') }}</td>
                                <td>{{ $baris->pelanggan?->nama }}</td>
                                <td>{{ $baris->sales?->name ?? '-' }}</td>
                                <td>{{ $baris->gudang?->nama }}</td>
                                <td class="text-end">Rp {{ number_format($baris->total_dpp, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($baris->total_ppn, 0, ',', '.') }}</td>
                                <td class="text-end font-semibold">Rp {{ number_format($baris->grand_total, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $baris->status === 'OPEN' ? 'badge-warning' : 'badge-success' }}">{{ $baris->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-6 text-center text-base-content/50">Belum ada pesanan penjualan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 pb-4">{{ $pesanan->links() }}</div>
        </div>
    </div>
@endsection
