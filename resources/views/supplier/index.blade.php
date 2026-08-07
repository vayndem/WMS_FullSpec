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
                            <h3 class="mb-1 fw-bold text-dark">Daftar Supplier</h3>
                            <p class="mb-0 text-muted">Kelola data vendor dan supplier perusahaan</p>
                        </div>
                        @can('create', App\Models\Supplier::class)
                            <button type="button" class="btn btn-primary shadow-sm btn-open-modal px-3 py-2"
                                data-url="{{ route('supplier.create') }}">
                                <i class="fa-solid fa-plus me-2"></i>Tambah Supplier
                            </button>
                        @endcan
                    </div>
                </div>

                @if (session('success'))
                    <div class="col-lg-12">
                        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    </div>
                @endif

                <div class="col-lg-12">
                    <div class="card border-0 mb-4">
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="table-supplier" width="100%" cellspacing="0"
                                    data-report-url="{{ route('supplier.report.pdf') }}"
                                    data-filter-columns="0:nama,1:alamat,2:npwp,3:telp,4:up,5:pembayaran">
                                    <thead>
                                        <tr>
                                            <th class="py-3 ps-3">Nama</th>
                                            <th class="py-3">Alamat</th>
                                            <th class="py-3">NPWP</th>
                                            <th class="py-3">Telp</th>
                                            <th class="py-3">UP</th>
                                            <th class="py-3">Pembayaran</th>
                                            <th width="12%" class="text-center py-3 pe-3">Aksi</th>
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
            $(document).ready(function() {
                let table = $('#table-supplier').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('supplier.index') }}",
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Cari supplier...",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        paginate: {
                            previous: "<i class='fa-solid fa-chevron-left'></i>",
                            next: "<i class='fa-solid fa-chevron-right'></i>"
                        }
                    },
                    columns: [{
                            data: 'nama',
                            name: 'nama',
                            className: 'align-middle ps-3 fw-bold text-primary'
                        },
                        {
                            data: 'alamat',
                            name: 'alamat',
                            className: 'align-middle text-secondary',
                            defaultContent: '-'
                        },
                        {
                            data: 'npwp',
                            name: 'npwp',
                            className: 'align-middle text-secondary',
                            defaultContent: '-'
                        },
                        {
                            data: 'telp',
                            name: 'telp',
                            className: 'align-middle text-secondary',
                            defaultContent: '-'
                        },
                        {
                            data: 'up',
                            name: 'up',
                            className: 'align-middle text-secondary',
                            defaultContent: '-'
                        },
                        {
                            data: 'pembayaran',
                            name: 'pembayaran',
                            className: 'align-middle text-secondary',
                            defaultContent: '-'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-3',
                            render: function(data) {
                                let btnEdit = '';
                                let btnDelete = '';

                                if (data.can_update) {
                                    btnEdit = `
                                        <button type="button" class="btn btn-sm btn-light text-warning me-1 btn-open-modal shadow-sm"
                                            data-url="{{ url('supplier') }}/${data.id}/edit" title="Edit Supplier">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    `;
                                }

                                if (data.can_delete) {
                                    btnDelete = `
                                        <form action="{{ url('supplier') }}/${data.id}" method="POST" class="d-inline form-delete-supplier">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger shadow-sm" title="Hapus Supplier">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    `;
                                }

                                return `<div class="d-flex justify-content-center align-items-center">${btnEdit}${btnDelete}</div>`;
                            }
                        }
                    ]
                });
                $(document).on('submit', '.form-delete-supplier', function(e) {
                    if ($(this).data('confirmed')) return;
                    e.preventDefault();
                    const form = this;
                    AppAlert.confirm('Yakin ingin menghapus supplier ini?').then(result => {
                        if (result.isConfirmed) {
                            $(form).data('confirmed', true);
                            form.submit();
                        }
                    });
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
                            $('#modal-container').find('.modal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form modal.');
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
