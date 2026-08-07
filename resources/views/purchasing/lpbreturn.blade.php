@extends('layouts.app')

@section('content')
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
    </style>

    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary"><i class="fas fa-truck me-2"></i>List Return Invoice</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4 filter-card">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3 mb-md-0">
                                    <label for="filterPeriode" class="fw-bold">Periode Invoice</label>
                                    <input type="month" id="filterPeriode" class="form-control"
                                        value="{{ date('Y-m') }}">
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
                                    <th>Tgl. Return</th>
                                    <th>No. PO</th>
                                    <th style="width: 40%;">Supplier</th>
                                    <th class="text-center">Jenis LPB</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            function formatCurrency(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 2
                }).format(number);
            }

            var invoiceTable = $('#invoiceLpbTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                order: [[ 2, 'desc' ]],
                ajax: {
                    url: "{{ route('purchasing.invoice.data') }}",
                    data: function(d) {
                        d.periode = $('#filterPeriode').val();
                        d.status = $('#filterStatus').val();
                        d.return = true;
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
                        data: 'jenis_lpb',
                        name: 'admin_lpb.jenis_lpb',
                        className: 'text-center',
                        render: function(data) {
                            if (data == 1) return '<span class="badge bg-info">PO</span>';
                            if (data == 2) return '<span class="badge bg-success">PP</span>';
                            return '<span class="badge bg-secondary">-</span>';
                        }
                    }
                ]
            });

            $('#filterPeriode, #filterStatus').on('change', () => invoiceTable.ajax.reload());

            function formatDetail(d) {
                let detailContainerId = `detail-container-${d.id}`;
                let loadingHtml =
                    `<div id="${detailContainerId}" class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Memuat rincian...</div>`;
                let detailUrl = "{{ route('invoice.detail', ['id' => ':id']) }}";
                detailUrl = detailUrl.replace(':id', d.id);

                let ajaxData = {
                    return: true
                };

                $.ajax({
                    url: detailUrl,
                    type: 'GET',
                    data: ajaxData,
                    success: function(response) {
                        let itemsHtml = '';
                        if (response.items && Object.keys(response.items).length > 0) {
                            for (const lpb_id in response.items) {
                                itemsHtml += `<h6 class="fw-bold mt-3">Rincian Barang Return ${lpb_id}</h6>
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
                            itemsHtml = '<p>Tidak ada rincian barang untuk data ini.</p>';
                        }
                        let financials = response.financials;
                        let finalHtml = `
            <div class="invoice-detail-wrapper">
                <div class="detail-flex-container">
                    <div class="detail-items-column">
                        <div class="detail-section-header">Rincian Barang</div>
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
        });
    </script>
@endpush
