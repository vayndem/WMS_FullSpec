@extends('layouts.app')

@section('title', 'Pembayaran Supplier (Bahan Baku & Penolong)')

@section('content')
    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary">Pembayaran Supplier (Bahan Baku & Penolong)</h3>
                    <button type="button" class="btn btn-success btn-sm fw-bold" id="btnExportMutu">
                        <i class="fas fa-file-excel me-2"></i>Laporan Mutu
                    </button>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-4">
                        <ul class="nav nav-pills payment-filter-nav" id="payment-status-filter" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#"
                                    data-status="belum_lunas">Belum Lunas</a></li>
                            <li class="nav-item"><a class="nav-link ms-2" data-bs-toggle="pill" href="#"
                                    data-status="lunas">Lunas</a></li>
                        </ul>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-borderless" id="pembayaran-table" width="100%">
                            <thead>
                                <tr>
                                    <th style="width: 20px;"></th>
                                    <th class="text-center" style="width: 20px;">No.</th>
                                    <th>Tgl Nota</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Tanggal Bayar</th>
                                    <th>No. Invoice</th>
                                    <th>No. LPB</th>
                                    <th>Supplier</th>
                                    <th class="text-end">Grand Total</th>
                                    <th class="text-end">Kekurangan</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Jenis</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="paymentModalLabel"></h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body">
                    <form id="paymentWorkspaceForm" autocomplete="off">
                        <input type="hidden" id="modal_no_invoice_hidden">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-info-circle me-2"></i>Informasi &
                                    Rincian Harga</h6>
                                <div class="row">
                                    <div class="mb-3 col-md-4"><label>No Invoice</label><input type="text"
                                            id="modal_no_invoice" class="form-control" readonly></div>
                                    <div class="mb-3 col-md-4"><label>Deadline</label><input type="text"
                                            id="modal_deadline" class="form-control" readonly></div>
                                    <div class="mb-3 col-md-4"><label>Grand Total</label><input type="text"
                                            id="modal_grand_total" class="form-control fw-bold" readonly></div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-3 mb-0"><label>Sub Total</label><input type="text"
                                            id="modal_sub_total" class="form-control" readonly></div>
                                    <div class="mb-3 col-md-2 mb-0"><label>PPN</label><input type="text" id="modal_ppn"
                                            class="form-control" readonly></div>
                                    <div class="mb-3 col-md-2 mb-0"><label>Diskon</label><input type="text"
                                            id="modal_diskon" class="form-control" readonly></div>
                                    <div class="mb-3 col-md-2 mb-0"><label>Ongkir</label><input type="text"
                                            id="modal_ongkir" class="form-control" readonly></div>
                                    <div class="mb-3 col-md-3 mb-0"><label>Sisa Tagihan</label><input type="text"
                                            id="modal_sisa_tagihan" class="form-control text-danger fw-bold" readonly></div>
                                </div>
                            </div>
                        </div>

                        <div id="form-tambah-transaksi-wrapper" class="card shadow-sm">
                            <div class="card-header py-3">
                                <h6 class="m-0 fw-bold text-primary">Tambah Transaksi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-end">
                                    <div class="mb-3 col-md-4 mb-0"><label>Jenis</label><select class="form-control"
                                            id="jenis_bayar"></select></div>
                                    <div class="mb-3 col-md-3 mb-0"><label>Tanggal</label><input type="date"
                                            class="form-control" id="tgl_pembayaran"></div>
                                    <div class="mb-3 col-md-3 mb-0"><label>Jumlah</label><input type="number"
                                            class="form-control" id="pembayaran" placeholder="0"></div>
                                    <div class="mb-3 col-md-2 mb-0"><label>&nbsp;</label><button type="button"
                                            class="btn btn-info w-100" id="btnAddTransaction">Tambah</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <div class="card w-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-primary" id="recap-title"></h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="stagedPaymentsTable" class="table table-hover table-bordered mb-0"
                                    width="100%">
                                    <thead class="thead">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jenis</th>
                                            <th class="text-end">Jumlah</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end w-100 mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary fw-bold ms-2" id="btnSaveAllTransactions">Validasi
                            & Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            let stagedPayments = [];
            let currentHistory = [];

            const formatIDR = (num) => 'Rp ' + parseFloat(num || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const jenisBayarOptions = {
                0: 'Pembayaran Supplier',
                1: 'Potongan Materai',
                4: 'PPh 23',
                2: 'Biaya Transfer Bank',
                3: 'Selisih Bayar'
            };

            const table = $('#pembayaran-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('pembayaran-nonkertas.data') }}",
                    data: function(d) {
                        d.status_filter = $('#payment-status-filter a.active').data('status');
                    }
                },
                order: [
                    [6, 'asc']
                ],
                columns: [{
                        className: 'details-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '<i class="fas fa-plus-square text-primary" style="cursor:pointer; font-size:1.2rem;"></i>'
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal_nota',
                        name: 'tanggal_nota',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tgl_deadline_pembayaran',
                        name: 'tgl_deadline_pembayaran',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal_bayar',
                        name: 'tanggal_bayar',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'no_invoice',
                        name: 'no_invoice'
                    },
                    {
                        data: 'list_lpb',
                        name: 'list_lpb',
                        orderable: false,
                        render: function(data) {
                            return data ? `<span>${data}</span>` : '-';
                        }
                    },
                    {
                        data: 'kode_supplier',
                        name: 'suppliers.nama',
                        width: '20%'
                    },
                    {
                        data: 'grand_total',
                        name: 'grand_total',
                        className: 'text-end'
                    },
                    {
                        data: 'sisa_tagihan',
                        name: 'sisa_tagihan',
                        className: 'text-end fw-bold'
                    },
                    {
                        data: 'status_pembayaran',
                        name: 'status_pembayaran',
                        className: 'text-center',
                        searchable: false
                    },
                    {
                        data: 'jenis_transaksi_label',
                        name: 'jenis_transaksi',
                        className: 'text-center',
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'tanggal_pembayaran_terakhir',
                        name: 'tanggal_pembayaran_terakhir',
                        orderable: false,
                        searchable: false,
                        visible: false
                    }
                ]
            });

            $('a[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
                table.ajax.reload();
            });

            $('#pembayaran-table tbody').on('click', 'td.details-control', function() {
                const tr = $(this).closest('tr');
                const icon = $(this).find('i');
                const row = table.row(tr);

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('details');
                    icon.removeClass('fa-minus-square text-danger').addClass('fa-plus-square text-primary');
                } else {
                    const noInvoice = row.data().no_invoice;
                    tr.addClass('details');
                    icon.removeClass('fa-plus-square text-primary').addClass('fa-minus-square text-danger');
                    row.child(
                        '<div class="p-3 text-center"><div class="spinner-border spinner-border-sm"></div></div>',
                        'p-0').show();

                    $.get(`{{ url('/pembayaran-nonkertas') }}/${noInvoice}/full-lpb-detail`, function(
                        response) {
                        row.child(formatInvoiceComposition(response.data), 'p-0').show();
                    });
                }
            });

            $('#btnExportMutu').on('click', function() {
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                const today = now.toISOString().split('T')[0];
                Swal.fire({
                    title: 'Export Laporan Mutu',
                    html: `
                            <div class="text-start">
                                <div class="mb-3">
                                    <label>Dari Tanggal (Faktur):</label>
                                    <input type="date" id="export_dari" class="form-control" value="${firstDay}">
                                </div>
                                <div class="mb-3">
                                    <label>Sampai Tanggal (Faktur):</label>
                                    <input type="date" id="export_sampai" class="form-control" value="${today}">
                                </div>
                            </div>
                        `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-download me-2"></i>Export Excel',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#1cc88a',
                    preConfirm: () => {
                        const dari = Swal.getPopup().querySelector('#export_dari').value;
                        const sampai = Swal.getPopup().querySelector('#export_sampai').value;
                        if (!dari || !sampai) {
                            Swal.showValidationMessage(`Harap pilih kedua rentang tanggal`);
                        }
                        return {
                            dari: dari,
                            sampai: sampai
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Sedang memproses...',
                            text: 'Mohon tunggu sebentar, file sedang disiapkan.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        const params = new URLSearchParams({
                            dari: result.value.dari,
                            sampai: result.value.sampai
                        });
                        const exportUrl = "{{ route('pembayaran.export.mutu') }}?" + params
                            .toString();
                        window.location.href = exportUrl;
                        setTimeout(() => {
                            Swal.close();
                        }, 3000);
                    }
                });
            });
            $('#pembayaran-table tbody').on('click', '.btn-detail', function() {
                const invoiceId = $(this).data('id');
                const invoiceStatus = $(this).data('status');
                const url = `{{ url('/pembayaran-nonkertas') }}/${invoiceId}/detail`;
                stagedPayments = [];

                $('#stagedPaymentsTable tbody').html(
                    '<tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>'
                );

                $.get(url, function(data) {
                    const invoice = data.invoice;
                    currentHistory = data.riwayat || [];

                    window.redrawRecapTable = function() {
                        const tbody = $('#stagedPaymentsTable tbody');
                        tbody.empty();
                        const allTransactions = [
                            ...currentHistory.map(item => ({
                                ...item,
                                is_saved: true
                            })),
                            ...stagedPayments
                        ];

                        if (allTransactions.length === 0) {
                            tbody.html(
                                '<tr><td colspan="4" class="text-center text-muted py-3">Belum ada transaksi.</td></tr>'
                            );
                            return;
                        }

                        allTransactions.forEach((trans, index) => {
                            let tanggal, jenisText, jumlah, aksiHtml;
                            if (trans.is_saved) {
                                tanggal = new Date(trans.tanggal_pembayaran +
                                    'T00:00:00').toLocaleDateString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric'
                                });
                                jenisText =
                                    `<span class="badge bg-secondary">${trans.metode_pembayaran}</span>`;
                                jumlah = [trans.jumlah_pembayaran, trans
                                    .potongan_materai, trans.potongan_pph23, trans
                                    .biaya_transfer_bank, trans.selisih_bayar
                                ].map(v => parseFloat(v || 0)).find(v => v > 0) || 0;
                                aksiHtml = invoiceStatus < 2 ?
                                    `<button class="btn btn-outline-danger btn-sm py-0 px-2 btn-delete-permanent" data-id="${trans.id}">&times;</button>` :
                                    `<i class="fas fa-check-circle text-success"></i>`;
                            } else {
                                tanggal = new Date(trans.tanggal + 'T00:00:00')
                                    .toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric'
                                    });
                                jenisText =
                                    `<span class="badge bg-info">${trans.jenis_bayar_text}</span>`;
                                jumlah = trans.pembayaran;
                                aksiHtml =
                                    `<button class="btn btn-danger btn-sm py-0 px-2 btn-delete-staged" data-index="${index - currentHistory.length}">&times;</button>`;
                            }
                            tbody.append(
                                `<tr><td>${tanggal}</td><td>${jenisText}</td><td class="text-end fw-bold">${formatIDR(jumlah)}</td><td class="text-center">${aksiHtml}</td></tr>`
                            );
                        });
                    };

                    populateModalFields(invoice);
                    setupModalView($('#paymentModal'), invoiceStatus, invoice.nama_supplier);
                    redrawRecapTable();
                    updateLiveSisaTagihan(invoice.sisa_tagihan, stagedPayments, currentHistory);
                    bindModalEvents(stagedPayments, redrawRecapTable, invoice, currentHistory);
                });
            });

            $(document).on('click', '.btn-delete-permanent', function() {
                const id = $(this).data('id');
                const noInvoice = $('#modal_no_invoice_hidden').val();

                Swal.fire({
                    title: 'Hapus Transaksi?',
                    text: "Data akan dihapus permanen dari database.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74a3b',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'DELETE',
                            url: `{{ url('/pembayaran-nonkertas/detail') }}/${id}`,
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function() {
                                $.get(`{{ url('/pembayaran-nonkertas') }}/${noInvoice}/detail`,
                                    function(newData) {
                                        currentHistory = newData.riwayat || [];
                                        redrawRecapTable();
                                        updateLiveSisaTagihan(newData.invoice
                                            .sisa_tagihan, stagedPayments,
                                            currentHistory);
                                        table.ajax.reload(null, false);
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Terhapus',
                                            timer: 800,
                                            showConfirmButton: false
                                        });
                                    });
                            }
                        });
                    }
                });
            });

            function updateLiveSisaTagihan(origSisa, staged, history) {
                let totalHistory = history.reduce((acc, t) => {
                    const val = [t.jumlah_pembayaran, t.potongan_materai, t.potongan_pph23, t
                            .biaya_transfer_bank, t.selisih_bayar
                        ]
                        .map(v => parseFloat(v || 0))
                        .find(v => v > 0) || 0;
                    return acc + val;
                }, 0);
                let totalStaged = staged.reduce((acc, t) => acc + parseFloat(t.pembayaran || 0), 0);
                let grandTotalRaw = $('#modal_grand_total').val().replace(/[^0-9,]/g, '').replace(',', '.');
                let grandTotal = parseFloat(grandTotalRaw) || 0;

                let sisa = grandTotal - (totalHistory + totalStaged);
                const $elSisa = $('#modal_sisa_tagihan');
                let sisaTampil = sisa > 0 ? sisa : 0;

                $elSisa.val(sisaTampil.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                if (sisa <= 1) {
                    $elSisa.removeClass('text-danger').addClass('text-success');
                } else {
                    $elSisa.removeClass('text-success').addClass('text-danger');
                }
                $('#pembayaran').val(sisa > 0 ? sisa.toFixed(2) : 0);
            }

            function populateModalFields(inv) {
                $('#modal_no_invoice_hidden, #modal_no_invoice').val(inv.no_invoice);
                $('#modal_deadline').val(new Date(inv.tgl_deadline_pembayaran).toLocaleDateString('id-ID', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }));
                $('#modal_grand_total').val(formatIDR(inv.grand_total));
                $('#modal_sub_total').val(formatIDR(inv.sub_total));
                $('#modal_ppn').val(formatIDR(inv.ppn));
                $('#modal_diskon').val(formatIDR(inv.diskon));
                $('#modal_ongkir').val(formatIDR(inv.ongkir));
            }

            function setupModalView(modal, status, supplier) {
                let title = status < 2 ? 'Workspace Pembayaran' : 'Detail Invoice Lunas';
                $('#paymentModalLabel').html(
                    `<i class="fas fa-wallet me-2"></i> ${title} <span class="fw-normal" style="font-size: 0.9em;">- ${supplier}</span>`
                );
                $('#form-tambah-transaksi-wrapper, #btnSaveAllTransactions').toggle(status < 2);
                $('#jenis_bayar').html(Object.entries(jenisBayarOptions).map(([v, t]) =>
                    `<option value="${v}">${t}</option>`).join(''));
                $('#tgl_pembayaran').val(new Date().toISOString().slice(0, 10));
            }

            function bindModalEvents(staged, redraw, inv, hist) {
                $('#btnAddTransaction').off('click').on('click', function() {
                    let val = parseFloat($('#pembayaran').val());
                    if (!val || val <= 0) return Swal.fire('Warning', 'Jumlah harus lebih dari 0',
                        'warning');
                    let totalHistory = hist.reduce((acc, t) => {
                        const valHist = [t.jumlah_pembayaran, t.potongan_materai, t.potongan_pph23,
                                t.biaya_transfer_bank, t.selisih_bayar
                            ]
                            .map(v => parseFloat(v || 0))
                            .find(v => v > 0) || 0;
                        return acc + valHist;
                    }, 0);
                    let totalStaged = staged.reduce((acc, t) => acc + parseFloat(t.pembayaran || 0), 0);
                    let grandTotalRaw = $('#modal_grand_total').val().replace(/[^0-9,]/g, '').replace(',',
                        '.');
                    let grandTotal = parseFloat(grandTotalRaw) || 0;
                    let sisaTagihanSaatIni = grandTotal - (totalHistory + totalStaged);
                    let valFixed = parseFloat(val.toFixed(2));
                    let sisaFixed = parseFloat(sisaTagihanSaatIni.toFixed(2));
                    if (valFixed > sisaFixed) {
                        return Swal.fire({
                            icon: 'error',
                            title: 'Pembayaran Berlebih!',
                            text: `Jumlah pembayaran (${formatIDR(val)}) tidak boleh melebihi sisa tagihan yang ada (${formatIDR(sisaTagihanSaatIni)}).`,
                        });
                    }

                    staged.push({
                        jenis_bayar: parseInt($('#jenis_bayar').val()),
                        jenis_bayar_text: jenisBayarOptions[$('#jenis_bayar').val()],
                        tanggal: $('#tgl_pembayaran').val(),
                        pembayaran: valFixed
                    });
                    redraw();
                    updateLiveSisaTagihan(inv.sisa_tagihan, staged, hist);
                    $('#pembayaran').val('').focus();
                });

                $('#stagedPaymentsTable').off('click', '.btn-delete-staged').on('click', '.btn-delete-staged',
                    function() {
                        staged.splice($(this).data('index'), 1);
                        redraw();
                        updateLiveSisaTagihan(inv.sisa_tagihan, staged, hist);
                    });
            }

            $('#btnSaveAllTransactions').on('click', function() {
                if (stagedPayments.length === 0) return Swal.fire('Info', 'Tidak ada transaksi baru',
                    'info');
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Menyimpan...');
                $.ajax({
                    type: 'POST',
                    url: "{{ route('pembayaran-nonkertas.store') }}",
                    data: {
                        _token: '{{ csrf_token() }}',
                        no_invoice: $('#modal_no_invoice_hidden').val(),
                        transactions: stagedPayments
                    },
                    success: (res) => {
                        $('#paymentModal').modal('hide');
                        Swal.fire('Berhasil!', res.success, 'success');
                        table.ajax.reload(null, false);
                    },
                    complete: () => btn.prop('disabled', false).html('Validasi & Simpan')
                });
            });

            function formatInvoiceComposition(data) {
                if (!data || data.length === 0)
                    return '<div class="p-3 text-center text-muted">Tidak ada data LPB.</div>';
                let html =
                    '<div class="p-3 child-table"><table class="table table-sm table-hover" width="100%"><thead class="thead"><tr><th>ID LPB / Nama Bahan</th><th>No. PO</th><th>No. Surat Jalan</th><th class="text-end">Jumlah Diterima</th><th class="text-center">Lot Number</th></tr></thead><tbody>';
                data.forEach(lpb => {
                    html +=
                        `<tr class="header-row"><td><strong>${lpb.header.id_lpb}</strong></td><td>${lpb.header.no_po}</td><td>${lpb.header.no_sj}</td><td></td><td></td></tr>`;
                    lpb.details.forEach(i => {
                        html +=
                            `<tr class="detail-row"><td style="padding-left: 30px !important;">${i.nm_bahan}</td><td></td><td></td><td class="text-end">${Number(i.jumlah_barang_diterima).toLocaleString('id-ID')}</td><td class="text-center">${i.lot_number || '-'}</td></tr>`;
                    });
                });
                return html + '</tbody></table></div>';
            }
        });
    </script>
@endpush
