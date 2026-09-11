@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Laporan Arus Kas</h3>
                <p class="text-base-content/60">Metode langsung, disusun dari mutasi akun kas/bank pada jurnal yang sudah diposting.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('financial-statements.arus-kas.pdf', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('financial-statements.arus-kas.excel', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
        </div>

        @include('financial_statements._tabs')

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Dari</span></label>
                    <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Sampai</span></label>
                    <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            @php
                $judul = [
                    'OPERASI' => ['Aktivitas Operasi', 'text-primary'],
                    'INVESTASI' => ['Aktivitas Investasi', 'text-warning'],
                    'PENDANAAN' => ['Aktivitas Pendanaan', 'text-info'],
                ];
            @endphp

            <div class="p-4">
                @foreach ($data['sections'] as $section)
                    @php([$nama, $warna] = $judul[$section['kelompok']])
                    <h6 class="mb-2 font-bold {{ $warna }}">{{ $nama }}</h6>
                    <div class="mb-4 overflow-x-auto">
                        <table class="table table-sm">
                            <tbody>
                                @forelse ($section['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['account']?->nama_akun ?? 'Tanpa akun lawan' }}</td>
                                        <td class="text-end {{ $row['amount'] < 0 ? 'text-error' : '' }}">
                                            Rp {{ number_format($row['amount'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-3 text-center text-base-content/50">Tidak ada arus kas pada kelompok ini.</td>
                                    </tr>
                                @endforelse
                                <tr class="bg-base-200/40 font-bold">
                                    <td class="text-end">Subtotal {{ $nama }}</td>
                                    <td class="text-end {{ $section['subtotal'] < 0 ? 'text-error' : '' }}">
                                        Rp {{ number_format($section['subtotal'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

            <div class="mx-4 mb-4 overflow-x-auto rounded-lg bg-base-200 p-4">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td>Saldo kas awal</td>
                            <td class="text-end">Rp {{ number_format($data['saldo_awal'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Kenaikan (penurunan) kas bersih</td>
                            <td class="text-end {{ $data['arus_bersih'] < 0 ? 'text-error' : '' }}">
                                Rp {{ number_format($data['arus_bersih'], 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="font-bold">
                            <td>Saldo kas akhir</td>
                            <td class="text-end">Rp {{ number_format($data['saldo_akhir'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Saldo kas akhir menurut buku besar</td>
                            <td class="text-end">Rp {{ number_format($data['saldo_akhir_buku'], 0, ',', '.') }}</td>
                        </tr>
                        @if (abs($data['selisih']) >= 0.01)
                            <tr class="text-error font-bold">
                                <td>Selisih (laporan tidak cocok dengan buku besar)</td>
                                <td class="text-end">Rp {{ number_format($data['selisih'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
