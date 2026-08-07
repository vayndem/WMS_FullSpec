@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">Master Tipe Pembebanan</h4>
                            <p class="mb-0 text-muted">Kelola kategori tipe pembebanan akuntansi untuk bahan</p>
                        </div>
                        @can('create', App\Models\TipePembebanan::class)
                            <button type="button" class="btn btn-primary add-list shadow-sm btn-open-create-modal">
                                <i class="fa-solid fa-plus me-2"></i>Tambah Tipe Pembebanan
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="table-tipe-pembebanan"
                                    width="100%" cellspacing="0"
                                    data-report-url="{{ route('tipe-pembebanan.report.pdf') }}"
                                    data-filter-columns="1:nama_tipe,2:keterangan">
                                    <thead class="bg-light text-uppercase font-size-12">
                                        <tr>
                                            <th width="5%" class="text-center py-3 ps-3">#</th>
                                            <th width="30%" class="py-3">Nama Tipe</th>
                                            <th class="py-3">Keterangan</th>
                                            <th width="15%" class="text-center py-3 pe-3">Aksi</th>
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
                let table = $('#table-tipe-pembebanan').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('tipe-pembebanan.index') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle ps-3'
                        },
                        {
                            data: 'nama_tipe',
                            name: 'nama_tipe',
                            className: 'align-middle fw-bold text-primary'
                        },
                        {
                            data: 'keterangan',
                            name: 'keterangan',
                            className: 'align-middle',
                            render: function(data) {
                                return data ? data : '-';
                            }
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
                                        <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-edit" data-id="${data.id}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    `;
                                }
                                if (data.can_delete) {
                                    btnDelete = `
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${data.id}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    `;
                                }
                                return btnEdit + btnDelete;
                            }
                        }
                    ]
                });

                $(document).on('click', '.btn-open-create-modal', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: "{{ route('tipe-pembebanan.create') }}",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#createTipePembebananModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form.');
                        }
                    });
                });

                $(document).on('submit', '#form-store-tipe-pembebanan', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "POST",
                        data: $(this).serialize(),
                        dataType: "JSON",
                        success: function(res) {
                            if (res.success) {
                                $('#createTipePembebananModal').modal('hide');
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            AppAlert.auto(xhr.responseJSON?.message || "Gagal menyimpan data.");
                        }
                    });
                });

                $(document).on('click', '.btn-edit', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    $.ajax({
                        url: "/tipe-pembebanan/" + id + "/edit",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#editTipePembebananModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form edit.');
                        }
                    });
                });

                $(document).on('submit', '#form-update-tipe-pembebanan', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "PUT",
                        data: $(this).serialize(),
                        dataType: "JSON",
                        success: function(res) {
                            if (res.success) {
                                $('#editTipePembebananModal').modal('hide');
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            AppAlert.auto(xhr.responseJSON?.message || "Gagal memperbarui data.");
                        }
                    });
                });

                $(document).on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    AppAlert.confirm("Hapus tipe pembebanan ini?").then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "/tipe-pembebanan/" + id,
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
            });
        </script>
    @endpush
@endsection
