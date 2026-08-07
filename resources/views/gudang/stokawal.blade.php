@extends('layouts.app')

@section('content')
    <style>
        /* Warna teks dan latar belakang utama */
        .select2-container--default .select2-selection--single {
            background-color: #2a2a2a !important;
            color: #ffffff !important;
            border: 1px solid #444 !important;
        }

        /* Warna placeholder (bawaan Select2) */
        .select2-container--default .select2-selection__placeholder {
            color: #bbbbbb !important;
            /* Abu-abu untuk placeholder */
        }

        /* Warna teks yang dipilih */
        .select2-container--default .select2-selection__rendered {
            color: #ffffff !important;
            /* Putih untuk teks yang dipilih */
        }

        /* Warna dropdown */
        .select2-dropdown {
            background-color: #2a2a2a !important;
            color: #ffffff !important;
            border: 1px solid #444 !important;
        }

        /* Warna saat opsi di-hover */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #444 !important;
            color: #ffffff !important;
        }

        /* Warna opsi yang sudah dipilih */
        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #555 !important;
            color: #ffffff !important;
        }

        /* Scrollbar untuk dark mode */
        .select2-results::-webkit-scrollbar {
            width: 8px;
        }

        .select2-results::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }

        .select2-results::-webkit-scrollbar-track {
            background: #333;
        }

        /* Ukuran Select2 agar sesuai dengan input lain */
        .select2-container .select2-selection--single,
        .select2-container .select2-selection--multiple {
            height: 38px !important;
            padding: 5px 10px !important;
            font-size: 14px !important;
        }

        /* Dropdown sejajar dengan input */
        .select2-container .select2-dropdown {
            font-size: 14px !important;
        }

        /* Ikon dropdown sejajar */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        /* Pagination style for dark mode */
        body.dark-mode .dataTables_paginate .paginate_button {
            color: #fff !important;
            /* Set text color to white in dark mode */
            border-color: #555;
            /* Optional: Change border color in dark mode */
        }

        body.dark-mode .dataTables_paginate .paginate_button.current {
            background-color: #007bff !important;
            /* Highlight current page */
            color: #fff !important;
            /* Ensure current page text is white */
        }

        /* Pagination style for light mode */
        body:not(.dark-mode) .dataTables_paginate .paginate_button {
            color: #333 !important;
            /* Set text color to dark in light mode */
            border-color: #ddd;
            /* Optional: Change border color in light mode */
        }

        body:not(.dark-mode) .dataTables_paginate .paginate_button.current {
            background-color: #007bff !important;
            /* Highlight current page */
            color: #fff !important;
            /* Ensure current page text is white */
        }
    </style>
    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary">{{ $data['title'] }}</h3>
                    <!-- Tombol untuk menambahkan "Stok Awal" -->
                    <button id="btnTambahStokAwal" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#modalTambahStokAwal">
                        Tambah Stok Awal
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="stockAwalTable" class="table table-striped table-hover compact responsive table-sm"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>Lihat Detail Stok Awal</th>
                                    <th>No</th>
                                    <th>ID LPB</th>
                                    <th>Tanggal</th>
                                    <th>No. Purchase Order</th>
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


    <!-- Modal untuk "Tambah Stok Awal" -->
    <div class="modal fade" id="modalTambahStokAwal" tabindex="-1" aria-labelledby="modalTambahStokAwalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahStokAwalLabel">Tambah Stok Awal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTambahStokAwal">
                    <div class="modal-body">
                        <!-- Input ID LPB dengan nilai otomatis "STOKAWAL" -->
                        <div class="mb-3">
                            <label for="id_lpb">ID LPB</label>
                            <input type="text" class="form-control" id="id_lpb" name="id_lpb" value="STOKAWAL"
                                readonly>
                        </div>
                        <!-- Input No. Purchase Order dengan nilai otomatis "STOKAWAL" -->
                        <div class="mb-3">
                            <label for="no_po">No. Purchase Order</label>
                            <input type="text" class="form-control" id="no_po" name="no_po" value="STOKAWAL"
                                readonly>
                        </div>
                        <!-- Input Tanggal, hanya tanggal yang harus diisi manual -->
                        <div class="mb-3">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                        <!-- Input No. Surat Jalan dengan nilai otomatis "STOKAWAL" -->
                        <div class="mb-3">
                            <label for="no_sj">No. Surat Jalan</label>
                            <input type="text" class="form-control" id="no_sj" name="no_sj" value="STOKAWAL"
                                readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal untuk "Tambah Detail Stok Awal" -->
    <div class="modal fade" id="modalTambahDetail" tabindex="-1" aria-labelledby="modalTambahDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahDetailLabel">Tambah Detail Stok Awal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTambahDetail">
                    <div class="modal-body">
                        <input type="hidden" id="detail_id_lpb" name="id_lpb">
                        <input type="hidden" id="kategori_id" name="id_kategori">
                        <!-- Dropdown Nama Barang -->
                        <div class="mb-3">
                            <label for="id_bahan">Nama Barang</label>
                            <select class="form-control" id="id_bahan" name="id_bahan" data-placeholder="Pilih Barang"
                                style="width: 100%;">
                                <!-- Options akan diisi oleh Select2 -->
                            </select>
                        </div>

                        <!-- Input Kategori (akan diisi otomatis) -->
                        <div class="mb-3">
                            <label for="kategori_bahan">Kategori</label>
                            <input type="text" class="form-control" id="kategori_bahan" name="kategori_bahan" readonly>
                        </div>

                        <!-- Input Jumlah dan Satuan (otomatis terisi) -->
                        <div class="mb-3">
                            <label for="jumlah_barang_diterima">Jumlah dan Satuan</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="number" class="form-control" id="jumlah_barang_diterima"
                                    name="jumlah_barang_diterima" min="1" placeholder="Jumlah" style="flex: 2;"
                                    required>
                                <input type="text" class="form-control" id="satuan" name="satuan" readonly
                                    style="flex: 1;"> <!-- Satuan otomatis terisi -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var table;
        const lembarPerRim = 500;

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function resetForm(formId) {
                $(formId).trigger("reset");
                $(formId).find('select').val(null).trigger('change');
            }

            window.showAddDetailModal = function(id, id_lpb) {
                $('#detail_id_lpb').val(id_lpb);
                $('#modalTambahDetail').modal('show');
            };

            $('#formTambahStokAwal').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "/store-stok-awal",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#modalTambahStokAwal').modal('hide');
                            table.ajax.reload(null, false);

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Stok Awal berhasil ditambahkan!',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            resetForm('#formTambahStokAwal');
                            $('#btnTambahStokAwal').prop('disabled', true);
                        } else if (response.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: response.error
                            });
                        }
                    }
                });
            });

            $('#id_bahan').select2({
                placeholder: 'Pilih Bahan',
                allowClear: true,
                ajax: {
                    url: "/get-bahan-dan-kategori",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });

            $('#id_bahan').on('change', function() {
                var bahanId = $(this).val();
                if (bahanId) {
                    $.ajax({
                        url: "/get-bahan-dan-kategori",
                        method: "GET",
                        data: {
                            id_bahan: bahanId
                        },
                        success: function(response) {
                            if (response.success) {
                                // Set kategori ke input
                                $('#kategori_bahan').val(response.kategori || '');
                                // Set satuan ke input
                                $('#satuan').val(response.satuan || '');
                                // Set id kategori ke input tersembunyi
                                $('#kategori_id').val(response.kategori_id ||
                                    ''); // Simpan ID kategori
                            } else {
                                $('#kategori_bahan').val(
                                    ''); // Kosongkan jika tidak ada kategori
                                $('#satuan').val(''); // Kosongkan jika tidak ada satuan
                                $('#kategori_id').val(
                                    ''); // Kosongkan ID kategori jika tidak ada
                            }
                        },
                        error: function() {
                            $('#kategori_bahan').val(''); // Kosongkan jika terjadi error
                            $('#satuan').val(''); // Kosongkan jika terjadi error
                            $('#kategori_id').val(
                                ''); // Kosongkan ID kategori jika terjadi error
                        }
                    });
                } else {
                    $('#kategori_bahan').val(''); // Kosongkan jika tidak ada bahan yang dipilih
                    $('#satuan').val(''); // Kosongkan jika tidak ada bahan yang dipilih
                    $('#kategori_id').val(''); // Kosongkan ID kategori jika tidak ada bahan yang dipilih
                }
            });



            // Mengecek apakah Stok Awal sudah ada
            $.ajax({
                url: "/check-stok-awal",
                method: "GET",
                success: function(response) {
                    if (response.exists) {
                        $('#btnTambahStokAwal').prop('disabled', true);
                    }
                }
            });

            $('#formTambahDetail').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: "/store-lpb-detail",
                    method: "POST",
                    data: $(this).serialize(), // Ensure data from kategori_bahan is sent
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Detail Stok Awal berhasil ditambahkan!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            // Hide the modal
                            $('#modalTambahDetail').modal('hide');

                            // Clear the modal form data
                            $('#formTambahDetail')[0].reset(); // Reset the form

                            // Clear additional fields manually if needed
                            $('#kategori_bahan').val(''); // Clear category input
                            $('#satuan').val(''); // Clear unit input

                            // Reload DataTable detail berdasarkan ID LPB
                            var id_lpb = $('#detail_id_lpb').val(); // Ambil ID LPB yang sesuai
                            var detailTable = $(`#detailTable-${id_lpb}`).DataTable();

                            // Reload tabel detail
                            detailTable.ajax.reload(null, false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: response.error
                            });
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan.'
                        });
                    }
                });
            });


            // Inisialisasi DataTables utama
            var table = $('#stockAwalTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "/stock-awal-data",
                scrollX: true,
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                    <button class="btn btn-primary btn-sm btn-lihat-detail">
                        Lihat Detail Stok Awal
                    </button>
                `;
                        }
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id_lpb',
                        name: 'id_lpb'
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal'
                    },
                    {
                        data: 'no_po',
                        name: 'no_po'
                    },
                    {
                        data: 'no_sj',
                        name: 'no_sj'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                lengthChange: false,
                searching: false,
                paging: true,
                info: true
            });

            $('#stockAwalTable tbody').on('click', '.btn-lihat-detail', function() {
                var tr = $(this).closest('tr');
                var row = table.row(tr);

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                } else {
                    var id_lpb = row.data().id_lpb;

                    var detailTableHtml = `
            <div style="padding: 10px;">
                <table id="detailTable-${id_lpb}" class="table table-sm table-striped table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Jumlah Diterima <i class="fas fa-pencil-alt text-primary"></i></th>
                            <th>Lot Number <i class="fas fa-pencil-alt text-primary"></i></th>
                        </tr>
                    </thead>
                </table>
            </div>
        `;

                    row.child(detailTableHtml).show();
                    tr.addClass('shown');

                    var detailTable = $(`#detailTable-${id_lpb}`).DataTable({
                        processing: true,
                        serverSide: true,
                        responsive: true,
                        destroy: true,
                        ajax: {
                            url: "/get-detail-stok-awal",
                            data: {
                                id_lpb: id_lpb
                            }
                        },
                        columns: [{
                                data: null,
                                render: function(data, type, row, meta) {
                                    return meta.row + 1;
                                }
                            },
                            {
                                data: 'nama_barang'
                            },
                            {
                                data: 'kategori'
                            },
                            {
                                data: 'jumlah_barang_diterima',
                                render: function(data, type, row) {
                                    return `<span class="editable" data-id="${row.id_lpb_detail}" data-column="jumlah_barang_diterima">${data || '-'}</span> <i class="fas fa-pencil-alt text-primary"></i>`;
                                }
                            },
                            {
                                data: 'lot_number',
                                render: function(data, type, row) {
                                    return `<span class="editable" data-id="${row.id_lpb_detail}" data-column="lot_number">${data || '-'}</span> <i class="fas fa-pencil-alt text-primary"></i>`;
                                }
                            }
                        ],
                        paging: true,
                        searching: true,
                        info: true,
                        pageLength: 10
                    });

                    $(`#detailTable-${id_lpb}`).on('click', '.editable', function() {
                        var $this = $(this);
                        var currentValue = $this.text().trim();
                        var id = $this.data('id');
                        var column = $this.data('column');

                        if ($this.find('input').length === 0) {
                            var input = $(
                                `<input type="text" class="form-control form-control-sm" value="${currentValue === '-' ? '' : currentValue}">`
                            );
                            $this.html(input);
                            input.focus().select();
                            var originalValue = currentValue === '-' ? '' : currentValue;

                            input.on('blur keydown', function(e) {
                                if (e.type === 'blur' || (e.type === 'keydown' && e.key ===
                                        'Enter')) {
                                    var newValue = $(this).val().trim();

                                    // Jika nilai kosong, set kembali nilai asli agar tidak menghilang
                                    if (newValue === '') {
                                        newValue = originalValue || '-';
                                    }

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
                                                $.ajax({
                                                    url: "/updateDetailStokAwal",
                                                    method: "POST",
                                                    data: {
                                                        id_lpb_detail: id,
                                                        column: column,
                                                        value: newValue,
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

                                                            // REFRESH DATATABLE DETAIL
                                                            detailTable
                                                                .ajax
                                                                .reload(
                                                                    null,
                                                                    false
                                                                );
                                                        } else {
                                                            $this.html(
                                                                originalValue ||
                                                                '-');
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
                                                        console.error(
                                                            xhr
                                                            .responseText
                                                        );
                                                        $this.html(
                                                            originalValue ||
                                                            '-');
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Error',
                                                            text: 'Terjadi kesalahan saat memperbarui data. Detail: ' +
                                                                (xhr.responseJSON
                                                                    ?.details ||
                                                                    'Tidak diketahui.'
                                                                )
                                                        });
                                                    }
                                                });
                                            } else {
                                                $this.html(originalValue || '-');
                                            }
                                        });
                                    } else {
                                        $this.html(originalValue || '-');
                                    }
                                }
                            });
                        }
                    });
                }
            });
        });
    </script>
@endpush
