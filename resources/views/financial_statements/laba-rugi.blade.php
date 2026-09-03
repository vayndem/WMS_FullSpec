@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Laporan Keuangan</h3>
                <p class="text-base-content/60">Neraca Saldo, Buku Besar, Laba Rugi, dan Neraca berdasarkan jurnal yang sudah diposting.</p>
            </div>
            <a href="{{ route('financial-statements.laba-rugi.pdf', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
        </div>

        @include('financial_statements._tabs')

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Dari Tanggal</span></label>
                    <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Sampai Tanggal</span></label>
                    <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            <div class="p-4">
                <h6 class="mb-2 font-bold text-primary">Pendapatan</h6>
                <div class="overflow-x-auto">
                    <table class="table table-sm mb-4">
                        <tbody>
                            @forelse ($data['pendapatan'] as $row)
                                <tr>
                                    <td class="w-24 font-bold">{{ $row['account']->kode_akun }}</td>
                                    <td>{{ $row['account']->nama_akun }}</td>
                                    <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-3 text-center text-base-content/50">Tidak ada pendapatan pada periode ini.</td>
                                </tr>
                            @endforelse
                            <tr class="bg-base-200/40 font-bold">
                                <td colspan="2" class="text-end">Total Pendapatan</td>
                                <td class="text-end">Rp {{ number_format($data['total_pendapatan'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="mb-2 font-bold text-error">Beban</h6>
                <div class="overflow-x-auto">
                    <table class="table table-sm mb-4">
                        <tbody>
                            @forelse ($data['beban'] as $row)
                                <tr>
                                    <td class="w-24 font-bold">{{ $row['account']->kode_akun }}</td>
                                    <td>{{ $row['account']->nama_akun }}</td>
                                    <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-3 text-center text-base-content/50">Tidak ada beban pada periode ini.</td>
                                </tr>
                            @endforelse
                            <tr class="bg-base-200/40 font-bold">
                                <td colspan="2" class="text-end">Total Beban</td>
                                <td class="text-end">Rp {{ number_format($data['total_beban'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between rounded-lg {{ $data['laba_bersih'] >= 0 ? 'bg-success/10' : 'bg-error/10' }} p-4">
                    <span class="font-bold">Laba (Rugi) Bersih</span>
                    <span class="text-lg font-bold {{ $data['laba_bersih'] >= 0 ? 'text-success' : 'text-error' }}">Rp {{ number_format($data['laba_bersih'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
