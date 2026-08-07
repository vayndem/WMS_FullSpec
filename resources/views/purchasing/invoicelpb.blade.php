@extends('layouts.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --bg-secondary: #f8f9fa;
            --border-color-light: #e3e6f0;
            --text-color-muted: #858796;
            --text-color-default: #5a5c69;
            --primary-accent: #4e73df;
            --danger-accent: #e74a3b;
        }

        body.dark,
        body[data-theme="dark"] {
            --bg-secondary: #2c2f33;
            --border-color-light: #4a4d58;
            --text-color-default: #f8f9fc;
        }

        td.details-control {
            text-align: center;
            cursor: pointer;
            width: 30px;
        }

        td.details-control i {
            font-size: 1.2rem;
            transition: transform 0.2s ease-in-out;
            color: var(--primary-accent);
        }

        tr.details td.details-control i {
            color: var(--danger-accent);
        }

        .invoice-detail-wrapper {
            border: 1px solid var(--border-color-light);
            border-radius: .35rem;
            padding: 1rem;
            background-color: var(--bg-secondary);
        }

        .detail-flex-container {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .detail-items-column {
            flex: 2;
            min-width: 300px;
        }

        .detail-financial-column {
            flex: 1;
            min-width: 250px;
            border-left: 1px solid var(--border-color-light);
            padding-left: 1.5rem;
        }

        .detail-section-header {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color-light);
            color: var(--text-color-default);
        }

        .financial-summary .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.9rem;
        }

        .financial-summary .summary-item.total {
            font-weight: bold;
            font-size: 1rem;
            border-top: 1px solid var(--border-color-light);
            padding-top: 0.75rem;
            margin-top: 0.5rem;
        }

        .detail-items-column .table {
            background-color: transparent;
        }

        #selectPoLokal,
        #selectJasa {
            text-align: center;
            text-align-last: center;
        }

        #selectPoLokal option,
        #selectJasa option {
            text-align: left;
        }

        #checkbox_ppn {
            transform: scale(1.4);
            margin-right: 0.75rem !important;
            cursor: default;
        }

        #checkbox_ppn:checked {
            accent-color: var(--primary-accent);
        }

        .pph-operator-group .btn {
            border-color: var(--border-color-light);
            background-color: var(--bg-secondary);
            color: var(--text-color-muted);
            font-weight: bold;
        }

        .pph-operator-group .btn.active {
            background-color: var(--primary-accent);
            color: #fff;
            border-color: var(--primary-accent);
        }

        .select2-container--default .select2-selection--single {
            background-color: #fff !important;
            border: 1px solid #d1d3e2 !important;
            border-radius: 8px !important;
            height: calc(1.5em + .75rem + 2px) !important;
            display: flex !important;
            align-items: center !important;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #6e707e !important;
            line-height: normal !important;
            padding-left: 15px !important;
            padding-right: 20px !important;
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            top: 0 !important;
            right: 10px !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #bac8f3 !important;
            outline: 0 !important;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
        }
    </style>

    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary"><i class="fas fa-file-invoice-dollar me-2"></i>Manajemen
                        Invoice</h3>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#invoiceLpb">
                        <i class="fas fa-plus me-2"></i>Buat Invoice Baru
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-4 filter-card">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 mb-md-0">
                                    <label for="filterPeriode" class="fw-bold">Periode Invoice</label>
                                    <input type="month" id="filterPeriode" class="form-control"
                                        value="{{ date('Y-m') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 mb-md-0">
                                    <label for="filterStatus" class="fw-bold">Status Pembayaran</label>
                                    <select id="filterStatus" class="form-select">
                                        <option value="all">Tampilkan Semua</option>
                                        <option value="0">Siap Bayar</option>
                                        <option value="1">Proses Pembayaran</option>
                                        <option value="2">Lunas</option>
                                        <option value="3">Jatuh Tempo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="invoiceLpbTable" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th>No. Invoice</th>
                                    <th>Tgl. Invoice</th>
                                    <th>No. PO / Jasa</th>
                                    <th style="width: 40%;">Supplier / Pelanggan</th>
                                    <th>Deadline</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Jenis LPB</th>
                                    <th class="text-center" style="width: 50px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoiceLpb" tabindex="-1" role="dialog" aria-labelledby="invoiceLpbLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content modal-content-premium">
                <div class="modal-header modal-header-premium">
                    <h5 class="modal-title fw-bold" id="invoiceLpbLabel"><i
                            class="fas fa-file-invoice me-2"></i>Buat Invoice Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-premium">
                    <form id="invoiceLpbForm">
                        @csrf
                        <div class="row mb-4 justify-content-center">
                            <div class="mb-3 col-md-12 text-center">
                                <label class="fw-bold text-muted d-block mb-2">Tipe Invoice</label>
                                <div class="btn-group btn-group-toggle" data-bs-toggle="buttons">
                                    <label class="btn btn-outline-primary active px-4">
                                        <input type="radio" name="tipe_invoice" id="tipeLpb" value="LPB" checked> <i
                                            class="fas fa-boxes me-1"></i> Invoice LPB
                                    </label>
                                    <label class="btn btn-outline-primary px-4">
                                        <input type="radio" name="tipe_invoice" id="tipeJasa" value="JASA"> <i
                                            class="fas fa-concierge-bell me-1"></i> Invoice Jasa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label for="no_invoice" class="text-muted"><i class="fas fa-hashtag me-2"></i>No
                                    Invoice</label>
                                <input type="text" class="form-control elegant-input" id="no_invoice" name="no_invoice"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="tanggal_nota" class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Tanggal
                                    Nota</label>
                                <input type="date" class="form-control elegant-input" id="tanggal_nota"
                                    name="tanggal_nota" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="tgl_deadline_pembayaran" class="text-muted"><i
                                        class="fas fa-calendar-check me-2"></i>Deadline Pembayaran</label>
                                <input type="date" class="form-control elegant-input" id="tgl_deadline_pembayaran"
                                    name="tgl_deadline_pembayaran" required>
                            </div>
                        </div>
                        <hr>

                        <div id="containerFormLpb">
                            <div class="row mb-3 justify-content-center">
                                <div class="mb-3 col-md-9 col-lg-8">
                                    <label for="selectPoLokal"
                                        class="text-muted fw-bold d-block text-center mb-2"><i
                                            class="fas fa-receipt me-2"></i>Pilih Purchase Order (PO):</label>
                                    <select id="selectPoLokal" name="selected_po_lokal"
                                        class="form-control elegant-input" style="width: 100%;"></select>
                                    <input type="hidden" id="selected_kode_supplier_lokal" name="kode_supplier">
                                </div>
                            </div>
                            <div id="lpbLoadingMessageLokal" class="text-center p-4 mb-3">
                                <p class="mb-0"><i class="fas fa-info-circle me-2"></i>Silakan pilih No. PO untuk
                                    menampilkan daftar LPB.</p>
                            </div>
                            <div id="tableLpbContainerLokal" style="display: none;">
                                <h6 class="text-muted mb-3"><i class="fas fa-list-ul me-2"></i>Pilih Data LPB</h6>
                                <div class="table-responsive">
                                    <table id="tableLpb" class="table table-hover table-striped" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Pilih</th>
                                                <th>ID LPB</th>
                                                <th>Tgl. LPB</th>
                                                <th>Supplier</th>
                                                <th>Tot. Qty</th>
                                                <th>SubTot LPB</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <input type="hidden" id="selected_lpb_ids" name="selected_lpb_ids">
                        </div>

                        <div id="containerFormJasa" style="display: none;">
                            <div class="row mb-3 justify-content-center">
                                <div class="mb-3 col-md-9 col-lg-8">
                                    <label for="selectJasa"
                                        class="text-muted fw-bold d-block text-center mb-2"><i
                                            class="fas fa-concierge-bell me-2"></i>Pilih Dokumen Jasa:</label>
                                    <select id="selectJasa" name="selected_jasa" class="form-control elegant-input"
                                        style="width: 100%;"></select>
                                    <input type="hidden" id="selected_jasa_no" name="selected_jasa_no">
                                </div>
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="sub_total" class="text-muted"><i class="fas fa-coins me-2"></i>Sub Total
                                    Invoice</label>
                                <input type="text" class="form-control elegant-input" id="sub_total" name="sub_total"
                                    value="Rp 0,00" readonly>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="ppn" class="text-muted"><i
                                        class="fas fa-percentage me-2"></i>PPN</label>
                                <div class="d-flex align-items-center">
                                    <input type="checkbox" id="checkbox_ppn" name="checkbox_ppn" class="me-2"
                                        disabled>
                                    <input type="text" class="form-control elegant-input" id="ppn"
                                        name="ppn" value="Rp 0,00" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="diskon_nominal" class="text-muted"><i class="fas fa-tags me-2"></i>Diskon
                                    (Nominal)</label>
                                <input type="text" class="form-control elegant-input" id="diskon_nominal"
                                    name="diskon_nominal" value="0">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="pph" class="text-muted"><i class="fas fa-hand-holding-usd me-2"></i>PPH
                                    (Nominal)</label>
                                <div class="input-group">
                                    <div class="d-flex">
                                        <div class="btn-group btn-group-toggle pph-operator-group" data-bs-toggle="buttons">
                                            <label class="btn btn-light active"><input type="radio" name="pph_operator"
                                                    value="+" checked> +</label>
                                            <label class="btn btn-light"><input type="radio" name="pph_operator"
                                                    value="-"> -</label>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control elegant-input" id="pph"
                                        name="pph" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="ongkir" class="text-muted"><i class="fas fa-truck me-2"></i>Ongkir /
                                    Jasa</label>
                                <input type="text" class="form-control elegant-input" id="ongkir" name="ongkir"
                                    value="0">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="grand_total" class="text-muted"><i class="fas fa-wallet me-2"></i>Grand
                                    Total</label>
                                <input type="text" class="form-control elegant-input" id="grand_total"
                                    name="grand_total" value="Rp 0,00" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label for="note_lokal" class="text-muted"><i class="fas fa-sticky-note me-2"></i>Catatan
                                    (Opsional):</label>
                                <textarea class="form-control elegant-input" id="note_lokal" name="note" rows="3"
                                    placeholder="Tambahkan catatan untuk invoice ini..."></textarea>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal"
                                    style="border-radius: 8px; font-weight: bold;"><i
                                        class="fas fa-times me-2"></i>Tutup</button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary w-100"
                                    style="border-radius: 8px; font-weight: bold;"><i class="fas fa-save me-2"></i>Simpan
                                    Invoice</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAllItemsModal" tabindex="-1" role="dialog"
        aria-labelledby="editAllItemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAllItemsModalLabel"><i class="fas fa-boxes me-2"></i>Mengajukan
                        Barang Ke Barang Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="editAllItemsContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="saveAllItemChangesBtn"><i
                            class="fas fa-save me-2"></i>Simpan Semua Barang Return</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            var lpbTable = null;
            const ppnPercentage = {{ config('app.konstanta_ppn') }} / 100;
            var daftarJasaCache = [];

            var invoiceTable = $('#invoiceLpbTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('purchasing.invoice.data') }}",
                    data: function(d) {
                        d.periode = $('#filterPeriode').val();
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [{
                        className: 'details-control',
                        orderable: false,
                        data: null,
                        searchable: false,
                        defaultContent: '<i class="fas fa-plus-circle"></i>'
                    },
                    {
                        data: 'no_invoice',
                        name: 'invoice_lpb.no_invoice'
                    },
                    {
                        data: 'tanggal',
                        name: 'invoice_lpb.tanggal'
                    },
                    {
                        data: 'no_po',
                        name: 'admin_lpb.no_po',
                        defaultContent: '-'
                    },
                    {
                        data: 'supplier_nama',
                        name: 'suppliers.nama'
                    },
                    {
                        data: 'tgl_deadline_pembayaran',
                        name: 'invoice_lpb.tgl_deadline_pembayaran'
                    },
                    {
                        data: 'status_pembayaran',
                        name: 'invoice_lpb.status_pembayaran',
                        className: 'text-center',
                        render: function(data, type, row) {
                            let badgeClass = '';
                            let statusText = String(data || '').trim();
                            if (row.is_overdue && statusText !== 'Lunas') {
                                badgeClass = 'bg-danger';
                                statusText = 'Jatuh Tempo';
                            } else {
                                switch (statusText) {
                                    case 'Belum Dibayar':
                                    case 'Siap Bayar':
                                        badgeClass = 'bg-warning text-dark';
                                        statusText = 'Siap Bayar';
                                        break;
                                    case 'Proses Pembayaran':
                                    case 'Proses':
                                        badgeClass = 'badge-primary';
                                        statusText = 'Proses';
                                        break;
                                    case 'Lunas':
                                        badgeClass = 'bg-success';
                                        break;
                                    default:
                                        badgeClass = 'bg-secondary';
                                        statusText = 'N/A';
                                }
                            }
                            return `<span class="badge ${badgeClass}">${statusText}</span>`;
                        }
                    },
                    {
                        data: 'jenis_lpb',
                        name: 'admin_lpb.jenis_lpb',
                        className: 'text-center',
                        render: function(data) {
                            if (data == 1) return '<span class="badge bg-info">PO</span>';
                            if (data == 2) return '<span class="badge bg-success">PP</span>';
                            return '<span class="badge bg-secondary">-</span>';
                        }
                    },
                    {
                        data: 'id',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            return `<button class="btn btn-danger btn-sm delete-invoice-btn" data-id="${row.id}" data-invoice-number="${row.no_invoice}" title="Hapus Invoice"><i class="fas fa-trash-alt"></i></button>`;
                        }
                    }
                ]
            });

            $('#filterPeriode, #filterStatus').on('change', () => invoiceTable.ajax.reload());

            $('input[name="tipe_invoice"]').on('change', function() {
                const tipe = $(this).val();
                if (tipe === 'LPB') {
                    $('#containerFormLpb').show();
                    $('#containerFormJasa').hide();
                    resetLpbTableView();
                    $('#selectJasa').val("").trigger('change');
                } else {
                    $('#containerFormLpb').hide();
                    $('#containerFormJasa').show();
                    $('#selectPoLokal').val("").trigger('change');
                    loadDaftarJasa();
                }
                clearFinansialForm();
            });

            function clearFinansialForm() {
                $('#sub_total').val('Rp 0,00');
                $('#ppn').val('Rp 0,00');
                $('#diskon_nominal').val('0');
                $('#pph').val('0');
                $('#ongkir').val('0');
                $('#grand_total').val('Rp 0,00');
                $('#checkbox_ppn').prop('checked', false).prop('disabled', true);
            }

            function loadDaftarJasa() {
                var selectJasa = $('#selectJasa');
                if (selectJasa.data('select2')) {
                    selectJasa.select2('destroy');
                }
                selectJasa.select2({
                    dropdownParent: $('#invoiceLpb'),
                    placeholder: '-- Pilih Dokumen Jasa --',
                    allowClear: true,
                    width: '100%'
                });
                selectJasa.empty().append('<option value="">-- Memuat Data Jasa... --</option>').prop('disabled',
                    true);
                $.ajax({
                    url: "{{ route('purchasing.invoice.available_jasa') }}",
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        selectJasa.prop('disabled', false);
                        selectJasa.empty().append(
                            '<option value="" selected>-- Pilih Dokumen Jasa --</option>');
                        daftarJasaCache = response;
                        if (response && response.length > 0) {
                            response.forEach(function(js) {
                                const optionText =
                                    `${js.no_jasa} - ${js.nama_pelanggan} (${formatCurrency(parseFloat(js.GrandTotalPembelian))})`;
                                const option = new Option(optionText, js.no_jasa, false, false);
                                selectJasa.append(option);
                            });
                        } else {
                            selectJasa.append(
                                '<option value="">Tidak ada dokumen jasa untuk diproses</option>');
                        }
                        selectJasa.trigger('change');
                    },
                    error: function() {
                        selectJasa.prop('disabled', false).empty().append(
                            '<option value="">-- Gagal Memuat --</option>');
                    }
                });
            }

            $('#selectJasa').on('change', function() {
                const selectedNoJasa = $(this).val();
                $('#selected_jasa_no').val(selectedNoJasa);
                if (!selectedNoJasa) {
                    clearFinansialForm();
                    return;
                }
                const dataJasa = daftarJasaCache.find(item => item.no_jasa === selectedNoJasa);
                if (dataJasa) {
                    $('#sub_total').val(formatCurrency(parseFloat(dataJasa.totalexclude)));
                    $('#checkbox_ppn').prop('checked', parseFloat(dataJasa.totalppn) > 0);
                    $('#ppn').val(formatCurrency(parseFloat(dataJasa.totalppn)));
                    $('#diskon_nominal').val(dataJasa.diskon);
                    $('#ongkir').val(dataJasa.ongkir);
                    $('#grand_total').val(formatCurrency(parseFloat(dataJasa.GrandTotalPembelian)));
                }
            });

            $('#invoiceLpbTable tbody').on('click', 'td.details-control', function() {
                var tr = $(this).closest('tr');
                var row = invoiceTable.row(tr);
                var icon = $(this).find('i');
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('details');
                    icon.removeClass('fa-minus-circle').addClass('fa-plus-circle');
                } else {
                    row.child(formatDetail(row.data())).show();
                    tr.addClass('details');
                    icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
                }
            });

            function formatDetail(d) {
                let detailContainerId = `detail-container-${d.id}`;
                let loadingHtml =
                    `<div id="${detailContainerId}" class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Memuat rincian...</div>`;
                let detailUrl = "{{ route('invoice.detail', ['id' => ':id']) }}";
                detailUrl = detailUrl.replace(':id', d.id);
                $.ajax({
                    url: detailUrl,
                    type: 'GET',
                    success: function(response) {
                        let itemsHtml = '';
                        if (response.items && Object.keys(response.items).length > 0) {
                            for (const lpb_id in response.items) {
                                itemsHtml += `<h6 class="fw-bold mt-3">Rincian Barang dari LPB ${lpb_id}</h6>
                                <table class="table table-sm table-bordered">
                                    <thead class="thead"><tr><th>Nama Barang</th><th class="text-center">Jumlah</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
                                    <tbody>`;
                                response.items[lpb_id].forEach(item => {
                                    itemsHtml += `<tr>
                                        <td>${item.nama_bahan}</td>
                                        <td class="text-center">${item.jumlah_barang_diterima}</td>
                                        <td class="text-end">${formatCurrency(item.harga)}</td>
                                        <td class="text-end">${formatCurrency(item.sub_total_item)}</td>
                                    </tr>`;
                                });
                                itemsHtml += '</tbody></table>';
                            }
                        } else {
                            itemsHtml = '<p>Tidak ada rincian barang/jasa untuk invoice ini.</p>';
                        }
                        let financials = response.financials;
                        let finalHtml = `
                        <div class="invoice-detail-wrapper">
                            <div class="detail-flex-container">
                                <div class="detail-items-column">
                                    <div class="detail-section-header d-flex justify-content-between align-items-center">
                                        <span>Rincian Barang dari LPB</span>
                                        <button class="btn btn-sm btn-outline-primary edit-all-items-btn" data-invoice-id="${d.id}" title="Masukan Barang Ke Tabel Return"><i class="fas fa-edit"></i>Masukan ke Return</button>
                                    </div>
                                    ${itemsHtml}
                                </div>
                                <div class="detail-financial-column">
                                    <div class="detail-section-header">Rincian Finansial</div>
                                    <div class="financial-summary">
                                        <div class="summary-item"><span>Sub Total</span> <span>${formatCurrency(financials.sub_total)}</span></div>
                                        <div class="summary-item"><span>PPN</span> <span>${formatCurrency(financials.ppn)}</span></div>
                                        <div class="summary-item"><span>Diskon</span> <span>- ${formatCurrency(financials.diskon)}</span></div>
                                        <div class="summary-item"><span>PPH</span> <span>${financials.pph >= 0 ? '+ ' : '- '}${formatCurrency(Math.abs(financials.pph))}</span></div>
                                        <div class="summary-item"><span>Ongkir</span> <span>${formatCurrency(financials.ongkir)}</span></div>
                                        <div class="summary-item total"><span>Grand Total</span> <span>${formatCurrency(financials.grand_total)}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        $('#' + detailContainerId).html(finalHtml);
                    },
                    error: function() {
                        $('#' + detailContainerId).html(
                            '<div class="p-3 text-danger">Gagal memuat rincian. Silakan coba lagi.</div>'
                        );
                    }
                });
                return loadingHtml;
            }

            $('#invoiceLpb').on('show.bs.modal', function() {
                $('#invoiceLpbForm')[0].reset();
                $('#selectPoLokal').val("");
                $('#tipeLpb').click();
                resetLpbTableView();
                var selectPo = $('#selectPoLokal');
                if (selectPo.data('select2')) {
                    selectPo.select2('destroy');
                }
                selectPo.select2({
                    dropdownParent: $('#invoiceLpb'),
                    placeholder: '-- Pilih Purchase Order --',
                    allowClear: true,
                    width: '100%'
                });
                selectPo.empty().append('<option value="">-- Memuat PO... --</option>').prop('disabled',
                    true);
                $.ajax({
                    url: "{{ route('purchasing.invoice.available_pos') }}",
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        selectPo.prop('disabled', false);
                        selectPo.empty().append(
                            '<option value="" selected>-- Pilih Purchase Order --</option>');
                        if (response && response.length > 0) {
                            response.forEach(function(po) {
                                const optionText = `${po.nama_supplier} - ${po.no_po}`;
                                const option = new Option(optionText, po.no_po, false,
                                    false);
                                $(option).data('ppn', po.ppn);
                                $(option).data('kode-supplier', po.kode_supplier);
                                selectPo.append(option);
                            });
                        } else {
                            selectPo.append(
                                '<option value="">Tidak ada PO untuk diproses</option>');
                        }
                        selectPo.trigger('change');
                    },
                    error: function(xhr, status, error) {
                        selectPo.prop('disabled', false).empty().append(
                            '<option value="">-- Gagal Memuat --</option>');
                    }
                });
            });

            $('#selectPoLokal').on('change', function() {
                const selectedPo = $(this).val();
                const selectedOption = $(this).find('option:selected');
                const ppnValue = selectedOption.data('ppn') || 0;
                $('#selected_kode_supplier_lokal').val(selectedOption.data('kode-supplier'));
                $('#checkbox_ppn').prop('checked', ppnValue > 0);
                if (selectedPo) {
                    loadLpbForPo(selectedPo);
                } else {
                    resetLpbTableView();
                }
                calculateTotals();
            });

            $('#tableLpb tbody').on('click', 'td.details-control', function() {
                var tr = $(this).closest('tr');
                var row = lpbTable.row(tr);
                var icon = $(this).find('i');
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('details');
                    icon.removeClass('fa-minus-circle text-danger').addClass('fa-plus-circle text-primary');
                } else {
                    row.child(formatLpbDetailModal(row.data().details)).show();
                    tr.addClass('details');
                    icon.removeClass('fa-plus-circle text-primary').addClass('fa-minus-circle text-danger');
                }
            });

            $('#tableLpb tbody').on('change', '.lpb-checkbox', calculateTotals);
            $('#checkbox_ppn, input[name="pph_operator"]').on('change', calculateTotals);
            $('#diskon_nominal, #pph, #ongkir').on('keyup blur', calculateTotals);

            $('#invoiceLpbForm').on('submit', function(e) {
                e.preventDefault();
                const tipe = $('input[name="tipe_invoice"]:checked').val();
                const targetUrl = (tipe === 'JASA') ? "{{ route('invoice.jasa.store') }}" :
                    "{{ route('invoice.lpb.store') }}";
                const submitButton = $(this).find('button[type="submit"]');
                const originalButtonText = submitButton.html();
                submitButton.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled',
                    true);
                $('.form-control.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                const formData = {
                    _token: "{{ csrf_token() }}",
                    no_invoice: $('#no_invoice').val(),
                    kode_supplier: $('#selected_kode_supplier_lokal').val(),
                    tanggal_nota: $('#tanggal_nota').val(),
                    tgl_deadline_pembayaran: $('#tgl_deadline_pembayaran').val(),
                    sub_total: $('#sub_total').val(),
                    ppn: $('#ppn').val(),
                    diskon_nominal: $('#diskon_nominal').val(),
                    pph: $('#pph').val(),
                    pph_operator: $('input[name="pph_operator"]:checked').val(),
                    ongkir: $('#ongkir').val(),
                    grand_total: $('#grand_total').val(),
                    selected_lpb_ids: $('#selected_lpb_ids').val(),
                    selected_jasa_no: $('#selected_jasa_no').val(),
                    note: $('#note_lokal').val()
                };

                $.ajax({
                    url: targetUrl,
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            })
                            .then(() => {
                                $('#invoiceLpb').modal('hide');
                                invoiceTable.ajax.reload();
                            });
                    },
                    error: function(xhr) {
                        let errorMessage = 'Terjadi kesalahan saat menyimpan data.';
                        if (xhr.responseJSON) {
                            errorMessage = xhr.responseJSON.message || errorMessage;
                            if (xhr.responseJSON.errors) {
                                $.each(xhr.responseJSON.errors, function(key, value) {
                                    $('#' + key).addClass('is-invalid').parent().append(
                                        `<div class="invalid-feedback d-block">${value[0]}</div>`
                                    );
                                });
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: errorMessage
                        });
                    },
                    complete: function() {
                        submitButton.html(originalButtonText).prop('disabled', false);
                    }
                });
            });

            function loadLpbForPo(noPo) {
                if (lpbTable) lpbTable.destroy();
                $('#lpbLoadingMessageLokal').hide();
                $('#tableLpbContainerLokal').show();
                let url = "{{ route('purchasing.lpb.by_po', ['no_po' => ':no_po']) }}";
                url = url.replace(':no_po', noPo);
                lpbTable = $('#tableLpb').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: url,
                        dataSrc: 'data'
                    },
                    paging: true,
                    pageLength: 5,
                    lengthChange: false,
                    searching: false,
                    info: true,
                    columns: [{
                            className: 'details-control',
                            orderable: false,
                            data: null,
                            defaultContent: '<i class="fas fa-plus-circle text-primary"></i>'
                        },
                        {
                            data: 'id_lpb',
                            orderable: false,
                            className: 'text-center',
                            render: data =>
                                `<input type="checkbox" class="lpb-checkbox" value="${data}">`
                        },
                        {
                            data: 'id_lpb'
                        },
                        {
                            data: 'tgl_lpb',
                            render: data => new Date(data).toLocaleDateString('id-ID', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            })
                        },
                        {
                            data: 'nama_supplier'
                        },
                        {
                            data: 'total_qty',
                            className: 'text-center'
                        },
                        {
                            data: 'sub_total_lpb',
                            className: 'text-end',
                            render: data => formatCurrency(data)
                        },
                        {
                            data: 'status',
                            className: 'text-center',
                            render: data => `<span class="badge bg-warning">Purchasing</span>`
                        }
                    ],
                    order: [
                        [3, 'asc']
                    ]
                });
            }

            function parseToFloat(value) {
                if (typeof value !== 'string' || value === '') return 0;
                return parseFloat(value.replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
            }

            function formatCurrency(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 2
                }).format(number);
            }

            function calculateTotals() {
                const tipe = $('input[name="tipe_invoice"]:checked').val();
                if (tipe === 'JASA') return;

                let subTotal = 0;
                let selectedLpbIds = [];
                if (lpbTable) {
                    lpbTable.rows().every(function() {
                        var checkbox = $(this.node()).find('.lpb-checkbox');
                        if (checkbox.is(':checked')) {
                            subTotal += this.data().sub_total_lpb;
                            selectedLpbIds.push(this.data().id_lpb);
                        }
                    });
                }
                $('#sub_total').val(formatCurrency(subTotal));
                $('#selected_lpb_ids').val(selectedLpbIds.join(','));
                let diskon = parseToFloat($('#diskon_nominal').val());
                let pph = parseToFloat($('#pph').val());
                let ongkir = parseToFloat($('#ongkir').val());
                let dpp = Math.max(0, subTotal - diskon);
                let ppnAmount = $('#checkbox_ppn').is(':checked') ? dpp * ppnPercentage : 0;
                $('#ppn').val(formatCurrency(ppnAmount));
                const pphOperator = $('input[name="pph_operator"]:checked').val();
                let adjustedPph = (pphOperator === '-') ? -Math.abs(pph) : Math.abs(pph);
                let grandTotal = dpp + ppnAmount + ongkir + adjustedPph;
                $('#grand_total').val(formatCurrency(grandTotal < 0 ? 0 : grandTotal));
            }

            function formatLpbDetailModal(details) {
                if (!details || details.length === 0)
                    return '<div class="p-3 text-center text-muted">Tidak ada rincian item untuk LPB ini.</div>';
                let detailHtml =
                    `<div class="p-3" style="background-color: #f8f9fa;"><h6 class="fw-bold">Rincian Barang (Harga Sesuai PO Terbaru)</h6><table class="table table-sm" style="width:100%;"><thead class="table-light"><tr><th>Nama Bahan</th><th class="text-center">Jumlah</th><th class="text-end">Harga Final</th><th class="text-end">Sub Total</th></tr></thead><tbody>`;
                details.forEach(item => {
                    let hargaHtml = `<strong>${formatCurrency(item.harga_final)}</strong>`;
                    if (parseFloat(item.harga_lpb_asli).toFixed(2) !== parseFloat(item.harga_final).toFixed(
                            2)) {
                        hargaHtml +=
                            `<br><small class="text-danger" style="font-style: italic;">(Semula: ${formatCurrency(item.harga_lpb_asli)})</small>`;
                    }
                    detailHtml +=
                        `<tr><td>${item.nama_bahan}</td><td class="text-center">${item.jumlah_barang_diterima}</td><td class="text-end">${hargaHtml}</td><td class="text-end">${formatCurrency(item.sub_total_item)}</td></tr>`;
                });
                detailHtml += '</tbody></table></div>';
                return detailHtml;
            }

            function resetLpbTableView() {
                if (lpbTable) {
                    lpbTable.destroy();
                    lpbTable = null;
                    $('#tableLpb tbody').empty();
                }
                $('#lpbLoadingMessageLokal').show();
                $('#tableLpbContainerLokal').hide();
                calculateTotals();
            }

            $('#invoiceLpbTable tbody').on('click', '.delete-invoice-btn', function() {
                const invoiceId = $(this).data('id');
                const invoiceNumber = $(this).data('invoice-number');
                Swal.fire({
                    title: 'Anda Yakin?',
                    html: `Anda akan menghapus invoice <strong>${invoiceNumber}</strong>.<br>Tindakan ini akan mengembalikan LPB terkait agar dapat dipilih kembali.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let url = "{{ route('invoice.lpb.destroy', ['id' => ':id']) }}";
                        url = url.replace(':id', invoiceId);
                        Swal.fire({
                            title: 'Menghapus...',
                            text: 'Mohon tunggu sebentar.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire('Dihapus!', response.message, 'success');
                                    invoiceTable.ajax.reload();
                                }
                            },
                            error: function(xhr) {
                                Swal.close();
                                const msg = xhr.responseJSON ? xhr.responseJSON
                                    .message : 'Terjadi kesalahan. Silakan coba lagi.';
                                Swal.fire('Gagal!', msg, 'error');
                            }
                        });
                    }
                });
            });

            $('#invoiceLpbTable tbody').on('click', '.edit-all-items-btn', function() {
                const invoiceId = $(this).data('invoice-id');
                const modal = $('#editAllItemsModal');
                const container = $('#editAllItemsContainer');
                modal.modal('show');
                modal.data('invoice-id', invoiceId);
                container.html(
                    '<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Memuat item...</p></div>'
                );
                let url = "{{ route('invoice.getInvoiceItems', ['invoiceId' => ':id']) }}";
                url = url.replace(':id', invoiceId);
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(items) {
                        container.empty();
                        if (items.length === 0) {
                            container.html(
                                '<p class="text-center">Tidak ada item yang bisa diedit.</p>'
                            );
                            return;
                        }
                        let itemsHtml = '<table class="table"><tbody>';
                        items.forEach(function(item) {
                            itemsHtml += `<tr>
                                <td class="align-middle">
                                    <strong>${item.nama_bahan}</strong><br>
                                    <small class="text-muted">Harga: ${formatCurrency(item.harga)}</small>
                                </td>
                                <td class="align-middle" style="width: 150px;">
                                    <input type="number" class="form-control quantity-input-massal" value="0" max="${item.jumlah_barang_diterima}" data-id-lpb="${item.id_lpb}" data-id-bahan="${item.id_bahan}" min="0">
                                </td>
                            </tr>`;
                        });
                        itemsHtml += '</tbody></table>';
                        container.html(itemsHtml);
                    },
                    error: function() {
                        container.html(
                            '<div class="alert alert-danger">Gagal memuat data item. Silakan tutup and coba lagi.</div>'
                        );
                    }
                });
            });

            $('body').on('click', '#saveAllItemChangesBtn', function() {
                const modal = $('#editAllItemsModal');
                const invoiceId = modal.data('invoice-id');
                let updatedItems = [];
                $('.quantity-input-massal').each(function() {
                    const input = $(this);
                    updatedItems.push({
                        id_lpb: input.data('id-lpb'),
                        id_bahan: input.data('id-bahan'),
                        new_quantity: input.val()
                    });
                });
                Swal.fire({
                    title: 'Simpan Semua Perubahan?',
                    text: "Total invoice akan dihitung ulang berdasarkan kuantitas baru. Anda yakin?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, simpan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('invoice.updateInvoiceItems') }}",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                invoice_id: invoiceId,
                                items: updatedItems
                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Menyimpan...',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function(response) {
                                Swal.fire('Berhasil!', response.message, 'success')
                                    .then(() => {
                                        modal.modal('hide');
                                        invoiceTable.ajax.reload(null, false);
                                    });
                            },
                            error: function(xhr) {
                                Swal.fire('Gagal!', xhr.responseJSON ? xhr.responseJSON
                                    .message : "Terjadi kesalahan.", 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
