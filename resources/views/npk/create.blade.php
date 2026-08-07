<div class="modal fade" id="modal-create-npk" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                    <i class="fa-solid fa-boxes-packing me-2"></i>Buat Pengeluaran Barang (NPK) Baru
                </h5>
                <button type="button" class="btn-close btn-close-white  opacity-100" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-store-npk" action="{{ route('npk.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="create-section">
                        <div class="create-section__title"><i class="fa-solid fa-clipboard-list"></i>Informasi
                            Pengeluaran</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Kode NPK</label>
                                <input type="text" name="kode" class="form-control" value="{{ $documentNumber }}"
                                    readonly>
                                <small class="text-muted">Format: NPK + tanggal + nomor urut.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Kode Pesanan</label>
                                <input type="text" name="kode_datapesanan" class="form-control"
                                    placeholder="Masukkan kode pesanan (opsional)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Tanggal Transaksi <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Pilih Barang <span
                                        class="text-danger">*</span></label>
                                <select name="id_barang" id="npk_bahan" class="form-select" required data-app-picker
                                    data-placeholder="Cari nama barang, kategori, atau satuan...">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach ($bahans as $bahan)
                                        <option value="{{ $bahan->id }}" data-unit="{{ $bahan->satuan }}"
                                            data-small-unit="{{ $bahan->hasSmallUnit() ? $bahan->satuan_kecil : '' }}"
                                            data-factor="{{ $bahan->hasSmallUnit() ? $bahan->berat_kecil : 1 }}"
                                            data-stocks='@json($bahan->stokGudangs->pluck("stok_tersedia", "gudang_id"))'
                                            data-subtitle="{{ $bahan->kategoriBahan->katnama ?? 'Kategori belum ditentukan' }}"
                                            data-meta="Stok {{ number_format((float) $bahan->stok_onhand, 6, ',', '.') }} {{ $bahan->satuan }}{{ $bahan->hasSmallUnit() ? ' · 1 ' . $bahan->satuan . ' = ' . number_format((float) $bahan->berat_kecil, 6, ',', '.') . ' ' . $bahan->satuan_kecil : '' }}"
                                            data-search="{{ $bahan->satuan }} {{ $bahan->satuan_kecil }}">
                                            {{ $bahan->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Gudang Asal</label>
                                <select name="id_gudang_asal" id="npk_gudang" class="form-select" data-app-picker
                                    data-placeholder="Cari gudang asal...">
                                    <option value="">-- Pilih Gudang Asal --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}" data-subtitle="Lokasi sumber stok">
                                            {{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                                <small id="npk_stock_help" class="text-muted">Pilih gudang dan barang untuk melihat saldo.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Gudang Tujuan</label>
                                <select name="id_gudang_tujuan" class="form-select" data-app-picker
                                    data-placeholder="Cari gudang tujuan...">
                                    <option value="">-- Pilih Gudang Tujuan --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}" data-subtitle="Lokasi tujuan pemakaian">
                                            {{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Jumlah Barang <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="any" name="jumlah" class="form-control" placeholder="0"
                                    required>
                                <small id="npk_unit_help" class="text-muted">Pilih barang untuk melihat satuan
                                    NPK.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Status Transaksi <span
                                        class="text-danger">*</span></label>
                                <select name="close" class="form-control" required>
                                    <option value="0">Draft (Belum Potong Stok & COA)</option>
                                    <option value="1">Keluar (Potong Stok & Catat COA)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Operator / Penanggung
                                    Jawab</label>
                                <input type="text" name="operator" class="form-control" placeholder="Nama operator">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="1" placeholder="Catatan tambahan"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3 px-4">
                    <button type="button" class="btn btn-light border fw-bold px-4"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btn-submit-npk">
                        <i class="fa-solid fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function refreshNpkUnit() {
        const option = document.querySelector('#npk_bahan')?.selectedOptions[0];
        const help = document.querySelector('#npk_unit_help');
        if (!option || !help) return;
        const small = option.dataset.smallUnit;
        const base = option.dataset.unit || '';
        const factor = Number(option.dataset.factor || 1);
        help.textContent = small ?
            `Input dalam ${small}. Konversi: 1 ${base} = ${factor.toLocaleString('id-ID')} ${small}.` :
            `Input dalam satuan utama ${base}.`;
    }
    document.querySelector('#npk_bahan')?.addEventListener('change', refreshNpkUnit);
    refreshNpkUnit();
    function refreshNpkStock() {
        const option = document.querySelector('#npk_bahan')?.selectedOptions[0];
        const warehouse = document.querySelector('#npk_gudang')?.value;
        const help = document.querySelector('#npk_stock_help');
        if (!option || !warehouse || !help) return;
        const stocks = JSON.parse(option.dataset.stocks || '{}');
        help.textContent = `Saldo gudang: ${Number(stocks[warehouse] || 0).toLocaleString('id-ID')} ${option.dataset.unit || ''}`;
    }
    document.querySelector('#npk_bahan')?.addEventListener('change', refreshNpkStock);
    document.querySelector('#npk_gudang')?.addEventListener('change', refreshNpkStock);

    $('#form-store-npk').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = $('#btn-submit-npk');

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#modal-create-npk').modal('hide');
                $('#table-npk').DataTable().ajax.reload();
                AppAlert.auto(response.message);
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i> Simpan');
                AppAlert.ajaxError(xhr);
            }
        });
    });
</script>
