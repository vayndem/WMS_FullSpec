@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Varians Pemakaian Material</h3>
                <p class="text-base-content/60">
                    Pemakaian aktual perintah kerja dibandingkan dengan BOM aktif bahan hasilnya.
                </p>
            </div>
            @if ($pesanan)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('varians-pemakaian.pdf', request()->query()) }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('varians-pemakaian.excel', request()->query()) }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-file-excel"></i> Excel
                    </a>
                </div>
            @endif
        </div>

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Perintah Kerja</span></label>
                    <select name="pesanan" class="select select-bordered select-sm" data-app-picker>
                        <option value="">Pilih perintah kerja</option>
                        @foreach ($pilihan as $item)
                            <option value="{{ $item->id }}" @selected($pesanan && $pesanan->id === $item->id)>
                                {{ $item->nomor }} &middot; {{ $item->bahanHasil?->nama }} &middot; {{ $item->status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>
        </div>

        @if ($pesanan && $varians)
            @if (!$varians['bom'])
                <div class="alert alert-warning mb-4">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Bahan hasil perintah kerja ini belum punya BOM aktif, jadi hanya pemakaian aktual yang bisa ditampilkan.
                        Tidak ada standar untuk dibandingkan.
                    </span>
                </div>
            @else
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <div class="text-xs uppercase text-base-content/60">BOM</div>
                        <div class="mt-1 font-bold">{{ $varians['bom']->kode }}</div>
                    </div>
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <div class="text-xs uppercase text-base-content/60">Basis Perhitungan</div>
                        <div class="mt-1 font-bold">{{ rtrim(rtrim(number_format($varians['basis'], 4, ',', '.'), '0'), ',') }}</div>
                    </div>
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <div class="text-xs uppercase text-base-content/60">Nilai Standar</div>
                        <div class="mt-1 font-bold">Rp {{ number_format($varians['total']['nilai_standar'], 0, ',', '.') }}</div>
                    </div>
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <div class="text-xs uppercase text-base-content/60">Selisih Nilai</div>
                        <div class="mt-1 font-bold {{ $varians['total']['selisih_nilai'] > 0 ? 'text-error' : 'text-success' }}">
                            Rp {{ number_format($varians['total']['selisih_nilai'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h4 class="font-semibold">{{ $pesanan->nomor }} &middot; {{ $pesanan->bahanHasil?->nama }}</h4>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Bahan</th>
                                <th class="text-end">Standar</th>
                                <th class="text-end">Aktual</th>
                                <th class="text-end">Selisih</th>
                                <th class="text-end">Harga Acuan</th>
                                <th class="text-end">Nilai Standar</th>
                                <th class="text-end">Nilai Aktual</th>
                                <th class="text-end">Selisih Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($varians['baris'] as $baris)
                                <tr>
                                    <td>{{ $baris['bahan'] }}</td>
                                    <td class="text-end">{{ $baris['standar'] === null ? '-' : rtrim(rtrim(number_format($baris['standar'], 4, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($baris['aktual'], 4, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">{{ $baris['selisih'] === null ? '-' : rtrim(rtrim(number_format($baris['selisih'], 4, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">{{ $baris['harga_acuan'] === null ? '-' : number_format($baris['harga_acuan'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ $baris['nilai_standar'] === null ? '-' : number_format($baris['nilai_standar'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($baris['nilai_aktual'], 0, ',', '.') }}</td>
                                    <td class="text-end {{ ($baris['selisih_nilai'] ?? 0) > 0 ? 'text-error' : '' }}">
                                        {{ $baris['selisih_nilai'] === null ? '-' : number_format($baris['selisih_nilai'], 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="badge badge-sm {{ $baris['status'] === 'BOROS' ? 'badge-error' : ($baris['status'] === 'SESUAI' ? 'badge-success' : 'badge-ghost') }}">
                                            {{ $baris['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="py-6 text-center text-base-content/50">Perintah kerja ini belum menyerap pemakaian material.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h4 class="font-semibold">Ringkasan Perintah Kerja Terakhir</h4>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Perintah Kerja</th>
                            <th>Bahan Hasil</th>
                            <th>Status</th>
                            <th>BOM</th>
                            <th class="text-end">Nilai Standar</th>
                            <th class="text-end">Nilai Aktual</th>
                            <th class="text-end">Selisih</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ringkasan as $baris)
                            <tr>
                                <td class="font-mono text-xs">{{ $baris['nomor'] }}</td>
                                <td>{{ $baris['bahan_hasil'] }}</td>
                                <td><span class="badge badge-ghost badge-sm">{{ $baris['status'] }}</span></td>
                                <td>
                                    @if ($baris['punya_bom'])
                                        <span class="badge badge-success badge-sm">Ada</span>
                                    @else
                                        <span class="badge badge-warning badge-sm">Belum ada</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($baris['nilai_standar'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($baris['nilai_aktual'], 0, ',', '.') }}</td>
                                <td class="text-end {{ $baris['punya_bom'] && $baris['selisih_nilai'] > 0 ? 'text-error' : '' }}">
                                    {{ $baris['punya_bom'] ? number_format($baris['selisih_nilai'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('varians-pemakaian.index', ['pesanan' => $baris['pesanan_id']]) }}"
                                        class="btn btn-ghost btn-xs">Rincian</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-base-content/50">Belum ada perintah kerja.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
