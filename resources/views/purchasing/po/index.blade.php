@extends('layouts.app')

@section('content')
    <style>
        .uppercase {
            text-transform: uppercase;
        }

        .modal-custom-width {
            width: min(1360px, calc(100vw - 2.25rem));
            max-width: 1360px;
            margin: .875rem auto;
        }

        .modal-custom2-width {
            width: min(1080px, calc(100vw - 2.5rem));
            max-width: 1080px;
            margin: 1rem auto;
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
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">{{ $title }}</h4>

                            <div class="d-flex align-items-center">
                                <button class="btn btn-success btn-sm" id="btndetail">Detail</button>
                                <button class="btn btn-info btn-sm ms-2" id="btnExport">Export</button>
                                <select id="filterMonth" class="form-control form-control-sm mx-2">
                                    <option value="0">Show All</option>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>

                                <select id="filterYear" class="form-control form-control-sm mx-2">
                                    @for ($year = date('Y'); $year >= date('Y') - 5; $year--)
                                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endfor
                                </select>

                                <button class="btn btn-primary btn-sm" id="btnAdd"><i
                                        class="fa fa-plus-square"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="poKertasTable" class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f8f9fa; color: #333;">
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">Nomor
                                        </th>
                                        <th
                                            style="border: 1px solid #dee2e6; padding: 10px; text-align: center; width: 200px;">
                                            Supplier
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">
                                            Tanggal
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">
                                            Exclude
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">PPN
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">
                                            Include
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">Action
                                        </th>
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
    <div class="modal fade" id="poModal" tabindex="-1" role="dialog" aria-labelledby="poModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="poModalLabel">Edit Nomor Sales Order</h5>
                    <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label style="width: 8em" for="noPoInput" class="col-form-label">Nomor
                                    Pembelian</label>
                            </div>
                            <div class="col">
                                <input style="width: 20em" type="text" class="form-control" id="noPoInput" readonly>
                            </div>
                        </div>
                        <div class="row align-items-center" style="margin-top: 10px">
                            <div class="col-auto">
                                <label style="width: 8em" for="noOrder" class="col-form-label">No Order</label>
                            </div>
                            <div class="col">
                                <input style="width: 20em" type="text" class="form-control" id="noOrder">
                            </div>
                        </div>
                        <div class="row align-items-center" style="margin-top: 10px">
                            <div class="col-auto">
                                <label style="width: 8em" for="term_pembayaran" class="col-form-label">Term
                                    Pembayaran</label>
                            </div>
                            <div class="col">
                                <input style="width: 20em" type="text" class="form-control" id="term_pembayaran">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="updateso" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="poModaledit" tabindex="-1" role="dialog" aria-labelledby="poModaleditLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="poModaleditLabel">Edit Detail</h5>
                    <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDetailForm">
                        <div class="mb-3">
                            <label for="harga_edit">Harga</label>
                            <input type="hidden" class="form-control" id="id_edit" name="id_edit">
                            <input type="text" class="form-control" id="harga_edit" name="harga_edit"
                                placeholder="Harga per Kg">
                        </div>

                        <div class="mb-3">
                            <label for="jumlah_edit">Jumlah</label>
                            <input type="text" class="form-control" id="jumlah_edit" name="jumlah_edit"
                                placeholder="Jumlah">
                        </div>

                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <div class="viewmodal" style="display: none;"></div>
    <div class="modal fade" id="Modaleditdiskon" tabindex="-1" role="dialog" aria-labelledby="poModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="poModalLabel">Edit Hitungan</h5>
                    <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label style="width: 8em" for="diskon" class="col-form-label">Diskon</label>
                            </div>
                            <div class="col">
                                <input style="width: 20em" type="hidden" class="form-control" id="nopoedit">
                                <input style="width: 20em" type="text" class="form-control" id="diskonedit">
                            </div>
                        </div>
                        <div class="row align-items-center" style="margin-top: 10px">
                            <div class="col-auto">
                                <input style="width: 8em" type="text" class="form-control" id="inputlabeledit">
                            </div>
                            <div class="col">
                                <input style="width: 20em" type="text" class="form-control" id="ongkiredit">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="updatediskon" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-preview" tabindex="-1" role="dialog" aria-labelledby="modal-preview-label"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-preview-label">Detail Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Konten preview akan dimuat di sini oleh JavaScript --}}
                    <div id="preview-content">
                        <p class="text-center">Memuat data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <div id="tempat-modal"></div>
