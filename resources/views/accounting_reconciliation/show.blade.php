@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3 mb-3"><a href="{{ route('reconciliation.index') }}"
                    class="btn btn-light"><i class="fa-solid fa-arrow-left"></i></a>
                <div>
                    <h4 class="mb-0">Detail Rekonsiliasi</h4><small class="text-muted">{{ strtoupper($check) }}</small>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="detailTable">
                            <thead>
                                <tr>
                                    @if ($check === 'stock')
                                        <th>Barang</th>
                                        <th class="text-end">Stok on hand</th>
                                        <th class="text-end">Total layer</th>
                                        <th class="text-end">Selisih</th>
                                        @if ($financial)
                                            <th class="text-end">Nilai persediaan</th>
                                        @endif
                                    @elseif($check === 'journal')
                                        <th>No jurnal</th>
                                        <th>Tanggal</th>
                                        <th>Sumber</th>
                                        <th>Status</th>
                                        @if ($financial)
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Kredit</th>
                                            <th class="text-end">Selisih</th>
                                        @endif
                                    @elseif($check === 'grni')
                                        <th>No LPB</th>
                                        <th>Tanggal</th>
                                        <th>No PO</th>
                                        @if ($financial)
                                            <th class="text-end">Nilai belum ditagih</th>
                                        @endif
                                    @else
                                        <th>Invoice</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        @if ($financial)
                                            <th class="text-end">Total</th>
                                            <th class="text-end">Dibayar</th>
                                            <th class="text-end">Sisa</th>
                                            <th class="text-end">Selisih</th>
                                        @endif
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        @if ($check === 'stock')
                                            <td>{{ $row->nama }}</td>
                                            <td class="text-end">{{ number_format($row->stok_onhand, 2, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format($row->layer_quantity, 2, ',', '.') }}</td>
                                            <td class="text-end"><span
                                                    class="badge {{ abs($row->difference) <= 0.000001 ? 'bg-success' : 'bg-danger' }}">{{ number_format($row->difference, 6, ',', '.') }}</span>
                                            </td>
                                            @if ($financial)
                                                <td class="text-end">Rp
                                                    {{ number_format($row->inventory_value, 2, ',', '.') }}
                                                </td>
                                            @endif
                                        @elseif($check === 'journal')
                                            <td>{{ $row->no_jurnal }}</td>
                                            <td>{{ $row->tanggal }}</td>
                                            <td>{{ $row->sumber_transaksi }}</td>
                                            <td>{{ $row->status }}</td>
                                            @if ($financial)
                                                <td class="text-end">Rp {{ number_format($row->total_debit, 2, ',', '.') }}
                                                </td>
                                                <td class="text-end">Rp
                                                    {{ number_format($row->total_kredit, 2, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($row->difference, 2, ',', '.') }}
                                                </td>
                                            @endif
                                        @elseif($check === 'grni')
                                            <td>{{ $row->id_lpb }}</td>
                                            <td>{{ $row->tanggal }}</td>
                                            <td>{{ $row->no_po }}</td>
                                            @if ($financial)
                                                <td class="text-end">Rp {{ number_format($row->amount, 2, ',', '.') }}</td>
                                            @endif
                                        @else
                                            <td>{{ $row->no_invoice }}</td>
                                            <td>{{ $row->tanggal }}</td>
                                            <td>{{ str($row->status)->replace('_', ' ')->title() }}</td>
                                            @if ($financial)
                                                <td class="text-end">Rp {{ number_format($row->grand_total, 2, ',', '.') }}
                                                </td>
                                                <td class="text-end">Rp
                                                    {{ number_format($row->total_pembayaran, 2, ',', '.') }}
                                                </td>
                                                <td class="text-end">Rp
                                                    {{ number_format($row->sisa_tagihan, 2, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($row->difference, 2, ',', '.') }}
                                                </td>
                                            @endif
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => $('#detailTable').DataTable({
            pageLength: 25,
            searchDelay: 250
        }));
    </script>
@endpush
