@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">Daftar Penerimaan (LPB & BAP)</h4>
                            <p class="mb-0 text-muted">Kelola penerimaan barang dan dimulainya pekerjaan jasa dari supplier</p>
                        </div>
                        @can('create', App\Models\Lpb::class)
                            <button type="button" class="btn btn-primary add-list shadow-sm btn-open-modal"
                                data-url="{{ route('lpb.create') }}">
                                <i class="fa-solid fa-plus me-2"></i>Buat LPB Baru
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="btn-group receipt-type-filter" role="group" aria-label="Filter jenis penerimaan">
                                    <input type="radio" class="btn-check" name="jenis_lpb_filter" id="jenisSemua"
                                        value="">
                                    <label class="btn btn-outline-primary" for="jenisSemua">
                                        <i class="fa-solid fa-layer-group me-1"></i>Semua
                                    </label>
                                    <input type="radio" class="btn-check" name="jenis_lpb_filter" id="jenisBarang"
                                        value="1" checked>
                                    <label class="btn btn-outline-primary" for="jenisBarang">
                                        <i class="fa-solid fa-box me-1"></i>LPB Barang
                                    </label>
                                    <input type="radio" class="btn-check" name="jenis_lpb_filter" id="jenisJasa"
                                        value="3">
                                    <label class="btn btn-outline-primary" for="jenisJasa">
                                        <i class="fa-solid fa-screwdriver-wrench me-1"></i>BAP Jasa
                                    </label>
                                </div>
                                <input type="hidden" id="filter_jenis_lpb" value="1">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="table-lpb" width="100%"
                                    cellspacing="0" data-report-url="{{ route('lpb.report.pdf') }}"
                                    data-filter-columns="1:id_lpb,3:tanggal,4:no_po,5:supplier_nama,6:gudang_nama,7:no_sj,8:user_nama">
                                    <thead class="bg-light text-uppercase font-size-12">
                                        <tr>
                                            <th width="5%" class="text-center py-3"></th>
                                            <th width="15%" class="py-3 ps-2">No LPB</th>
                                            <th width="11%" class="py-3">Jenis</th>
                                            <th width="12%" class="py-3">Tanggal</th>
                                            <th width="15%" class="py-3">No PO</th>
                                            <th class="py-3">Supplier</th>
                                            <th class="py-3">Gudang</th>
                                            <th width="12%" class="py-3">No SJ</th>
                                            <th width="12%" class="py-3">Petugas</th>
                                            <th width="15%" class="text-center py-3 pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
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
            function formatChildRow(d) {
                const isService = d.document_type === 'SERVICE_BAP';
                let itemsHtml = '';
                if (isService && d.service_details && d.service_details.length > 0) {
                    d.service_details.forEach(function(item) {
                        const poDetail = item.service_po_detail || {};
                        const category = poDetail.category || {};
                        let allocation = item.department_cost_center || '-';
                        if (item.allocations && item.allocations.length > 0) {
                            allocation = item.allocations.map(value =>
                                `<span class="badge bg-light text-dark border me-1 mb-1">${value.datapesanan_code} (${Number(value.percentage).toLocaleString('id-ID')}%)</span>`
                            ).join('');
                        }
                        itemsHtml += `
                            <tr>
                                <td>${poDetail.description || '-'}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary">${category.display_code || '-'}</span>
                                    ${category.name || ''}
                                </td>
                                <td>${allocation}</td>
                                <td class="text-center">
                                    <span class="badge ${d.no_invoice ? 'bg-success' : 'bg-warning text-dark'}">
                                        ${d.no_invoice ? 'Selesai 100%' : 'Sedang dikerjakan'}
                                    </span>
                                </td>
                                @if ($financial)
                                    <td class="text-end fw-semibold">Rp ${Number(item.amount || 0).toLocaleString('id-ID')}</td>
                                @endif
                            </tr>
                        `;
                    });
                } else if (!isService && d.details && d.details.length > 0) {
                    d.details.forEach(function(item) {
                        itemsHtml += `
                            <tr>
                                <td>${item.bahan ? item.bahan.nama : '-'}</td>
                                <td class="text-center">${item.kategori ? item.kategori.katnama : '-'}</td>
                                <td class="text-center">${item.lot_number ?? '-'}</td>
                                <td class="text-center text-success fw-bold">${item.jumlah_barang_diterima}</td>
                                @if ($financial)
                                    <td class="text-end">${item.harga ? 'Rp ' + Number(item.harga).toLocaleString('id-ID') : '-'}</td>
                                @endif
                            </tr>
                        `;
                    });
                } else {
                    itemsHtml =
                        '<tr><td colspan="5" class="text-center text-muted">Tidak ada detail item penerimaan.</td></tr>';
                }

                return `
                    <div class="p-3 bg-light rounded border m-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="fa-solid fa-list-check me-2 text-primary"></i>
                                ${isService ? 'Detail BAP Jasa' : 'Detail Item LPB'} (${d.id_lpb})
                            </h6>
                        </div>
                        <table class="table table-sm table-bordered bg-white mb-0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>${isService ? 'Pekerjaan Jasa' : 'Nama Barang / Bahan'}</th>
                                    <th width="20%" class="text-center">Kategori</th>
                                    <th width="20%" class="${isService ? '' : 'text-center'}">${isService ? 'Cost Center / Datapesanan' : 'Lot Number'}</th>
                                    <th width="16%" class="text-center">${isService ? 'Status Pekerjaan' : 'Qty Diterima'}</th>
                                    @if ($financial)
                                        <th width="18%" class="text-end">${isService ? 'Nilai BAP' : 'Harga Satuan'}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            $(document).ready(function() {
                let table = $('#table-lpb').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('lpb.index') }}",
                        data: function(params) {
                            params.jenis_lpb = $('input[name="jenis_lpb_filter"]:checked').val();
                        }
                    },
                    columns: [{
                            className: 'dt-control text-center align-middle',
                            orderable: false,
                            data: null,
                            defaultContent: '<button type="button" class="btn btn-sm btn-outline-primary btn-expand"><i class="fa-solid fa-chevron-right"></i></button>'
                        },
                        {
                            data: 'id_lpb',
                            name: 'id_lpb',
                            className: 'align-middle ps-2 fw-bold text-primary'
                        },
                        {
                            data: 'jenis_lpb_label',
                            name: 'document_type',
                            className: 'align-middle',
                            render: function(value, type, row) {
                                if (type !== 'display') return value;
                                const service = row.document_type === 'SERVICE_BAP';
                                return `<span class="badge ${service ? 'bg-info-subtle text-info-emphasis' : 'bg-primary-subtle text-primary-emphasis'}">${value}</span>`;
                            }
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
                            className: 'align-middle'
                        },
                        {
                            data: 'no_po',
                            name: 'no_po',
                            className: 'align-middle fw-bold'
                        },
                        {
                            data: 'supplier_nama',
                            name: 'supplier_nama',
                            className: 'align-middle'
                        },
                        {
                            data: 'gudang_nama',
                            name: 'gudang_nama',
                            className: 'align-middle'
                        },
                        {
                            data: 'no_sj',
                            name: 'no_sj',
                            className: 'align-middle'
                        },
                        {
                            data: 'user_nama',
                            name: 'user_nama',
                            className: 'align-middle'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-4',
                            render: function(data) {
                                let btnDetail = `
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-toggle-detail">
                                        <i class="fa-solid fa-eye me-1"></i> Detail
                                    </button>
                                `;
                                let btnDelete = '';
                                if (data.can_delete) {
                                    btnDelete = `
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${data.id}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    `;
                                }
                                return btnDetail + btnDelete;
                            }
                        }
                    ]
                });

                $('input[name="jenis_lpb_filter"]').on('change', function() {
                    $('#filter_jenis_lpb').val(this.value);
                    table.ajax.reload();
                });

                function toggleRow(tr) {
                    let row = table.row(tr);
                    let icon = tr.find('button.btn-expand i');

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                        icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    } else {
                        row.child(formatChildRow(row.data())).show();
                        tr.addClass('shown');
                        icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    }
                }

                $('#table-lpb tbody').on('click', 'button.btn-expand, button.btn-toggle-detail', function() {
                    let tr = $(this).closest('tr');
                    toggleRow(tr);
                });

                $(document).on('click', '.btn-open-modal', function(e) {
                    e.preventDefault();
                    let url = $(this).data('url');

                    $.ajax({
                        url: url,
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#createLpbModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message ||
                                'Anda tidak memiliki hak akses untuk tindakan ini.');
                        }
                    });
                });

                if (new URLSearchParams(window.location.search).get('create') === '1') {
                    $('.btn-open-modal').first().trigger('click');
                }

                $(document).on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    AppAlert.confirm("Hapus LPB draft ini? LPB yang sudah diposting tidak dapat dihapus.").then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "/lpb/" + id,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                if (res.success) {
                                    table.ajax.reload();
                                }
                            },
                            error: function(err) {
                                AppAlert.auto("Gagal menghapus data.");
                            }
                        });
                    }});
                });
            });
        </script>
    @endpush
@endsection
