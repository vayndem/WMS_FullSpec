@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Kartu Skor Supplier</h3>
                <p class="text-base-content/60">Kecepatan kirim, nilai belanja, dan kualitas barang per supplier, dihitung dari penerimaan yang sudah diposting.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('supplier-scorecard.pdf', ['from' => $data['from']->format('Y-m-d'), 'to' => $data['to']->format('Y-m-d'), 'target_lead_time' => $data['target_lead_time']]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('supplier-scorecard.excel', ['from' => $data['from']->format('Y-m-d'), 'to' => $data['to']->format('Y-m-d'), 'target_lead_time' => $data['target_lead_time']]) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Dari</span></label>
                    <input type="date" name="from" value="{{ $data['from']->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Sampai</span></label>
                    <input type="date" name="to" value="{{ $data['to']->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Target Lead Time (hari)</span></label>
                    <input type="number" name="target_lead_time" min="1" value="{{ $data['target_lead_time'] }}" class="input input-bordered input-sm w-32">
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
                            <th>Supplier</th>
                            <th class="text-end">Penerimaan</th>
                            <th class="text-end">Lead Time Rata</th>
                            <th class="text-end">Tercepat &ndash; Terlama</th>
                            <th class="text-end">Dalam Target</th>
                            <th class="text-end">Nilai Pembelian</th>
                            <th class="text-end">Nilai Retur</th>
                            <th class="text-end">Rasio Retur</th>
                            <th class="text-end">Rasio Reject QC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['baris'] as $row)
                            <tr>
                                <td class="font-medium">{{ $row['supplier'] }}</td>
                                <td class="text-end">{{ $row['penerimaan'] }}</td>
                                <td class="text-end">{{ $row['lead_time_rata'] === null ? '-' : number_format($row['lead_time_rata'], 1, ',', '.') . ' hari' }}</td>
                                <td class="text-end">{{ $row['lead_time_tercepat'] === null ? '-' : $row['lead_time_tercepat'] . ' &ndash; ' . $row['lead_time_terlama'] }}</td>
                                <td class="text-end">
                                    @if ($row['ketepatan'] === null)
                                        <span class="text-base-content/40">-</span>
                                    @else
                                        <span class="badge {{ $row['ketepatan'] >= 90 ? 'badge-success' : ($row['ketepatan'] >= 70 ? 'badge-warning' : 'badge-error') }}">{{ number_format($row['ketepatan'], 1, ',', '.') }}%</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $rupiah($row['nilai_pembelian']) }}</td>
                                <td class="text-end">{{ $rupiah($row['nilai_retur']) }}</td>
                                <td class="text-end {{ $row['rasio_retur'] > 0 ? 'text-error' : '' }}">{{ $row['rasio_retur'] === null ? '-' : number_format($row['rasio_retur'], 2, ',', '.') . '%' }}</td>
                                <td class="text-end">
                                    @if ($row['rasio_reject'] === null)
                                        <span class="badge badge-ghost badge-sm">belum ada data</span>
                                    @else
                                        <span class="{{ $row['rasio_reject'] > 0 ? 'text-error' : '' }}">{{ number_format($row['rasio_reject'], 2, ',', '.') }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-3 text-center text-base-content/50">Belum ada penerimaan yang diposting pada rentang ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-base-200/40 font-bold">
                            <td>TOTAL</td>
                            <td class="text-end">{{ $data['total_penerimaan'] }}</td>
                            <td colspan="3"></td>
                            <td class="text-end">{{ $rupiah($data['total_nilai_pembelian']) }}</td>
                            <td class="text-end">{{ $rupiah($data['total_nilai_retur']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mx-4 mb-4 space-y-2">
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    <span><strong>Lead time diukur dari tanggal PO ke tanggal penerimaan</strong>, bukan dari tanggal janji kirim: sistem tidak menyimpan tanggal janji supplier. Kolom "Dalam Target" memakai angka target yang kamu isi sendiri di atas, jadi ubah targetnya kalau standar kamu berbeda.</span>
                </div>
                @unless ($data['ada_data_qc'])
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Rasio reject QC masih kosong karena belum ada penerimaan yang melewati Pemeriksaan Kualitas. Angkanya akan terisi sendiri begitu operator mulai menjalankan QC; sampai saat itu pakai <strong>rasio retur</strong> sebagai penanda kualitas.</span>
                    </div>
                @endunless
            </div>
        </div>
    </div>
@endsection