@endsection
@push('scripts')
    <script src="assets/js/autoNumeric.js"></script>
    <script>
        let id_bahan = 0,
            id_permintaan = 0,
            harga = 0,
            id = 0,
            name = '',
            satuan = '',
            hargakalijumlah = 0,
            counter = 1,
            totalHargadanppn = 0,
            nilaippn = 0,
            nominalppn = 0,
            SumTotalExclude = 0,
            SumTotalppn = 0,
            SumTotalInclude = 0,
            diskon = 0,
            ongkir = 0,
            GrandTotalPembelian = 0,
            qty = 0;
        let table = [];
        let tabledetail = [];

        function formatNumber(number, decimals = 0) {
            if (isNaN(number)) {
                return '0';
            }
            var formattedNumber = parseFloat(number).toFixed(decimals).toString();
            return formattedNumber.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function hapusdetail(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/purchasing/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            );
                            table.ajax.reload();
                        },
                        error: function(xhr, status, error) {
                            console.error("Terjadi kesalahan:", error);
                            Swal.fire(
                                'Error!',
                                'Gagal menghapus detail.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        function editdetail(id) {
            $.ajax({
                url: `/createpo/${id}`,
                method: 'GET',
                success: function(response) {
                    console.log(response);
                    $('#id_edit').val(response.id);
                    $('#jumlah_edit').autoNumeric('set', response.jumlah);
                    $('#harga_edit').autoNumeric('set', response.harga);
                    if (parseFloat(response.ppn) > 0) {
                        nilaippn = {{ config('app.konstanta_ppn') }};
                    } else {
                        nilaippn = 0;
                    }
                    $('#poModaledit').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error('Terjadi kesalahan:', error);
                    AppAlert.auto('Gagal mengambil detail. Silakan coba lagi.');
                }
            });
        }

        function tambahDetail(noPo) {
            $.ajax({
                url: `/purchasing/${noPo}/edit`,
                method: 'GET',
                data: {
                    jenis: {{ $jenis }}
                },
                dataType: "json",
                success: function(response) {
                    if (response.data) {
                        $('.viewmodal').html(response.data).show();
                        $('#Modaltambah').modal('show');
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    AppAlert.auto(xhr.status + '\n' + thrownError);
                }
            });
        }
        $(document).ready(function() {
            $('#jumlah_edit').autoNumeric('init', {
                aSep: ',',
                mDec: 2
            });
            $('#harga_edit').autoNumeric('init', {
                aSep: ',',
                mDec: 2
            });
            $('#diskonedit').autoNumeric('init', {
                aSep: ',',
                mDec: 2
            });
            $('#ongkiredit').autoNumeric('init', {
                aSep: ',',
                mDec: 2
            });
            table = $('#poKertasTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('getDataPO') }}",
                    data: function(d) {
                        d.bulan = $('#filterMonth').val();
                        d.tahun = $('#filterYear').val();
                        d.jenis = {{ $jenis }};
                    }
                },
                columns: [{
                        data: 'no_po',
                        name: 'no_po',
                        orderable: false,
                        searchable: true,
                        render: function(data, type, row, meta) {
                            let btnClass = "btn-success";
                            if (row.status == 1) {
                                btnClass = "btn-warning";
                            } else if (row.status == 2) {
                                btnClass = "btn-danger";
                            }
                            return `<button class="btn ${btnClass} btn-sm clickable-po"
                                data-no-po="${data}"
                                data-no-order="${row.no_order}"
                                data-untukperhatian="${row.untukperhatian}"
                                data-diskon="${row.diskon}"
                                data-ongkir="${row.ongkir}"
                                data-term_pembayaran="${row.term}"
                                data-inputlabel="${row.inputlabel}">${data}</button>`;
                        }
                    },
                    {
                        data: 'nama',
                        name: 'nama',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'totalexclude',
                        name: 'totalexclude',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'totalppn',
                        name: 'totalppn',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'totalinclude',
                        name: 'totalinclude',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'no_po',
                        render: function(data, type, row, meta) {
                            let cetakButtonHtml = '';
                            if (row.kunci == 1) {
                                cetakButtonHtml = `
                                <button class="btn btn-secondary btn-sm" title="PO ini sudah dikunci" disabled>
                                    <i class="fa fa-lock" aria-hidden="true"></i>
                                </button>
                            `;
                            } else {
                                cetakButtonHtml = `
                                <button id="cetak-${data}" class="btn btn-success btn-sm btn-cetak" data-no-po="${data}">
                                    <i class="fa fa-print" aria-hidden="true"></i>
                                </button>
                            `;
                            }
                            return `
                            <button class="btn btn-primary btn-sm btn-lihat" data-no-po="${data}">
                                <i class="fa fa-eye" aria-hidden="true"></i>
                            </button>
                            <button class="btn btn-info btn-sm btn-detail" data-no-po="${data}">
                                <i class="fa fa-caret-square-down" aria-hidden="true"></i>
                            </button>
                            ${cetakButtonHtml}
                        `;
                        },
                        orderable: false,
                        searchable: false
                    }
                ],
                initComplete: function() {
                    $('div.dataTables_filter input').attr('placeholder',
                        'Cari nomor transaksi');
                }
            });

            $('#filterMonth, #filterYear').on('change', function() {
                table.ajax.reload();
            });

            $('#btnAdd').click(function(e) {
                e.preventDefault();
                $.ajax({
                    type: "get",
                    url: "/purchasing/create",
                    data: {
                        jenis: {{ $jenis }}
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $('#btnAdd').prop('disabled', true).html(
                            '<i class="fa fa-spin fa-spinner"></i>');
                    },
                    complete: function() {
                        $('#btnAdd').prop('disabled', false).html(
                            '<i class="fa fa-plus-square"></i>');
                    },
                    success: function(response) {
                        if (response.data) {
                            $('.viewmodal').html(response.data).show();
                            $('#exampleModal').modal('show');
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        AppAlert.auto(xhr.status + '\n' + thrownError);
                    }
                });
            });

            $('#btndetail').click(function(e) {
                e.preventDefault();
                $.ajax({
                    type: "get",
                    url: "/getdetailPO",
                    data: {
                        bulan: $('#filterMonth').val(),
                        tahun: $('#filterYear').val(),
                        jenis: {{ $jenis }},
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $('#btndetail').prop('disabled', true).html(
                            '<i class="fa fa-spin fa-spinner"></i>');
                    },
                    complete: function() {
                        $('#btndetail').prop('disabled', false).html('Detail');
                    },
                    success: function(response) {
                        if (response.data) {
                            $('.viewmodal').html(response.data).show();
                            $('#detailModal').modal('show');
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        AppAlert.auto(xhr.status + '\n' + thrownError);
                    }
                });
            });

            $('#btnExport').click(function(e) {
                e.preventDefault();

                let bulan = $('#filterMonth').val();
                let tahun = $('#filterYear').val();
                let jenis = {{ $jenis }};
                let url = `{{ route('export.po') }}?bulan=${bulan}&tahun=${tahun}&jenis=${jenis}`;
                window.location.href = url;
            });


            $('#poKertasTable').on('click', '.btn-lihat', async function() {
                const nomorpo = $(this).data('no-po');
                const button = $(this);
                button.attr('disabled', true);
                button.html('<i class="fa fa-spin fa-spinner"></i>');
                try {
                    const data = new FormData();
                    data.append("nomorpo", nomorpo);
                    const csrfToken = "{{ csrf_token() }}";
                    const response = await fetch('/lihatcetak', {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken
                        },
                        body: data
                    });
                    if (!response.ok) throw new Error("Gagal mengambil data dari server");
                    const html = await response.text();
                    $('#preview-content').html(html);
                    $('#modal-preview').modal('show');
                } catch (error) {
                    console.error('Error saat mengambil preview:', error);
                    AppAlert.auto("Terjadi kesalahan saat memuat tampilan.");
                } finally {
                    button.attr('disabled', false);
                    button.html('<i class="fa fa-eye"></i>');
                }
            });

            $('#poKertasTable').on('click', '.clickable-po', function() {
                let noPo = $(this).data('no-po');
                let noOrder = $(this).data('no-order');
                let inputlabeledit = $(this).data('inputlabel');
                let diskonedit = $(this).data('diskon');
                let ongkiredit = $(this).data('ongkir');
                let term_pembayaran = $(this).data('term_pembayaran');
                Swal.fire({
                    title: "Anda Mau Apa ?",
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: "Edit nomor SO",
                    denyButtonText: `Close PO`,
                    cancelButtonText: "Edit"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#noPoInput').val(noPo);
                        $('#noOrder').val(noOrder);
                        if (term_pembayaran == 'Tidak Ada') {
                            term_pembayaran = '';
                        } else {
                            term_pembayaran = $(this).data('term_pembayaran')
                        }
                        $('#term_pembayaran').val(term_pembayaran);
                        $('#poModal').modal('show');
                    } else if (result.isDenied) {
                        let randomNumber = Math.floor(1000 + Math.random() * 9000);
                        Swal.fire({
                            title: "Konfirmasi Close PO",
                            html: `Masukkan nomor berikut untuk konfirmasi: <b>${randomNumber}</b><br><br>
                                <input type="text" id="confirmInput" class="swal2-input" placeholder="Masukkan nomor di atas">`,
                            showCancelButton: true,
                            confirmButtonText: "OK",
                            preConfirm: () => {
                                let userInput = document.getElementById(
                                        "confirmInput")
                                    .value;
                                if (userInput != randomNumber) {
                                    Swal.showValidationMessage(
                                        "Nomor tidak sesuai!");
                                }
                                return userInput == randomNumber;
                            }
                        }).then((confirmResult) => {
                            if (confirmResult.isConfirmed) {
                                $.ajax({
                                    url: `/purchasing/${noPo}`,
                                    type: 'PUT',
                                    data: {
                                        no_order: noOrder,
                                        closestatus: 2,
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function(response) {
                                        $('#poModal').modal('hide');
                                        table.ajax.reload();
                                        let timerInterval;
                                        Swal.fire({
                                            title: "Done!",
                                            html: "PO Closed",
                                            timer: 750,
                                            timerProgressBar: true,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            },
                                            willClose: () => {
                                                clearInterval(
                                                    timerInterval
                                                );
                                            }
                                        }).then((result) => {
                                            if (result
                                                .dismiss ===
                                                Swal
                                                .DismissReason
                                                .timer) {
                                                console.log(
                                                    "I was closed by the timer"
                                                );
                                            }
                                        });
                                    },
                                    error: function(xhr, status,
                                        error) {
                                        console.error(
                                            "Terjadi kesalahan:",
                                            error);
                                        AppAlert.auto(
                                            "Gagal memperbarui data. Silakan coba lagi."
                                        );
                                        console.log(xhr
                                            .responseJSON);
                                    },
                                    complete: function() {
                                        $('#updateso').prop(
                                            'disabled', false
                                        );
                                    }
                                });
                            }
                        });
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        if (inputlabeledit == '-') {
                            $('#inputlabeledit').val('Freight Handling');
                        } else {
                            $('#inputlabeledit').val(inputlabeledit);
                        }
                        $('#nopoedit').val(noPo);
                        $('#ongkiredit').autoNumeric('set', ongkiredit);
                        $('#diskonedit').autoNumeric('set', diskonedit);
                        $('#Modaleditdiskon').modal('show');
                    }
                });
            });

            $('#updateso').on('click', function(event) {
                event.preventDefault();
                let noPo = $('#noPoInput').val();
                let noOrder = $('#noOrder').val();
                let term_pembayaran = $('#term_pembayaran').val();
                if (term_pembayaran == '') {
                    term_pembayaran = 'Tidak Ada';
                } else {
                    term_pembayaran = $('#term_pembayaran').val();
                }
                let untukPerhatian = $('#untukperhatian').val();
                $(this).prop('disabled', true);
                $.ajax({
                    url: `/purchasing/${noPo}`,
                    type: 'PUT',
                    data: {
                        no_order: noOrder,
                        term_pembayaran: term_pembayaran,
                        closestatus: 0,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#poModal').modal('hide');
                        table.ajax.reload();
                        let timerInterval;
                        Swal.fire({
                            title: "Done!",
                            html: "Nomor SO Terupadate",
                            timer: 750,
                            timerProgressBar: true,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                            willClose: () => {
                                clearInterval(timerInterval);
                            }
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.timer) {
                                console.log("I was closed by the timer");
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Terjadi kesalahan:", error);
                        AppAlert.auto("Gagal memperbarui data. Silakan coba lagi.");
                        console.log(xhr.responseJSON);
                    },
                    complete: function() {
                        $('#updateso').prop('disabled', false);
                    }
                });
            });

            $('#poKertasTable').on('click', '.btn-detail', function() {
                const noPo = $(this).data('no-po');
                let detailRow = $(`#detail-${noPo}`);
                if (!detailRow.length) {
                    const parentRow = $(this).closest('tr');
                    parentRow.after(`
                        <tr id="detail-${noPo}" class="collapse">
                            <td colspan="7">
                                <div class="table-responsive">
                                    <table class="table table-striped" style="background-color: #f0f9f0; color: #2c3e50;">
                                        <thead>
                                            <tr>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: left; border-bottom: 2px solid #7cb37c;">Nama Bahan</th>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: left; border-bottom: 2px solid #7cb37c;">Satuan</th>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: center; border-bottom: 2px solid #7cb37c;">Qty</th>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: right; border-bottom: 2px solid #7cb37c;">Harga</th>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: right; border-bottom: 2px solid #7cb37c;">Exclude</th>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: right; border-bottom: 2px solid #7cb37c;">PPN</th>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: right; border-bottom: 2px solid #7cb37c;">Include</th>
                                                <th style="background-color: #a8d5a8; font-weight: bold; color: #1d3b1d; text-align: center; border-bottom: 2px solid #7cb37c;"><button class="btn btn-primary btn-sm" onclick="tambahDetail('${noPo}')"><i class="fa fa-wrench"></i></button></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    `);
                    detailRow = $(`#detail-${noPo}`);
                }

                if (!detailRow.hasClass('loaded')) {
                    $.ajax({
                        url: '/purchasing/' + noPo,
                        method: 'GET',
                        success: function(detailData) {
                            const detailBody = detailRow.find('tbody');
                            detailBody.empty();
                            detailData.forEach((d) => {
                                detailBody.append(`
                                <tr>
                                    <td style="text-align: left;">${d.nama}</td>
                                    <td style="text-align: left;">${d.satuan}</td>
                                    <td style="text-align: center;">${d.jumlah}</td>
                                    <td style="text-align: right;">${d.harga_bahan}</td>
                                    <td style="text-align: right;">${d.exclude}</td>
                                    <td style="text-align: right;">${d.ppn}</td>
                                    <td style="text-align: right;">${d.include}</td>
                                    <td style="width: 70px;">
                                        <button class="btn btn-danger btn-sm" onclick="hapusdetail('${d.unique}')">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn btn-info btn-sm ms-1" onclick="editdetail('${d.unique}')">
                                            <i class="fa fa-edit" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            `);
                            });
                            detailRow.addClass('loaded');
                        }
                    });
                }
                detailRow.collapse('toggle');
            });

            $('#saveChanges').click(function(e) {
                e.preventDefault();
                let id = $('#id_edit').val()
                $.ajax({
                    url: `/createpo/${id}`,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        harga: parseFloat($('#harga_edit').autoNumeric('get')) || 0,
                        jumlah: parseFloat($('#jumlah_edit').autoNumeric('get')) || 0,
                        nilaippn: nilaippn,
                    },
                    success: function(response) {
                        if (response.message === 'OK') {
                            $('#poModaledit').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses!',
                                text: 'Data berhasil disimpan atau diperbarui.',
                                showConfirmButton: false,
                                showLoaderOnConfirm: true,
                                timer: 1000,
                                onClose: () => {
                                    $('#saveChanges').prop('disabled', false);
                                    $('#saveChanges').html('Simpan');
                                }
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Gagal menyimpan atau memperbarui data.'
                            });
                            $('#saveChanges').prop('disabled', false);
                            $('#saveChanges').html('Simpan');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Terjadi kesalahan saat menyimpan atau memperbarui data.'
                        });
                        $('#saveChanges').prop('disabled', false);
                        $('#saveChanges').html('Simpan');
                    }
                });
            });

            $('#poKertasTable').on('click', '.btn-cetak', async function() {
                const nomorpo = $(this).data('no-po');
                const button = $(this);
                const row = button.closest('tr');
                const detailButton = row.find('.btn-detail');
                button.attr('disabled', true);
                button.html('<i class="fa fa-spin fa-spinner"></i>');
                detailButton.attr('disabled', true);
                try {
                    const data = new FormData();
                    data.append("nomorpo", nomorpo);
                    const csrfToken = "{{ csrf_token() }}";
                    const response = await fetch('/cetakpembelian', {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken
                        },
                        body: data
                    });
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(
                            `Gagal mendapatkan respons dari server: ${errorText}`);
                    }
                    const pdfBlob = await response.blob();
                    const pdfUrl = URL.createObjectURL(pdfBlob);
                    const newWindow = window.open(pdfUrl, '_blank');
                    if (!newWindow) {
                        AppAlert.auto(
                            "Tidak dapat membuka PDF. Pastikan pop-up blocker dinonaktifkan."
                        );
                    }
                } catch (error) {
                    console.error('Error saat mencetak:', error);
                    AppAlert.auto("Terjadi kesalahan saat mencetak:\n" + error.message);
                } finally {
                    button.html('<i class="fas fa-print"></i>');
                }
            });

            $('#updatediskon').on('click', function(event) {
                event.preventDefault();
                let noPo = $("#nopoedit").val();
                let diskonedit = parseFloat($('#diskonedit').autoNumeric('get')) || 0;
                let ongkiredit = parseFloat($('#ongkiredit').autoNumeric('get')) || 0;
                let inputlabeledit = $("#inputlabeledit").val().toLowerCase().replace(/\s+/g, '') ===
                    'freighthandling' ? '-' : $("#inputlabeledit").val();
                $(this).prop('disabled', true);
                $.ajax({
                    url: `/purchasing/${noPo}`,
                    type: 'PUT',
                    data: {
                        diskonedit: diskonedit,
                        ongkiredit: ongkiredit,
                        inputlabeledit: inputlabeledit,
                        closestatus: 99,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#poModal').modal('hide');
                        table.ajax.reload();
                        let timerInterval;
                        Swal.fire({
                            title: "Done!",
                            html: "Berhasil Update",
                            timer: 750,
                            timerProgressBar: true,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                            willClose: () => {
                                clearInterval(timerInterval);
                            }
                        }).then((result) => {
                            if (result.dismiss === Swal.DismissReason.timer) {
                                console.log("I was closed by the timer");
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Terjadi kesalahan:", error);
                        AppAlert.auto("Gagal memperbarui data. Silakan coba lagi.");
                        console.log(xhr.responseJSON);
                    },
                    complete: function() {
                        $('#updatediskon').prop('disabled', false);
                        $('#Modaleditdiskon').modal('hide');
                    }
                });
            });
        });
    </script>
@endpush
