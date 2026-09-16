@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="text-2xl font-bold">Rekonsiliasi Inventory</h3>
        <p class="mb-4 text-base-content/60">Standar kontrol: master bahan = saldo gudang + transit; saldo gudang = layer; nilai layer = General Ledger.</p>

        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
            @foreach ([['Master Qty', $master_quantity], ['Gudang Qty', $warehouse_quantity], ['In Transit', $transit_quantity], ['Selisih Global', $global_quantity_difference]] as [$label, $value])
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <p class="text-sm text-base-content/50">{{ $label }}</p>
                    <p class="text-xl font-bold {{ abs($value) > 0.000001 && str_contains($label, 'Selisih') ? 'text-error' : '' }}">{{ number_format($value, 6, ',', '.') }}</p>
                </div>
            @endforeach
        </div>
        @if ($financial)
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ([['Nilai Layer', $layer_value], ['Persediaan GL', $inventory_gl_value], ['Selisih Nilai', $value_difference]] as [$label, $value])
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <p class="text-sm text-base-content/50">{{ $label }}</p>
                        <p class="text-xl font-bold {{ str_contains($label, 'Selisih') && abs($value) > .01 ? 'text-error' : '' }}">Rp {{ number_format($value, 2, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($financial)
            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h4 class="font-semibold">Nilai Persediaan per Gudang</h4>
                    <p class="text-sm text-base-content/60">Dihitung dari dimensi gudang pada baris jurnal, jadi rupiah per gudang bisa ditarik langsung dari buku besar.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Gudang</th>
                                <th class="text-end">Nilai Buku Besar</th>
                                <th class="text-end">Nilai Layer</th>
                                <th class="text-end">Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($per_gudang as $baris)
                                <tr>
                                    <td>
                                        {{ $baris['gudang'] }}
                                        @if ($baris['gudang_id'] === null || $baris['gudang_id'] === '')
                                            <span class="badge badge-ghost badge-sm">jurnal tanpa gudang</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($baris['nilai_buku_besar'], 2, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($baris['nilai_layer'], 2, ',', '.') }}</td>
                                    <td class="text-end {{ abs($baris['selisih']) > .01 ? 'text-error font-semibold' : '' }}">Rp {{ number_format($baris['selisih'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-3 text-center text-base-content/50">Belum ada nilai persediaan untuk direkonsiliasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 text-xs text-base-content/50">
                    Stok yang sedang dalam perjalanan tidak dihitung di sini: jurnal transfer baru terbentuk saat barang diterima, jadi sampai penerimaan nilainya masih melekat pada gudang asal.
                </div>
            </div>
        @endif

        @php($hasException = $quantity_exceptions || $reservation_exceptions || abs($global_quantity_difference) > .000001 || ($financial && abs($value_difference) > .01))
        <div role="alert" class="alert {{ $hasException ? 'alert-error' : 'alert-success' }} mb-4">
            <span>{{ $hasException ? 'Ditemukan ketidaksesuaian yang harus diselesaikan sebelum closing.' : 'Seluruh kontrol inventory sesuai.' }}</span>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Gudang</th>
                            <th>Bahan</th>
                            <th>Saldo Gudang</th>
                            <th>Saldo Layer</th>
                            <th>Selisih</th>
                            <th>Reservasi Tercatat</th>
                            <th>Reservasi Hidup</th>
                            @if ($financial)<th>Nilai Layer</th>@endif
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php
                                $selisihQty = abs((float) $r->selisih) > 0.000001;
                                $selisihRsv = abs((float) $r->selisih_reservasi) > 0.000001;
                            @endphp
                            <tr class="{{ $selisihQty || $selisihRsv ? 'bg-error/10' : '' }}">
                                <td>{{ $r->gudang_nama }}</td>
                                <td class="font-semibold">{{ $r->bahan_nama }}</td>
                                <td>{{ $r->stok_tersedia }}</td>
                                <td>{{ $r->layer_quantity }}</td>
                                <td>{{ $r->selisih }}</td>
                                <td>{{ $r->stok_direservasi }}</td>
                                <td class="{{ $selisihRsv ? 'font-bold text-error' : '' }}">{{ $r->reservasi_hidup }}</td>
                                @if ($financial)<td>Rp {{ number_format($r->layer_value, 2, ',', '.') }}</td>@endif
                                <td>
                                    <span class="badge {{ !$selisihQty && !$selisihRsv ? 'badge-success' : 'badge-error' }}">
                                        {{ match (true) {
                                            $selisihQty && $selisihRsv => 'SELISIH QTY + RESERVASI',
                                            $selisihQty => 'SELISIH',
                                            $selisihRsv => 'SELISIH RESERVASI',
                                            default => 'SESUAI',
                                        } }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
