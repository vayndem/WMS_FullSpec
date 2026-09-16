@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">PPh Badan &amp; Pajak Tangguhan</h3>
            <p class="text-base-content/60">Tarif 22% dengan fasilitas Pasal 31E, dan pajak tangguhan PSAK 46 dari selisih nilai buku komersial terhadap nilai buku fiskal.</p>
        </div>

        @include('financial_statements._tabs')
        @include('warehouse_partials.alerts')

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Tahun Pajak</span></label>
                    <input type="number" name="tahun_pajak" value="{{ $tahun }}" class="input input-bordered input-sm w-32">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Peredaran Bruto Setahun</span></label>
                    <input type="number" step="any" min="0" name="peredaran_bruto" value="{{ $peredaranBruto ?: '' }}" class="input input-bordered input-sm w-64" placeholder="contoh: 10000000000">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Hitung</button>
            </form>

            <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-2">
                <div>
                    <h6 class="mb-2 font-bold text-primary">Menuju Penghasilan Kena Pajak</h6>
                    <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <tbody>
                            @foreach ([
                                ['Laba (rugi) komersial', $data['laba_komersial']],
                                ['Koreksi positif — beda tetap', $data['koreksi_positif']],
                                ['Koreksi negatif — beda tetap', -$data['koreksi_negatif']],
                                ['Koreksi beda waktu', $data['koreksi_beda_waktu']],
                                ['Laba (rugi) fiskal', $data['laba_fiskal']],
                            ] as [$label, $nilai])
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end {{ $nilai < 0 ? 'text-error' : '' }}">Rp {{ number_format($nilai, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-base-200/40 font-bold">
                                <td>Penghasilan Kena Pajak <span class="text-xs font-normal text-base-content/50">(dibulatkan ke bawah ribuan penuh)</span></td>
                                <td class="text-end">Rp {{ number_format($data['penghasilan_kena_pajak'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <div>
                    <h6 class="mb-2 font-bold text-warning">PPh Terutang</h6>
                    <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td>Bagian PKP dapat fasilitas Pasal 31E <span class="text-xs text-base-content/50">(tarif 11%)</span></td>
                                <td class="text-end">Rp {{ number_format($data['pkp_fasilitas'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Bagian PKP tarif penuh <span class="text-xs text-base-content/50">(22%)</span></td>
                                <td class="text-end">Rp {{ number_format($data['pkp_normal'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-base-200/40 font-bold">
                                <td>PPh Badan terutang</td>
                                <td class="text-end">Rp {{ number_format($data['pph_terutang'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>

                    <h6 class="mb-2 mt-4 font-bold text-info">Pajak Tangguhan (PSAK 46)</h6>
                    <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td>Nilai buku komersial dikurangi nilai buku fiskal</td>
                                <td class="text-end {{ $data['beda_waktu_kumulatif'] < 0 ? 'text-error' : '' }}">Rp {{ number_format($data['beda_waktu_kumulatif'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Posisi pajak tangguhan seharusnya <span class="text-xs text-base-content/50">(+ liabilitas / − aset)</span></td>
                                <td class="text-end">Rp {{ number_format($data['pajak_tangguhan_seharusnya'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Sudah tercatat di buku besar</td>
                                <td class="text-end">Rp {{ number_format($data['pajak_tangguhan_tercatat'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-base-200/40 font-bold">
                                <td>Perlu dijurnal</td>
                                <td class="text-end">Rp {{ number_format($data['gerakan_pajak_tangguhan'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="border-t border-base-300 p-4">
                @if ($tersimpan)
                    <div role="alert" class="alert alert-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Tahun {{ $tahun }} sudah diposting lewat jurnal <strong>{{ $tersimpan->jurnal?->no_jurnal ?? '-' }}</strong>. Perhitungan di atas hanya simulasi ulang.</span>
                    </div>
                @elseif ($peredaranBruto <= 0)
                    <div role="alert" class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Isi peredaran bruto setahun dulu — angka itu menentukan porsi fasilitas Pasal 31E.</span>
                    </div>
                @else
                    <form method="POST" action="{{ route('financial-statements.pajak-penghasilan.posting') }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="tahun_pajak" value="{{ $tahun }}">
                        <input type="hidden" name="peredaran_bruto" value="{{ $peredaranBruto }}">
                        <div class="form-control">
                            <label class="label"><span class="label-text text-xs font-semibold uppercase">Tanggal Posting Jurnal</span></label>
                            <input type="date" name="posting_date" value="{{ today()->format('Y-m-d') }}" class="input input-bordered input-sm" required>
                        </div>
                        <button class="btn btn-primary btn-sm"><i class="fa-solid fa-file-invoice-dollar"></i> Posting Jurnal PPh &amp; Pajak Tangguhan</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($riwayat->isNotEmpty())
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Riwayat Perhitungan</h5></div>
                <div class="overflow-x-auto">
                    <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Tahun</th><th class="text-end">Laba Fiskal</th><th class="text-end">PKP</th><th class="text-end">PPh Terutang</th><th class="text-end">Pajak Tangguhan</th><th>Jurnal</th></tr></thead>
                        <tbody>
                            @foreach ($riwayat as $row)
                                <tr>
                                    <td class="font-bold">{{ $row->tahun_pajak }}</td>
                                    <td class="text-end">Rp {{ number_format($row->laba_fiskal, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row->penghasilan_kena_pajak, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row->pph_terutang, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row->gerakan_pajak_tangguhan, 0, ',', '.') }}</td>
                                    <td>{{ $row->jurnal?->no_jurnal ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
