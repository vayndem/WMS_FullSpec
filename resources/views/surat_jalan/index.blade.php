@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Surat Jalan</h3>
            <p class="text-base-content/60">Pengeluaran barang ke pelanggan. Posting mengurangi stok FIFO dan membentuk jurnal harga pokok penjualan.</p>
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
                        @foreach (['DRAFT' => 'Draft', 'POSTED' => 'Posted', 'REVERSED' => 'Reversed'] as $kode => $label)
                            <option value="{{ $kode }}" @selected($status === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead><tr><th>Nomor</th><th>Tanggal</th><th>Pelanggan</th><th>Gudang</th><th>Pesanan</th><th class="text-end">HPP</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($suratJalan as $baris)
                            <tr>
                                <td><a href="{{ route('surat-jalan.show', $baris) }}" class="link link-primary font-mono text-xs">{{ $baris->nomor }}</a></td>
                                <td>{{ $baris->tanggal?->format('d-m-Y') }}</td>
                                <td>{{ $baris->pelanggan?->nama }}</td>
                                <td>{{ $baris->gudang?->nama }}</td>
                                <td class="font-mono text-xs">{{ $baris->pesanan?->nomor }}</td>
                                <td class="text-end">Rp {{ number_format($baris->total_hpp, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $baris->status === 'POSTED' ? 'badge-success' : ($baris->status === 'DRAFT' ? 'badge-ghost' : 'badge-error') }}">{{ $baris->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-base-content/50">Belum ada surat jalan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 pb-4">{{ $suratJalan->links() }}</div>
        </div>
    </div>
@endsection
