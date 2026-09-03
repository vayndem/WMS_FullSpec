@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Laporan Keuangan</h3>
                <p class="text-base-content/60">Neraca Saldo, Buku Besar, Laba Rugi, dan Neraca berdasarkan jurnal yang sudah diposting.</p>
            </div>
            <a href="{{ route('financial-statements.neraca-saldo.pdf', ['as_of' => $asOf->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
        </div>

        @include('financial_statements._tabs')

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Per Tanggal</span></label>
                    <input type="date" name="as_of" value="{{ $asOf->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['rows'] as $row)
                            <tr>
                                <td class="font-bold text-primary">{{ $row['account']->kode_akun }}</td>
                                <td>{{ $row['account']->nama_akun }}</td>
                                <td class="text-end">{{ $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-base-content/50">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td colspan="2" class="text-end">Total</td>
                            <td class="text-end">Rp {{ number_format($data['total_debit'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($data['total_kredit'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
