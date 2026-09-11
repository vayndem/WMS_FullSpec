@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Rekonsiliasi Fiskal</h3>
                <p class="text-base-content/60">Laba komersial menurut pembukuan, dikoreksi menjadi laba fiskal sesuai klasifikasi tiap akun.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('financial-statements.rekonsiliasi-fiskal.pdf', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('financial-statements.rekonsiliasi-fiskal.excel', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
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

            <div class="p-4">
                <div class="mb-4 flex items-center justify-between rounded-lg bg-base-200 p-4">
                    <span class="font-bold">Laba (Rugi) Komersial Sebelum Pajak</span>
                    <span class="text-lg font-bold {{ $data['laba_komersial'] < 0 ? 'text-error' : '' }}">
                        Rp {{ number_format($data['laba_komersial'], 0, ',', '.') }}
                    </span>
                </div>

                @foreach ([['beda_tetap_positif', 'Koreksi Positif — Beda Tetap', 'text-success', 'total_koreksi_positif'], ['beda_tetap_negatif', 'Koreksi Negatif — Beda Tetap', 'text-error', 'total_koreksi_negatif']] as [$kunci, $judul, $warna, $totalKunci])
                    <h6 class="mb-2 font-bold {{ $warna }}">{{ $judul }}</h6>
                    <div class="mb-4 overflow-x-auto">
                        <table class="table table-sm">
                            <tbody>
                                @forelse ($data[$kunci] as $row)
                                    <tr>
                                        <td class="w-24 font-bold">{{ $row['kode_akun'] }}</td>
                                        <td>{{ $row['nama_akun'] }}</td>
                                        <td class="text-end">Rp {{ number_format($row['koreksi'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-3 text-center text-base-content/50">Tidak ada koreksi.</td></tr>
                                @endforelse
                                <tr class="bg-base-200/40 font-bold">
                                    <td colspan="2" class="text-end">Subtotal</td>
                                    <td class="text-end">Rp {{ number_format($data[$totalKunci], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <h6 class="mb-2 font-bold text-warning">Beda Waktu</h6>
                <div class="mb-2 overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th class="w-24">Kode</th><th>Akun</th><th class="text-end">Komersial</th><th class="text-end">Fiskal</th><th class="text-end">Koreksi</th></tr></thead>
                        <tbody>
                            @forelse ($data['beda_waktu'] as $row)
                                <tr>
                                    <td class="font-bold">{{ $row['kode_akun'] }}</td>
                                    <td>
                                        {{ $row['nama_akun'] }}
                                        @if ($row['sumber_koreksi'] === 'MENUNGGU_JADWAL_FISKAL')
                                            <span class="badge badge-warning badge-sm">belum ada dasar fiskal</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        {{ $row['nilai_fiskal'] === null ? '-' : 'Rp ' . number_format($row['nilai_fiskal'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end {{ $row['koreksi'] < 0 ? 'text-error' : '' }}">
                                        Rp {{ number_format($row['koreksi'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-3 text-center text-base-content/50">Tidak ada akun beda waktu.</td></tr>
                            @endforelse
                            <tr class="bg-base-200/40 font-bold">
                                <td colspan="4" class="text-end">Total Koreksi Beda Waktu</td>
                                <td class="text-end {{ $data['total_koreksi_beda_waktu'] < 0 ? 'text-error' : '' }}">
                                    Rp {{ number_format($data['total_koreksi_beda_waktu'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if ($data['beda_waktu']->contains('sumber_koreksi', 'MENUNGGU_JADWAL_FISKAL'))
                    <div role="alert" class="alert alert-warning mb-4">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Akun bertanda "belum ada dasar fiskal" tidak dikoreksi karena belum punya perhitungan fiskal pembanding. Nilai komersialnya hanya rujukan.</span>
                    </div>
                @endif

                <div class="flex items-center justify-between rounded-lg bg-primary/10 p-4">
                    <span class="font-bold">Laba (Rugi) Fiskal</span>
                    <span class="text-xl font-bold {{ $data['laba_fiskal'] < 0 ? 'text-error' : 'text-primary' }}">
                        Rp {{ number_format($data['laba_fiskal'], 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
