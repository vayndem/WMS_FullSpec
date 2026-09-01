@extends('layouts.app')
@section('content')
    @php($editing = isset($po))
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">{{ $editing ? 'Edit' : 'Buat' }} PO Jasa</h3>
        <form method="post" action="{{ $editing ? route('service-purchases.update', $po) : route('service-purchases.store') }}" class="card border border-base-300 bg-base-100 shadow-sm">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div class="p-4">
                <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">No PO Jasa</span></label>
                        <input name="no_po" class="input input-bordered bg-base-200" value="{{ $editing ? $po->no_po : $documentNumber }}" readonly>
                        <span class="label-text-alt mt-1 text-base-content/50">Kode finansial jasa menggunakan penanda PJ.</span>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Tanggal</span></label>
                        <input required type="date" name="tanggal" class="input input-bordered" value="{{ old('tanggal', $po->tanggal ?? today()->format('Y-m-d')) }}">
                    </div>
                    <div class="form-control md:col-span-2">
                        <label class="label"><span class="label-text font-semibold">Supplier</span></label>
                        <select required name="supplier_id" class="select select-bordered" data-app-picker data-placeholder="Cari nama, telepon, atau alamat supplier...">
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" data-subtitle="{{ $s->telp ?: 'Telepon tidak tersedia' }}" data-meta="{{ $s->alamat }}" @selected(old('supplier_id', $po->supplier_id ?? null) == $s->id)>{{ $s->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Untuk Perhatian</span></label>
                        <input name="untuk_perhatian" class="input input-bordered" value="{{ old('untuk_perhatian', $po->untuk_perhatian ?? '') }}">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Term</span></label>
                        <input name="term" class="input input-bordered" value="{{ old('term', $po->term ?? '') }}">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Catatan</span></label>
                        <input name="notes" class="input input-bordered" value="{{ old('notes', $po->notes ?? '') }}">
                    </div>
                </div>
                <h5 class="mb-2 font-bold">Detail Jasa</h5>
                <div id="items" class="flex flex-col gap-2">
                    @php($rows = old('items', isset($po) ? $po->serviceDetails->toArray() : [[]]))
                    @foreach ($rows as $i => $row)
                        <div class="service-row grid grid-cols-1 gap-2 rounded-lg border border-base-300 p-2 md:grid-cols-12">
                            <select required data-app-picker data-placeholder="Cari kategori jasa..." name="items[{{ $i }}][service_category_id]" class="select select-bordered md:col-span-3">
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" @selected(($row['service_category_id'] ?? null) == $c->id)>{{ $c->display_code }} — {{ $c->name }}</option>
                                @endforeach
                            </select>
                            <input required name="items[{{ $i }}][description]" class="input input-bordered md:col-span-4" placeholder="Uraian jasa" value="{{ $row['description'] ?? '' }}">
                            <input required type="number" min=".01" step=".01" name="items[{{ $i }}][quantity]" class="input input-bordered md:col-span-1" value="{{ $row['quantity'] ?? 1 }}">
                            <input required name="items[{{ $i }}][unit]" class="input input-bordered md:col-span-1" value="{{ $row['unit'] ?? 'JOB' }}">
                            <input required type="number" min=".01" step=".01" name="items[{{ $i }}][unit_price]" class="input input-bordered md:col-span-2" data-money-input placeholder="Harga" value="{{ $row['unit_price'] ?? '' }}">
                            <button type="button" class="btn btn-outline btn-error remove-row md:col-span-1">&times;</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="addRow" class="btn btn-outline btn-primary btn-sm mt-3">+ Tambah Baris</button>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 p-4">
                <a href="{{ route('service-purchases.index') }}" class="btn btn-ghost border border-base-300">Batal</a>
                <button class="btn btn-primary">Simpan PO</button>
            </div>
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const box = document.querySelector('#items');
            document.querySelector('#addRow').onclick = () => {
                const row = box.querySelector('.service-row').cloneNode(true);
                const i = box.children.length;
                row.querySelectorAll('.app-smart-picker').forEach(picker => picker.remove());
                row.querySelectorAll('[name]').forEach(el => {
                    el.classList.remove('hidden');
                    el.name = el.name.replace(/items\[\d+\]/, `items[${i}]`);
                    if (el.tagName === 'INPUT') el.value = el.name.includes('[quantity]') ? 1 : (el.name.includes('[unit]') ? 'JOB' : '');
                });
                box.append(row);
            };
            box.addEventListener('click', e => {
                if (e.target.classList.contains('remove-row') && box.children.length > 1) e.target.closest('.service-row').remove();
            });
        });
    </script>
@endpush
