@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Laporan Keuangan</h3>
                <p class="text-base-content/60">Neraca Saldo, Buku Besar, Laba Rugi, dan Neraca berdasarkan jurnal yang sudah diposting.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('financial-statements.neraca.pdf', ['as_of' => $asOf->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('financial-statements.neraca.excel', ['as_of' => $asOf->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
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

            <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-2">
                <div>
                    <h6 class="mb-2 font-bold text-primary">Aset</h6>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <tbody>
                                @forelse ($data['aset'] as $row)
                                    <tr>
                                        <td class="w-24 font-bold">{{ $row['account']->kode_akun }}</td>
                                        <td>{{ $row['account']->nama_akun }}</td>
                                        <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-3 text-center text-base-content/50">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                                <tr class="bg-base-200/40 font-bold">
                                    <td colspan="2" class="text-end">Total Aset</td>
                                    <td class="text-end">Rp {{ number_format($data['total_aset'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h6 class="mb-2 font-bold text-error">Liabilitas</h6>
                    <div class="overflow-x-auto">
                        <table class="table table-sm mb-4">
                            <tbody>
                                @forelse ($data['liabilitas'] as $row)
                                    <tr>
                                        <td class="w-24 font-bold">{{ $row['account']->kode_akun }}</td>
                                        <td>{{ $row['account']->nama_akun }}</td>
                                        <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-3 text-center text-base-content/50">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                                <tr class="bg-base-200/40 font-bold">
                                    <td colspan="2" class="text-end">Total Liabilitas</td>
                                    <td class="text-end">Rp {{ number_format($data['total_liabilitas'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2 font-bold text-info">Ekuitas</h6>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <tbody>
                                @foreach ($data['ekuitas'] as $row)
                                    <tr>
                                        <td class="w-24 font-bold">{{ $row['account']->kode_akun }}</td>
                                        <td>{{ $row['account']->nama_akun }}</td>
                                        <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="w-24">-</td>
                                    <td>
                                        Laba Ditahan Berjalan
                                        <span class="tooltip" data-tip="Akumulasi laba/rugi berjalan sejak sistem digunakan, karena belum ada proses tutup buku formal.">
                                            <i class="fa-solid fa-circle-info text-base-content/40"></i>
                                        </span>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($data['laba_ditahan_berjalan'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-base-200/40 font-bold">
                                    <td colspan="2" class="text-end">Total Ekuitas</td>
                                    <td class="text-end">Rp {{ number_format($data['total_ekuitas'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mx-4 mb-4 flex items-center justify-between rounded-lg bg-base-200 p-4">
                <span class="font-bold">Total Liabilitas + Ekuitas</span>
                <span class="text-lg font-bold">Rp {{ number_format($data['total_liabilitas'] + $data['total_ekuitas'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
@endsection
