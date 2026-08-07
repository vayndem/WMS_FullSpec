@extends('layouts.app')

@section('content')
    <style>
        :root {
            --detail-row-bg-light: #ffffff;
            --detail-row-color-light: #000;
            --header-row-bg-light: #f0f0f0;
            --header-row-color-light: #333;

            --detail-row-bg-dark: #333;
            --detail-row-color-dark: #fff;
            --header-row-bg-dark: #555;
            --header-row-color-dark: #fff;
        }

        body:not(.dark-mode) .detail-row td {
            background-color: var(--detail-row-bg-light);
            color: var(--detail-row-color-light);
        }

        body:not(.dark-mode) .header-row td {
            font-weight: bold;
            background-color: var(--header-row-bg-light);
            color: var(--header-row-color-light);
        }

        body.dark-mode .detail-row td {
            background-color: var(--detail-row-bg-dark);
            color: var(--detail-row-color-dark);
        }

        body.dark-mode .header-row td {
            font-weight: bold;
            background-color: var(--header-row-bg-dark);
            color: var(--header-row-color-dark);
        }

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
            color: #000000 !important;
        }

        .table-responsive,
        .dataTables_wrapper {
            overflow: visible !important;
        }

        table.dataTable {
            overflow: visible !important;
        }

        table.dataTable tbody td,
        table.dataTable tbody tr {
            overflow: visible !important;
            position: relative;
        }

        .dropdown-menu {
            z-index: 3000 !important;
            position: absolute !important;
        }
    </style>

    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary">Laporan Penerimaan Barang (LPB)</h3>
                    <div>
                        <button id="btnAddLpb" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahLpbModal">
                            Tambah LPB
                        </button>
                        <button class="btn btn-success ms-2">
                            Export ke Excel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bulantahun">Filter Bulan dan Tahun:</label>
                                <input type="month" id="bulantahun" class="form-control" value="{{ date('Y-m') }}"
                                    required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="filterLpbType">Filter Jenis LPB:</label>
                                <select id="filterLpbType" class="form-control">
                                    @if ($user['type'] == 5)
                                        <option value="all">All</option>
                                        <option value="LPBPO">LPBPO</option>
                                        <option value="LPBPP">LPBPP</option>
                                        <option value="LPBMO">LPBMO</option>
                                    @else
                                        <option value="all">All</option>
                                        <option value="LPBPO">LPBPO</option>
                                        <option value="LPBPP">LPBPP</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive p-4 mb-4">
                        <table id="lpbTable" class="table table-striped table-hover compact responsive table-sm"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>ID LPB</th>
                                    <th>Tanggal</th>
                                    <th>Nama Supplier</th>
                                    <th>No. PO</th>
                                    <th>No. Surat Jalan</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tambahLpbModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="tambahLpbModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="tambahLpbModalLabel">Tambah Laporan Penerimaan Barang (LPB)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="overflow-y: auto;">
                    <div class="p-3 mb-4 border rounded">
                        <div class="mb-3 row mb-1">
                            <div class="col-sm-3">
                                <div class="table-responsive" style="font-size:10pt;">
                                    <table id="table_supplier"
                                        class="table table-striped table-hover table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Supplier</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-9">
                                <div class="table-responsive" style="font-size:10pt;">
                                    <table id="table_header"
                                        class="table table-striped table-hover table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>No Purchase Order</th>
                                                <th>Tanggal</th>
                                                <th>No Order</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 mb-4 border rounded">
                        <div class="mb-3 row mb-3">
                            <div class="col-sm-6">
                                <label for="tanggalBarangDiterima">Tanggal Diterima</label>
                                <input type="date" class="form-control" id="tanggalBarangDiterima"
                                    name="tanggalBarangDiterima" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="nomorSuratJalan">Surat Jalan</label>
                                <input type="text" class="form-control" id="nomorSuratJalan" name="nomorSuratJalan"
                                    oninput="this.value = this.value.toUpperCase()" required>
                                <div id="suratJalanAlert" class="mt-2 text-danger" style="display: none;">Nomor Surat Jalan
                                    sudah dipakai!</div>
                            </div>

                        </div>
                        <div class="table-responsive" style="font-size:10pt;">
                            <table id="table_detail" class="table table-striped table-hover table-sm table-bordered"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>No Purchase Order</th>
                                        <th>Nama Barang</th>
                                        <th>Jumlah</th>
                                        <th>Satuan</th>
                                        <th>Kategori</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="p-3 mb-4 border rounded">
                        <div class="table-responsive" style="font-size:10pt;">
                            <table id="table_detailpilih" class="table table-striped table-hover table-sm table-bordered"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>No Purchase Order</th>
                                        <th>Nama Barang</th>
                                        <th>Barang Diterima</th>
                                        <th>Satuan</th>
                                        <th>Kategori</th>
                                        <th>Lot Number</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" id="btnSaveLpb" class="btn btn-primary">Simpan LPB</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inputJumlahModal" tabindex="-1" role="dialog" aria-labelledby="inputJumlahLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="overflow: hidden;">
                <div class="modal-header">
                    <h5 class="modal-title" id="inputJumlahLabel">Input Jumlah Barang Diterima</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="jumlahBarangDiterima">Jumlah Barang Diterima</label>
                        <input type="number" id="jumlahBarangDiterima" class="form-control"
                            placeholder="Masukkan jumlah barang diterima" required>
                        <small id="jumlahDiterimaError" class="text-danger" style="display: none;"></small>
                    </div>
                    <div class="mb-3">
                        <label for="lotNumber">Lot Number</label>
                        <div class="input-group">
                            <input type="text" id="lotNumber" class="form-control" placeholder="Masukkan Lot Number">
                            <div class="d-flex">
                                <button class="btn btn-outline-secondary" type="button" id="scanBarcodeBtn">
                                    <i class="fa fa-qrcode"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="confirmInputJumlah" class="btn btn-primary">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let debounceTimeout;

        function showLoading(button, text = 'Mohon tunggu...') {
            if (!button.data('original-html')) {
                button.data('original-html', button.html());
            }
            button.prop('disabled', true);
            button.html(`<i class="fas fa-spinner fa-spin"></i> ${text}`);
        }

        function hideLoading(button) {
            if (button.data('original-html')) {
                button.html(button.data('original-html'));
            }
            button.prop('disabled', false);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('dark-mode');

            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                darkModeToggle.checked = true;
            } else {
                document.body.classList.remove('dark-mode');
            }

            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
            });
        });

        $(document).ready(function() {
            var table = $('#lpbTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: true,
                pagingType: "full_numbers",
                ajax: {
                    url: "/gudang/lpbData",
                    data: function(d) {
                        d.filterMonthYear = $('#bulantahun').val();
                        d.searchTerm = d.search.value;
                        d.filterLpbType = $('#filterLpbType').val();
                    },
                    dataSrc: function(json) {
                        return json.data;
                    }
                },
                columns: [{
                        className: 'details-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '<i class="fas fa-plus-square" style="cursor:pointer; font-size:18px;"></i>',
                        width: "5%"
                    },
                    {
                        data: 'id_lpb',
                        name: 'id_lpb',
                        width: "15%"
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal',
                        width: "15%",
                        render: function(data, type, row) {
                            if (row.kunci == 1) {
                                return data;
                            } else {
                                return `<span class="editable" data-id="${row.id_lpb}" data-column="tanggal">${data}</span> <i class="fas fa-pencil-alt text-primary"></i>`;
                            }
                        }
                    },
                    {
                        data: 'supplier_nama',
                        name: 'supplier_nama',
                        width: "15%"
                    },
                    {
                        data: 'no_po',
                        name: 'no_po',
                        width: "15%"
                    },
                    {
                        data: 'no_sj',
                        name: 'no_sj',
                        width: "15%",
                        render: function(data, type, row) {
                            if (row.kunci == 1) {
                                return data;
                            } else {
                                return `<span class="editable" data-id="${row.id_lpb}" data-column="no_sj">${data}</span> <i class="fas fa-pencil-alt text-primary"></i>`;
                            }
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: "15%"
                    }
                ],
                order: [
                    [2, 'desc']
                ],
                drawCallback: function(settings) {
                    if (settings.json.recordsTotal > 0) {
                        $('#lpbTable_paginate').show();
                    } else {
                        $('#lpbTable_paginate').hide();
                    }
                }
            });

            $('#bulantahun, #filterLpbType').on('change', function() {
                table.ajax.reload();
            });


            $('#lpbTable tbody').on('click', '.editable', function(e) {
                e.stopPropagation();
                var $this = $(this);

                if ($this.find('input').length > 0) {
                    return;
                }

                var currentValue = $this.text().trim();
                var id = $this.data('id');
                var column = $this.data('column');
                var originalValue = currentValue;

                if (column === 'tanggal') {
                    var input = $('<input type="date" class="form-control form-control-sm" value="' +
                        currentValue + '">');
                } else {
                    var input = $(
                        '<input type="text" class="form-control form-control-sm" oninput="this.value = this.value.toUpperCase()" value="' +
                        currentValue + '">');
                }

                $this.html(input);
                input.focus().select();

                input.on('blur keydown', function(e) {
                    if (e.type === 'blur' || (e.type === 'keydown' && e.key === 'Enter')) {
                        var newValue = $(this).val().trim();
                        if (newValue !== originalValue) {
                            Swal.fire({
                                title: 'Konfirmasi Update',
                                text: 'Apakah Anda yakin ingin memperbarui data?',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya',
                                cancelButtonText: 'Tidak'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $this.html('<i class="fas fa-spinner fa-spin"></i>');
                                    $.ajax({
                                        url: "/gudang/updateLpbData",
                                        method: "POST",
                                        data: {
                                            id_lpb: id,
                                            type: column,
                                            value: newValue,
                                            _token: $('meta[name="csrf-token"]')
                                                .attr('content')
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                $this.html(
                                                    newValue
                                                );
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Berhasil',
                                                    text: 'Data berhasil diperbarui.'
                                                });
                                            } else {
                                                $this.html(
                                                    originalValue
                                                );
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Gagal',
                                                    text: response
                                                        .message ||
                                                        'Terjadi kesalahan saat memperbarui data.'
                                                });
                                            }
                                        },
                                        error: function(xhr) {
                                            $this.html(
                                                originalValue
                                            );
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: 'Terjadi kesalahan saat memperbarui data.'
                                            });
                                        }
                                    });
                                } else {
                                    $this.html(
                                        originalValue);
                                }
                            });
                        } else {
                            $this.html(originalValue);
                        }
                    }
                });
            });

            function format(data) {
                var childTableId = 'detailTable-' + data.id_lpb;
                var html = `
                    <div style="padding: 10px;">
                        <table id="${childTableId}" class="table table-sm table-striped table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Barang</th>
                                    <th>Keterangan Bahan</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th>Barang Diterima</th>
                                    <th>Lot Number</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                `;
                return html;
            }

            $('#lpbTable tbody').on('click', 'td.details-control', function(e) {
                e.stopPropagation();
                var tr = $(this).closest('tr');
                var row = table.row(tr);

                $('#lpbTable tbody tr.shown').each(function() {
                    if (this !== tr[0]) {
                        var r = table.row(this);
                        r.child.hide();
                        $(this).removeClass('shown');
                        $(this).find('td.details-control').html(
                            '<i class="fas fa-plus-square" style="cursor:pointer; font-size:18px;"></i>'
                        );
                    }
                });

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    $(this).html(
                        '<i class="fas fa-plus-square" style="cursor:pointer; font-size:18px;"></i>');
                } else {
                    row.child(format(row.data())).show();
                    tr.addClass('shown');
                    $(this).html(
                        '<i class="fas fa-minus-square" style="cursor:pointer; font-size:18px;"></i>');

                    var isLocked = row.data().kunci == 1;
                    var childTableSelector = '#detailTable-' + row.data().id_lpb;

                    $(childTableSelector).DataTable({
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        ajax: {
                            url: "/lpb/detail",
                            data: {
                                id_lpb: row.data().id_lpb
                            }
                        },
                        columns: [{
                                data: null,
                                render: function(data, type, rowDetail, meta) {
                                    return meta.row + 1;
                                }
                            },
                            {
                                data: 'nama',
                                title: 'Nama Barang'
                            },
                            {
                                data: 'keterangan_bahan',
                                title: 'Keterangan Bahan'
                            },
                            {
                                data: 'katnama',
                                title: 'Kategori'
                            },
                            {
                                data: 'satuan',
                                title: 'Satuan'
                            },
                            {
                                data: 'jumlah_barang_diterima',
                                title: 'Barang Diterima',
                                render: function(data, type, rowDetail) {
                                    if (isLocked) {
                                        return data;
                                    }
                                    return `<span class="editable" data-id="${rowDetail.id_lpb_detail}" data-column="9">${data}</span> <i class="fas fa-pencil-alt text-primary"></i>`;
                                }
                            },
                            {
                                data: 'lot_number',
                                title: 'Lot Number',
                                render: function(data, type, rowDetail) {
                                    var displayData = data ? data : '-';
                                    if (isLocked) {
                                        return displayData;
                                    }
                                    return `<span class="editable" data-id="${rowDetail.id_lpb_detail}" data-column="10">${displayData}</span> <i class="fas fa-pencil-alt text-primary"></i>`;
                                }
                            },
                            {
                                data: null,
                                title: 'Action',
                                orderable: false,
                                searchable: false,
                                render: function(data, type, rowDetail) {
                                    if (isLocked) {
                                        return `<button class="btn btn-secondary btn-sm" disabled>Terkunci</button>`;
                                    }
                                    return `<button class="btn btn-danger btn-sm btn-delete-detail" data-id="${rowDetail.id_lpb_detail}">Hapus</button>`;
                                }
                            }
                        ],
                        paging: true,
                        searching: true,
                        info: true,
                        pageLength: 3,
                        ordering: false,
                        language: {
                            emptyTable: "Tidak ada detail barang."
                        }
                    });

                    $(`#detailTable-${row.data().id_lpb}`).on('click', '.editable', function(e) {
                        e.stopPropagation();
                        if (isLocked) {
                            return false;
                        }

                        var $this = $(this);
                        if ($this.find('input').length > 0) {
                            return;
                        }
                        var currentValue = $this.text().trim();
                        var id = $this.data('id');
                        var column = $this.data('column');
                        var originalValue = currentValue;
                        var input = $(
                            `<input type="text" class="form-control form-control-sm" value="${currentValue}">`
                        );
                        $this.html(input);
                        input.focus().select();
                        input.on('blur keydown', function(e) {
                            if (e.type === 'blur' || (e.type === 'keydown' && e.key ===
                                    'Enter')) {
                                var newValue = $(this).val().trim();
                                if (newValue !== originalValue) {
                                    Swal.fire({
                                        title: 'Konfirmasi Update',
                                        text: 'Apakah Anda yakin ingin memperbarui data?',
                                        icon: 'question',
                                        showCancelButton: true,
                                        confirmButtonText: 'Ya',
                                        cancelButtonText: 'Tidak'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            $this.html(
                                                '<i class="fas fa-spinner fa-spin"></i>'
                                            );
                                            $.ajax({
                                                url: "/lpb/detail/update",
                                                method: "POST",
                                                data: {
                                                    id: id,
                                                    column: column,
                                                    new_value: newValue,
                                                    _token: $(
                                                        'meta[name="csrf-token"]'
                                                    ).attr(
                                                        'content')
                                                },
                                                success: function(
                                                    response) {
                                                    if (response
                                                        .success) {
                                                        $this.html(
                                                            newValue
                                                        );
                                                        Swal.fire({
                                                            icon: 'success',
                                                            title: 'Berhasil',
                                                            text: 'Data berhasil diperbarui.'
                                                        });
                                                    } else {
                                                        $this.html(
                                                            originalValue
                                                        );
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Gagal',
                                                            text: response
                                                                .message ||
                                                                'Terjadi kesalahan saat memperbarui data.'
                                                        });
                                                    }
                                                },
                                                error: function(xhr) {
                                                    console.error(xhr
                                                        .responseText
                                                    );
                                                    $this.html(
                                                        originalValue
                                                    );
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: 'Terjadi kesalahan saat memperbarui data.'
                                                    });
                                                }
                                            });
                                        } else {
                                            $this.html(originalValue);
                                        }
                                    });
                                } else {
                                    $this.html(originalValue);
                                }
                            }
                        });
                    });
                }
            });

            $(document).on('click', '.btn-delete-detail', function(e) {
                e.stopPropagation();
                var $thisButton = $(this);
                var detailId = $thisButton.data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin ingin menghapus detail ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoading($thisButton, 'Menghapus...');
                        $.ajax({
                            url: '/lpb/detail/' + detailId,
                            method: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                id: detailId
                            },
                            dataType: 'json',
                            success: function(response) {
                                Swal.fire('Berhasil', response.message, 'success');
                                table.ajax.reload();
                            },
                            error: function(xhr, status, error) {
                                Swal.fire('Gagal', 'Gagal menghapus detail', 'error');
                            },
                            complete: function() {
                                hideLoading($thisButton);
                            }
                        });
                    }
                });
            });

            $('#bulantahun').change(function() {
                table.ajax.reload();
            });

            var headerTable = $('#table_header').DataTable({
                processing: true,
                searching: true,
                paging: true,
                pageLength: 5,
                info: true,
                columns: [{
                        title: 'No Purchase Order'
                    },
                    {
                        title: 'Tanggal'
                    },
                    {
                        title: 'No Order'
                    }
                ]
            });

            let currentPoDetails = [];
            let selectedLpbItems = [];
            let lpbItemCounter = 0;
            let selectedDetailData = null;

            $('#table_detail').DataTable({
                destroy: true,
                paging: false,
                searching: false,
                info: false,
                columns: [{
                        title: 'No',
                        width: '25px',
                        className: 'text-center'
                    },
                    {
                        title: 'No Purchase Order'
                    },
                    {
                        title: 'Nama Barang'
                    },
                    {
                        title: 'Jumlah'
                    },
                    {
                        title: 'Satuan'
                    },
                    {
                        title: 'Kategori'
                    }
                ]
            }).on('draw.dt', function() {
                var api = new $.fn.dataTable.Api(this);
                api.column(0, {
                    search: 'applied',
                    order: 'applied'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
            });

            $('#table_detailpilih').DataTable({
                destroy: true,
                paging: false,
                searching: false,
                info: false,
                columns: [{
                        title: 'No',
                        width: '25px',
                        className: 'text-center'
                    },
                    {
                        title: 'No Purchase Order'
                    },
                    {
                        title: 'Nama Barang'
                    },
                    {
                        title: 'Barang Diterima'
                    },
                    {
                        title: 'Satuan'
                    },
                    {
                        title: 'Kategori'
                    },
                    {
                        title: 'Lot Number'
                    },
                    {
                        title: 'Action'
                    }
                ]
            }).on('draw.dt', function() {
                var api = new $.fn.dataTable.Api(this);
                api.column(0, {
                    search: 'applied',
                    order: 'applied'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
            });

            function loadSuppliers() {
                $.ajax({
                    url: "/getSuppliers",
                    method: 'GET',
                    success: function(data) {
                        let supplierTable = $('#table_supplier tbody');
                        supplierTable.empty();

                        if (data.suppliers.length === 0) {
                            supplierTable.append(
                                '<tr><td colspan="2" class="text-center">Tidak ada supplier tersedia</td></tr>'
                            );
                        } else {
                            $.each(data.suppliers, function(index, supplier) {
                                supplierTable.append(`
                                <tr class="select-supplier" data-id="${supplier.id_supplier}">
                                    <td>${index + 1}</td>
                                    <td>${supplier.nama}</td>
                                </tr>
                            `);
                            });

                            if ($.fn.dataTable.isDataTable('#table_supplier')) {
                                $('#table_supplier').DataTable().clear().destroy();
                            }

                            $('#table_supplier').DataTable({
                                processing: true,
                                serverSide: false,
                                pageLength: 5,
                                pagingType: 'simple_numbers',
                                destroy: true,
                                lengthChange: false,
                                paging: true,
                                info: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Terjadi kesalahan saat memuat supplier:", error);
                    }
                });
            }

            $('#tambahLpbModal').on('shown.bs.modal', function() {
                loadSuppliers();
            });

            $('#tambahLpbModal').on('hidden.bs.modal', function() {
                if ($.fn.dataTable.isDataTable('#table_supplier')) {
                    $('#table_supplier').DataTable().clear().destroy();
                }

                $('#table_header').DataTable().clear().draw();
                $('#table_detail').DataTable().clear().draw();
                $('#table_detailpilih').DataTable().clear().draw();
                currentPoDetails = [];
                selectedLpbItems = [];
                lpbItemCounter = 0;
                selectedDetailData = null;

                $('#tanggalBarangDiterima').val('');
                $('#nomorSuratJalan').val('');
                $('#suratJalanAlert').hide();
            });

            $(document).on('click', '.select-supplier', function() {
                let supplierId = $(this).data('id');
                loadHeaderData(supplierId);
            });

            function loadHeaderData(supplierId) {
                let headerTable = $('#table_header').DataTable();
                headerTable.clear().draw();

                $('#table_detail').DataTable().clear().draw();
                $('#table_detailpilih').DataTable().clear().draw();
                currentPoDetails = [];
                selectedLpbItems = [];
                lpbItemCounter = 0;
                selectedDetailData = null;

                $.ajax({
                    url: "/gudang/no-po",
                    method: 'GET',
                    data: {
                        id_supplier: supplierId
                    },
                    success: function(data) {
                        $.each(data.no_po, function(index, header) {
                            headerTable.row.add([
                                header.no_po,
                                header.tanggal,
                                header.no_order
                            ]).draw();
                        });
                    }
                });
            }

            $(document).on('click', '#table_header tbody tr', function() {
                let noPo = $(this).find('td').eq(0).text();
                loadDetailData(noPo);
            });

            function loadDetailData(noPo) {
                let detailTable = $('#table_detail').DataTable();
                detailTable.clear().draw();
                currentPoDetails = [];
                $.ajax({
                    url: "/getDetailByNoPo",
                    method: 'GET',
                    data: {
                        no_po: noPo
                    },
                    success: function(data) {
                        currentPoDetails = data.details;

                        $.each(data.details, function(index, detail) {
                            detailTable.row.add([
                                '',
                                detail.no_po,
                                detail.nama_barang,
                                detail.jumlah,
                                detail.satuan,
                                detail.kategori
                            ]).draw();
                        });
                    }
                });
            }

            $(document).on('click', '#table_detail tbody tr', function() {
                let detailTable = $('#table_detail').DataTable();
                let rowIndex = detailTable.row(this).index();

                let clickedDetail = currentPoDetails[rowIndex];

                selectedDetailData = {
                    noPo: clickedDetail.no_po,
                    namaBarang: clickedDetail.nama_barang,
                    jumlah: clickedDetail.jumlah,
                    satuan: clickedDetail.satuan,
                    kategori: clickedDetail.kategori,
                    idBahan: clickedDetail.id_bahan,
                    katid: clickedDetail.katid,
                    harga: clickedDetail.harga
                };

                $('#inputJumlahModal').modal('show');
            });

            $('#confirmInputJumlah').on('click', function() {
                const jumlahDiterima = $('#jumlahBarangDiterima').val();
                const lotNumber = $('#lotNumber').val();
                const errorElement = $('#jumlahDiterimaError');
                const inputElement = $('#jumlahBarangDiterima');
                const lot = lotNumber || '-';
                const jumlahDiterimaNum = parseFloat(jumlahDiterima);

                if (!jumlahDiterima || jumlahDiterimaNum <= 0) {
                    errorElement.text('Jumlah barang diterima harus diisi dan lebih dari 0.').show();
                    inputElement.addClass('is-invalid');
                    return;
                }

                const jumlahDipesanNum = parseFloat(selectedDetailData.jumlah);
                const currentKatId = parseInt(selectedDetailData.katid);
                const currentKatName = selectedDetailData.kategori ? selectedDetailData.kategori
                    .toUpperCase() : '';
                const isAllowedOver = (currentKatId === 12 || currentKatName === 'PACKING');

                let totalQtyBarangDiKeranjang = selectedLpbItems
                    .filter(item => item.id_bahan === selectedDetailData.idBahan)
                    .reduce((a, b) => a + parseFloat(b.jumlah_barang_diterima), 0);

                let totalAkhir = totalQtyBarangDiKeranjang + jumlahDiterimaNum;

                if (totalAkhir > jumlahDipesanNum) {
                    if (!isAllowedOver) {
                        let sisa = jumlahDipesanNum - totalQtyBarangDiKeranjang;
                        if (sisa < 0) sisa = 0;
                        errorElement.text(
                            `Jumlah melebihi pesanan. Sisa maks: ${sisa}. Hanya kategori PACKING yang boleh berlebih!`
                        ).show();
                        inputElement.addClass('is-invalid');
                        return;
                    }
                }

                errorElement.hide();
                inputElement.removeClass('is-invalid');

                const existingItem = selectedLpbItems.find(item =>
                    item.id_bahan === selectedDetailData.idBahan &&
                    item.lot_number === lot
                );

                let table = $('#table_detailpilih').DataTable();

                if (existingItem) {
                    existingItem.jumlah_barang_diterima = parseFloat(existingItem.jumlah_barang_diterima) +
                        jumlahDiterimaNum;
                    let row = table.row($(`tr[data-temp-id="${existingItem.tempId}"]`));
                    if (row.any()) {
                        let rowData = row.data();
                        rowData[3] = existingItem.jumlah_barang_diterima;
                        row.data(rowData).draw();
                    }
                } else {
                    let newItem = {
                        tempId: lpbItemCounter++,
                        no_po: selectedDetailData.noPo,
                        nama_barang: selectedDetailData.namaBarang,
                        jumlah_barang_diterima: jumlahDiterimaNum,
                        satuan: selectedDetailData.satuan,
                        kategori: selectedDetailData.kategori,
                        lot_number: lot,
                        id_bahan: selectedDetailData.idBahan,
                        katid: selectedDetailData.katid,
                        harga: selectedDetailData.harga
                    };
                    selectedLpbItems.push(newItem);
                    let newRow = table.row.add([
                        '',
                        newItem.no_po,
                        newItem.nama_barang,
                        newItem.jumlah_barang_diterima,
                        newItem.satuan,
                        newItem.kategori,
                        newItem.lot_number,
                        `<button class="btn btn-danger btn-sm" onclick="removeRow(this, ${newItem.tempId})">Hapus</button>`
                    ]).draw().node();
                    $(newRow).attr('data-temp-id', newItem.tempId);
                }
                $('#inputJumlahModal').modal('hide');
            });

            $('#inputJumlahModal').on('hidden.bs.modal', function() {
                $('#tambahLpbModal').css('overflow-y', 'auto');

                $('#jumlahBarangDiterima').val('');
                $('#lotNumber').val('');
                $('#jumlahDiterimaError').hide();
                $('#jumlahBarangDiterima').removeClass('is-invalid');
            });

            window.removeRow = function(button, tempId) {
                selectedLpbItems = selectedLpbItems.filter(item => item.tempId !== tempId);
                $('#table_detailpilih').DataTable().row($(button).parents('tr')).remove().draw();
            };

            $('#btnSaveLpb').on('click', function() {
                let tanggalBarangDiterima = $('#tanggalBarangDiterima').val();
                let nomorSuratJalan = $('#nomorSuratJalan').val();
                var $thisButton = $(this);

                if (!tanggalBarangDiterima) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Tanggal diterima harus diisi.'
                    });
                    return;
                }

                if (!nomorSuratJalan) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Nomor Surat Jalan harus diisi.'
                    });
                    return;
                }

                saveLpbData(tanggalBarangDiterima, nomorSuratJalan, $thisButton);
            });

            function saveLpbData(tanggalBarangDiterima, nomorSuratJalan, $button) {
                let details = selectedLpbItems.map(item => ({
                    no_po: item.no_po,
                    nama_barang: item.nama_barang,
                    jumlah_barang_diterima: item.jumlah_barang_diterima,
                    satuan: item.satuan,
                    kategori: item.kategori,
                    lot_number: item.lot_number,
                    id_bahan: item.id_bahan,
                    katid: item.katid,
                    harga: item.harga
                }));

                let noPo = details.length > 0 ? details[0].no_po : (selectedDetailData ? selectedDetailData.noPo :
                    null);

                if (selectedLpbItems.length === 0 || !noPo) {
                    Swal.fire('Gagal', 'Tidak ada item yang dipilih untuk disimpan.', 'error');
                    return;
                }

                showLoading($button, 'Menyimpan...');

                $.ajax({
                    url: "/gudang/save-lpb",
                    method: 'POST',
                    data: {
                        tanggalBarangDiterima: tanggalBarangDiterima,
                        no_po: noPo,
                        nomorSuratJalan: nomorSuratJalan,
                        details: details,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: response.success ? 'success' : 'error',
                            title: response.success ? 'Berhasil' : 'Gagal',
                            text: response.message
                        }).then(() => {
                            if (response.success) {
                                $('#tambahLpbModal').modal('hide');
                                $('#lpbTable').DataTable().ajax.reload();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: 'Terjadi kesalahan saat menyimpan data LPB.'
                        });
                    },
                    complete: function() {
                        hideLoading($button);
                    }
                });
            }

            $(document).on('click', '.btn-cetak', async function() {
                var $thisButton = $(this);
                showLoading($thisButton, 'Mencetak...');

                let id_lpb = $thisButton.data('id');
                const data = new FormData();
                data.append("id_lpb", id_lpb);
                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                try {
                    const response = await fetch('/cetak-lpb', {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken
                        },
                        body: data
                    });

                    if (response.ok) {
                        const pdfBlob = await response.blob();
                        const pdfUrl = URL.createObjectURL(pdfBlob);
                        window.open(pdfUrl, '_blank');
                        $('#lpbTable').DataTable().ajax.reload(null, false);
                    } else {
                        AppAlert.auto("Gagal mendapatkan PDF.");
                    }
                } catch (error) {
                    console.error("Error:", error);
                    AppAlert.auto("Terjadi kesalahan saat mengirim permintaan.");
                } finally {
                    hideLoading($thisButton);
                }
            });

            function exportToExcel() {
                var filterMonthYear = $('#bulantahun').val();
                var filterLpbType = $('#filterLpbType').val();
                var searchTerm = $('#lpbTable_filter input').val();

                var url = '/gudang/lpb-export?filterMonthYear=' + filterMonthYear +
                    '&filterLpbType=' + filterLpbType +
                    '&searchTerm=' + searchTerm;

                window.location.href = url;
            }

            $('.btn-success').on('click', function() {
                var $thisButton = $(this);
                showLoading($thisButton, 'Exporting...');
                exportToExcel();
                setTimeout(function() {
                    hideLoading($thisButton);
                }, 3000);
            });

            $('#nomorSuratJalan').on('input', function() {
                let nomorSuratJalan = $(this).val();

                if (nomorSuratJalan.trim() === '') {
                    $('#suratJalanAlert').hide();
                    return;
                }

                clearTimeout(debounceTimeout);

                debounceTimeout = setTimeout(function() {
                    $.ajax({
                        url: '/gudang/check-surat-jalan',
                        method: 'POST',
                        data: {
                            nomor_surat_jalan: nomorSuratJalan,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (!response.success) {
                                $('#suratJalanAlert')
                                    .text(response.message)
                                    .removeClass('text-success')
                                    .addClass('text-danger')
                                    .show();
                            } else {
                                $('#suratJalanAlert')
                                    .text('Nomor Surat Jalan belum dipakai')
                                    .removeClass('text-danger')
                                    .addClass('text-success')
                                    .show();
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan',
                                text: 'Terjadi kesalahan saat memeriksa nomor surat jalan.'
                            });
                        }
                    });
                }, 500);
            });
        });
    </script>
@endpush
