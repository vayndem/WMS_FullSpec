@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Umur Piutang</h3>
                <p class="text-base-content/60">
                    Sisa tagihan pelanggan dikelompokkan menurut lama lewat jatuh tempo, per
                    {{ $laporan['per_tanggal']->translatedFormat('d F Y') }}.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('piutang-aging.pdf', request()->query()) }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('piutang-aging.excel', request()->query()) }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
        </div>

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Per Tanggal</span></label>
                    <input type="date" name="per_tanggal" class="input input-bordered input-sm"
                        value="{{ request('per_tanggal', $laporan['per_tanggal']->toDateString()) }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Pelanggan</span></label>
                    <select name="pelanggan_id" class="select select-bordered select-sm" data-app-picker>
                        <option value="">Semua pelanggan</option>
                        @foreach ($pelanggans as $item)
                            <option value="{{ $item->id }}" @selected($pelangganId === $item->id)>{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                <a href="{{ route('piutang-aging.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </form>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($ember as $nama)
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <div class="text-xs uppercase text-base-content/60">{{ $nama }}</div>
                    <div class="mt-1 text-lg font-bold {{ $nama !== \App\Services\PiutangAgingService::BELUM_JATUH_TEMPO && $laporan['total'][$nama] > 0 ? 'text-error' : '' }}">
                        Rp {{ number_format($laporan['total'][$nama], 0, ',', '.') }}
                    </div>
                </div>
            @endforeach
        </div>

        @if ($laporan['tie_out']['tersedia'])
            @php
                $selisih = $laporan['tie_out']['selisih'];
            @endphp
            <div class="alert {{ abs($selisih) > 0.005 ? 'alert-error' : 'alert-success' }} mb-4">
                <i class="fa-solid {{ abs($selisih) > 0.005 ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
                <span>
                    Subledger Rp {{ number_format($laporan['tie_out']['subledger'], 0, ',', '.') }}
                    vs saldo buku besar Piutang Usaha Rp {{ number_format($laporan['tie_out']['saldo_gl'], 0, ',', '.') }}
                    &mdash; selisih Rp {{ number_format($selisih, 0, ',', '.') }}.
                </span>
            </div>
        @else
            <div class="alert alert-warning mb-4">
                <i class="fa-solid fa-circle-info"></i>
                <span>{{ $laporan['tie_out']['alasan'] }}</span>
            </div>
        @endif

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h4 class="font-semibold">Ringkasan per Pelanggan</h4>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th class="text-end">Faktur</th>
                            @foreach ($ember as $nama)
                                <th class="text-end">{{ $nama }}</th>
                            @endforeach
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporan['per_pelanggan'] as $baris)
                            <tr>
                                <td class="font-medium">{{ $baris['pelanggan'] }}</td>
                                <td class="text-end">{{ $baris['jumlah_faktur'] }}</td>
                                @foreach ($ember as $nama)
                                    <td class="text-end">{{ number_format($baris['ember'][$nama], 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end font-semibold">{{ number_format($baris['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($ember) + 3 }}" class="py-6 text-center text-base-content/50">
                                    Tidak ada piutang beredar pada tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($laporan['per_pelanggan']->isNotEmpty())
                        <tfoot>
                            <tr class="font-semibold">
                                <td>Total</td>
                                <td class="text-end">{{ $laporan['baris']->count() }}</td>
                                @foreach ($ember as $nama)
                                    <td class="text-end">{{ number_format($laporan['total'][$nama], 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end">{{ number_format($laporan['total_piutang'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h4 class="font-semibold">Rincian Faktur</h4>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Faktur</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-end">Hari Lewat</th>
                            <th>Umur</th>
                            <th class="text-end">Tagihan</th>
                            <th class="text-end">Dibayar</th>
                            <th class="text-end">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporan['baris'] as $baris)
                            <tr>
                                <td>
                                    <a href="{{ route('faktur-penjualan.show', $baris['faktur_id']) }}"
                                        class="link link-primary font-mono text-xs">{{ $baris['nomor'] }}</a>
                                </td>
                                <td>{{ $baris['pelanggan'] }}</td>
                                <td>{{ $baris['tanggal']?->format('d-m-Y') }}</td>
                                <td>{{ $baris['jatuh_tempo']?->format('d-m-Y') ?? '-' }}</td>
                                <td class="text-end">{{ $baris['hari_lewat'] > 0 ? $baris['hari_lewat'] : '-' }}</td>
                                <td>
                                    <span class="badge badge-sm {{ $baris['ember'] === \App\Services\PiutangAgingService::BELUM_JATUH_TEMPO ? 'badge-ghost' : 'badge-error' }}">
                                        {{ $baris['ember'] }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($baris['tagihan'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($baris['dibayar'], 0, ',', '.') }}</td>
                                <td class="text-end font-semibold">{{ number_format($baris['sisa'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-base-content/50">
                                    Belum ada faktur penjualan yang masih menyisakan tagihan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
