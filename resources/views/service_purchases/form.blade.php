@extends('layouts.app')
@section('content')
    @php($editing = isset($po))
    <div class="content-page">
        <div class="container-fluid">
            <h4 class="mb-3">{{ $editing ? 'Edit' : 'Buat' }} PO Jasa</h4>
            <form method="post"
                action="{{ $editing ? route('service-purchases.update', $po) : route('service-purchases.store') }}"
                class="card border-0 shadow-sm create-section">@csrf @if ($editing)
                    @method('PUT')
                @endif
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label>No PO Jasa</label><input name="no_po" class="form-control"
                                value="{{ $editing ? $po->no_po : $documentNumber }}" readonly>
                            <small class="text-muted">Kode finansial jasa menggunakan penanda PJ.</small>
                        </div>
                        <div class="col-md-3"><label>Tanggal</label><input required type="date" name="tanggal"
                                class="form-control" value="{{ old('tanggal', $po->tanggal ?? today()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-5"><label>Supplier</label><select required name="supplier_id"
                                class="form-select" data-app-picker
                                data-placeholder="Cari nama, telepon, atau alamat supplier...">
                                @foreach ($suppliers as $s)
                                    <option value="{{ $s->id }}" data-subtitle="{{ $s->telp ?: 'Telepon tidak tersedia' }}"
                                        data-meta="{{ $s->alamat }}" @selected(old('supplier_id', $po->supplier_id ?? null) == $s->id)>{{ $s->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label>Untuk perhatian</label><input name="untuk_perhatian"
                                class="form-control" value="{{ old('untuk_perhatian', $po->untuk_perhatian ?? '') }}"></div>
                        <div class="col-md-4"><label>Term</label><input name="term" class="form-control"
                                value="{{ old('term', $po->term ?? '') }}"></div>
                        <div class="col-md-4"><label>Catatan</label><input name="notes" class="form-control"
                                value="{{ old('notes', $po->notes ?? '') }}"></div>
                    </div>
                    <h5>Detail Jasa</h5>
                    <div id="items">@php($rows = old('items', isset($po) ? $po->serviceDetails->toArray() : [[]]))@foreach ($rows as $i => $row)
                            <div class="row g-2 border rounded p-2 mb-2 service-row">
                                <div class="col-md-3"><select required data-app-picker
                                        data-placeholder="Cari kategori jasa..."
                                        name="items[{{ $i }}][service_category_id]" class="form-select">
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}" @selected(($row['service_category_id'] ?? null) == $c->id)>
                                                {{ $c->display_code }} — {{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4"><input required name="items[{{ $i }}][description]"
                                        class="form-control" placeholder="Uraian jasa"
                                        value="{{ $row['description'] ?? '' }}"></div>
                                <div class="col-md-1"><input required type="number" min=".01" step=".01"
                                        name="items[{{ $i }}][quantity]" class="form-control"
                                        value="{{ $row['quantity'] ?? 1 }}"></div>
                                <div class="col-md-1"><input required name="items[{{ $i }}][unit]"
                                        class="form-control" value="{{ $row['unit'] ?? 'JOB' }}"></div>
                                <div class="col-md-2"><input required type="number" min=".01" step=".01"
                                        name="items[{{ $i }}][unit_price]" class="form-control"
                                        placeholder="Harga" value="{{ $row['unit_price'] ?? '' }}"></div>
                                <div class="col-md-1"><button type="button"
                                        class="btn btn-outline-danger remove-row">×</button></div>
                            </div>
                        @endforeach
                    </div><button type="button" id="addRow" class="btn btn-outline-primary btn-sm">+ Tambah
                        Baris</button>
                </div>
                <div class="card-footer bg-white text-end"><a href="{{ route('service-purchases.index') }}"
                        class="btn btn-light">Batal</a><button class="btn btn-primary">Simpan PO</button></div>
            </form>
        </div>
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
                    el.classList.remove('app-picker-source');
                    el.name = el.name.replace(/items\[\d+\]/, `items[${i}]`);
                    if (el.tagName === 'INPUT') el.value = el.name.includes('[quantity]') ? 1 : (el.name
                        .includes('[unit]') ? 'JOB' : '')
                });
                box.append(row)
            };
            box.addEventListener('click', e => {
                if (e.target.classList.contains('remove-row') && box.children.length > 1) e.target.closest(
                    '.service-row').remove()
            })
        });
    </script>
@endpush
