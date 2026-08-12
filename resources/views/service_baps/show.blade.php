@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h4>{{ $bap->id_lpb }}</h4>
                    <p class="text-muted">{{ $bap->pembelian->supplier->nama }} · PO {{ $bap->no_po }}</p>
                </div><span
                    class="badge bg-{{ $bap->status === \App\Models\Lpb::CANCELLED ? 'danger' : ($bap->invoiceReceipts->isNotEmpty() ? 'success' : 'warning') }} align-self-center">
                    {{ $bap->status === \App\Models\Lpb::CANCELLED ? 'Dibatalkan' : ($bap->invoiceReceipts->isNotEmpty() ? 'SELESAI · SUDAH INVOICE' : 'SEDANG DIKERJAKAN') }}
                </span>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Uraian</th>
                                <th>Status Pekerjaan</th>
                                <th>Cost Object</th>
                                @if ($financial)<th class="text-end">Nilai</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bap->serviceDetails as $d)
                                <tr>
                                    <td>
                                        {{ $d->servicePoDetail->category->display_code }}
                                        <small class="d-block text-muted">{{ $d->kategori->katnama ?? '-' }}</small>
                                    </td>
                                    <td>{{ $d->servicePoDetail->description }}</td>
                                    <td>{{ $bap->invoiceReceipts->isNotEmpty() ? 'Selesai 100%' : 'Sedang dikerjakan' }}</td>
                                    <td>
                                        @if ($d->allocations->isNotEmpty())
                                            @foreach ($d->allocations as $a)
                                                <span class="badge bg-light text-dark me-1">{{ $a->datapesanan_code }}
                                                    ({{ $a->percentage }}%)</span>
                                            @endforeach
                                        @else
                                            {{ $d->department_cost_center ?: '-' }}
                                        @endif
                                    </td>
                                    @if ($financial)
                                        <td class="text-end">Rp {{ number_format($d->amount, 0, ',', '.') }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @can('cancel', $bap)
                <form method="post" action="{{ route('service-baps.cancel', $bap) }}" class="card border-0 shadow-sm mt-3">
                    @csrf<div class="card-body"><label>Alasan pembatalan</label>
                        <div class="input-group"><input required name="reason" class="form-control"><button
                                class="btn btn-danger">Batalkan BAP</button></div>
                    </div>
                </form>
            @endcan
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelector('form[action*="/cancel"]')?.addEventListener('submit', async function(e) {
            if (this.dataset.confirmed) return;
            e.preventDefault();
            const result = await AppAlert.confirm('Batalkan BAP yang sedang berjalan?');
            if (result.isConfirmed) {
                this.dataset.confirmed = '1';
                this.submit()
            }
        });
    </script>
@endpush
