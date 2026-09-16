@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Laporan Perubahan Ekuitas</h3>
                <p class="text-base-content/60">Mutasi setiap komponen ekuitas dari saldo awal ke saldo akhir, disusun dari jurnal yang sudah diposting.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('financial-statements.perubahan-ekuitas.pdf', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('financial-statements.perubahan-ekuitas.excel', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
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
                $rupiah = fn ($nilai) => 'Rp ' . number_format($nilai, 0, ',', '.');
            @endphp

            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Komponen Ekuitas</th>
                            <th class="text-end">Saldo Awal</th>
                            <th class="text-end">Penambahan</th>
                            <th class="text-end">Pengurangan</th>
                            <th class="text-end">Laba (Rugi) Periode</th>
                            <th class="text-end">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['komponen'] as $row)
                            <tr>
                                <td>
                                    {{ $row['nama'] }}
                                    @unless ($row['account'])
                                        <span class="badge badge-ghost badge-sm">akumulasi laba rugi</span>
                                    @endunless
                                </td>
                                <td class="text-end {{ $row['saldo_awal'] < 0 ? 'text-error' : '' }}">{{ $rupiah($row['saldo_awal']) }}</td>
                                <td class="text-end">{{ $row['setoran'] != 0 ? $rupiah($row['setoran']) : '—' }}</td>
                                <td class="text-end">{{ $row['penarikan'] != 0 ? '(' . $rupiah($row['penarikan']) . ')' : '—' }}</td>
                                <td class="text-end {{ $row['laba_bersih'] < 0 ? 'text-error' : '' }}">{{ $row['laba_bersih'] != 0 ? $rupiah($row['laba_bersih']) : '—' }}</td>
                                <td class="text-end font-semibold {{ $row['saldo_akhir'] < 0 ? 'text-error' : '' }}">{{ $rupiah($row['saldo_akhir']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-3 text-center text-base-content/50">Belum ada mutasi ekuitas pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-base-200/40 font-bold">
                            <td>Total Ekuitas</td>
                            <td class="text-end {{ $data['total_saldo_awal'] < 0 ? 'text-error' : '' }}">{{ $rupiah($data['total_saldo_awal']) }}</td>
                            <td class="text-end">{{ $rupiah($data['total_setoran']) }}</td>
                            <td class="text-end">{{ $rupiah($data['total_penarikan']) }}</td>
                            <td class="text-end {{ $data['total_laba_bersih'] < 0 ? 'text-error' : '' }}">{{ $rupiah($data['total_laba_bersih']) }}</td>
                            <td class="text-end {{ $data['total_saldo_akhir'] < 0 ? 'text-error' : '' }}">{{ $rupiah($data['total_saldo_akhir']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mx-4 mb-4 overflow-x-auto rounded-lg bg-base-200 p-4">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td>Ekuitas akhir menurut laporan ini</td>
                            <td class="text-end font-bold">{{ $rupiah($data['total_saldo_akhir']) }}</td>
                        </tr>
                        <tr>
                            <td>Ekuitas akhir menurut neraca</td>
                            <td class="text-end">{{ $rupiah($data['saldo_akhir_buku']) }}</td>
                        </tr>
                        @if (abs($data['selisih']) >= 0.01)
                            <tr class="text-error font-bold">
                                <td>Selisih (laporan tidak cocok dengan neraca)</td>
                                <td class="text-end">{{ $rupiah($data['selisih']) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
