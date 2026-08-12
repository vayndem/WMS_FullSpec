@extends('layouts.app')

@push('styles')
    <style>
        .content-page {
            padding-top: 100px !important;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            padding: 6px 12px;
            border: 1px solid #ced4da;
            outline: none;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            padding: 4px 8px;
            border: 1px solid #ced4da;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-expand {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .child-row-card {
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }

        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
        }
    </style>
@endpush

@section('content')
    <div class="content-page pb-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h3 class="mb-1 fw-bold text-dark">Daftar Request</h3>
                            <p class="mb-0 text-muted">Kelola seluruh pengajuan request barang perusahaan</p>
                        </div>
                        @can('create', App\Models\Request::class)
                            <button type="button" class="btn btn-primary shadow-sm btn-open-modal px-3 py-2"
                                data-url="{{ route('request.create') }}">
                                <i class="fa-solid fa-plus me-2"></i>Buat Request Baru
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card border-0 mb-4">
                        <div class="card-body p-4">
                            <div class="row align-items-end mb-4 bg-light p-3 rounded border">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <label for="filter_status" class="fw-bold text-dark small text-uppercase">Filter
                                        Status</label>
                                    <select id="filter_status" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="PENDING" selected>Pending</option>
                                        <option value="APPROVED">Approved</option>
                                        <option value="REJECTED">Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button id="btn_filter" class="btn btn-info text-white w-100 shadow-sm">
                                        <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="table-request" width="100%" cellspacing="0"
                                    data-report-url="{{ route('request.report.pdf') }}"
                                    data-filter-columns="1:no_request,2:status,3:created_at">
                                    <thead>
                                        <tr>
                                            <th width="4%" class="text-center py-3"></th>
                                            <th width="25%" class="py-3 ps-2">No Request</th>
                                            <th width="20%" class="text-center py-3">Status</th>
                                            <th width="25%" class="py-3">Tgl Request</th>
                                            <th width="26%" class="text-center py-3 pe-4">Aksi</th>
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
                let itemsHtml = '';
                if (d.details && d.details.length > 0) {
                    d.details.forEach(function(item) {
                        itemsHtml += `
                            <tr>
                                <td class="align-middle fw-bold text-dark">${item.nama_barang}</td>
                                <td class="text-center align-middle fw-bold text-primary">${item.jumlah_minta}</td>
                                <td class="text-center align-middle text-success fw-bold">${item.jumlah_acc ?? '-'}</td>
                                <td class="align-middle text-secondary">${item.keterangan ?? '-'}</td>
                            </tr>
                        `;
                    });
                } else {
                    itemsHtml = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada detail item.</td></tr>';
                }

                return `
                    <div class="p-4 rounded shadow-sm child-row-card my-2">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="fa-solid fa-list-check me-2 text-primary"></i>Detail Item Request (${d.no_request})
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered bg-white mb-0 rounded overflow-hidden">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th class="py-2 px-3">Nama Barang</th>
                                        <th width="15%" class="text-center py-2">Jumlah Minta</th>
                                        <th width="15%" class="text-center py-2">Jumlah ACC</th>
                                        <th class="py-2 px-3">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            $(document).ready(function() {
                let table = $('#table-request').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('request.index') }}",
                        data: function(d) {
                            d.status = $('#filter_status').val();
                        }
                    },
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Cari request...",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        paginate: {
                            previous: "<i class='fa-solid fa-chevron-left'></i>",
                            next: "<i class='fa-solid fa-chevron-right'></i>"
                        }
                    },
                    columns: [{
                            className: 'dt-control text-center align-middle',
                            orderable: false,
                            data: null,
                            defaultContent: '<button type="button" class="btn btn-sm btn-outline-primary btn-expand"><i class="fa-solid fa-chevron-right"></i></button>'
                        },
                        {
                            data: 'no_request',
                            name: 'no_request',
                            className: 'align-middle ps-2 fw-bold text-primary'
                        },
                        {
                            data: 'status',
                            name: 'status',
                            className: 'text-center align-middle',
                            render: function(data) {
                                if (data === 'PENDING') {
                                    return '<span class="badge bg-warning px-3 py-2 text-white">Pending</span>';
                                } else if (data === 'APPROVED') {
                                    return '<span class="badge bg-success px-3 py-2">Approved</span>';
                                } else {
                                    return '<span class="badge bg-danger px-3 py-2">Rejected</span>';
                                }
                            }
                        },
                        {
                            data: 'formatted_date',
                            name: 'created_at',
                            className: 'align-middle text-secondary'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-4',
                            render: function(data) {
                                if (data.can_approve) {
                                    return `
                                        <a class="btn btn-sm btn-success shadow-sm px-3"
                                            href="{{ url('request') }}/${data.id}/approve">
                                            <i class="fa-solid fa-check me-1"></i> ACC Request
                                        </a>
                                    `;
                                }
                                return `
                                    <button type="button" class="btn btn-sm btn-light text-primary border btn-toggle-detail shadow-sm">
                                        <i class="fa-solid fa-eye me-1"></i> Detail
                                    </button>
                                `;
                            }
                        }
                    ]
                });

                $('#btn_filter').click(function() {
                    table.draw();
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

                $('#table-request tbody').on('click', 'button.btn-expand, button.btn-toggle-detail', function() {
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
                            $('#modal-container').find('.modal').first().modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message ||
                                'Anda tidak memiliki hak akses untuk tindakan ini.');
                        }
                    });
                });

                if (new URLSearchParams(window.location.search).get('create') === '1') {
                    setTimeout(() => $('[data-url="{{ route('request.create') }}"]').first().trigger('click'), 100);
                }
            });
        </script>
    @endpush
@endsection
