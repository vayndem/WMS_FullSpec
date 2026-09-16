@extends('layouts.app')

@section('content')
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">Perintah Kerja Baru</h3>
        @if (session('success'))
            <div class="alert alert-success mb-4"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="list-inside list-disc">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('data-pesanan.store') }}" class="card border border-base-300 bg-base-100 p-4 shadow-sm">
            @csrf

            <div class="alert alert-info mb-4">
                <i class="fa-solid fa-circle-info"></i>
                <span>Perintah kerja selalu lahir dari satu baris pesanan penjualan, dan produk yang dibuat mengikuti baris itu. Dengan begitu tidak mungkin memproduksi barang yang berbeda dari yang dipesan.</span>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text">Baris Pesanan Penjualan</span></label>
                    <select name="pesanan_penjualan_detail_id" data-app-picker required class="select select-bordered select-sm">
                        <option value="">Pilih baris pesanan</option>
                        @foreach ($pesananPenjualan as $so)
                            @foreach ($so->details as $detail)
                                <option value="{{ $detail->id }}" @selected(old('pesanan_penjualan_detail_id') == $detail->id)>
                                    {{ $so->nomor }} &middot; {{ $so->pelanggan?->nama }} &middot; {{ $detail->bahan?->nama }} ({{ number_format($detail->sisaKirim(), 2, ',', '.') }} belum dikirim)
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Tanggal</span></label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', today()->format('Y-m-d')) }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Jumlah Rencana</span></label>
                    <input type="number" step="0.000001" min="0.000001" name="jumlah_rencana" value="{{ old('jumlah_rencana') }}" required class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Gudang Produksi</span></label>
                    <select name="gudang_id" class="select select-bordered select-sm">
                        <option value="">Ikuti gudang pesanan</option>
                        @foreach ($gudang as $item)
                            <option value="{{ $item->id }}" @selected(old('gudang_id') == $item->id)>{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text">Keterangan</span></label>
                    <textarea name="keterangan" rows="2" class="textarea textarea-bordered">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                <a href="{{ route('data-pesanan.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </div>
        </form>
    </div>
@endsection
