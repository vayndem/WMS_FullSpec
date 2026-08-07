@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">Daftar Invoice LPB</h4>
                            <p class="mb-0 text-muted">Kelola data tagihan dan pelunasan penerimaan barang</p>
                        </div>
                        @can('create', App\Models\Invoicelpb::class)
                            <button type="button" class="btn btn-primary add-list shadow-sm btn-open-create-modal">
                                <i class="fa-solid fa-plus me-2"></i>Buat Invoice LPB
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="invoice-status-filter mb-3" role="radiogroup"
                                aria-label="Filter status pembayaran">
                                <input class="btn-check" type="radio" name="invoice_payment_status"
                                    id="invoice-status-unpaid" value="0" checked>
                                <label for="invoice-status-unpaid">
                                    <i class="fa-regular fa-clock"></i>Belum Lunas
                                </label>

                                <input class="btn-check" type="radio" name="invoice_payment_status"
                                    id="invoice-status-partial" value="1">
                                <label for="invoice-status-partial">
                                    <i class="fa-solid fa-circle-half-stroke"></i>Dibayar Sebagian
                                </label>

                                <input class="btn-check" type="radio" name="invoice_payment_status"
                                    id="invoice-status-paid" value="2">
                                <label for="invoice-status-paid">
                                    <i class="fa-solid fa-circle-check"></i>Lunas
                                </label>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="table-invoice-lpb" width="100%"
                                    cellspacing="0" data-report-url="{{ route('invoice-lpb.report.pdf') }}"
                                    data-filter-columns="0:no_invoice,1:tanggal,2:supplier_nama,3:tgl_deadline_pembayaran,4:grand_total,5:sisa_tagihan,6:status_pembayaran">
                                    <thead class="bg-light text-uppercase font-size-12">
                                        <tr>
                                            <th width="15%" class="py-3 ps-3">No. Invoice</th>
                                            <th width="12%" class="py-3">Tanggal</th>
                                            <th class="py-3">Supplier</th>
                                            <th width="12%" class="py-3">Deadline</th>
                                            <th width="15%" class="text-end py-3">Grand Total</th>
                                            <th width="15%" class="text-end py-3">Sisa Tagihan</th>
                                            <th width="12%" class="text-center py-3">Status</th>
                                            <th width="12%" class="text-center py-3 pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-container"></div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                let nextPaymentNumber = @json($paymentNumber);
                let table = $('#table-invoice-lpb').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('invoice-lpb.index') }}",
                        data: function(data) {
                            data.focus = new URLSearchParams(window.location.search).get('invoice') || '';
                            data.payment_status =
                                $('input[name="invoice_payment_status"]:checked').val();
                        }
                    },
                    columns: [{
                            data: 'no_invoice',
                            name: 'no_invoice',
                            className: 'align-middle ps-3 fw-bold text-primary'
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
                            className: 'align-middle'
                        },
                        {
                            data: 'supplier_nama',
                            name: 'supplier_nama',
                            className: 'align-middle'
                        },
                        {
                            data: 'tgl_deadline_pembayaran',
                            name: 'tgl_deadline_pembayaran',
                            className: 'align-middle',
                            defaultContent: '-'
                        },
                        {
                            data: 'grand_total',
                            name: 'grand_total',
                            className: 'text-end align-middle fw-bold',
                            render: function(data) {
                                return 'Rp ' + Number(data).toLocaleString('id-ID');
                            }
                        },
                        {
                            data: 'sisa_tagihan',
                            name: 'sisa_tagihan',
                            className: 'text-end align-middle fw-bold text-danger',
                            render: function(data) {
                                return 'Rp ' + Number(data).toLocaleString('id-ID');
                            }
                        },
                        {
                            data: 'status_pembayaran',
                            name: 'status_pembayaran',
                            className: 'text-center align-middle',
                            render: function(data, type, row) {
                                if (row.status === 2) {
                                    return '<span class="badge bg-success p-2">Lunas</span>';
                                } else if (row.status === 1) {
                                    return '<span class="badge bg-warning p-2 text-white">Dibayar Sebagian</span>';
                                } else {
                                    return '<span class="badge bg-secondary p-2">Belum Dibayar</span>';
                                }
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-3',
                            render: function(data) {
                                let btnShow = `
                                    <button type="button" class="btn btn-sm btn-outline-info me-1 btn-show" data-id="${data.id}" title="Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                `;
                                let btnEdit = '';
                                if (data.can_update) {
                                    btnEdit = `
                                        <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-open-edit-modal" data-id="${data.id}" title="Edit Invoice">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    `;
                                }
                                let btnDelete = '';
                                if (data.can_delete) {
                                    btnDelete = `
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${data.id}" title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    `;
                                }
                                return btnShow + btnEdit + btnDelete;
                            }
                        }
                    ],
                    initComplete: function() {
                        if (new URLSearchParams(window.location.search).has('invoice')) {
                            $('#table-invoice-lpb').one('draw.dt', () => $('#table-invoice-lpb .btn-show')
                                .first().trigger('click'));
                            setTimeout(() => $('#table-invoice-lpb .btn-show').first().trigger('click'), 0);
                        }
                    }
                });

                $(document).on('click', '.btn-open-create-modal', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: "{{ route('invoice-lpb.create') }}",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#createInvoiceModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form.');
                        }
                    });
                });

                $(document).on('click', '.btn-open-edit-modal', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    $.ajax({
                        url: "/invoice-lpb/" + id + "/edit",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#editInvoiceModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form edit.');
                        }
                    });
                });

                $(document).on('click', '.btn-show', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    $.ajax({
                        url: "/invoice-lpb/" + id,
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(res) {
                            if (res.success) {
                                renderShowModal(res.data);
                            }
                        },
                        error: function() {
                            AppAlert.auto('Gagal mengambil data detail invoice.');
                        }
                    });
                });

                $(document).on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    AppAlert.confirm("Hapus invoice ini? Invoice yang sudah dibayar tidak dapat dihapus.").then(
                        function(result) {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "/invoice-lpb/" + id,
                                    type: "DELETE",
                                    data: {
                                        _token: "{{ csrf_token() }}"
                                    },
                                    success: function(res) {
                                        if (res.success) {
                                            table.ajax.reload();
                                        }
                                    },
                                    error: function() {
                                        AppAlert.auto("Gagal menghapus data.");
                                    }
                                });
                            }
                        });
                });

                function renderShowModal(data) {
                    let detailsHtml = '';
                    if (data.details && data.details.length > 0) {
                        data.details.forEach(function(item, i) {
                            let coaInfo = item.coa_kas_bank ?
                                `${item.coa_kas_bank.kode_akun} - ${item.coa_kas_bank.nama_akun}` : '-';
                            detailsHtml += `
                                <tr>
                                    <td class="text-center align-middle">${i + 1}</td>
                                    <td class="align-middle fw-semibold">${item.payment_number ?? '-'}</td>
                                    <td class="align-middle">${item.tanggal_pembayaran}</td>
                                    <td class="align-middle fw-bold">${item.metode_pembayaran}</td>
                                    <td class="align-middle fw-bold text-info">${coaInfo}</td>
                                    <td class="text-end align-middle">Rp ${Number(item.jumlah_pembayaran).toLocaleString('id-ID')}</td>
                                    <td class="text-end align-middle">Rp ${Number(item.potongan_pph23).toLocaleString('id-ID')}</td>
                                    <td class="text-end align-middle">Rp ${Number(item.selisih_bayar).toLocaleString('id-ID')}<small class="d-block text-muted">${item.jenis_selisih ? item.jenis_selisih.replaceAll('_',' ') : ''}</small></td>
                                    <td class="text-end align-middle fw-bold text-success">Rp ${Number(item.total_transaksi_pengurang_hutang).toLocaleString('id-ID')}</td>
                                    <td class="align-middle">${item.user_finance ? item.user_finance.name : '-'}</td>
                                </tr>
                            `;
                        });
                    } else {
                        detailsHtml =
                            '<tr><td colspan="10" class="text-center text-muted py-3">Belum ada riwayat pembayaran.</td></tr>';
                    }

                    let btnAddPayment = '';
                    if (data.can_pay) {
                        btnAddPayment = `
                            <button type="button" class="btn btn-sm btn-success fw-bold btn-add-payment"
                                data-id="${data.id}" data-remaining="${Number(data.sisa_tagihan || 0)}">
                                <i class="fa-solid fa-plus me-1"></i>Tambah Pembayaran
                            </button>
                        `;
                    }

                    let modalHtml = `
                        <div class="modal fade" id="showInvoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Invoice: ${data.no_invoice}</h5>
                                        <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4 bg-light">
                                        <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                                            <div class="row">
                                                <div class="col-md-4"><small class="text-muted d-block">Supplier</small><span class="fw-bold h6">${data.supplier ? data.supplier.nama : '-'}</span></div>
                                                <div class="col-md-4"><small class="text-muted d-block">Tanggal Invoice</small><span class="fw-bold h6">${data.tanggal}</span></div>
                                                <div class="col-md-4"><small class="text-muted d-block">Deadline</small><span class="fw-bold h6">${data.tgl_deadline_pembayaran ?? '-'}</span></div>
                                            </div>
                                            <hr class="my-2">
                                            <div class="row">
                                                <div class="col-md-3"><small class="text-muted d-block">Sub Total</small><span class="fw-bold">Rp ${Number(data.sub_total).toLocaleString('id-ID')}</span></div>
                                                <div class="col-md-3"><small class="text-muted d-block">PPN (${Number(data.tarif_ppn || 0).toLocaleString('id-ID')}%)</small><span class="fw-bold">Rp ${Number(data.ppn).toLocaleString('id-ID')}</span></div>
                                                <div class="col-md-3"><small class="text-muted d-block">Grand Total</small><span class="fw-bold text-primary h6">Rp ${Number(data.grand_total).toLocaleString('id-ID')}</span></div>
                                                <div class="col-md-3"><small class="text-muted d-block">Sisa Tagihan</small><span class="fw-bold text-danger h6">Rp ${Number(data.sisa_tagihan).toLocaleString('id-ID')}</span></div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-receipt me-2 text-info"></i>Riwayat Pembayaran</h6>
                                            ${btnAddPayment}
                                        </div>
                                        <div class="table-responsive bg-white rounded shadow-sm">
                                            <table class="table table-bordered table-sm mb-0">
                                                <thead class="bg-light font-size-12">
                                                    <tr>
                                                        <th width="4%" class="text-center">#</th>
                                                        <th>No Pembayaran</th>
                                                        <th width="10%">Tgl Bayar</th>
                                                        <th>Metode</th>
                                                        <th>Akun Kas/Bank (COA)</th>
                                                        <th width="11%" class="text-end">Jml Bayar</th>
                                                        <th width="10%" class="text-end">PPh 23</th>
                                                        <th width="10%" class="text-end">Selisih</th>
                                                        <th width="13%" class="text-end">Total Pengurang</th>
                                                        <th width="10%">User Finance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>${detailsHtml}</tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-white border-top">
                                        <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    $('#modal-container').html(modalHtml);
                    $('#showInvoiceModal').modal('show');
                }

                $(document).on('click', '.btn-add-payment', function() {
                    let invoiceId = $(this).data('id');
                    let remainingBalance = Number($(this).data('remaining') || 0);
                    if ($('#addPaymentModal').length) {
                        document.querySelector('#addPaymentModal')?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                        return;
                    }

                    $.ajax({
                        url: "/chart-of-accounts/kas-bank",
                        type: "GET",
                        dataType: "JSON",
                        success: function(res) {
                            let coaOptions =
                                '<option value="">-- Pilih Akun Kas / Bank --</option>';
                            if (res.data && res.data.length > 0) {
                                res.data.forEach(function(coa) {
                                    coaOptions +=
                                        `<option value="${coa.id}">${coa.kode_akun} - ${coa.nama_akun}</option>`;
                                });
                            }
                            let differenceCoaOptions =
                                '<option value="">-- Pilih Akun Selisih / Uang Muka --</option>';
                            (res.postable || []).forEach(function(coa) {
                                differenceCoaOptions +=
                                    `<option value="${coa.id}">${coa.kode_akun} - ${coa.nama_akun}</option>`;
                            });

                            let modalPaymentHtml = `
                                <div class="collapse mt-3" id="addPaymentModal">
                                    <div class="create-section payment-inline-panel" data-remaining="${remainingBalance}">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-money-bill-wave me-2"></i>Catat Pembayaran</h5>
                                                <button type="button" class="btn btn-sm btn-light border"
                                                    data-bs-toggle="collapse" data-bs-target="#addPaymentModal">
                                                    <i class="fa-solid fa-xmark me-1"></i>Tutup
                                                </button>
                                            </div>
                                            <form id="form-store-payment" action="{{ route('invoice-lpb-detail.store') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="id_invoice_lpb" value="${invoiceId}">
                                                <div class="modal-body p-0 bg-light">
                                                    <div class="card border-0 shadow-sm p-3 bg-white mb-0">
                                                        <div class="row g-2 payment-compact-grid">
                                                        <div class="col-xl-3 col-md-6">
                                                            <label class="fw-bold text-dark small text-uppercase">Nomor Pembayaran</label>
                                                            <input type="text" class="form-control bg-white" name="payment_number"
                                                                value="${nextPaymentNumber ?? ''}" readonly>
                                                        </div>
                                                        <div class="col-xl-3 col-md-6">
                                                            <label class="fw-bold text-dark small text-uppercase">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="tanggal_pembayaran" value="{{ date('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="col-xl-3 col-md-6">
                                                            <label class="fw-bold text-dark small text-uppercase">Metode Pembayaran <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="metode_pembayaran" placeholder="Contoh: Transfer BCA" required>
                                                        </div>
                                                        <div class="col-xl-3 col-md-6">
                                                            <label class="fw-bold text-dark small text-uppercase">Akun Sumber (Kas / Bank) <span class="text-danger">*</span></label>
                                                            <select class="form-select" name="coa_kas_bank_id" required data-app-picker data-placeholder="Cari akun kas atau bank...">${coaOptions}</select>
                                                        </div>
                                                            <div class="col-xl-3 col-md-6">
                                                                <label class="fw-bold text-dark small text-uppercase">Jumlah Pembayaran</label>
                                                                <input type="number" step="any" min="0" class="form-control"
                                                                    name="jumlah_pembayaran" value="${remainingBalance}">
                                                            </div>
                                                            <div class="col-xl-3 col-md-6">
                                                                <label class="fw-bold text-dark small text-uppercase">Potongan PPh 23</label>
                                                                <input type="number" step="any" min="0" class="form-control" name="potongan_pph23" value="0">
                                                            </div>
                                                            <div class="col-xl-3 col-md-6">
                                                                <label class="fw-bold text-dark small text-uppercase d-block">Materai Tambahan</label>
                                                                <label class="payment-check-card">
                                                                    <input type="checkbox" class="form-check-input" name="potongan_materai" value="10000">
                                                                    <span>
                                                                        <strong>Gunakan materai</strong>
                                                                        <small>Biaya tetap Rp10.000</small>
                                                                    </span>
                                                                </label>
                                                            </div>
                                                            <div class="col-xl-3 col-md-6">
                                                                <label class="fw-bold text-dark small text-uppercase">Biaya Transfer Bank</label>
                                                                <input type="number" step="any" min="0" class="form-control" name="biaya_transfer_bank" value="0">
                                                            </div>
                                                            <div class="col-xl-3 col-md-6">
                                                                <label class="fw-bold text-dark small text-uppercase">Nominal Selisih / Kelebihan Bayar</label>
                                                                <input type="number" step="any" min="0" class="form-control" name="selisih_bayar" value="0">
                                                            </div>
                                                            <div class="col-xl-3 col-md-6">
                                                                <label class="fw-bold text-dark small text-uppercase">Jenis Selisih</label>
                                                                <select class="form-select" name="jenis_selisih">
                                                                    <option value="">Tidak ada selisih</option>
                                                                    <option value="PENDAPATAN_SELISIH">Pendapatan selisih</option>
                                                                    <option value="BEBAN_SELISIH">Beban selisih</option>
                                                                    <option value="UANG_MUKA_SUPPLIER">Uang muka supplier</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-xl-3 col-md-6">
                                                                <label class="fw-bold text-dark small text-uppercase">Akun Selisih / Uang Muka</label>
                                                                <select class="form-select" name="coa_selisih_id" data-app-picker data-placeholder="Cari akun selisih...">${differenceCoaOptions}</select>
                                                            </div>
                                                        <div class="col-xl-3 col-md-6">
                                                            <label class="fw-bold text-dark small text-uppercase">Keterangan</label>
                                                            <input class="form-control" name="keterangan" placeholder="Catatan tambahan...">
                                                        </div>
                                                        </div>
                                                        <div class="payment-draft-summary mt-3">
                                                            <div>
                                                                <small>Kas keluar</small>
                                                                <strong id="draft-cash-out">Rp 0</strong>
                                                                <span>Bayar + materai + transfer</span>
                                                            </div>
                                                            <div>
                                                                <small>Pengurang hutang</small>
                                                                <strong id="draft-ap-reduction">Rp 0</strong>
                                                                <span>Termasuk PPh dan selisih</span>
                                                            </div>
                                                            <div>
                                                                <small>Biaya tambahan</small>
                                                                <strong id="draft-extra-cost">Rp 0</strong>
                                                                <span>Materai + transfer</span>
                                                            </div>
                                                            <div>
                                                                <small>Estimasi sisa tagihan</small>
                                                                <strong id="draft-remaining">Rp ${remainingBalance.toLocaleString('id-ID')}</strong>
                                                                <span id="draft-payment-status">Sebelum pembayaran disimpan</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-white border-top">
                                                    <button type="button" class="btn btn-light border fw-bold"
                                                        data-bs-toggle="collapse" data-bs-target="#addPaymentModal">Batal</button>
                                                    <button type="submit" class="btn btn-success fw-bold shadow-sm"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Pembayaran</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            `;

                            $('#showInvoiceModal .modal-body').append(modalPaymentHtml);
                            bootstrap.Collapse.getOrCreateInstance('#addPaymentModal', {
                                toggle: false
                            }).show();
                            const paymentForm = $('#form-store-payment');
                            const paymentPanel = paymentForm.closest('.payment-inline-panel');
                            const readAmount = name => Number.parseFloat(
                                paymentForm.find(`[name="${name}"]`).val()
                            ) || 0;
                            const refreshPaymentDraft = () => {
                                const remaining = Number.parseFloat(paymentPanel.attr('data-remaining')) || 0;
                                const payment = readAmount('jumlah_pembayaran');
                                const pph = readAmount('potongan_pph23');
                                const stamp = paymentForm.find('[name="potongan_materai"]').prop('checked') ? 10000 : 0;
                                const transfer = readAmount('biaya_transfer_bank');
                                const difference = readAmount('selisih_bayar');
                                const type = paymentForm.find('[name="jenis_selisih"]').val();
                                const cashAndTax = payment + pph;

                                let reduction = cashAndTax;
                                if (type === 'PENDAPATAN_SELISIH') reduction += difference;
                                if (type === 'BEBAN_SELISIH') reduction -= difference;
                                if (type === 'UANG_MUKA_SUPPLIER') reduction = Math.min(remaining, cashAndTax);

                                const cashOut = payment + stamp + transfer;
                                const extraCost = stamp + transfer;
                                const estimatedRemaining = Math.max(0, remaining - Math.max(0, reduction));
                                const invalid = reduction <= 0 || reduction > remaining + 0.01;

                                paymentForm.find('#draft-cash-out').text(formatRupiah(cashOut));
                                paymentForm.find('#draft-ap-reduction').text(formatRupiah(Math.max(0, reduction)));
                                paymentForm.find('#draft-extra-cost').text(formatRupiah(extraCost));
                                paymentForm.find('#draft-remaining').text(formatRupiah(estimatedRemaining))
                                    .toggleClass('text-danger', invalid)
                                    .toggleClass('text-success', !invalid && estimatedRemaining <= 0);
                                paymentForm.find('#draft-payment-status').text(invalid
                                    ? 'Periksa nominal — pengurang hutang tidak valid'
                                    : (estimatedRemaining <= 0 ? 'Invoice akan menjadi lunas' : 'Invoice masih memiliki sisa'));
                            };
                            paymentForm.on('input change', 'input, select', refreshPaymentDraft);
                            refreshPaymentDraft();
                            document.querySelector('#addPaymentModal')?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest'
                            });

                            $('#addPaymentModal').on('hidden.bs.collapse', function() {
                                $(this).remove();
                            });
                        },
                        error: function() {
                            AppAlert.auto("Gagal mengambil data akun Kas/Bank COA.");
                        }
                    });
                });

                $('input[name="invoice_payment_status"]').on('change', function() {
                    table.ajax.reload(null, true);
                });

                function paymentNumber(name) {
                    return Number($(`#form-store-payment [name="${name}"]`).val() || 0);
                }

                function updatePaymentDraft() {
                    const panel = $('.payment-inline-panel');
                    if (!panel.length) return;

                    const remaining = Number(panel.data('remaining') || 0);
                    const payment = paymentNumber('jumlah_pembayaran');
                    const pph = paymentNumber('potongan_pph23');
                    const stamp = $('#form-store-payment [name="potongan_materai"]').is(':checked') ? 10000 : 0;
                    const transfer = paymentNumber('biaya_transfer_bank');
                    const difference = paymentNumber('selisih_bayar');
                    const type = $('#form-store-payment [name="jenis_selisih"]').val();
                    const cashAndTax = payment + pph;

                    let reduction = cashAndTax;
                    if (type === 'PENDAPATAN_SELISIH') reduction += difference;
                    if (type === 'BEBAN_SELISIH') reduction -= difference;
                    if (type === 'UANG_MUKA_SUPPLIER') reduction = Math.min(remaining, cashAndTax);

                    const cashOut = payment + stamp + transfer;
                    const extraCost = stamp + transfer;
                    const estimatedRemaining = Math.max(0, remaining - Math.max(0, reduction));
                    const invalid = reduction <= 0 || reduction > remaining + 0.01;

                    $('#draft-cash-out').text(formatRupiah(cashOut));
                    $('#draft-ap-reduction').text(formatRupiah(Math.max(0, reduction)));
                    $('#draft-extra-cost').text(formatRupiah(extraCost));
                    $('#draft-remaining').text(formatRupiah(estimatedRemaining))
                        .toggleClass('text-danger', invalid)
                        .toggleClass('text-success', !invalid && estimatedRemaining <= 0);
                    $('#draft-payment-status').text(invalid
                        ? 'Periksa nominal—pengurang hutang tidak valid'
                        : (estimatedRemaining <= 0 ? 'Invoice akan menjadi lunas' : 'Invoice masih memiliki sisa'));
                }

                $(document).on('input change',
                    '#form-store-payment input, #form-store-payment select',
                    updatePaymentDraft);

                $(document).on('submit', '#form-store-payment', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "POST",
                        data: $(this).serialize(),
                        dataType: "JSON",
                        success: function(res) {
                            if (res.success) {
                                nextPaymentNumber = res.next_document_number ?? nextPaymentNumber;
                                bootstrap.Collapse.getOrCreateInstance('#addPaymentModal', {
                                    toggle: false
                                }).hide();
                                $('#showInvoiceModal').modal('hide');
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            AppAlert.ajaxError(xhr);
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
