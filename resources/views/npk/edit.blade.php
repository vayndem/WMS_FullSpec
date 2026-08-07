<div class="modal fade" id="modal-edit-npk" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white py-3 px-4">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Pengeluaran Barang ({{ $npk->kode }})
                </h5>
                <button type="button" class="btn-close btn-close-white  opacity-100" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-update-npk" action="{{ route('npk.update', $npk->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 mb-0 bg-white rounded-lg">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Kode NPK</label>
                                <input type="text" name="kode" class="form-control" value="{{ $npk->kode }}"
                                    readonly>
                                <small class="text-muted">Kode tidak dapat diubah.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Kode Pesanan</label>
                                <input type="text" name="kode_datapesanan" class="form-control"
                                    value="{{ $npk->kode_datapesanan }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Tanggal Transaksi <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="{{ $npk->tanggal }}"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Pilih Barang <span
                                        class="text-danger">*</span></label>
                                <select name="id_barang" id="npk_edit_bahan" class="form-select" required
                                    data-app-picker data-placeholder="Cari barang...">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach ($bahans as $bahan)
                                        <option value="{{ $bahan->id }}" data-unit="{{ $bahan->satuan }}"
                                            data-small-unit="{{ $bahan->hasSmallUnit() ? $bahan->satuan_kecil : '' }}"
                                            data-factor="{{ $bahan->hasSmallUnit() ? $bahan->berat_kecil : 1 }}"
                                            @selected($npk->id_barang == $bahan->id)>
                                            {{ $bahan->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Gudang Asal</label>
                                <select name="id_gudang_asal" class="form-select" data-app-picker
                                    data-placeholder="Cari gudang asal...">
                                    <option value="">-- Pilih Gudang Asal --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}"
                                            {{ $npk->id_gudang_asal == $gudang->id ? 'selected' : '' }}>
                                            {{ $gudang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Gudang Tujuan</label>
                                <select name="id_gudang_tujuan" class="form-select" data-app-picker
                                    data-placeholder="Cari gudang tujuan...">
                                    <option value="">-- Pilih Gudang Tujuan --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}"
                                            {{ $npk->id_gudang_tujuan == $gudang->id ? 'selected' : '' }}>
                                            {{ $gudang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Jumlah Barang <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="any" name="jumlah" class="form-control"
                                    value="{{ $npk->jumlah }}" required>
                                <small id="npk_edit_unit_help" class="text-muted"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Status Transaksi <span
                                        class="text-danger">*</span></label>
                                <select name="close" class="form-control" required>
                                    <option value="0" {{ (int) $npk->close === 0 ? 'selected' : '' }}>Draft (Belum
                                        Potong Stok & COA)</option>
                                    <option value="1" {{ (int) $npk->close === 1 ? 'selected' : '' }}>Keluar
                                        (Potong Stok & Catat COA)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Operator / Penanggung
                                    Jawab</label>
                                <input type="text" name="operator" class="form-control"
                                    value="{{ $npk->operator }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="1">{{ $npk->keterangan }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3 px-4">
                    <button type="button" class="btn btn-light border fw-bold px-4"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info fw-bold px-4 shadow-sm text-white" id="btn-update-npk">
                        <i class="fa-solid fa-rotate me-1"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function refreshEditNpkUnit() {
        const option = document.querySelector('#npk_edit_bahan')?.selectedOptions[0];
        const help = document.querySelector('#npk_edit_unit_help');
        if (!option || !help) return;
        const small = option.dataset.smallUnit;
        const base = option.dataset.unit || '';
        const factor = Number(option.dataset.factor || 1);
        help.textContent = small ?
            `Input dalam ${small}. Konversi: 1 ${base} = ${factor.toLocaleString('id-ID')} ${small}.` :
            `Input dalam satuan utama ${base}.`;
    }
    document.querySelector('#npk_edit_bahan')?.addEventListener('change', refreshEditNpkUnit);
    refreshEditNpkUnit();

    $('#form-update-npk').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = $('#btn-update-npk');

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Memperbarui...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#modal-edit-npk').modal('hide');
                $('#table-npk').DataTable().ajax.reload();
                AppAlert.auto(response.message);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(
                    '<i class="fa-solid fa-rotate me-1"></i> Perbarui');
                AppAlert.ajaxError(xhr);
            }
        });
    });
</script>
