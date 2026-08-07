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

        .sticky-active {
            position: -webkit-sticky;
            position: sticky;
            top: -16px;
            z-index: 1020;
            background: white;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid #007bff !important;
            padding-top: 10px;
            margin-left: -16px;
            margin-right: -16px;
            padding-left: 16px;
            padding-right: 16px;
        }

        /* Class ini akan dipasang otomatis oleh JS */
        .sticky-header-qc {
            position: -webkit-sticky;
            position: sticky;
            top: -16px;
            /* Menyesuaikan padding modal-body bootstrap */
            z-index: 1020;
            background-color: #ffffff;
            border-bottom: 3px solid #007bff;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            /* Penting: jangan tambahkan overflow di sini */
        }

        /* Memastikan baris tabel tidak tumpang tindih dengan header saat scroll */
        #table_detail_wrapper,
        #table_detailpilih_wrapper {
            background: white;
        }
    </style>

    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary">QC - Penerimaan Barang (Temporary)</h3>
                    @if ($user['type'] == 14)
                        <div>
                            <button id="btnAddLpb" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#tambahLpbModal">Tambah LPB Temporary</button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Filter Bulan dan Tahun:</label>
                                <input type="month" id="bulantahun" class="form-control" value="{{ date('Y-m') }}">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive p-4 mb-4">
                        <table id="lpbTable" class="table table-striped table-hover compact responsive table-sm"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>ID LPB (Temp)</th>
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

    <div class="modal fade" id="tambahLpbModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Form Input LPB (Staging QC)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="overflow-y: auto;">
                    <div class="p-3 mb-4 border rounded">
                        <div class="row">
                            <div class="col-sm-3">
                                <table id="table_supplier" class="table table-striped table-hover table-sm table-bordered"
                                    style="font-size:9pt; width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Supplier</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="col-sm-9">
                                <table id="table_header" class="table table-striped table-hover table-sm table-bordered"
                                    style="font-size:9pt; width:100%">
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
                    <div class="p-3 mb-4 border rounded">
                        <div class="mb-3 row">
                            <div class="col-sm-6"><label>Tanggal Diterima</label><input type="date" class="form-control"
                                    id="tanggalBarangDiterima" required></div>
                            <div class="col-sm-6"><label>Surat Jalan</label><input type="text" class="form-control"
                                    id="nomorSuratJalan" oninput="this.value = this.value.toUpperCase()" required></div>
                        </div>
                        <table id="table_detail" class="table table-striped table-hover table-sm table-bordered"
                            style="font-size:9pt; width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>PO</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah PO</th>
                                    <th>Satuan</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="p-3 mb-4 border rounded">
                        <h6>Daftar Barang Akan Disimpan (Temporary)</h6>
                        <table id="table_detailpilih" class="table table-striped table-hover table-sm table-bordered"
                            style="font-size:9pt; width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>PO</th>
                                    <th>Nama Barang</th>
                                    <th>Qty Terima</th>
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
                <div class="modal-footer">
                    <button type="button" id="btnSaveLpb" class="btn btn-primary">Simpan ke Temporary</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inputJumlahModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Input Jumlah Barang</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label>Jumlah Barang Diterima</label><input type="number"
                            id="jumlahBarangDiterima" class="form-control"></div>
                    <div class="mb-3"><label>Lot Number</label><input type="text" id="lotNumber"
                            class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Batal</button><button type="button" id="confirmInputJumlah"
                        class="btn btn-primary">OK</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCatatanCustomer" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark fw-bold">Verifikasi Kualitas Barang (QC)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5 border-right">
                            <h6>Input Kendala Per Item</h6>
                            <hr>
                            <form id="formCatatanCustomer">
                                <div class="mb-3">
                                    <label>Pilih Item:</label>
                                    <select class="form-control" id="catatan_id_bahan" name="id_bahan"></select>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="salah_spesifikasi">
                                            <label class="form-check-label" for="salah_spesifikasi">Salah
                                                Spesifikasi</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="jumlah_kurang">
                                            <label class="form-check-label" for="jumlah_kurang">Jumlah Kurang</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="rusak">
                                            <label class="form-check-label" for="rusak">Rusak</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="tidak_layak">
                                            <label class="form-check-label" for="tidak_layak">Expired > 1 Thn</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="cover_rusak">
                                            <label class="form-check-label" for="cover_rusak">Cover Rusak</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="kemasan_bocor">
                                            <label class="form-check-label" for="kemasan_bocor">Kemasan Bocor</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 mt-2">
                                    <label>Notes (Freestyle):</label>
                                    <textarea class="form-control" id="catatan_notes" rows="2"></textarea>
                                </div>
                                <button type="button" class="btn btn-primary w-100"
                                    id="btnAddToListCatatan">Tambahkan ke Daftar</button>
                            </form>
                        </div>
                        <div class="col-md-7">
                            <h6>Daftar Catatan Terinput (Holding Area)</h6>
                            <hr>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-sm table-bordered table-striped" id="tableHoldingCatatan">
                                    <thead>
                                        <tr class="bg-light">
                                            <th>Item</th>
                                            <th>Kendala</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnSubmitApproveWithCatatan">SIMPAN & ACC
                        LPB</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const AUTH_USER_TYPE = {{ $user['type'] }};
        let currentPoDetails = [];
        let selectedLpbItems = [];
        let selectedDetailData = null;
        let currentIdLpbTemp = null;
        let daftarCatatanKolektif = [];

        function showLoading(button, text = 'Mohon tunggu...') {
            if (!button.data('original-html')) button.data('original-html', button.html());
            button.prop('disabled', true).html(`<i class="fas fa-spinner fa-spin"></i> ${text}`);
        }

        function hideLoading(button) {
            if (button.data('original-html')) button.html(button.data('original-html'));
            button.prop('disabled', false);
        }

        $(document).ready(function() {
            var table = $('#lpbTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "/get-lpb-temporary-data",
                    data: function(d) {
                        d.filterMonthYear = $('#bulantahun').val();
                    },
                    dataSrc: 'data'
                },
                columns: [{
                        className: 'details-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '<i class="fas fa-plus-square text-primary" style="cursor:pointer; font-size:18px;"></i>'
                    },
                    {
                        data: 'id_lpb'
                    }, {
                        data: 'tanggal'
                    }, {
                        data: 'supplier_nama'
                    }, {
                        data: 'no_po'
                    }, {
                        data: 'no_sj'
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let cetakButton = (AUTH_USER_TYPE == 14) ?
                                `<a href="/cetak-lpb-temporary/${row.id_lpb}" target="_blank" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-print"></i> Cetak
                                </a>` : '';

                            return `
                                ${cetakButton}
                                <button class="btn btn-info btn-sm btn-cek-detail" data-id="${row.id_lpb}">
                                    <i class="fas fa-search"></i> Cek Detail
                                </button>
                            `;
                        }
                    }
                ]
            });

            function format(data) {
                var childTableId = 'detailTable-' + data.id_lpb.replace(/[^a-zA-Z0-9]/g, '');
                let approveButton = AUTH_USER_TYPE == 12 ?
                    `<div class="text-end mt-4"><button class="btn btn-warning btn-lg btn-trigger-catatan shadow" data-id="${data.id_lpb}">ISI CATATAN & ACC</button></div>` :
                    '';
                return `<div style="padding: 20px; border: 2px dashed #17a2b8; background: #f8f9fa; border-radius: 10px; margin: 10px 0;">
                <h5 class="text-info fw-bold mb-3">Verifikasi Item QC</h5>
                <table id="${childTableId}" class="table table-sm table-striped table-bordered" width="100%">
                    <thead class="bg-info text-white"><tr><th>No</th><th>Nama Barang</th><th>Kategori</th><th>Satuan</th><th>Qty Diterima</th><th>Lot Number</th><th>Action</th></tr></thead>
                    <tbody></tbody>
                </table>
                ${approveButton}
            </div>`;
            }

            $('#lpbTable tbody').on('click', 'td.details-control, .btn-cek-detail', function() {
                var tr = $(this).closest('tr');
                var row = table.row(tr);
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                } else {
                    row.child(format(row.data())).show();
                    tr.addClass('shown');
                    loadChildTable(row.data().id_lpb);
                }
            });

            function loadChildTable(idLpb) {
                var childTableId = '#detailTable-' + idLpb.replace(/[^a-zA-Z0-9]/g, '');
                $(childTableId).DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: `/get-detail-lpb-temporary?id_lpb=${idLpb}`,
                    paging: false,
                    searching: false,
                    info: false,
                    columns: [{
                        data: null,
                        render: (d, t, r, m) => m.row + 1
                    }, {
                        data: 'nama'
                    }, {
                        data: 'katnama'
                    }, {
                        data: 'satuan'
                    }, {
                        data: 'jumlah_barang_diterima'
                    }, {
                        data: 'lot_number'
                    }, {
                        data: null,
                        render: (data, type, row) =>
                            `<button class="btn btn-danger btn-xs" onclick="deleteDetailItem(${row.id})">Hapus</button>`
                    }]
                });
            }

            $(document).on('click', '.btn-trigger-catatan', function() {
                currentIdLpbTemp = $(this).data('id');
                daftarCatatanKolektif = [];
                renderHoldingTable();
                $('#formCatatanCustomer').trigger('reset');
                let items = [];
                let tableId = '#detailTable-' + currentIdLpbTemp.replace(/[^a-zA-Z0-9]/g, '');
                $(tableId).DataTable().rows().data().each(function(d) {
                    items.push(`<option value="${d.id_bahan}">${d.nama}</option>`);
                });
                if (items.length > 0) {
                    $('#catatan_id_bahan').html(items.join(''));
                    $('#modalCatatanCustomer').modal('show');
                }
            });

            $('#btnAddToListCatatan').on('click', function() {
                let idBahan = $('#catatan_id_bahan').val();
                let namaBahan = $('#catatan_id_bahan option:selected').text();
                if (daftarCatatanKolektif.some(i => i.id_bahan === idBahan)) return Swal.fire('Info',
                    'Item sudah ada di daftar', 'info');

                let kendala = [];
                if ($('#salah_spesifikasi').is(':checked')) kendala.push('Salah Spek');
                if ($('#jumlah_kurang').is(':checked')) kendala.push('Kurang');
                if ($('#rusak').is(':checked')) kendala.push('Rusak');
                if ($('#tidak_layak').is(':checked')) kendala.push('Expired');
                if ($('#cover_rusak').is(':checked')) kendala.push('Cover Rusak');
                if ($('#kemasan_bocor').is(':checked')) kendala.push('Bocor');

                daftarCatatanKolektif.push({
                    id_bahan: idBahan,
                    nama_bahan: namaBahan,
                    salah_spesifikasi: $('#salah_spesifikasi').is(':checked') ? 1 : 0,
                    jumlah_kurang: $('#jumlah_kurang').is(':checked') ? 1 : 0,
                    rusak: $('#rusak').is(':checked') ? 1 : 0,
                    tidak_layak: $('#tidak_layak').is(':checked') ? 1 : 0,
                    cover_rusak: $('#cover_rusak').is(':checked') ? 1 : 0,
                    kemasan_bocor: $('#kemasan_bocor').is(':checked') ? 1 : 0,
                    notes: $('#catatan_notes').val(),
                    kendala_text: kendala.join(', ') || 'Normal'
                });
                renderHoldingTable();
                $('#catatan_notes').val('');
                $('#formCatatanCustomer input[type="checkbox"]').prop('checked', false);
            });

            function renderHoldingTable() {
                let html = daftarCatatanKolektif.length === 0 ?
                    '<tr><td colspan="3" class="text-center text-muted">Belum ada catatan</td></tr>' : '';
                daftarCatatanKolektif.forEach((item, index) => {
                    html +=
                        `<tr><td><small><b>${item.nama_bahan}</b><br>${item.notes}</small></td><td><span class="badge bg-warning">${item.kendala_text}</span></td><td class="text-center"><button type="button" class="btn btn-danger btn-xs" onclick="removeItemCatatan(${index})">X</button></td></tr>`;
                });
                $('#tableHoldingCatatan tbody').html(html);
            }

            window.removeItemCatatan = (index) => {
                daftarCatatanKolektif.splice(index, 1);
                renderHoldingTable();
            };

            $('#btnSubmitApproveWithCatatan').on('click', function() {
                let $btn = $(this);
                Swal.fire({
                    title: 'Konfirmasi Final ACC',
                    text: `Proses LPB dengan ${daftarCatatanKolektif.length} catatan?`,
                    icon: 'warning',
                    showCancelButton: true
                }).then((r) => {
                    if (r.isConfirmed) {
                        showLoading($btn, 'Memproses...');
                        $.post("/approve-lpb-temporary", {
                            id_lpb: currentIdLpbTemp,
                            catatan: daftarCatatanKolektif,
                            _token: "{{ csrf_token() }}"
                        }, function(res) {
                            if (res.success) {
                                $('#modalCatatanCustomer').modal('hide');
                                table.ajax.reload();
                                Swal.fire('Berhasil', res.message, 'success');
                            } else {
                                Swal.fire('Gagal', res.message, 'error');
                            }
                        }).always(() => hideLoading($btn));
                    }
                });
            });

            function loadSuppliers() {
                $.get("/getSuppliers", function(data) {
                    let body = $('#table_supplier tbody');
                    if ($.fn.dataTable.isDataTable('#table_supplier')) $('#table_supplier')
                        .DataTable()
                        .destroy();
                    body.empty();
                    $.each(data.suppliers, (i, s) => body.append(
                        `<tr class="select-supplier" data-id="${s.id_supplier}" style="cursor:pointer"><td>${i+1}</td><td>${s.nama}</td></tr>`
                    ));
                    $('#table_supplier').DataTable({
                        pageLength: 10,
                        lengthChange: false,
                        dom: '<"p-1"f>rt<"p-1"p>'
                    });
                });
            }

            $('#tambahLpbModal').on('shown.bs.modal', loadSuppliers);
            $(document).on('click',
                '.select-supplier',
                function() {
                    let id = $(this).data('id');
                    $('#table_header').DataTable().clear().draw();
                    $.get("/gudang/no-po", {
                        id_supplier: id
                    }, (data) => $.each(data.no_po, (i, h) => $('#table_header').DataTable().row
                        .add([h
                            .no_po, h.tanggal, h.no_order
                        ]).draw()));
                });
            $(document).on('click', '#table_header tbody tr', function() {
                let po = $(this).find('td').eq(0).text();
                $('#table_detail').DataTable().clear().draw();
                $.get("/getDetailByNoPo", {
                    no_po: po
                }, (data) => {
                    currentPoDetails = data.details;
                    $.each(data.details, (i, d) => $('#table_detail').DataTable().row.add([i +
                        1, d
                        .no_po, d.nama_barang, d.jumlah, d.satuan, d.kategori
                    ]).draw());
                });
            });
            $(document).on('click', '#table_detail tbody tr', function() {
                let idx = $('#table_detail').DataTable().row(this).index();
                let d = currentPoDetails[idx];
                selectedDetailData = {
                    noPo: d.no_po,
                    namaBarang: d.nama_barang,
                    jumlah: d.jumlah,
                    satuan: d.satuan,
                    kategori: d.kategori,
                    idBahan: d.id_bahan,
                    katid: d.katid,
                    harga: d.harga
                };
                $('#inputJumlahModal').modal('show');
            });
            $('#confirmInputJumlah').on('click', function() {
                const qty = parseFloat($('#jumlahBarangDiterima').val());
                const lot = $('#lotNumber').val() || '-';
                if (isNaN(qty) || qty <= 0) return;
                const isPacking = (selectedDetailData.katid == 12 || selectedDetailData.kategori
                    .toUpperCase() == 'PACKING');
                let totalInCart = selectedLpbItems.filter(i => i.idBahan === selectedDetailData.idBahan)
                    .reduce((a, b) => a + parseFloat(b.jumlah_barang_diterima), 0);
                if ((totalInCart + qty) > parseFloat(selectedDetailData.jumlah) && !isPacking)
                    return Swal
                        .fire('Gagal', 'Jumlah melebihi PO', 'error');
                let existing = selectedLpbItems.find(i => i.idBahan === selectedDetailData.idBahan && i
                    .lot_number === lot);
                if (existing) existing.jumlah_barang_diterima = parseFloat(existing
                    .jumlah_barang_diterima) + qty;
                else selectedLpbItems.push({
                    ...selectedDetailData,
                    jumlah_barang_diterima: qty,
                    lot_number: lot,
                    tempId: Date.now()
                });
                renderCart();
                $('#inputJumlahModal').modal('hide');
            });

            // Update fungsi renderCart Anda
            function renderCart() {
                let t = $('#table_detailpilih').DataTable();
                t.clear();

                // LOGIKA STICKY: Targetkan div pertama (Supplier & Header PO)
                let headerContainer = $('#tambahLpbModal .modal-body > div').first();

                if (selectedLpbItems.length > 0) {
                    headerContainer.addClass('sticky-header-qc');
                } else {
                    headerContainer.removeClass('sticky-header-qc');
                }

                selectedLpbItems.forEach((item, index) => {
                    t.row.add([
                        index + 1,
                        item.noPo,
                        item.namaBarang,
                        item.jumlah_barang_diterima,
                        item.satuan,
                        item.kategori,
                        item.lot_number,
                        `<button class="btn btn-danger btn-sm" onclick="removeTempItem(${item.tempId})">X</button>`
                    ]);
                });
                t.draw();
            }

            // Tambahkan/Update bagian ini untuk menangani modal yang menumpuk (Penyebab scroll macet)
            $(document).on('hidden.bs.modal', '.modal', function() {
                if ($('.modal:visible').length > 0) {
                    $('body').addClass('modal-open');
                }
            });
            window.removeTempItem = (id) => {
                selectedLpbItems = selectedLpbItems.filter(i => i.tempId !== id);
                renderCart();
            };
            $('#btnSaveLpb').on('click', function() {
                let tgl = $('#tanggalBarangDiterima').val();
                let sj = $('#nomorSuratJalan').val();
                let $btn = $(this);
                if (!tgl || !sj || selectedLpbItems.length === 0) return Swal.fire('Error',
                    'Data belum lengkap', 'error');
                showLoading($btn, 'Menyimpan...');
                $.post("/save-lpb-temporary", {
                    tanggalBarangDiterima: tgl,
                    nomorSuratJalan: sj,
                    no_po: selectedLpbItems[0].noPo,
                    details: selectedLpbItems.map(i => ({
                        id_bahan: i.idBahan,
                        katid: i.katid,
                        harga: i.harga,
                        jumlah_barang_diterima: i.jumlah_barang_diterima,
                        lot_number: i.lot_number
                    })),
                    _token: "{{ csrf_token() }}"
                }, (res) => {
                    if (res.success) {
                        $('#tambahLpbModal').modal('hide');
                        table.ajax.reload();
                        selectedLpbItems = [];
                        $('#table_detailpilih').DataTable().clear().draw();
                        Swal.fire('Berhasil', 'Data antrean QC tersimpan', 'success');
                    }
                }).always(() => hideLoading($btn));
            });
            window.deleteDetailItem = (id) => {
                Swal.fire({
                    title: 'Hapus Item?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((r) => {
                    if (r.isConfirmed) $.ajax({
                        url: `/delete-lpb-detail-temporary/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: () => table.ajax.reload()
                    });
                });
            };
        });
    </script>
@endpush
