@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <h4>{{ $transfer->exists ? 'Edit' : 'Buat' }} Transfer Gudang</h4>@include('warehouse_partials.alerts')
            @php($ability = $transfer->exists ? 'update' : 'create')
            @php($subject = $transfer->exists ? $transfer : App\Models\TransferGudang::class)
            @can($ability, $subject)
            <form method="POST"
                action="{{ $transfer->exists ? route('transfer-gudangs.update', $transfer) : route('transfer-gudangs.store') }}"
                class="card card-body">@csrf @if ($transfer->exists)
                    @method('PUT')
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><label>Nomor</label><input name="nomor_transfer" class="form-control" readonly
                            value="{{ old('nomor_transfer', $transfer->nomor_transfer) }}"></div>
                    <div class="col-md-3"><label>Tanggal</label><input type="date" name="tanggal" class="form-control"
                            required value="{{ old('tanggal', optional($transfer->tanggal)->format('Y-m-d')) }}"></div>
                    <div class="col-md-3"><label>Gudang Asal</label><select name="gudang_asal_id" class="form-select"
                            required>
                            <option value="">Pilih</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}" @selected(old('gudang_asal_id', $transfer->gudang_asal_id) == $g->id)>{{ $g->nama }}
                                    ({{ $g->jenis }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label>Gudang Tujuan</label><select name="gudang_tujuan_id" class="form-select"
                            required>
                            <option value="">Pilih</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}" @selected(old('gudang_tujuan_id', $transfer->gudang_tujuan_id) == $g->id)>{{ $g->nama }}
                                    ({{ $g->jenis }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label>Keterangan</label>
                        <textarea name="keterangan" class="form-control">{{ old('keterangan', $transfer->keterangan) }}</textarea>
                    </div>
                </div>
                <h6>Detail Barang</h6>
                <div id="detailRows">@php($details = old('details', $transfer->exists ? $transfer->details->toArray() : [['bahan_id' => '', 'jumlah' => '', 'keterangan' => '']]))@foreach ($details as $i => $d)
                        <div class="row g-2 mb-2 detail-row">
                            <div class="col-md-6"><select name="details[{{ $i }}][bahan_id]" class="form-select"
                                    required>
                                    <option value="">Pilih bahan</option>
                                    @foreach ($bahans as $b)
                                        <option value="{{ $b->id }}" @selected(($d['bahan_id'] ?? null) == $b->id)>
                                            {{ $b->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2"><input type="number" step="any" min="0.000001"
                                    name="details[{{ $i }}][jumlah]" class="form-control"
                                    value="{{ $d['jumlah'] ?? '' }}" placeholder="Jumlah" required></div>
                            <div class="col-md-3"><input name="details[{{ $i }}][keterangan]"
                                    class="form-control" value="{{ $d['keterangan'] ?? '' }}" placeholder="Keterangan"></div>
                            <div class="col"><button type="button" class="btn btn-outline-danger remove-row">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="addRow" class="btn btn-sm btn-outline-secondary mb-3">Tambah Baris</button>
                <div><button class="btn btn-primary">Simpan Draft</button></div>
            </form>
            @endcan
        </div>
    </div>
    <template id="rowTemplate">
        <div class="row g-2 mb-2 detail-row">
            <div class="col-md-6"><select data-name="bahan_id" class="form-select" required>
                    <option value="">Pilih bahan</option>
                    @foreach ($bahans as $b)
                        <option value="{{ $b->id }}">{{ $b->nama }}</option>
                    @endforeach
                </select></div>
            <div class="col-md-2"><input data-name="jumlah" type="number" step="any" min="0.000001"
                    class="form-control" placeholder="Jumlah" required></div>
            <div class="col-md-3"><input data-name="keterangan" class="form-control" placeholder="Keterangan"></div>
            <div class="col"><button type="button" class="btn btn-outline-danger remove-row">×</button></div>
        </div>
    </template>
    @push('scripts')
        <script>
            let trIndex = {{ count($details) }};
            document.querySelector('#addRow').onclick = () => {
                let n = document.querySelector('#rowTemplate').content.cloneNode(true);
                n.querySelectorAll('[data-name]').forEach(e => e.name = `details[${trIndex}][${e.dataset.name}]`);
                document.querySelector('#detailRows').append(n);
                trIndex++
            };
            document.addEventListener('click', e => {
                if (e.target.classList.contains('remove-row') && document.querySelectorAll('.detail-row').length > 1) e
                    .target.closest('.detail-row').remove()
            });
        </script>
    @endpush
@endsection
