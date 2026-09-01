@extends('layouts.app')
@section('content')
    <div class="content-page" x-data="{
        async confirmCancel(event) {
            if (event.target.dataset.confirmed) return;
            event.preventDefault();
            const result = await AppAlert.confirm('Batalkan BAP yang sedang berjalan?');
            if (result.isConfirmed) {
                event.target.dataset.confirmed = '1';
                event.target.submit();
            }
        },
    }">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">{{ $bap->id_lpb }}</h3>
                <p class="text-base-content/60">{{ $bap->pembelian->supplier->nama }} · PO {{ $bap->no_po }}</p>
            </div>
            <span class="badge badge-lg {{ $bap->status === \App\Models\Lpb::CANCELLED ? 'badge-error' : ($bap->invoiceReceipts->isNotEmpty() ? 'badge-success' : 'badge-warning') }}">
                {{ $bap->status === \App\Models\Lpb::CANCELLED ? 'Dibatalkan' : ($bap->invoiceReceipts->isNotEmpty() ? 'SELESAI · SUDAH INVOICE' : 'SEDANG DIKERJAKAN') }}
            </span>
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
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
                                    <span class="block text-sm text-base-content/50">{{ $d->kategori->katnama ?? '-' }}</span>
                                </td>
                                <td>{{ $d->servicePoDetail->description }}</td>
                                <td>{{ $bap->invoiceReceipts->isNotEmpty() ? 'Selesai 100%' : 'Sedang dikerjakan' }}</td>
                                <td>
                                    @if ($d->allocations->isNotEmpty())
                                        @foreach ($d->allocations as $a)
                                            <span class="badge badge-ghost mr-1">{{ $a->datapesanan_code }} ({{ $a->percentage }}%)</span>
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
            <form method="post" action="{{ route('service-baps.cancel', $bap) }}" class="card mt-4 border border-base-300 bg-base-100 p-4 shadow-sm" @submit="confirmCancel($event)">
                @csrf
                <label class="label"><span class="label-text font-semibold">Alasan Pembatalan</span></label>
                <div class="join">
                    <input required name="reason" class="input input-bordered join-item flex-1">
                    <button class="btn btn-error join-item">Batalkan BAP</button>
                </div>
            </form>
        @endcan
    </div>
@endsection
