@extends('layouts.app')

@section('content')
    <style>
        .modal-custom-width {
            max-width: 90%;
        }

        .custom-table-border th,
        .custom-table-border td {
            border: 1px solid #ede5e5e6;
            padding: 2px;
        }

        .column-search-input {
            padding: 5px;
            background-color: #ffffff;
            color: #f30d09;
            border: 1px solid #555;
            font-size: 12px;
            margin-left: 5px;
        }

        #searchRow {
            background-color: #333;
            color: #fff;
        }
    </style>
    <div class="content-page">
        <div class="container-fluid">
            <div class="row py-3 px-3">
                <h4>Permintaan Bahan {{ $jenis == 0 ? 'Bahan Penunjang' : 'Bahan Penolong' }}</h4>
                <button class="btn btn-outline-success ms-2" type="button" data-bs-toggle="modal" id="tombolTambah">
                    <i class="fa fa-plus-square"></i>
                </button>
                <select id="filterStatus" class="form-control ms-3" style="width: 200px; display: inline-block;">
                    <option value="0" selected>Proses</option>
                    <option value="1">Finish</option>
                    <option value="">Semua Status</option>
                </select>
                <button class="btn btn-success ms-2" type="button" id="tombolExport">
                    <i class="fa fa-file-excel"></i> Export
                </button>
            </div>
            <table id="permintaanTable" class="table table-bordered" style="margin-top: 15px">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal Permintaan</th>
                        <th>Bahan</th>
                        <th>Satuan</th>
                        <th>Jumlah Order</th>
                        <th>Realisasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <div class="viewmodalpencarian" style="display: none;"></div>

    <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Permintaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPermintaanForm">
                        <input type="hidden" id="id_permintaan">
                        <div class="mb-3">
                            <label for="jumlah_order_edit">Jumlah Order</label>
                            <input type="number" class="form-control" id="jumlah_order_edit" step="0.01" required>
                        </div>
                        <button type="submit" id="submitButton" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let jenis = {{ $jenis }};
        let table = [];

        function editPermintaan(id, jumlah_order) {
            $('#id_permintaan').val(id);
            $('#jumlah_order_edit').val(jumlah_order);
            $('#editModal').modal('show');
        }

        function reload() {
            $('#permintaanTable').DataTable().ajax.reload(null, false);
        }

        function deletePermintaan(id) {
            Swal.fire({
                title: "Yakin ingin menghapus?",
                text: "Data yang sudah dihapus tidak bisa dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/permintaan/" + id,
                        type: "DELETE",
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: "Menghapus...",
                                text: "Mohon tunggu sebentar",
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            Swal.fire({
                                title: "Berhasil!",
                                text: response.message || "Data berhasil dihapus.",
                                icon: "success",
                                timer: 2000,
                                showConfirmButton: false
                            });
                            reload();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: "Gagal!",
                                text: "Terjadi kesalahan saat menghapus data.",
                                icon: "error"
                            });
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            table = $('#permintaanTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/homepermintaan/{{ $jenis }}",
                    type: 'GET',
                    data: function(d) {
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        orderable: true,
                        searchable: true,
                        render: function(data, type, row) {
                            if (data) {
                                var date = new Date(data);
                                return date.getFullYear() + '-' +
                                    ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                                    ('0' + date.getDate()).slice(-2);
                            }
                            return '';
                        }
                    },
                    {
                        data: 'bahan',
                        name: 'bahan.nama',
                        orderable: false,
                    },
                    {
                        data: 'satuan',
                        name: 'bahan.satuan',
                        orderable: false,
                    },
                    {
                        data: 'jumlah_order',
                        name: 'jumlah_order',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                    {
                        data: 'realisasi',
                        name: 'realisasi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#tombolTambah').on('click', function() {
                $.ajax({
                    type: "get",
                    url: "/permintaan/create",
                    data: {
                        jenis: jenis
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $('#tombolTambah').prop('disabled', true).html(
                            '<i class="fa fa-spin fa-spinner"></i> Loading...');
                    },
                    complete: function() {
                        $('#tombolTambah').prop('disabled', false).html(
                            '<i class="fa fa-plus-square"></i>');
                    },
                    success: function(response) {
                        if (response.data) {
                            $('.viewmodalpencarian').html(response.data).show();
                            $('#pilihmodalbahan').modal('show');
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        AppAlert.auto(xhr.status + '\n' + thrownError);
                    }
                });
            });
            $('#editPermintaanForm').on('submit', function(e) {
                e.preventDefault();
                var id = $('#id_permintaan').val();
                var jumlah_order = $('#jumlah_order_edit').val();
                $('#submitButton').attr('disabled', true).text('Updating...');
                $.ajax({
                    type: 'PUT',
                    url: '/permintaan/' + id,
                    data: {
                        id: id,
                        jumlah_order: jumlah_order,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        AppAlert.auto(response.message);
                        $('#editModal').modal('hide');
                        reload();
                    },
                    error: function(xhr, status, error) {
                        AppAlert.auto('Terjadi kesalahan: ' + error);
                    },
                    complete: function() {
                        $('#submitButton').attr('disabled', false).text('Update');
                    }
                });
            });
            $('#filterStatus').on('change', function() {
                table.ajax.reload();
            });

            $('#tombolExport').on('click', function() {
                const exportButton = $(this);
                const today = new Date().toISOString().split('T')[0];

                Swal.fire({
                    title: 'Export Data Permintaan',
                    html: `
                                <div style="text-align: left; margin-top: 1rem;">
                                    <label for="swal_tanggal_awal" style="display: block; margin-bottom: .5rem;">Tanggal Awal:</label>
                                    <input type="date" id="swal_tanggal_awal" class="swal2-input" value="${today}" style="width: 100%;">
                                </div>
                                <div style="text-align: left; margin-top: 1rem;">
                                    <label for="swal_tanggal_akhir" style="display: block; margin-bottom: .5rem;">Tanggal Akhir:</label>
                                    <input type="date" id="swal_tanggal_akhir" class="swal2-input" value="${today}" style="width: 100%;">
                                </div>
                            `,
                    confirmButtonText: 'Export',
                    showCancelButton: true,
                    cancelButtonText: 'Batal',
                    preConfirm: () => {
                        const startDate = document.getElementById('swal_tanggal_awal').value;
                        const endDate = document.getElementById('swal_tanggal_akhir').value;

                        if (!startDate || !endDate) {
                            Swal.showValidationMessage(
                                'Tanggal awal dan tanggal akhir harus diisi!');
                            return false;
                        }

                        return {
                            startDate: startDate,
                            endDate: endDate
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let status = '';
                        let {
                            startDate,
                            endDate
                        } = result.value;
                        let url =
                            `/permintaan/export/${jenis}?status=${status}&start_date=${startDate}&end_date=${endDate}`;
                        exportButton.prop('disabled', true)
                            .html('<i class="fa fa-spin fa-spinner"></i> Sedang proses...');
                        window.location.href = url;
                        setTimeout(() => {
                            exportButton.prop('disabled', false)
                                .html('<i class="fa fa-file-excel"></i> Export');
                        }, 4000);

                    }
                });
            });
        });
    </script>
@endpush
