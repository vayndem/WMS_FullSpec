@php($editing = isset($opname) && $opname->exists)
@php($selected = $editing ? $opname->details->keyBy('bahan_id') : collect())
<form id="opname-form" data-autosave data-autosave-key="stock-opname-{{ $editing ? 'edit-' . $opname->id : 'create' }}"
    action="{{ $editing ? route('stock-opname.update', $opname) : route('stock-opname.store') }}" method="POST">
    @csrf @if ($editing)
        @method('PUT')
    @endif
    <div class="row g-3 mb-4">
        <div class="col-md-4"><label class="form-label fw-semibold">Nomor Opname</label><input
                class="form-control bg-white" name="number"
                value="{{ $editing ? $opname->number : $documentNumber }}" readonly></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Gudang</label><select id="warehouse" class="form-select"
                name="warehouse_id" required data-app-picker data-placeholder="Cari gudang opname...">
                <option value="">Pilih gudang</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected($editing && $opname->warehouse_id == $warehouse->id)>{{ $warehouse->nama }}</option>
                @endforeach
            </select></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Waktu cut-off</label><input class="form-control"
                type="datetime-local" name="cutoff_at"
                value="{{ $editing ? $opname->cutoff_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}"
                required></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Catatan</label><input class="form-control"
                name="notes" value="{{ $editing ? $opname->notes : '' }}"></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Pilih</th>
                    <th>Barang</th>
                    <th>Stok Sistem</th>
                    <th>Fisik</th>
                    <th>Alasan jika selisih</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($materials as $material)
                    @php($detail = $selected->get($material->id))
                    <tr class="material-row d-none" data-warehouse="{{ $material->tipe_gudang }}">
                        <td><input class="form-check-input item-check" type="checkbox" @checked($detail)>
                        </td>
                        <td><strong>{{ $material->nama }}</strong><small
                                class="d-block text-muted">{{ $material->tipeBarang->katnama ?? '-' }}</small><input
                                class="item-input" type="hidden" data-name="bahan_id" value="{{ $material->id }}"
                                disabled></td>
                        <td>{{ number_format($material->stok_onhand, 6, ',', '.') }} {{ $material->satuan }}</td>
                        <td><input class="form-control item-input" type="number" min="0" step=".000001"
                                data-name="physical_quantity"
                                value="{{ $detail?->physical_quantity ?? $material->stok_onhand }}" disabled required>
                        </td>
                        <td><input class="form-control item-input" data-name="reason" value="{{ $detail?->reason }}"
                                disabled placeholder="Rusak/hilang/koreksi"></td>
                        <td><input class="form-control item-input" data-name="notes" value="{{ $detail?->notes }}"
                                disabled></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end gap-2"><a href="{{ route('stock-opname.index') }}"
            class="btn btn-light">Batal</a><button class="btn btn-primary">Simpan Draft</button></div>
</form>
@push('scripts')
    <script>
        $(function() {
            function rows() {
                const w = $('#warehouse').val();
                $('.material-row').addClass('d-none');
                $(`.material-row[data-warehouse="${w}"]`).removeClass('d-none')
            }

            function names() {
                let i = 0;
                $('.material-row:not(.d-none)').each(function() {
                    const c = $(this).find('.item-check').is(':checked');
                    $(this).find('.item-input').prop('disabled', !c).each(function() {
                        const key = $(this).data('name');
                        this.name = c ? `items[${i}][${key}]` : ''
                    });
                    if (c) i++
                })
            }
            $('#warehouse').on('change', function() {
                $('.item-check').prop('checked', false);
                rows();
                names()
            });
            $(document).on('change', '.item-check', names);
            rows();
            names();
            $('#opname-form').on('submit', function(e) {
                e.preventDefault();
                names();
                if (!$('.item-check:checked').length) {
                    AppAlert.warning('Pilih minimal satu barang.');
                    return
                }
                $.ajax({
                    url: this.action,
                    type: 'POST',
                    data: $(this).serialize()
                }).done(r => {
                    AppAlert.success(r.message).then(() => location.href =
                        "{{ route('stock-opname.index') }}")
                }).fail(x => AppAlert.ajaxError(x))
            });
        });
    </script>
@endpush
