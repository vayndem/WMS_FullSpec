<style>
    #createModal .input-group-text,
    #createModal .form-control {
        height: 42px;
    }

    #createModal select.form-control {
        height: 42px !important;
    }
</style>

<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm"
                        style="width: 42px; height: 42px;">
                        <i class="fa-solid fa-cart-flatbed fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Form Pengajuan Request Barang</h5>
                        <small class="text-white-50">Lengkapi detail pengajuan bahan atau barang kebutuhan
                            perusahaan</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white  opacity-8" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="formStoreRequest" action="{{ route('request.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="fw-bold text-dark small text-uppercase mb-1">Nomor Request</label>
                            <input type="text" name="no_request" class="form-control bg-white"
                                value="{{ $documentNumber }}" readonly>
                            <small class="text-muted">Nomor sudah disiapkan otomatis dan tidak dapat diubah.</small>
                        </div>
                    </div>
                    <div id="items-container">
                        <div class="item-row card border-0 shadow-sm mb-3 rounded-lg overflow-hidden">
                            <div class="card-body p-4 bg-white">
                                <div class="row mb-3">
                                    <div class="col-lg-8 mb-2 mb-lg-0">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">
                                            Nama Barang / Bahan <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="d-flex">
                                                <span class="input-group-text bg-white border-right-0"><i
                                                        class="fa-solid fa-box text-muted"></i></span>
                                            </div>
                                            <input type="hidden" name="items[0][bahan_id]" class="input-bahan-id">
                                            <input type="text" name="items[0][nama_barang]"
                                                class="form-control border-left-0 input-nama-barang bg-white"
                                                placeholder="Ketik nama barang baru atau cari dari master..." required>
                                            <div class="d-flex">
                                                <button type="button"
                                                    class="btn btn-outline-info btn-buka-modal-master px-3"
                                                    title="Cari dari Master Bahan">
                                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Master
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-secondary btn-reset-master" title="Reset"
                                                    style="display:none;">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">Jumlah Minta
                                            <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="d-flex">
                                                <span class="input-group-text bg-white border-right-0"><i
                                                        class="fa-solid fa-hashtag text-muted"></i></span>
                                            </div>
                                            <input type="number" step="any" min="0.01"
                                                name="items[0][jumlah_minta]"
                                                class="form-control border-left-0 input-jumlah-minta" placeholder="0"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xl-3 col-md-4 mb-3">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">Kategori
                                            Bahan <span class="text-danger">*</span></label>
                                        <select name="items[0][kategori]" class="form-select input-kategori" required
                                            data-app-picker data-placeholder="Cari kategori bahan...">
                                            <option value="">-- Pilih --</option>
                                            @foreach ($kategoris as $k)
                                                <option value="{{ $k->id }}">{{ $k->katnama }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="items[0][tipe_barang]" class="input-tipe-barang">
                                    </div>
                                    <div class="col-xl-2 col-md-4 mb-3">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">Satuan Utama
                                            <span class="text-danger">*</span></label>
                                        <input type="text" name="items[0][satuan]" class="form-control input-satuan"
                                            placeholder="PCS / KG" required>
                                    </div>
                                    <div class="col-xl-3 col-md-4 mb-3">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">Tipe Gudang
                                            <span class="text-danger">*</span></label>
                                        <select name="items[0][tipe_gudang]" class="form-select input-tipe-gudang"
                                            required data-app-picker data-placeholder="Cari gudang...">
                                            <option value="">-- Pilih --</option>
                                            @foreach ($gudangs as $g)
                                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xl-1 col-md-2 mb-3">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">Isi/Utama</label>
                                        <input type="number" step="any" name="items[0][berat_kecil]"
                                            value="1.0" class="form-control input-berat-kecil"
                                            title="Jumlah satuan kecil dalam 1 satuan utama">
                                    </div>
                                    <div class="col-xl-1 col-md-2 mb-3">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">Sat. Kecil</label>
                                        <input type="text" name="items[0][satuan_kecil]"
                                            class="form-control input-satuan-kecil" placeholder="Opsional">
                                    </div>
                                    <div class="col-xl-2 col-md-4 mb-3">
                                        <label class="fw-bold text-dark small text-uppercase mb-1">Keterangan</label>
                                        <div class="d-flex">
                                            <input type="text" name="items[0][keterangan]"
                                                class="form-control input-keterangan me-2" placeholder="Spesifikasi">
                                            <button type="button" class="btn btn-outline-danger shadow-sm px-3"
                                                onclick="window.removeItem(this)" title="Hapus Item"
                                                style="height: 42px;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="collapse mb-3" id="materialPickerCanvas">
                        <div class="create-section material-picker-panel">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>
                                        Pilih Barang dari Master
                                    </h6>
                                    <small class="text-muted">Cari lalu pilih barang untuk baris request yang
                                        aktif.</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="collapse"
                                    data-bs-target="#materialPickerCanvas">
                                    <i class="fa-solid fa-xmark me-1"></i>Tutup
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped w-100 mb-0"
                                    id="table-modal-master-bahan">
                                    <thead>
                                        <tr>
                                            <th>Nama Bahan</th>
                                            <th>Satuan</th>
                                            <th width="90" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($bahans as $b)
                                            <tr>
                                                <td class="fw-bold align-middle">{{ $b->nama }}</td>
                                                <td class="align-middle text-secondary">{{ $b->satuan ?? '-' }}</td>
                                                <td class="text-center align-middle">
                                                    <button type="button"
                                                        class="btn btn-sm btn-success btn-pilih-master-item shadow-sm"
                                                        data-id="{{ $b->id }}" data-nama="{{ $b->nama }}"
                                                        data-kategori="{{ $b->kategori }}"
                                                        data-satuan="{{ $b->satuan }}"
                                                        data-tipegudang="{{ $b->tipe_gudang }}"
                                                        data-tipebarang="{{ $b->tipe_barang }}"
                                                        data-beratkecil="{{ $b->berat_kecil }}"
                                                        data-satuankecil="{{ $b->satuan_kecil }}"
                                                        data-keterangan="{{ $b->keterangan_bahan }}">
                                                        <i class="fa-solid fa-check me-1"></i>Pilih
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary fw-bold shadow-sm px-4"
                            onclick="window.addItemRow()">
                            <i class="fa-solid fa-plus me-1"></i> Tambah Item Request
                        </button>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 px-4 py-3">
                    <button type="button" class="btn btn-light border px-4 fw-bold"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-bold" id="btnSubmitRequest">
                        <i class="fa-solid fa-paper-plane me-2"></i> Kirim Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.itemIdx = 1;
    let currentRowTarget = null;

    $(document).off('change', '.input-kategori').on('change', '.input-kategori', function() {
        let val = $(this).val();
        $(this).closest('.item-row').find('.input-tipe-barang').val(val);
    });

    window.addItemRow = function() {
        let container = document.getElementById('items-container');
        let newRow = document.createElement('div');
        newRow.className = 'item-row card border-0 shadow-sm mb-3 rounded-lg overflow-hidden';
        newRow.innerHTML = `
            <div class="card-body p-4 bg-white">
                <div class="row mb-3">
                    <div class="col-lg-8 mb-2 mb-lg-0">
                        <label class="fw-bold text-dark small text-uppercase mb-1">
                            Nama Barang / Bahan <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <div class="d-flex">
                                <span class="input-group-text bg-white border-right-0"><i class="fa-solid fa-box text-muted"></i></span>
                            </div>
                            <input type="hidden" name="items[${window.itemIdx}][bahan_id]" class="input-bahan-id">
                            <input type="text" name="items[${window.itemIdx}][nama_barang]" class="form-control border-left-0 input-nama-barang bg-white" placeholder="Ketik nama barang baru atau cari dari master..." required>
                            <div class="d-flex">
                                <button type="button" class="btn btn-outline-info btn-buka-modal-master px-3" title="Cari dari Master Bahan">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Master
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-reset-master" title="Reset" style="display:none;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label class="fw-bold text-dark small text-uppercase mb-1">Jumlah Minta <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="d-flex">
                                <span class="input-group-text bg-white border-right-0"><i class="fa-solid fa-hashtag text-muted"></i></span>
                            </div>
                            <input type="number" step="any" min="0.01" name="items[${window.itemIdx}][jumlah_minta]" class="form-control border-left-0 input-jumlah-minta" placeholder="0" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-3 col-md-4 mb-3">
                        <label class="fw-bold text-dark small text-uppercase mb-1">Kategori Bahan <span class="text-danger">*</span></label>
                        <select name="items[${window.itemIdx}][kategori]" class="form-select input-kategori" required
                            data-app-picker data-placeholder="Cari kategori bahan...">
                            <option value="">-- Pilih --</option>
                            @foreach ($kategoris as $k)
                                <option value="{{ $k->id }}">{{ $k->katnama }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="items[${window.itemIdx}][tipe_barang]" class="input-tipe-barang">
                    </div>
                    <div class="col-xl-2 col-md-4 mb-3">
                        <label class="fw-bold text-dark small text-uppercase mb-1">Satuan Utama <span class="text-danger">*</span></label>
                        <input type="text" name="items[${window.itemIdx}][satuan]" class="form-control input-satuan" placeholder="PCS / KG" required>
                    </div>
                    <div class="col-xl-3 col-md-4 mb-3">
                        <label class="fw-bold text-dark small text-uppercase mb-1">Tipe Gudang <span class="text-danger">*</span></label>
                        <select name="items[${window.itemIdx}][tipe_gudang]" class="form-select input-tipe-gudang" required
                            data-app-picker data-placeholder="Cari gudang...">
                            <option value="">-- Pilih --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-1 col-md-2 mb-3">
                        <label class="fw-bold text-dark small text-uppercase mb-1">Isi/Utama</label>
                        <input type="number" step="any" name="items[${window.itemIdx}][berat_kecil]" value="1.0" class="form-control input-berat-kecil" title="Jumlah satuan kecil dalam 1 satuan utama">
                    </div>
                    <div class="col-xl-1 col-md-2 mb-3">
                        <label class="fw-bold text-dark small text-uppercase mb-1">Sat. Kecil</label>
                        <input type="text" name="items[${window.itemIdx}][satuan_kecil]" class="form-control input-satuan-kecil" placeholder="Opsional">
                    </div>
                    <div class="col-xl-2 col-md-4 mb-3">
                        <label class="fw-bold text-dark small text-uppercase mb-1">Keterangan</label>
                        <div class="d-flex">
                            <input type="text" name="items[${window.itemIdx}][keterangan]" class="form-control input-keterangan me-2" placeholder="Spesifikasi">
                            <button type="button" class="btn btn-outline-danger shadow-sm px-3" onclick="window.removeItem(this)" title="Hapus Item" style="height: 42px;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newRow);
        window.itemIdx++;
    };

    window.removeItem = function(btn) {
        let rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            btn.closest('.item-row').remove();
        }
    };

    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#table-modal-master-bahan')) {
            $('#table-modal-master-bahan').DataTable();
        }

        $(document).off('click', '.btn-buka-modal-master').on('click', '.btn-buka-modal-master', function() {
            currentRowTarget = $(this).closest('.item-row');
            bootstrap.Collapse.getOrCreateInstance('#materialPickerCanvas', {
                toggle: false
            }).show();
            $('#table-modal-master-bahan').DataTable().columns.adjust();
            document.querySelector('#materialPickerCanvas')?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        });

        $(document).off('click', '.btn-pilih-master-item').on('click', '.btn-pilih-master-item', function() {
            if (currentRowTarget) {
                let btn = $(this);

                currentRowTarget.find('.input-bahan-id').val(btn.data('id'));
                currentRowTarget.find('.input-nama-barang').val(btn.data('nama')).prop('readonly',
                    true);

                if (btn.data('kategori')) {
                    currentRowTarget.find('.input-kategori').val(btn.data('kategori')).trigger(
                        'change');
                    currentRowTarget.find('.input-tipe-barang').val(btn.data('kategori'));
                }
                if (btn.data('satuan')) currentRowTarget.find('.input-satuan').val(btn.data('satuan'));
                if (btn.data('tipegudang')) currentRowTarget.find('.input-tipe-gudang').val(btn.data(
                    'tipegudang')).trigger('change');
                if (btn.data('beratkecil')) currentRowTarget.find('.input-berat-kecil').val(btn.data(
                    'beratkecil'));
                if (btn.data('satuankecil')) currentRowTarget.find('.input-satuan-kecil').val(btn.data(
                    'satuankecil'));
                if (btn.data('keterangan')) currentRowTarget.find('.input-keterangan').val(btn.data(
                    'keterangan'));

                currentRowTarget.find('.btn-reset-master').show();
                bootstrap.Collapse.getOrCreateInstance('#materialPickerCanvas', {
                    toggle: false
                }).hide();
            }
        });

        $(document).off('click', '.btn-reset-master').on('click', '.btn-reset-master', function() {
            let row = $(this).closest('.item-row');
            row.find('.input-bahan-id').val('');
            row.find('.input-nama-barang').val('').prop('readonly', false);
            row.find('.input-kategori').val('').trigger('change');
            row.find('.input-tipe-barang').val('');
            row.find('.input-satuan').val('');
            row.find('.input-tipe-gudang').val('').trigger('change');
            row.find('.input-berat-kecil').val('1.0');
            row.find('.input-satuan-kecil').val('');
            row.find('.input-keterangan').val('');
            $(this).hide();
        });

        $('#formStoreRequest').submit(function(e) {
            e.preventDefault();
            let btn = $('#btnSubmitRequest');
            btn.prop('disabled', true).html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Mengirim...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#createModal').modal('hide');
                    if ($.fn.DataTable.isDataTable('#table-request')) {
                        $('#table-request').DataTable().draw();
                    }
                    AppAlert.auto(res.message);
                },
                error: function(err) {
                    AppAlert.auto(err.responseJSON?.message || 'Gagal menyimpan request.');
                },
                complete: function() {
                    btn.prop('disabled', false).html(
                        '<i class="fa-solid fa-paper-plane me-2"></i> Kirim Request');
                }
            });
        });
    });
</script>
