@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h3 class="fw-bold mb-1">{{ $opname->number }}</h3>
                    <p class="text-muted mb-0">{{ $opname->warehouse->nama ?? '-' }} · Cut-off
                        {{ $opname->cutoff_at->format('d-m-Y H:i') }}</p>
                </div>
                <div><span class="badge bg-primary fs-6">{{ $opname->status }}</span> <a target="_blank"
                        class="btn btn-danger ms-2" href="{{ route('stock-opname.pdf', $opname) }}"><i
                            class="fa-solid fa-file-pdf me-1"></i>PDF</a></div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body"><small class="text-muted">Konfirmasi fisik Gudang</small>
                            <div class="fw-semibold">{{ $opname->submitted_by ? 'User #' . $opname->submitted_by : 'Belum dikonfirmasi' }}</div>
                            <small>{{ $opname->submitted_at?->format('d-m-Y H:i') ?: 'Dibuat User #' . $opname->created_by }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body"><small class="text-muted">Konfirmasi valuasi Accounting</small>
                            <div class="fw-semibold">{{ $opname->approved_by ? 'User #' . $opname->approved_by : '-' }}
                            </div>
                            <small>{{ $opname->approval_note ?: 'Belum ada catatan' }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body"><small class="text-muted">Posting</small>
                            <div class="fw-semibold">{{ $opname->posted_by ? 'User #' . $opname->posted_by : '-' }}</div>
                            <small>{{ $opname->posted_at?->format('d-m-Y H:i') ?: 'Belum diposting' }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th class="text-end">Sistem</th>
                                    <th class="text-end">Fisik</th>
                                    <th class="text-end">Selisih</th>
                                    @if ($financial)
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Nilai</th>
                                    @endif
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($opname->details as $detail)
                                    <tr>
                                        <td class="fw-semibold">{{ $detail->bahan->nama ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($detail->system_quantity, 6, ',', '.') }}
                                        </td>
                                        <td class="text-end">{{ number_format($detail->physical_quantity, 6, ',', '.') }}
                                        </td>
                                        <td
                                            class="text-end fw-bold {{ $detail->difference_quantity < 0 ? 'text-danger' : ($detail->difference_quantity > 0 ? 'text-success' : '') }}">
                                            {{ number_format($detail->difference_quantity, 6, ',', '.') }}</td>
                                        @if ($financial)
                                            <td class="text-end">Rp {{ number_format($detail->unit_cost, 2, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($detail->difference_value, 2, ',', '.') }}</td>
                                        @endif
                                        <td>{{ $detail->reason ?: '-' }}</td>
                                </tr>@empty<tr>
                                        <td colspan="{{ $financial ? 7 : 5 }}" class="text-center text-muted">Tidak ada detail.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @can('approve', $opname)
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h5 class="fw-bold">Konfirmasi valuasi Accounting</h5>
                        <p class="text-muted">Selisih negatif dihitung otomatis dari layer FIFO. Isi harga hanya untuk
                            selisih positif.</p>
                        <form id="valuation-form" action="{{ route('stock-opname.approve', $opname) }}" method="POST">
                            @csrf
                            @foreach ($opname->details as $detail)
                                <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $detail->id }}">
                                @if ((float) $detail->difference_quantity > 0)
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-md-6 fw-semibold">{{ $detail->bahan->nama }}</div>
                                        <div class="col-md-6">
                                            <div class="input-group"><span class="input-group-text">Rp</span>
                                                <input class="form-control" type="number" min="0.0001" step="0.0001"
                                                    name="items[{{ $loop->index }}][unit_cost]" required
                                                    placeholder="Harga per {{ $detail->bahan->satuan }}">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            <textarea class="form-control mt-3" name="approval_note" rows="2"
                                placeholder="Catatan Accounting (opsional)"></textarea>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-success"><i
                                        class="fa-solid fa-check-double me-1"></i>Konfirmasi Harga & ACC</button>
                            </div>
                        </form>
                    </div>
                </div>
                @push('scripts')
                    <script>
                        $('#valuation-form').on('submit', function(e) {
                            e.preventDefault();
                            const form = this;
                            AppAlert.confirm('Konfirmasi valuasi dan ACC stock opname ini?').then(result => {
                                if (!result.isConfirmed) return;
                                $.post(form.action, $(form).serialize())
                                    .done(r => AppAlert.success(r.message).then(() => location.reload()))
                                    .fail(e => AppAlert.ajaxError(e));
                            });
                        });
                    </script>
                @endpush
            @endcan
            <div class="mt-3"><a class="btn btn-light" href="{{ route('stock-opname.index') }}">Kembali</a></div>
        </div>
    </div>
@endsection
