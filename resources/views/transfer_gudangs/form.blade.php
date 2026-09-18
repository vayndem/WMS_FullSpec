@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">{{ $transfer->exists ? 'Edit' : 'Buat' }} Transfer Gudang</h3>
        @include('warehouse_partials.alerts')
        @php($ability = $transfer->exists ? 'update' : 'create')
        @php($subject = $transfer->exists ? $transfer : App\Models\TransferGudang::class)
        @can($ability, $subject)
            <form method="POST"
                action="{{ $transfer->exists ? route('transfer-gudangs.update', $transfer) : route('transfer-gudangs.store') }}"
                class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                @csrf
                @if ($transfer->exists)
                    @method('PUT')
                @endif
                <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Nomor</span></label>
                        <input class="input input-bordered bg-base-200" readonly disabled value="{{ $transfer->nomor_transfer }}">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Tanggal</span></label>
                        <input type="date" name="tanggal" class="input input-bordered" required value="{{ old('tanggal', optional($transfer->tanggal)->format('Y-m-d')) }}">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Gudang Asal</span></label>
                        <select name="gudang_asal_id" class="select select-bordered" required>
                            <option value="">Pilih</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}" @selected(old('gudang_asal_id', $transfer->gudang_asal_id) == $g->id)>{{ $g->nama }} ({{ $g->jenis }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Gudang Tujuan</span></label>
                        <select name="gudang_tujuan_id" class="select select-bordered" required>
                            <option value="">Pilih</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}" @selected(old('gudang_tujuan_id', $transfer->gudang_tujuan_id) == $g->id)>{{ $g->nama }} ({{ $g->jenis }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control md:col-span-4">
                        <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                        <textarea name="keterangan" class="textarea textarea-bordered">{{ old('keterangan', $transfer->keterangan) }}</textarea>
                    </div>
                </div>
                <h6 class="mb-2 font-bold">Detail Barang</h6>
                @php($details = old('details', $transfer->exists ? $transfer->details->toArray() : [['bahan_id' => '', 'jumlah' => '', 'keterangan' => '']]))
                <div id="detailRows" class="flex flex-col gap-2">
                    @foreach ($details as $i => $d)
                        <div class="detail-row grid grid-cols-1 gap-2 md:grid-cols-12">
                            <select name="details[{{ $i }}][bahan_id]" class="select select-bordered md:col-span-6" required>
                                <option value="">Pilih bahan</option>
                                @foreach ($bahans as $b)
                                    <option value="{{ $b->id }}" @selected(($d['bahan_id'] ?? null) == $b->id)>{{ $b->nama }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="any" min="0.000001" name="details[{{ $i }}][jumlah]" class="input input-bordered md:col-span-2" value="{{ $d['jumlah'] ?? '' }}" placeholder="Jumlah" required>
                            <input name="details[{{ $i }}][keterangan]" class="input input-bordered md:col-span-3" value="{{ $d['keterangan'] ?? '' }}" placeholder="Keterangan">
                            <button type="button" class="btn btn-outline btn-error remove-row md:col-span-1">&times;</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="addRow" class="btn btn-outline btn-sm mb-3 mt-3">Tambah Baris</button>
                <div><button class="btn btn-primary">Simpan Draft</button></div>
            </form>
        @endcan
    </div>
    <template id="rowTemplate">
        <div class="detail-row grid grid-cols-1 gap-2 md:grid-cols-12">
            <select data-name="bahan_id" class="select select-bordered md:col-span-6" required>
                <option value="">Pilih bahan</option>
                @foreach ($bahans as $b)
                    <option value="{{ $b->id }}">{{ $b->nama }}</option>
                @endforeach
            </select>
            <input data-name="jumlah" type="number" step="any" min="0.000001" class="input input-bordered md:col-span-2" placeholder="Jumlah" required>
            <input data-name="keterangan" class="input input-bordered md:col-span-3" placeholder="Keterangan">
            <button type="button" class="btn btn-outline btn-error remove-row md:col-span-1">&times;</button>
        </div>
    </template>
    @push('scripts')
        <script>
            let trIndex = {{ count($details) }};
            document.querySelector('#addRow').onclick = () => {
                let n = document.querySelector('#rowTemplate').content.cloneNode(true);
                n.querySelectorAll('[data-name]').forEach(e => e.name = `details[${trIndex}][${e.dataset.name}]`);
                document.querySelector('#detailRows').append(n);
                trIndex++;
            };
            document.addEventListener('click', e => {
                if (e.target.classList.contains('remove-row') && document.querySelectorAll('.detail-row').length > 1) {
                    e.target.closest('.detail-row').remove();
                }
            });
        </script>
    @endpush
@endsection
