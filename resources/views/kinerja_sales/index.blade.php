@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Kinerja Sales</h3>
                <p class="text-base-content/60">
                    Pesanan, penjualan, retur, dan piutang beredar per sales pemegang pelanggan.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('kinerja-sales.pdf', request()->query()) }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('kinerja-sales.excel', request()->query()) }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
        </div>

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Dari</span></label>
                    <input type="date" name="dari" class="input input-bordered input-sm"
                        value="{{ request('dari', $laporan['dari']->toDateString()) }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Sampai</span></label>
                    <input type="date" name="sampai" class="input input-bordered input-sm"
                        value="{{ request('sampai', $laporan['sampai']->toDateString()) }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                <a href="{{ route('kinerja-sales.index') }}" class="btn btn-ghost btn-sm">Bulan ini</a>
            </form>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Nilai Pesanan', $laporan['total']['nilai_pesanan']],
                ['Penjualan (DPP)', $laporan['total']['penjualan']],
                ['Penjualan Bersih', $laporan['total']['penjualan_bersih']],
                ['Piutang Beredar', $laporan['total']['piutang_beredar']],
            ] as [$label, $nilai])
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <div class="text-xs uppercase text-base-content/60">{{ $label }}</div>
                    <div class="mt-1 text-lg font-bold">Rp {{ number_format($nilai, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Sales</th>
                            <th class="text-end">Pesanan</th>
                            <th class="text-end">Nilai Pesanan</th>
                            <th class="text-end">Faktur</th>
                            <th class="text-end">Penjualan (DPP)</th>
                            <th class="text-end">Retur</th>
                            <th class="text-end">Penjualan Bersih</th>
                            <th class="text-end">Piutang Beredar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporan['baris'] as $baris)
                            <tr>
                                <td class="font-medium">
                                    {{ $baris['sales'] }}
                                    @if ($baris['sales'] === \App\Services\KinerjaSalesService::TANPA_SALES)
                                        <span class="badge badge-ghost badge-xs">belum ditandai</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $baris['jumlah_pesanan'] }}</td>
                                <td class="text-end">{{ number_format($baris['nilai_pesanan'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ $baris['jumlah_faktur'] }}</td>
                                <td class="text-end">{{ number_format($baris['penjualan'], 0, ',', '.') }}</td>
                                <td class="text-end {{ $baris['retur'] > 0 ? 'text-error' : '' }}">
                                    {{ number_format($baris['retur'], 0, ',', '.') }}
                                </td>
                                <td class="text-end font-semibold">{{ number_format($baris['penjualan_bersih'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($baris['piutang_beredar'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-base-content/50">
                                    Belum ada pesanan atau faktur penjualan pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($laporan['baris']->isNotEmpty())
                        <tfoot>
                            <tr class="font-semibold">
                                <td>Total</td>
                                <td class="text-end">{{ $laporan['total']['jumlah_pesanan'] }}</td>
                                <td class="text-end">{{ number_format($laporan['total']['nilai_pesanan'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ $laporan['total']['jumlah_faktur'] }}</td>
                                <td class="text-end">{{ number_format($laporan['total']['penjualan'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($laporan['total']['retur'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($laporan['total']['penjualan_bersih'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($laporan['total']['piutang_beredar'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
