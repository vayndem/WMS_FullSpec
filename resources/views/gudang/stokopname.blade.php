@extends('layouts.app')

@section('content')
    @php
        $userData = session('user_data');
        $userType = $userData['type'] ?? null;
    @endphp

    <style>
        body.dark-mode .dataTables_paginate .paginate_button {
            color: #fff !important;
            border-color: #555;
        }

        body.dark-mode .dataTables_paginate .paginate_button.current {
            background-color: #007bff !important;
            color: #fff !important;
        }

        body:not(.dark-mode) .dataTables_paginate .paginate_button {
            color: #333 !important;
            border-color: #ddd;
        }

        body:not(.dark-mode) .dataTables_paginate .paginate_button.current {
            background-color: #007bff !important;
            color: #fff !important;
        }

        .editable-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            padding: 8px;
            margin: -4px;
            transition: background 0.2s;
            min-height: 35px;
        }

        .editable-container:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .editable-trigger {
            cursor: pointer;
        }
    </style>

    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary">Stok Opname Non-Kertas</h3>
                    @if ($userType == 14)
                        <button id="btnAddOpname" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOpnameModal">
                            Buat Sesi Opname Baru
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="stockOpnameTable" class="table table-striped table-hover compact responsive table-sm"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>Lihat Detail</th>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th class="text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addOpnameModal" tabindex="-1" aria-labelledby="addOpnameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formAddOpname">
                    <input type="hidden" id="opname_id" name="opname_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addOpnameModalLabel">Tambah Stok Opname</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="gudang_id">Pilih Gudang Asal</label>
                            <select class="form-control" id="gudang_id" name="gudang_id" required>
                                <option value="" disabled selected>-- Pilih Gudang --</option>
                                @foreach (DB::table('admin_namagudang')->orderBy('id', 'asc')->get() as $gudang)
                                    <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="kode">Kode</label>
                            <input type="text" class="form-control" id="kode" name="kode" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const userType = {{ $userType }};
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#addOpnameModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var opnameId = button.data('id');
                var form = $('#formAddOpname');
                form[0].reset();
                $('#opname_id').val('');
                $('#gudang_id').prop('disabled', false);

                if (opnameId) {
                    $('#addOpnameModalLabel').text('Edit Stok Opname');
                    $('#gudang_id').prop('disabled', true);
                    $.get("{{ url('gudang/getStockOpname') }}/" + opnameId, function(response) {
                        if (response.success) {
                            $('#opname_id').val(response.data.id);
                            $('#gudang_id').val(response.data.gudang_id);
                            $('#kode').val(response.data.kode);
                            $('#tanggal').val(response.data.tanggal);
                        }
                    });
                } else {
                    $('#addOpnameModalLabel').text('Tambah Stok Opname');
                    $('#kode').val('');
                }
            });

            $('#gudang_id').on('change', function() {
                var gudangId = $(this).val();
                var opnameId = $('#opname_id').val();

                if (!opnameId && gudangId !== null) {
                    $.ajax({
                        url: "{{ route('gudang.generateStockOpnameCode') }}",
                        method: "GET",
                        data: {
                            gudang_id: gudangId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#kode').val(response.kode);
                            }
                        }
                    });
                }
            });

            var table = $('#stockOpnameTable').DataTable({
                processing: true,
                serverSide: true,
                order: [],
                ajax: "{{ route('gudang.getStockOpnameData') }}",
                scrollX: true,
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        defaultContent: '<button class="btn btn-primary btn-sm btn-lihat-detail">Lihat Detail</button>'
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kode',
                        name: 'kode'
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal'
                    },
                    {
                        data: 'flag',
                        name: 'flag',
                        render: function(data) {
                            if (data == 0)
                                return '<span class="badge bg-info">Dalam Pengerjaan</span>';
                            if (data == 1)
                                return '<span class="badge bg-warning">Menunggu Persetujuan</span>';
                            return '<span class="badge bg-success">Disetujui</span>';
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                lengthChange: false,
                searching: false,
                paging: true,
                info: true
            });

            $('#formAddOpname').on('submit', function(event) {
                event.preventDefault();
                var id = $('#opname_id').val();
                var url = id ? "{{ url('gudang/updateStockOpname') }}/" + id :
                    "{{ route('gudang.storeStockOpname') }}";

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Berhasil', response.message, 'success');
                            $('#addOpnameModal').modal('hide');
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            });

            $('#stockOpnameTable tbody').on('click', '.btn-edit', function() {
                var btn = $(this);
                $('#addOpnameModal').modal('show', btn);
            });

            $('#stockOpnameTable tbody').on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data detail stok opname juga akan dihapus!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('gudang/deleteStockOpname') }}/" + id,
                            type: 'DELETE',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Terhapus!', response.message, 'success');
                                    table.ajax.reload(null, false);
                                } else {
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            $('#stockOpnameTable tbody').on('click', '.btn-lihat-detail', function() {
                var tr = $(this).closest('tr');
                var row = table.row(tr);

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                } else {
                    var rowData = row.data();
                    var kode = rowData.kode;
                    var flag = rowData.flag;
                    var id = rowData.id;

                    var actionButtonHtml = ((userType == 11 || userType == 33) && flag == 0) ? `<div class="p-3 text-end">
                        <button class="btn btn-success btn-kirim-pengajuan" data-id="${id}">
                            <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                        </button>
                    </div>` : '';

                    var tableHeader = `
                        <tr>
                            <th>No</th>
                            <th>Nama Bahan</th>
                            <th>Kategori</th>
                            ${(userType == 11 || userType == 33) ? '<th>Harga</th>' : ''}
                            <th>Stok Sistem</th>
                            <th>Stok Fisik</th>
                            <th>Selisih</th>
                            ${(userType == 11 || userType == 33) ? '<th>Total Kerugian</th>' : ''}
                        </tr>`;

                    var detailTableHtml = `<div style="padding: 10px;">
                        <table id="detailTable-${kode}" class="table table-sm table-striped table-bordered" width="100%">
                            <thead>${tableHeader}</thead>
                        </table>
                        ${actionButtonHtml}
                    </div>`;

                    row.child(detailTableHtml).show();
                    tr.addClass('shown');

                    var detailColumns = [{
                            data: 'id_detail',
                            name: 'id_detail'
                        },
                        {
                            data: 'nama_bahan',
                            name: 'nama_bahan'
                        },
                        {
                            data: 'nama_kategori',
                            name: 'nama_kategori'
                        }
                    ];

                    if (userType == 11 || userType == 33) {
                        detailColumns.push({
                            data: 'harga',
                            name: 'harga',
                            render: function(data, type, row) {
                                var formattedHarga = new Intl.NumberFormat('id-ID').format(
                                    data);
                                if (flag != 0) return formattedHarga;
                                return `<div class="editable-container" data-id="${row.id_detail}" data-column="harga">
                                            <span class="editable-value">${data}</span>
                                            <i class="fas fa-pencil-alt text-primary editable-icon"></i>
                                        </div>`;
                            }
                        });
                    }

                    detailColumns.push({
                        data: 'stok_sistem',
                        name: 'stok_sistem'
                    });

                    detailColumns.push({
                        data: 'stok_real',
                        name: 'stok_real',
                        render: function(data, type, row) {
                            if (flag != 0) return data;
                            if (userType == 14) {
                                return `<div class="editable-container" data-id="${row.id_detail}" data-column="stok_real">
                                        <span class="editable-value">${data}</span>
                                        <i class="fas fa-pencil-alt text-primary editable-icon"></i>
                                    </div>`;
                            }
                            return data;
                        }
                    });

                    detailColumns.push({
                        data: 'selisih',
                        name: 'selisih'
                    });

                    if (userType == 11 || userType == 33) {
                        detailColumns.push({
                            data: 'kerugian',
                            name: 'kerugian',
                            render: function(data) {
                                return new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    maximumFractionDigits: 0
                                }).format(data);
                            }
                        });
                    }

                    var detailTable = $(`#detailTable-${kode}`).DataTable({
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        ajax: {
                            url: "{{ route('gudang.getDetailStockOpname') }}",
                            type: "GET",
                            data: {
                                kode: kode
                            }
                        },
                        columns: detailColumns,
                        paging: true,
                        searching: true,
                        info: true
                    });

                    $(`#detailTable-${kode} tbody`).on('click', '.editable-container', function() {
                        var container = $(this);
                        var valueSpan = container.find('.editable-value');
                        var icon = container.find('.editable-icon');
                        if (valueSpan.find('input').length) return;

                        var originalValue = valueSpan.text().trim();
                        var id_detail = container.data('id');
                        var column = container.data('column');
                        var input = $(
                            `<input type="number" class="form-control form-control-sm" value="${originalValue}" style="width: 100px;">`
                        );

                        valueSpan.html(input);
                        icon.hide();
                        input.focus().select();

                        input.on('blur keydown', function(e) {
                            if (e.type === 'blur' || (e.type === 'keydown' && e.key ===
                                    'Enter')) {
                                var newValue = $(this).val().trim();
                                if (newValue === originalValue) {
                                    valueSpan.text(originalValue);
                                    icon.show();
                                    return;
                                }

                                $.ajax({
                                    url: "{{ route('gudang.updateDetailStockOpname') }}",
                                    method: "POST",
                                    data: {
                                        id_detail: id_detail,
                                        column: column,
                                        value: newValue
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            detailTable.ajax.reload(null,
                                                false);
                                        } else {
                                            Swal.fire('Gagal!', response
                                                .message, 'error');
                                            valueSpan.text(originalValue);
                                            icon.show();
                                        }
                                    }
                                });
                            }
                        });
                    });
                }
            });

            $('#stockOpnameTable tbody').on('click', '.btn-kirim-pengajuan', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Kirim Pengajuan?',
                    text: "Data tidak akan bisa diubah setelah dikirim!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kirim!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('gudang.completeStockOpname', '') }}/" + id, function(
                            response) {
                            Swal.fire('Terkirim!', response.message, 'success');
                            table.ajax.reload();
                        });
                    }
                });
            });
        });
    </script>
@endpush
