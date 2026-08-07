@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h4 class="card-title">Halaman Master Kategori</h4>
                            @if ($user['type'] != 29)
                                <button class="btn btn-primary btn-sm" id="btnAddKategori">Tambah Kategori</button>
                            @endif
                        </div>
                        <div class="card-body">
                            <table id="kategoriTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Kategori</th>
                                        @if ($user['type'] != 29)
                                            <th>Aksi</th>
                                        @endif
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

    @if ($user['type'] != 29)
        <div class="modal fade" id="kategoriModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="kategoriForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitle">Tambah Kategori</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="kategoriId">
                            <div class="mb-3">
                                <label for="katnama">Nama Kategori</label>
                                <input type="text" id="katnama" name="katnama" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="saveKategori">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let userType = "{{ $user['type'] }}";

            let columnsDefinition = [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'katnama',
                    name: 'katnama'
                }
            ];
            if (userType != "29") {
                columnsDefinition.push({
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-warning btn-sm btnEdit" data-id="${row.katid}" data-name="${row.katnama}">Edit</button>
                            <button class="btn btn-danger btn-sm btnDelete" data-id="${row.katid}">Hapus</button>
                        `;
                    }
                });
            }

            let kategoriTable = $('#kategoriTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/kategori/fetch',
                    type: 'GET'
                },
                columns: columnsDefinition,
                columnDefs: [{
                        targets: 0,
                        className: 'text-center'
                    },
                    {
                        targets: (userType != "29" ? 2 : 1),
                        className: userType != "29" ? 'text-center' : ''
                    }
                ],
                order: [
                    [1, 'asc']
                ]
            });

            if (userType != "29") {
                $('#btnAddKategori').click(function() {
                    $('#modalTitle').text('Tambah Kategori');
                    $('#kategoriForm')[0].reset();
                    $('#kategoriId').val('');
                    $('#kategoriModal').modal('show');
                });

                $('#kategoriForm').submit(function(e) {
                    e.preventDefault();
                    let id = $('#kategoriId').val();
                    let url = id ? `/kategori/update/${id}` : '/kategori/store';
                    let method = id ? 'PUT' : 'POST';

                    $.ajax({
                        url: url,
                        method: method,
                        data: {
                            katnama: $('#katnama').val(),
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#kategoriModal').modal('hide');
                            kategoriTable.ajax.reload();
                            Swal.fire('Berhasil!', response.success, 'success');
                        },
                        error: function() {
                            Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
                        }
                    });
                });

                $(document).on('click', '.btnEdit', function() {
                    $('#modalTitle').text('Edit Kategori');
                    $('#kategoriId').val($(this).data('id'));
                    $('#katnama').val($(this).data('name'));
                    $('#kategoriModal').modal('show');
                });

                $(document).on('click', '.btnDelete', function() {
                    let kategoriId = $(this).data('id');
                    Swal.fire({
                        title: 'Yakin ingin menghapus?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `/kategori/delete/${kategoriId}`,
                                method: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    kategoriTable.ajax.reload();
                                    Swal.fire('Berhasil!', response.success, 'success');
                                }
                            });
                        }
                    });
                });
            }
        });
    </script>
@endpush
