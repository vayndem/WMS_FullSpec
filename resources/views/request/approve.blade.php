@extends('layouts.app')

@section('content')
    <div class="content-page" x-data="{
        submitting: false,
        async submit(event) {
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
                await window.AppAlert.success(data.message, 'Berhasil');
                window.location.href = '{{ route('request.index') }}';
            } catch (error) {
                window.AppAlert.error('Gagal memproses persetujuan.');
            } finally {
                this.submitting = false;
            }
        },
    }">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="mb-1 flex items-center gap-2">
                    <a href="{{ route('request.index') }}" class="btn btn-sm btn-ghost border border-base-300" title="Kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <h3 class="text-2xl font-bold">Persetujuan Request Barang</h3>
                </div>
                <p class="text-base-content/60">Periksa jumlah yang disetujui sebelum memproses request.</p>
            </div>
            <span class="badge badge-lg badge-primary badge-outline">{{ $requestData->no_request }}</span>
        </div>

        <form action="{{ route('request.processApprove', $requestData) }}" method="POST" @submit.prevent="submit"
            class="card border border-base-300 bg-base-100 shadow-sm">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-base-300 p-4">
                <div>
                    <h5 class="font-bold">Detail Barang</h5>
                    <p class="text-sm text-base-content/50">{{ $requestData->details->count() }} item dalam request ini</p>
                </div>
                <span class="badge badge-warning badge-outline"><i class="fa-solid fa-clock"></i> Menunggu tindakan</span>
            </div>
            <div class="flex flex-col gap-3 p-4">
                @foreach ($requestData->details as $item)
                    <div class="rounded-lg border border-base-300 bg-base-200/40 p-3">
                        <div class="grid grid-cols-1 items-center gap-3 lg:grid-cols-12">
                            <div class="lg:col-span-5">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <i class="fa-solid fa-box"></i>
                                    </span>
                                    <div>
                                        <h6 class="font-bold">{{ $item->nama_barang }}</h6>
                                        <p class="text-sm text-base-content/50">{{ $item->kategoriBahan->katnama ?? 'Tanpa kategori' }}</p>
                                        <p class="text-sm text-base-content/50">Gudang: {{ $item->gudang->nama ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="lg:col-span-3">
                                <label class="label"><span class="label-text text-xs">Jumlah diminta</span></label>
                                <div class="font-semibold">
                                    {{ number_format((float) $item->jumlah_minta, 2, ',', '.') }} {{ $item->satuan }}
                                </div>
                            </div>
                            <div class="lg:col-span-4">
                                <label class="label" for="approved-{{ $item->id }}">
                                    <span class="label-text font-semibold">Jumlah disetujui</span>
                                </label>
                                <div class="join w-full">
                                    <input id="approved-{{ $item->id }}" type="number" step="any" min="0"
                                        max="{{ $item->jumlah_minta }}" name="items[{{ $item->id }}][jumlah_acc]"
                                        value="{{ old("items.{$item->id}.jumlah_acc", $item->jumlah_minta) }}"
                                        class="input input-bordered join-item flex-1" required>
                                    <span class="join-item btn btn-disabled btn-outline">{{ $item->satuan }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="mt-2">
                    <label class="label" for="approval-note"><span class="label-text font-semibold">Catatan approver</span></label>
                    <textarea id="approval-note" name="catatan_approver" class="textarea textarea-bordered w-full" rows="3"
                        placeholder="Tambahkan catatan bila diperlukan">{{ old('catatan_approver') }}</textarea>
                </div>
            </div>
            <div class="flex flex-col justify-end gap-2 border-t border-base-300 p-4 sm:flex-row">
                <a href="{{ route('request.index') }}" class="btn btn-ghost border border-base-300">Batal</a>
                <button type="submit" class="btn btn-success" :disabled="submitting">
                    <span x-show="submitting" class="loading loading-spinner loading-sm"></span>
                    <i class="fa-solid fa-check" x-show="!submitting"></i> Setujui Request
                </button>
            </div>
        </form>
    </div>
@endsection
