@extends('layouts.app')
@section('content')
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">{{ $pemeriksaan->exists ? 'Edit' : 'Buat' }} Pemeriksaan Consider</h3>
        @include('warehouse_partials.alerts')
        @php($ability = $pemeriksaan->exists ? 'update' : 'create')
        @php($subject = $pemeriksaan->exists ? $pemeriksaan : App\Models\PemeriksaanConsider::class)
        @can($ability, $subject)
            <form method="POST"
                action="{{ $pemeriksaan->exists ? route('pemeriksaan-considers.update', $pemeriksaan) : route('pemeriksaan-considers.store') }}"
                class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                @csrf
                @if ($pemeriksaan->exists)
                    @method('PUT')
                @endif
                <div class="mb-3 grid grid-cols-1 gap-2 md:grid-cols-5">
                    <input class="input input-bordered bg-base-200" readonly disabled value="{{ $pemeriksaan->nomor_pemeriksaan }}">
                    <input type="date" name="tanggal" class="input input-bordered" value="{{ old('tanggal', optional($pemeriksaan->tanggal)->format('Y-m-d')) }}" required>
                    <select name="gudang_consider_id" class="select select-bordered" required>
                        @foreach ($consider as $g)
                            <option value="{{ $g->id }}" @selected(old('gudang_consider_id', $pemeriksaan->gudang_consider_id) == $g->id)>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                    <select name="gudang_baik_id" class="select select-bordered" required>
                        @foreach ($normal as $g)
                            <option value="{{ $g->id }}" @selected(old('gudang_baik_id', $pemeriksaan->gudang_baik_id) == $g->id)>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                    <select name="gudang_rusak_id" class="select select-bordered" required>
                        @foreach ($rusak as $g)
                            <option value="{{ $g->id }}" @selected(old('gudang_rusak_id', $pemeriksaan->gudang_rusak_id) == $g->id)>{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <textarea name="catatan" class="textarea textarea-bordered mb-3 w-full" placeholder="Catatan">{{ old('catatan', $pemeriksaan->catatan) }}</textarea>
                @php($details = old('details', $pemeriksaan->exists ? $pemeriksaan->details->toArray() : [['bahan_id' => '', 'jumlah_diperiksa' => '', 'jumlah_baik' => '', 'jumlah_rusak' => '', 'alasan' => '']]))
                <div id="considerRows" class="flex flex-col gap-2">
                    @foreach ($details as $i => $d)
                        <div class="consider-row grid grid-cols-1 gap-2 md:grid-cols-7">
                            <select name="details[{{ $i }}][bahan_id]" class="select select-bordered md:col-span-2" required>
                                @foreach ($bahans as $b)
                                    <option value="{{ $b->id }}" @selected(($d['bahan_id'] ?? null) == $b->id)>{{ $b->nama }}</option>
                                @endforeach
                            </select>
                            @foreach (['jumlah_diperiksa' => 'Diperiksa', 'jumlah_baik' => 'Baik', 'jumlah_rusak' => 'Rusak'] as $f => $p)
                                <input type="number" step="any" min="0" name="details[{{ $i }}][{{ $f }}]" class="input input-bordered" value="{{ $d[$f] ?? '' }}" placeholder="{{ $p }}" required>
                            @endforeach
                            <input name="details[{{ $i }}][alasan]" class="input input-bordered" value="{{ $d['alasan'] ?? '' }}" placeholder="Alasan">
                            <button type="button" class="btn btn-outline btn-error remove-consider">&times;</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="addConsider" class="btn btn-outline btn-sm mb-3 mt-3">Tambah Baris</button>
                <div><button class="btn btn-primary">Simpan Draft</button></div>
            </form>
        @endcan
    </div>
    <template id="considerTemplate">
        <div class="consider-row grid grid-cols-1 gap-2 md:grid-cols-7">
            <select data-name="bahan_id" class="select select-bordered md:col-span-2" required>
                @foreach ($bahans as $b)
                    <option value="{{ $b->id }}">{{ $b->nama }}</option>
                @endforeach
            </select>
            @foreach (['jumlah_diperiksa' => 'Diperiksa', 'jumlah_baik' => 'Baik', 'jumlah_rusak' => 'Rusak'] as $f => $p)
                <input type="number" step="any" min="0" data-name="{{ $f }}" class="input input-bordered" placeholder="{{ $p }}" required>
            @endforeach
            <input data-name="alasan" class="input input-bordered" placeholder="Alasan">
            <button type="button" class="btn btn-outline btn-error remove-consider">&times;</button>
        </div>
    </template>
    @push('scripts')
        <script>
            let cIndex = {{ count($details) }};
            document.querySelector('#addConsider').onclick = () => {
                let n = document.querySelector('#considerTemplate').content.cloneNode(true);
                n.querySelectorAll('[data-name]').forEach(e => e.name = `details[${cIndex}][${e.dataset.name}]`);
                document.querySelector('#considerRows').append(n);
                cIndex++;
            };
            document.addEventListener('click', e => {
                if (e.target.classList.contains('remove-consider') && document.querySelectorAll('.consider-row').length > 1) {
                    e.target.closest('.consider-row').remove();
                }
            });
        </script>
    @endpush
@endsection
