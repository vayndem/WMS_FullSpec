@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Faktur Penjualan</h3>
                <p class="text-base-content/60">Tagihan ke pelanggan beserta PPN keluaran dan sisa piutang.</p>
            </div>
            <div class="rounded-lg border border-base-300 px-4 py-2 text-end">
                <div class="text-xs uppercase text-base-content/60">Total Piutang Berjalan</div>
                <div class="text-xl font-bold">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</div>
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

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Status</span></label>
                    <select name="status" class="select select-bordered select-sm">
                        <option value="">Semua</option>
                        @foreach (['DRAFT' => 'Draft', 'POSTED' => 'Belum Dibayar', 'PARTIALLY_PAID' => 'Sebagian Dibayar', 'PAID' => 'Lunas', 'VOID' => 'Void'] as $kode => $label)
                            <option value="{{ $kode }}" @selected($status === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead><tr><th>Nomor</th><th>Tanggal</th><th>Jatuh Tempo</th><th>Pelanggan</th><th class="text-end">Total</th><th class="text-end">Sisa</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($faktur as $baris)
                            <tr>
                                <td><a href="{{ route('faktur-penjualan.show', $baris) }}" class="link link-primary font-mono text-xs">{{ $baris->nomor }}</a></td>
                                <td>{{ $baris->tanggal?->format('d-m-Y') }}</td>
                                <td>
                                    {{ $baris->jatuh_tempo?->format('d-m-Y') }}
                                    @if ($baris->isTertagih() && $baris->jatuh_tempo?->isPast())
                                        <span class="badge badge-error badge-xs">lewat</span>
                                    @endif
                                </td>
                                <td>{{ $baris->pelanggan?->nama }}</td>
                                <td class="text-end">Rp {{ number_format($baris->grand_total, 0, ',', '.') }}</td>
                                <td class="text-end font-semibold">Rp {{ number_format($baris->sisa_tagihan, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $baris->status === 'PAID' ? 'badge-success' : ($baris->status === 'DRAFT' ? 'badge-ghost' : 'badge-warning') }}">{{ $baris->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-base-content/50">Belum ada faktur penjualan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 pb-4">{{ $faktur->links() }}</div>
        </div>
    </div>
@endsection
