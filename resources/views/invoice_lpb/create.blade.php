<div class="modal fade" id="createInvoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>Buat Invoice Supplier Baru
                </h5>
                <button type="button" class="btn-close btn-close-white  opacity-100" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-store-invoice" action="{{ route('invoice-lpb.store') }}" method="POST" data-autosave
                data-autosave-key="invoice-lpb-create">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white rounded-lg">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">1. Pilih Supplier <span
                                        class="text-danger">*</span></label>
                                <select class="d-none" name="kode_supplier" id="modal_kode_supplier">
                                    <option value=""></option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" data-name="{{ $supplier->nama }}"
                                            data-phone="{{ $supplier->telp }}" data-address="{{ $supplier->alamat }}">
                                            {{ $supplier->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="dropdown" id="supplier-picker">
                                    <button class="form-select text-start" type="button" data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside" id="supplier-picker-button">
                                        Cari dan pilih supplier
                                    </button>
                                    <div class="dropdown-menu w-100 p-2 shadow">
                                        <input class="form-control mb-2" id="supplier-search"
                                            placeholder="Ketik nama, telepon, atau alamat..." autocomplete="off">
                                        <div id="supplier-results" class="overflow-auto" style="max-height: 260px">
                                        </div>
                                    </div>
                                </div>
                                <div id="supplier-preview" class="border rounded-3 bg-body-tertiary p-3 mt-2 d-none">
                                    <div class="fw-bold text-primary supplier-name"></div>
                                    <small class="text-muted supplier-meta"></small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">2. Pilih LPB / BAP Supplier <span
                                        class="text-danger">*</span></label>
                                <select class="d-none" name="lpb_ids[]" id="modal_select_id_lpb" multiple disabled>
                                    @foreach ($lpbs as $lpb)
                                        <option value="{{ $lpb->id }}" data-code="{{ $lpb->id_lpb }}"
                                            data-supplier="{{ $lpb->pembelian->supplier_id ?? '' }}"
                                            data-type="{{ $lpb->document_type === 'SERVICE_BAP' ? 'BAP Jasa' : 'LPB Barang' }}"
                                            data-date="{{ $lpb->tanggal?->format('d-m-Y') }}"
                                            data-po="{{ $lpb->no_po }}">
                                            {{ $lpb->document_type === 'SERVICE_BAP' ? '[BAP JASA]' : '[LPB BARANG]' }}
                                            {{ $lpb->id_lpb }} · {{ $lpb->tanggal?->format('d-m-Y') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="dropdown" id="receipt-picker">
                                    <button class="form-select text-start" type="button" data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside" id="receipt-picker-button" disabled>
                                        Pilih supplier terlebih dahulu
                                    </button>
                                    <div class="dropdown-menu w-100 p-2 shadow">
                                        <input class="form-control mb-2" id="receipt-search"
                                            placeholder="Cari nomor LPB, BAP, PO, atau tanggal..." autocomplete="off">
                                        <div id="receipt-results" class="overflow-auto" style="max-height: 300px"></div>
                                    </div>
                                </div>
                                <div class="form-text" id="receipt-help">Pilih supplier terlebih dahulu.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">No. Invoice Supplier
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="no_invoice"
                                    placeholder="Masukkan Nomor Invoice" required>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="fw-bold text-dark small text-uppercase">Tanggal Invoice <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="fw-bold text-dark small text-uppercase">Deadline
                                    Pembayaran</label>
                                <input type="date" class="form-control" name="tgl_deadline_pembayaran">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white rounded-lg">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Preview Item LPB / BAP Terpilih
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="modal-table-lpb-items">
                                <thead class="bg-light text-uppercase font-size-12">
                                    <tr>
                                        <th width="4%" class="text-center">#</th>
                                        <th>Nama Bahan</th>
                                        <th width="15%" class="text-center">Qty Diterima</th>
                                        <th width="20%" class="text-end">Harga Satuan</th>
                                        <th width="20%" class="text-end">Total Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Pilih Nomor LPB
                                            terlebih
                                            dahulu.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card border-0 shadow-sm p-3 bg-white rounded-lg h-100">
                                <label class="fw-bold text-dark small text-uppercase">Catatan / Note</label>
                                <textarea class="form-control" name="note" rows="5" placeholder="Catatan opsional untuk invoice ini..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm p-3 bg-white rounded-lg">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-muted">Sub Total:</span>
                                    <span class="fw-bold h6 text-dark mb-0" id="text_sub_total">Rp 0</span>
                                </div>
                                <div class="mb-3 mb-2 d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_ppn"
                                            id="is_ppn" value="1">
                                        <label class="form-check-label fw-bold" for="is_ppn">Gunakan PPN
                                            (11%)</label>
                                    </div>
                                    <span class="fw-bold text-muted" id="text_ppn">Rp 0</span>
                                </div>
                                <div class="mb-3 mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label fw-bold py-0">Diskon:</label>
                                    <div class="col-sm-8">
                                        <input type="number" step="any" min="0"
                                            class="form-control form-control-sm text-end fw-bold" name="diskon"
                                            id="input_diskon" value="0">
                                    </div>
                                </div>
                                <div class="mb-3 mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label fw-bold py-0">Ongkir:</label>
                                    <div class="col-sm-8">
                                        <input type="number" step="any" min="0"
                                            class="form-control form-control-sm text-end fw-bold" name="ongkir"
                                            id="input_ongkir" value="0">
                                    </div>
                                </div>
                                <div class="alert alert-info mb-2" data-keep-alert>PPh 23 dicatat saat pembayaran,
                                    bukan saat invoice diterima.</div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="fw-bold text-dark mb-0">Grand Total:</h5>
                                    <h5 class="fw-bold text-primary mb-0" id="text_grand_total">Rp 0</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3 px-4">
                    <button type="button" class="btn btn-light border fw-bold px-4"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btn-submit-invoice">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let currentSubTotal = 0;
        let currentSupplier = '';
        const esc = value => $('<div>').text(value ?? '').html();

        function renderSuppliers(term = '') {
            term = term.toLowerCase();
            const rows = $('#modal_kode_supplier option[value!=""]').filter(function() {
                const option = $(this);
                return `${option.data('name')} ${option.data('phone')} ${option.data('address')}`
                    .toLowerCase().includes(term);
            }).map(function() {
                const option = $(this);
                return `<button type="button" class="dropdown-item rounded-2 py-2 supplier-choice"
                    data-id="${this.value}">
                    <span class="d-block fw-semibold">${esc(option.data('name'))}</span>
                    <small class="text-muted text-wrap">${esc(option.data('phone') || '-')} · ${esc(option.data('address') || '-')}</small>
                </button>`;
            }).get();
            $('#supplier-results').html(rows.length ? rows.join('') :
                '<div class="text-muted text-center py-3">Supplier tidak ditemukan.</div>');
        }

        function renderReceipts(term = '') {
            term = term.toLowerCase();
            const selected = ($('#modal_select_id_lpb').val() || []).map(String);
            const rows = $('#modal_select_id_lpb option[value!=""]').filter(function() {
                const option = $(this);
                if (String(option.data('supplier')) !== String(currentSupplier)) return false;
                return `${option.data('code')} ${option.data('type')} ${option.data('date')} ${option.data('po')}`
                    .toLowerCase().includes(term);
            }).map(function() {
                const option = $(this);
                const checked = selected.includes(String(this.value)) ? 'checked' : '';
                return `<label class="dropdown-item rounded-2 py-2 d-flex gap-2 align-items-start receipt-choice">
                    <input class="form-check-input mt-1 receipt-check" type="checkbox" value="${this.value}" ${checked}>
                    <span class="flex-grow-1">
                        <span class="d-flex justify-content-between gap-2">
                            <strong>${esc(option.data('code'))}</strong>
                            <span class="badge bg-primary-subtle text-primary">${esc(option.data('type'))}</span>
                        </span>
                        <small class="text-muted">Tanggal ${esc(option.data('date'))} · PO ${esc(option.data('po') || '-')}</small>
                    </span>
                </label>`;
            }).get();
            $('#receipt-results').html(rows.length ? rows.join('') :
                '<div class="text-muted text-center py-3">Tidak ada LPB/BAP yang cocok.</div>');
        }

        renderSuppliers();
        $('#supplier-search').on('input', function() {
            renderSuppliers(this.value);
        });
        $('#receipt-search').on('input', function() {
            renderReceipts(this.value);
        });
        $('#supplier-picker').on('shown.bs.dropdown', () => $('#supplier-search').trigger('focus'));
        $('#receipt-picker').on('shown.bs.dropdown', function() {
            renderReceipts($('#receipt-search').val());
            $('#receipt-search').trigger('focus');
        });
        $(document).on('click', '.supplier-choice', function() {
            $('#modal_kode_supplier').val($(this).data('id')).trigger('change');
            bootstrap.Dropdown.getOrCreateInstance($('#supplier-picker-button')[0]).hide();
        });
        $(document).on('change', '.receipt-check', function() {
            let selected = ($('#modal_select_id_lpb').val() || []).map(String);
            const value = String(this.value);
            selected = this.checked ? [...new Set([...selected, value])] :
                selected.filter(id => id !== value);
            $('#modal_select_id_lpb').val(selected).trigger('change');
        });

        $('#modal_kode_supplier').on('change', function() {
            currentSupplier = this.value || '';
            $('#modal_select_id_lpb').val(null).trigger('change').prop('disabled', !currentSupplier);
            $('#receipt-picker-button').prop('disabled', !currentSupplier)
                .text(currentSupplier ? 'Pilih LPB / BAP (bisa lebih dari satu)' :
                    'Pilih supplier terlebih dahulu');
            const option = $(this).find(':selected');
            if (currentSupplier) {
                $('#supplier-picker-button').text(option.data('name') || option.text());
                $('#supplier-preview').removeClass('d-none');
                $('#supplier-preview .supplier-name').text(option.data('name') || option.text());
                $('#supplier-preview .supplier-meta').text(
                    `${option.data('phone') || '-'} · ${option.data('address') || '-'}`
                );
                const total = $('#modal_select_id_lpb option').filter(function() {
                    return String($(this).data('supplier')) === String(currentSupplier);
                }).length;
                $('#receipt-help').text(`${total} dokumen LPB/BAP belum ditagih tersedia.`);
            } else {
                $('#supplier-picker-button').text('Cari dan pilih supplier');
                $('#supplier-preview').addClass('d-none');
                $('#receipt-help').text('Pilih supplier terlebih dahulu.');
            }
        });

        function calculateTotals() {
            let isPpn = $('#is_ppn').is(':checked');
            let ppnNominal = isPpn ? Math.round((currentSubTotal * 11) / 100) : 0;
            let diskon = parseFloat($('#input_diskon').val()) || 0;
            let ongkir = parseFloat($('#input_ongkir').val()) || 0;
            let grandTotal = (currentSubTotal + ppnNominal + ongkir) - diskon;

            $('#text_sub_total').text('Rp ' + currentSubTotal.toLocaleString('id-ID'));
            $('#text_ppn').text('Rp ' + ppnNominal.toLocaleString('id-ID'));
            $('#text_grand_total').text('Rp ' + grandTotal.toLocaleString('id-ID'));
        }

        $('#modal_select_id_lpb').on('change', function() {
            let selected = $(this).find(':selected').filter(function() {
                return String(this.value).trim() !== '';
            });
            const count = selected.length;
            $('#receipt-picker-button').text(count ?
                `${count} dokumen dipilih: ${selected.map(function() { return $(this).data('code'); }).get().join(', ')}` :
                (currentSupplier ? 'Pilih LPB / BAP (bisa lebih dari satu)' :
                    'Pilih supplier terlebih dahulu'));
            if (!selected.length) {
                $('#modal-table-lpb-items tbody').html(
                    '<tr><td colspan="5" class="text-center text-muted py-3">Pilih LPB atau BAP terlebih dahulu.</td></tr>'
                );
                currentSubTotal = 0;
                calculateTotals();
                return;
            }

            let suppliers = [...new Set(selected.map(function() {
                return $(this).data('supplier');
            }).get())];
            if (suppliers.length !== 1) {
                AppAlert.warning('Semua LPB harus berasal dari supplier yang sama.');
                $(this).val(null).trigger('change');
                return;
            }
            if (String(suppliers[0]) !== String(currentSupplier)) {
                AppAlert.warning('Dokumen penerimaan tidak sesuai supplier terpilih.');
                $(this).val(null).trigger('change');
                return;
            }
            currentSubTotal = 0;
            let rows = [];
            Promise.all(selected.map(function() {
                return $.getJSON("/invoice-lpb/lpb-detail/" + $(this).data('code'));
            }).get()).then(function(results) {
                results.forEach(function(res) {
                    if (res.success) {
                        currentSubTotal += Number(res.sub_total);
                        $.each(res.items, function(i, item) {
                            rows.push(`<tr>
                                <td class="text-center align-middle">${rows.length + 1}</td>
                                <td class="align-middle fw-bold">${item.nama_bahan}</td>
                                <td class="text-center align-middle fw-bold text-success">${item.jumlah_barang_diterima}</td>
                                <td class="text-end align-middle">Rp ${Number(item.harga).toLocaleString('id-ID')}</td>
                                <td class="text-end align-middle fw-bold">Rp ${Number(item.total_harga).toLocaleString('id-ID')}</td>
                            </tr>`);
                        });

                    }
                });
                $('#modal-table-lpb-items tbody').html(rows.join(''));
                calculateTotals();
            }).catch(function(xhr) {
                AppAlert.ajaxError(xhr);
            });
        });

        $('#is_ppn, #input_diskon, #input_ongkir').on('input change', function() {
            calculateTotals();
        });

        $('#form-store-invoice').on('submit', function(e) {
            e.preventDefault();
            if (!$('#modal_kode_supplier').val()) {
                AppAlert.warning('Supplier wajib dipilih dari dropdown.');
                return;
            }
            if (!$('#modal_select_id_lpb').val()?.length) {
                AppAlert.warning('Pilih minimal satu LPB atau BAP dari supplier tersebut.');
                return;
            }
            let btn = $('#btn-submit-invoice');
            btn.prop('disabled', true).html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(res) {
                    if (res.success) {
                        $('#createInvoiceModal').modal('hide');
                        $('#table-invoice-lpb').DataTable().ajax.reload();
                        AppAlert.auto(res.message);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(
                        '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Invoice');
                    AppAlert.ajaxError(xhr);
                }
            });
        });
    });
</script>
