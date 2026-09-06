@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Laporan Keuangan</h3>
                <p class="text-base-content/60">Neraca Saldo, Buku Besar, Laba Rugi, dan Neraca berdasarkan jurnal yang sudah diposting.</p>
            </div>
            @if ($account)
                <div class="flex gap-2">
                    <a href="{{ route('financial-statements.buku-besar.pdf', ['coa_id' => $account->id, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('financial-statements.buku-besar.excel', ['coa_id' => $account->id, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-file-excel"></i> Excel
                    </a>
                </div>
            @endif
        </div>

        @include('financial_statements._tabs')

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="grid grid-cols-1 gap-3 border-b border-base-300 p-4 md:grid-cols-4">
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Akun</span></label>
                    <select name="coa_id" class="select select-bordered select-sm" data-app-picker data-placeholder="Cari kode atau nama akun...">
                        <option value="">-- Pilih Akun --</option>
                        @foreach ($accounts as $coa)
                            <option value="{{ $coa->id }}" @selected($account && $account->id === $coa->id)>{{ $coa->kode_akun }} - {{ $coa->nama_akun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Dari Tanggal</span></label>
                    <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold text-xs uppercase">Sampai Tanggal</span></label>
                    <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                </div>
            </form>

            @if (!$account)
                <div class="p-6 text-center text-base-content/50">Pilih akun terlebih dahulu untuk melihat Buku Besar.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No Jurnal</th>
                                <th>Keterangan</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-base-200/40">
                                <td colspan="5" class="font-semibold">Saldo Awal</td>
                                <td class="text-end font-semibold">Rp {{ number_format($data['opening_balance'], 0, ',', '.') }}</td>
                            </tr>
                            @forelse ($data['rows'] as $row)
                                <tr>
                                    <td>{{ $row['tanggal']->format('d-m-Y') }}</td>
                                    <td class="font-bold text-primary">{{ $row['no_jurnal'] }}</td>
                                    <td>{{ $row['keterangan'] ?: '-' }}</td>
                                    <td class="text-end">{{ $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-base-content/50">Tidak ada mutasi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="font-bold">
                                <td colspan="5" class="text-end">Saldo Akhir</td>
                                <td class="text-end">Rp {{ number_format($data['closing_balance'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
