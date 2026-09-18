@extends('layouts.app')
@section('content')
    @php($selected = $orders->firstWhere('id', (int) request('po')) ?? $orders->first())
    <div class="content-page">
        <h3 class="mb-4 text-2xl font-bold">Buat Penerimaan Jasa</h3>
        @if (!$selected)
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="py-10 text-center text-base-content/50">
                    <i class="fa-solid fa-circle-info mb-2 text-2xl"></i>
                    <div>Tidak ada PO Jasa yang belum mempunyai Penerimaan Jasa.</div>
                </div>
            </div>
        @else
            <form method="get" class="card mb-4 border border-base-300 bg-base-100 p-4 shadow-sm">
                <label class="label"><span class="label-text font-semibold">Pilih PO Jasa</span></label>
                <select name="po" onchange="this.form.submit()" class="select select-bordered" data-app-picker data-placeholder="Cari nomor PO Jasa atau supplier...">
                    @foreach ($orders as $o)
                        <option value="{{ $o->id }}" @selected($selected->id === $o->id)>{{ $o->no_po }} — {{ $o->supplier->nama }}</option>
                    @endforeach
                </select>
            </form>
            <form method="post" action="{{ route('penerimaan-jasa.store') }}" class="card border border-base-300 bg-base-100 shadow-sm">
                @csrf
                <div class="p-4">
                    <input type="hidden" name="no_po" value="{{ $selected->no_po }}">
                    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">No Penerimaan Jasa</span></label>
                            <input class="input input-bordered bg-base-200" value="{{ $documentNumber }}" readonly disabled>
                            <span class="label-text-alt mt-1 text-base-content/50">Format nomor mengikuti tanggal dokumen.</span>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Tanggal</span></label>
                            <input required type="date" name="tanggal" class="input input-bordered" value="{{ old('tanggal', today()->format('Y-m-d')) }}">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">No Dokumen/BA</span></label>
                            <input required name="no_sj" class="input input-bordered" value="{{ old('no_sj', 'BA-') }}">
                        </div>
                    </div>
                    <div role="alert" class="alert alert-info mb-4">
                        <span>Penerimaan jasa menandai seluruh pekerjaan dalam PO ini mulai dikerjakan. Belum ada jurnal sampai penerimaan jasa dimasukkan ke invoice.</span>
                    </div>
                    @foreach ($selected->serviceDetails as $i => $d)
                        <div class="bap-item mb-3 rounded-lg border border-base-300 p-3">
                            <input type="hidden" name="items[{{ $i }}][service_po_detail_id]" value="{{ $d->id }}">
                            <input type="hidden" name="items[{{ $i }}][progress_percent]" value="100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="badge badge-primary">{{ $d->category->display_code }}</span>
                                    <strong>{{ $d->description }}</strong>
                                </div>
                                @if ($financial)
                                    <span>Nilai kontrak Rp {{ number_format($d->subtotal, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge badge-info badge-outline">Akan mulai dikerjakan</span>
                                @endif
                            </div>
                            <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-3">
                                @if ($d->category->requires_cost_center)
                                    <div class="form-control">
                                        <label class="label"><span class="label-text">Departemen/Cost Center</span></label>
                                        <input required name="items[{{ $i }}][department_cost_center]" class="input input-bordered">
                                    </div>
                                @endif
                                <div class="form-control">
                                    <label class="label"><span class="label-text">Catatan</span></label>
                                    <input name="items[{{ $i }}][notes]" class="input input-bordered">
                                </div>
                            </div>
                            @if ($d->category->requires_datapesanan)
                                <div class="allocation-box mt-3" data-index="{{ $i }}">
                                    <label class="font-semibold">Alokasi Datapesanan (total 100%)</label>
                                    <div class="allocation-row mt-1 grid grid-cols-1 gap-2 md:grid-cols-12">
                                        <input required name="items[{{ $i }}][allocations][0][datapesanan_code]" class="input input-bordered md:col-span-7" placeholder="Kode Datapesanan">
                                        <input required type="number" min=".0001" max="100" step=".0001" name="items[{{ $i }}][allocations][0][percentage]" class="input input-bordered md:col-span-4" value="100">
                                        <button type="button" class="btn btn-outline btn-error remove-allocation md:col-span-1">&times;</button>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-primary btn-sm add-allocation mt-2">+ Datapesanan</button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end border-t border-base-300 p-4">
                    <button class="btn btn-primary">Mulai Pekerjaan &amp; Simpan Penerimaan Jasa</button>
                </div>
            </form>
        @endif
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('click', e => {
            if (e.target.classList.contains('add-allocation')) {
                const box = e.target.closest('.allocation-box'),
                    rows = box.querySelectorAll('.allocation-row'),
                    row = rows[0].cloneNode(true),
                    n = rows.length,
                    i = box.dataset.index;
                row.querySelectorAll('input').forEach(x => {
                    x.name = x.name.replace(/allocations\]\[\d+\]/, `allocations][${n}]`);
                    x.value = '';
                });
                e.target.before(row);
            }
            if (e.target.classList.contains('remove-allocation')) {
                const box = e.target.closest('.allocation-box');
                if (box.querySelectorAll('.allocation-row').length > 1) e.target.closest('.allocation-row').remove();
            }
        });
    </script>
@endpush
