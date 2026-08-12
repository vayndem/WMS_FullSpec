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
                            <h3 class="mb-1 fw-bold text-dark">Daftar Pengeluaran Barang (NPK)</h3>
                            <p class="mb-0 text-muted">Kelola seluruh transaksi pengeluaran barang gudang</p>
                        </div>
                        @can('create', App\Models\Npk::class)
                            <button type="button" class="btn btn-primary shadow-sm btn-open-modal px-3 py-2"
                                data-url="{{ route('npk.create') }}">
                                <i class="fa-solid fa-plus me-2"></i>Buat NPK Baru
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
                                        <option value="DRAFT">Draft</option>
                                        <option value="POSTED" selected>Keluar</option>
                                        <option value="REVERSED">Reversed</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button id="btn_filter" class="btn btn-info text-white w-100 shadow-sm">
                                        <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="table-npk" width="100%" cellspacing="0"
                                    data-report-url="{{ route('npk.report.pdf') }}"
                                    data-filter-columns="{{ $financial
                                        ? '0:kode,1:kode_datapesanan,2:tanggal,3:nama_barang,4:jumlah,7:status,8:operator'
                                        : '0:kode,1:kode_datapesanan,2:tanggal,3:nama_barang,4:jumlah,5:status,6:operator' }}">
                                    <thead>
                                        <tr>
                                            <th width="15%" class="py-3 ps-2">Kode NPK</th>
                                            <th width="15%" class="py-3">Kode Pesanan</th>
                                            <th width="12%" class="py-3">Tanggal</th>
                                            <th width="20%" class="py-3">Nama Barang</th>
                                            <th width="10%" class="text-center py-3">Jumlah</th>
                                            @if ($financial)
                                                <th class="text-end py-3">Harga Rata-rata</th>
                                                <th class="text-end py-3">Nilai Pemakaian</th>
                                            @endif
                                            <th width="10%" class="text-center py-3">Status</th>
                                            <th width="10%" class="py-3">Operator</th>
                                            <th width="8%" class="text-center py-3 pe-4">Aksi</th>
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
                let table = $('#table-npk').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('npk.index') }}",
                        data: function(d) {
                            d.status = $('#filter_status').val();
                        }
                    },
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Cari NPK...",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        paginate: {
                            previous: "<i class='fa-solid fa-chevron-left'></i>",
                            next: "<i class='fa-solid fa-chevron-right'></i>"
                        }
                    },
                    columns: [{
                            data: 'kode',
                            name: 'kode',
                            className: 'align-middle ps-2 fw-bold text-primary'
                        },
                        {
                            data: 'kode_datapesanan',
                            name: 'kode_datapesanan',
                            className: 'align-middle text-secondary',
                            defaultContent: '-'
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
                            className: 'align-middle text-secondary'
                        },
                        {
                            data: 'nama_barang',
                            name: 'barang.nama',
                            className: 'align-middle fw-bold text-dark'
                        },
                        {
                            data: 'jumlah_display',
                            name: 'jumlah',
                            className: 'text-center align-middle fw-bold text-primary'
                        },
                        @if ($financial)
                            {
                                data: 'harga_satuan',
                                name: 'harga_satuan',
                                className: 'text-end align-middle',
                                render: data => 'Rp ' + Number(data || 0).toLocaleString('id-ID')
                            }, {
                                data: 'total_nilai',
                                name: 'total_nilai',
                                className: 'text-end align-middle fw-semibold',
                                render: data => 'Rp ' + Number(data || 0).toLocaleString('id-ID')
                            },
                        @endif {
                            data: 'status',
                            name: 'status',
                            className: 'text-center align-middle',
                            render: function(data) {
                                if (data === 'POSTED') {
                                    return '<span class="badge bg-success px-3 py-2">Keluar</span>';
                                } else if (data === 'REVERSED') {
                                    return '<span class="badge bg-danger px-3 py-2">Reversed</span>';
                                } else {
                                    return '<span class="badge bg-warning px-3 py-2 text-white">Draft</span>';
                                }
                            }
                        },
                        {
                            data: 'operator',
                            name: 'operator',
                            className: 'align-middle text-secondary',
                            defaultContent: '-'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-4',
                            render: function(data) {
                                let btnHtml = '';
                                if (data.can_update) {
                                    btnHtml += `
                                        <button type="button" class="btn btn-sm btn-info shadow-sm btn-open-modal me-1 px-2"
                                            data-url="{{ url('npk') }}/${data.id}/edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    `;
                                }
                                if (data.can_delete) {
                                    btnHtml += `
                                        <button type="button" class="btn btn-sm btn-danger shadow-sm btn-delete-npk px-2"
                                            data-id="${data.id}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    `;
                                }
                                return btnHtml || '-';
                            }
                        }
                    ]
                });

                $('#btn_filter').click(function() {
                    table.draw();
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

                $(document).on('click', '.btn-delete-npk', function() {
                    let id = $(this).data('id');
                    AppAlert.confirm('Hapus draft pengeluaran barang ini?').then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `{{ url('npk') }}/${id}`,
                                type: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    table.ajax.reload();
                                    AppAlert.auto(response.message);
                                },
                                error: function(err) {
                                    AppAlert.auto(err.responseJSON?.message ||
                                        'Gagal menghapus data.');
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
