@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-start justify-between">
            <div>
                <h3 class="text-2xl font-bold">{{ $opname->number }}</h3>
                <p class="text-base-content/60">{{ $opname->warehouse->nama ?? '-' }} · Cut-off {{ $opname->cutoff_at->format('d-m-Y H:i') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-lg badge-primary">{{ $opname->status }}</span>
                <a target="_blank" class="btn btn-error" href="{{ route('stock-opname.pdf', $opname) }}">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a class="btn btn-success" href="{{ route('stock-opname.excel', $opname) }}">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/50">Konfirmasi Fisik Gudang</p>
                    <p class="font-semibold">{{ $opname->submitted_by ? 'User #' . $opname->submitted_by : 'Belum dikonfirmasi' }}</p>
                    <p class="text-sm">{{ $opname->submitted_at?->format('d-m-Y H:i') ?: 'Dibuat User #' . $opname->created_by }}</p>
                </div>
            </div>
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/50">Konfirmasi Valuasi Accounting</p>
                    <p class="font-semibold">{{ $opname->approved_by ? 'User #' . $opname->approved_by : '-' }}</p>
                    <p class="text-sm">{{ $opname->approval_note ?: 'Belum ada catatan' }}</p>
                </div>
            </div>
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-sm text-base-content/50">Posting</p>
                    <p class="font-semibold">{{ $opname->posted_by ? 'User #' . $opname->posted_by : '-' }}</p>
                    <p class="text-sm">{{ $opname->posted_at?->format('d-m-Y H:i') ?: 'Belum diposting' }}</p>
                </div>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
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
                                <td class="font-semibold">{{ $detail->bahan->nama ?? '-' }}</td>
                                <td class="text-end">{{ number_format($detail->system_quantity, 6, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($detail->physical_quantity, 6, ',', '.') }}</td>
                                <td class="text-end font-bold {{ $detail->difference_quantity < 0 ? 'text-error' : ($detail->difference_quantity > 0 ? 'text-success' : '') }}">
                                    {{ number_format($detail->difference_quantity, 6, ',', '.') }}
                                </td>
                                @if ($financial)
                                    <td class="text-end">Rp {{ number_format($detail->unit_cost, 2, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($detail->difference_value, 2, ',', '.') }}</td>
                                @endif
                                <td>{{ $detail->reason ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $financial ? 7 : 5 }}" class="text-center text-base-content/50">Tidak ada detail.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @can('approve', $opname)
            <div class="card mt-4 border border-base-300 bg-base-100 shadow-sm" x-data="{
                submitting: false,
                async submit(event) {
                    const result = await window.AppAlert.confirm('Konfirmasi valuasi dan ACC stock opname ini?');
                    if (!result.isConfirmed) return;
                    this.submitting = true;
                    try {
                        const form = event.target;
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            body: new FormData(form),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) { window.AppAlert.ajaxError(data); return; }
                        await window.AppAlert.success(data.message);
                        window.location.reload();
                    } catch (error) {
                        window.AppAlert.error('Gagal menyimpan konfirmasi.');
                    } finally {
                        this.submitting = false;
                    }
                },
            }">
                <div class="card-body p-4">
                    <h5 class="text-lg font-bold">Konfirmasi Valuasi Accounting</h5>
                    <p class="text-base-content/60">Selisih negatif dihitung otomatis dari layer FIFO. Isi harga hanya untuk selisih positif.</p>
                    <form action="{{ route('stock-opname.approve', $opname) }}" method="POST" @submit.prevent="submit($event)" class="mt-3">
                        @csrf
                        @foreach ($opname->details as $detail)
                            <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $detail->id }}">
                            @if ((float) $detail->difference_quantity > 0)
                                <div class="mb-2 grid grid-cols-1 items-center gap-2 md:grid-cols-2">
                                    <div class="font-semibold">{{ $detail->bahan->nama }}</div>
                                    <div class="join">
                                        <span class="join-item btn btn-disabled btn-outline">Rp</span>
                                        <input class="input input-bordered join-item flex-1" type="number" min="0.0001" step="0.0001"
                                            name="items[{{ $loop->index }}][unit_cost]" required placeholder="Harga per {{ $detail->bahan->satuan }}">
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <textarea class="textarea textarea-bordered mt-3 w-full" name="approval_note" rows="2" placeholder="Catatan Accounting (opsional)"></textarea>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="btn btn-success" :disabled="submitting">
                                <span x-show="submitting" class="loading loading-spinner loading-sm"></span>
                                <i class="fa-solid fa-check-double" x-show="!submitting"></i> Konfirmasi Harga & ACC
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <div class="mt-4">
            <a class="btn btn-ghost border border-base-300" href="{{ route('stock-opname.index') }}">Kembali</a>
        </div>
    </div>
@endsection
