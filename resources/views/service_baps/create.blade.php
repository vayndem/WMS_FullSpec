@extends('layouts.app')
@section('content')
    @php($selected = $orders->firstWhere('id', (int) request('po')) ?? $orders->first())
    <div class="content-page">
        <div class="container-fluid">
            <h4 class="mb-3">Buat BAP Jasa</h4>
            @if (!$selected)
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5"><i class="fa-solid fa-circle-info fa-2x mb-2"></i>
                        <div>Tidak ada PO Jasa yang belum mempunyai BAP.</div>
                    </div>
                </div>
            @else
                <form method="get" class="card border-0 shadow-sm mb-3">
                    <div class="card-body"><label>Pilih PO Jasa</label><select name="po" onchange="this.form.submit()"
                            class="form-select" data-app-picker
                            data-placeholder="Cari nomor PO Jasa atau supplier...">
                            @foreach ($orders as $o)
                                <option value="{{ $o->id }}" @selected($selected->id === $o->id)>{{ $o->no_po }} —
                                    {{ $o->supplier->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <form method="post" action="{{ route('service-baps.store') }}" class="card border-0 shadow-sm">@csrf<div
                        class="card-body"><input type="hidden" name="no_po" value="{{ $selected->no_po }}">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><label>No BAP</label><input name="id_lpb" class="form-control"
                                    value="{{ $documentNumber }}" readonly>
                                <small class="text-muted">Format BAP mengikuti tanggal dokumen.</small>
                            </div>
                            <div class="col-md-4"><label>Tanggal</label><input required type="date" name="tanggal"
                                    class="form-control" value="{{ old('tanggal', today()->format('Y-m-d')) }}"></div>
                            <div class="col-md-4"><label>No Dokumen/BA</label><input required name="no_sj"
                                    class="form-control" value="{{ old('no_sj', 'BA-') }}"></div>
                        </div>
                        <div class="border-start border-4 border-primary rounded bg-primary-subtle p-3 mb-3">
                            BAP menandai seluruh pekerjaan dalam PO ini mulai dikerjakan. Belum ada jurnal sampai BAP
                            dimasukkan ke invoice.
                        </div>
                        @foreach ($selected->serviceDetails as $i => $d)
                            <div class="border rounded p-3 mb-3 bap-item">
                                <input type="hidden" name="items[{{ $i }}][service_po_detail_id]"
                                    value="{{ $d->id }}">
                                <input type="hidden" name="items[{{ $i }}][progress_percent]" value="100">
                                <div class="d-flex justify-content-between">
                                    <div><span class="badge bg-primary">{{ $d->category->display_code }}</span>
                                        <strong>{{ $d->description }}</strong></div>
                                    @if ($financial)
                                        <span>Nilai kontrak Rp {{ number_format($d->subtotal, 0, ',', '.') }}</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info-emphasis">Akan mulai dikerjakan</span>
                                    @endif
                                </div>
                                <div class="row g-2 mt-1">
                                    @if ($d->category->requires_cost_center)
                                        <div class="col-md-4"><label>Departemen/Cost Center</label><input required
                                                name="items[{{ $i }}][department_cost_center]"
                                                class="form-control"></div>
                                    @endif
                                    <div class="col">
                                        <label>Catatan</label><input name="items[{{ $i }}][notes]"
                                            class="form-control">
                                    </div>
                                </div>
                                @if ($d->category->requires_datapesanan)
                                    <div class="mt-3 allocation-box" data-index="{{ $i }}"><label
                                            class="fw-semibold">Alokasi Datapesanan (total 100%)</label>
                                        <div class="row g-2 allocation-row">
                                            <div class="col-7"><input required
                                                    name="items[{{ $i }}][allocations][0][datapesanan_code]"
                                                    class="form-control" placeholder="Kode Datapesanan"></div>
                                            <div class="col-4"><input required type="number" min=".0001" max="100"
                                                    step=".0001"
                                                    name="items[{{ $i }}][allocations][0][percentage]"
                                                    class="form-control" value="100"></div>
                                            <div class="col-1"><button type="button"
                                                    class="btn btn-outline-danger remove-allocation">×</button></div>
                                        </div><button type="button"
                                            class="btn btn-sm btn-outline-primary mt-2 add-allocation">+
                                            Datapesanan</button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer bg-white text-end"><button class="btn btn-primary">Mulai Pekerjaan & Simpan BAP</button>
                    </div>
                </form>
            @endif
        </div>
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
                    x.value = ''
                });
                e.target.before(row)
            }
            if (e.target.classList.contains('remove-allocation')) {
                const box = e.target.closest('.allocation-box');
                if (box.querySelectorAll('.allocation-row').length > 1) e.target.closest('.allocation-row').remove()
            }
        });
    </script>
@endpush
