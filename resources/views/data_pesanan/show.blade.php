@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Perintah Kerja {{ $pesanan->nomor }}</h3>
                <p class="text-base-content/60">
                    {{ $pesanan->pesananPenjualan?->pelanggan?->nama }} &middot;
                    {{ $pesanan->bahanHasil?->nama }} &middot;
                    pesanan <span class="font-mono">{{ $pesanan->pesananPenjualan?->nomor }}</span>
                    <span class="badge {{ $pesanan->status === 'DITUTUP' ? 'badge-success' : ($pesanan->status === 'SELESAI' ? 'badge-info' : 'badge-warning') }}">{{ $pesanan->status }}</span>
                </p>
            </div>
            <div class="flex gap-2">
                @can('rilis', $pesanan)
                    <form method="POST" action="{{ route('data-pesanan.rilis', $pesanan) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">Rilis</button>
                    </form>
                @endcan
                @can('batalkan', $pesanan)
                    <form method="POST" action="{{ route('data-pesanan.batalkan', $pesanan) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">Batalkan</button>
                    </form>
                @endcan
            </div>
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

        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-base-300 p-4">
                <div class="text-xs uppercase text-base-content/60">Rencana / Selesai / Terkirim</div>
                <div class="text-xl font-bold">
                    {{ number_format($pesanan->jumlah_rencana, 0, ',', '.') }} /
                    {{ number_format($pesanan->jumlah_selesai, 0, ',', '.') }} /
                    {{ number_format($pesanan->jumlah_terkirim, 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded-lg border border-base-300 p-4">
                <div class="text-xs uppercase text-base-content/60">Biaya Terserap</div>
                <div class="text-xl font-bold">Rp {{ number_format($pesanan->totalBiaya(), 0, ',', '.') }}</div>
            </div>
            <div class="rounded-lg border border-base-300 p-4">
                <div class="text-xs uppercase text-base-content/60">Harga Pokok per Unit</div>
                <div class="text-xl font-bold">
                    {{ $pesanan->sudahSelesai() ? 'Rp ' . number_format($pesanan->biaya_per_unit, 2, ',', '.') : 'belum dikunci' }}
                </div>
            </div>
            <div class="rounded-lg border border-info/40 bg-info/5 p-4">
                <div class="text-xs uppercase text-base-content/60">Saldo Barang Dalam Proses</div>
                <div class="text-xl font-bold text-info">Rp {{ number_format($pesanan->saldoWip(), 0, ',', '.') }}</div>
            </div>
        </div>

        @can('selesaikan', $pesanan)
            <form method="POST" action="{{ route('data-pesanan.selesaikan', $pesanan) }}" class="card mb-4 border border-base-300 bg-base-100 p-4 shadow-sm">
                @csrf
                <h4 class="mb-3 font-semibold">Laporkan Selesai</h4>
                <div class="alert alert-warning mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Setelah dilaporkan selesai, harga pokok per unit dikunci dan <strong>perintah kerja ini tidak dapat lagi menerima biaya</strong>. Pastikan seluruh pemakaian barang dan biaya jasa sudah tercatat.</span>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Jumlah Hasil Produksi</span></label>
                        <input type="number" step="0.000001" min="0.000001" name="jumlah_selesai" value="{{ $pesanan->jumlah_rencana }}" required class="input input-bordered input-sm">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Laporkan Selesai</button>
                </div>
            </form>
        @endcan

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4"><h4 class="font-semibold">Biaya yang Terserap</h4></div>
            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead><tr><th>Sumber</th><th>Tanggal</th><th>Keterangan</th><th class="text-end">Nilai</th></tr></thead>
                    <tbody>
                        @forelse ($pesanan->biaya as $biaya)
                            <tr>
                                <td><span class="badge badge-ghost badge-sm">{{ $biaya->sumber }}</span></td>
                                <td>{{ $biaya->tanggal?->format('d-m-Y') }}</td>
                                <td>{{ $biaya->keterangan ?: '-' }}</td>
                                <td class="text-end">Rp {{ number_format($biaya->nilai, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-base-content/50">Belum ada biaya yang terserap.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-base-200/40 font-bold">
                            <td colspan="3" class="text-end">Total</td>
                            <td class="text-end">Rp {{ number_format($pesanan->totalBiaya(), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="px-4 pb-4 text-xs text-base-content/50">
                Saldo barang dalam proses dihitung dari baris-baris di atas, bukan dari angka yang disimpan terpisah &mdash; sehingga tidak bisa melenceng diam-diam dari buku besar.
            </div>
        </div>

        @if ($pesanan->sudahSelesai())
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info"></i>
                <span>
                    Hasil produksi tidak dibukukan sebagai stok barang jadi. Nilainya tetap duduk di Barang Dalam Proses sampai dikirim,
                    dan setiap pengiriman melepasnya sebesar {{ number_format($pesanan->biaya_per_unit, 2, ',', '.') }} per unit ke Beban Pokok Penjualan.
                </span>
            </div>
        @endif
    </div>
@endsection
