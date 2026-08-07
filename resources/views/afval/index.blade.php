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

        td.dt-control {
            background: url('https://datatables.net/examples/resources/details_open.png') no-repeat center center;
            cursor: pointer;
        }

        tr.dt-shown td.dt-control {
            background: url('https://datatables.net/examples/resources/details_close.png') no-repeat center center;
        }
    </style>
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">{{ $title }}</h4>
                            <div class="d-flex">
                                <button class="btn btn-info btn-sm me-2" id="btnToggleStatus">
                                    <i class="fa fa-check-circle"></i> Tampilkan Afval Selesai Faktur
                                </button>
                                <button class="btn btn-primary btn-sm" id="btnAdd">
                                    <i class="fa fa-plus-square"></i> Tambah
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="afvaltable" class="table table-striped table-bordered" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th style="width: 5%;">No</th>
                                        <th>Kode Afval</th>
                                        <th>Nama</th>
                                        <th>Alamat</th>
                                        <th>Barang</th>
                                        <th>Berat</th>
                                        <th>Tanggal</th>
                                        <th>Harga Total</th>
                                        <th>Notes</th>
                                        <th>Status</th>
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

    <div class="viewmodal" style="display: none;"></div>
    @include('afval.tambah')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            let currentStatus = 'waiting';

            function format(detailsData) {
                if (!detailsData || detailsData.length === 0) {
                    return '<p>Tidak ada data detail untuk ditampilkan.</p>';
                }
                let detailsTable =
                    '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px; width:100%;">' +
                    '<thead style="background-color: #f0f0f0;"><tr><th>Tipe Afval</th><th style="text-align:right;">Berat</th><th style="text-align:right;">Harga Satuan</th></tr></thead>' +
                    '<tbody>';

                const options = {
                    minimumFractionDigits: 4,
                    maximumFractionDigits: 4
                };

                detailsData.forEach(item => {
                    let berat = parseFloat(item.berat) || 0;
                    let harga = parseFloat(item.harga_satuan) || 0;

                    detailsTable += '<tr>' +
                        '<td>' + item.tipe + '</td>' +
                        '<td style="text-align:right;">' + berat.toLocaleString('id-ID', options) +
                        ' kg</td>' +
                        '<td style="text-align:right;">Rp ' + harga.toLocaleString('id-ID', options) +
                        '</td>' +
                        '</tr>';
                });
                detailsTable += '</tbody></table>';
                return detailsTable;
            }

            let table = $('#afvaltable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('readafval') }}",
                    type: "GET",
                    data: function(d) {
                        d.status = currentStatus;
                    }
                },
                columns: [{
                    className: 'dt-control',
                    orderable: false,
                    data: null,
                    defaultContent: ''
                }, {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                }, {
                    data: 'kode_afval',
                    name: 'kode_afval'
                }, {
                    data: 'nama',
                    name: 'nama'
                }, {
                    data: 'alamat',
                    name: 'alamat'
                }, {
                    data: 'tipe',
                    name: 'tipe'
                }, {
                    data: 'berat',
                    name: 'berat',
                    render: function(data, type, row) {
                        return (parseFloat(data) || 0).toLocaleString('id-ID') + " kg";
                    }
                }, {
                    data: 'tanggal',
                    name: 'tanggal'
                }, {
                    data: 'harga_satuan',
                    name: 'harga_satuan',
                    render: function(data, type, row) {
                        let number = parseFloat(data) || 0;
                        return 'Rp ' + number.toLocaleString('id-ID');
                    }
                }, {
                    data: 'notes',
                    name: 'notes'
                }, {
                    data: 'status_faktur',
                    name: 'status_faktur'
                }],
                order: [
                    [2, 'asc']
                ]
            });

            $('#afvaltable tbody').on('click', 'td.dt-control', function() {
                var tr = $(this).closest('tr');
                var row = table.row(tr);
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('dt-shown');
                } else {
                    const rowData = row.data();
                    const kodeAfval = rowData.kode_afval;
                    row.child('<em>Loading...</em>').show();
                    tr.addClass('dt-shown');
                    $.ajax({
                        url: '/afval/details/' + kodeAfval,
                        type: 'GET',
                        success: function(response) {
                            row.child(format(response)).show();
                        },
                        error: function() {
                            row.child('<p class="text-danger">Gagal memuat detail.</p>').show();
                        }
                    });
                }
            });

            $('#btnToggleStatus').on('click', function() {
                if (currentStatus === 'waiting') {
                    currentStatus = 'done faktur';
                    $(this).html('<i class="fa fa-clock"></i> Tampilkan Afval Waiting');
                    $(this).removeClass('btn-info').addClass('btn-success');
                } else {
                    currentStatus = 'waiting';
                    $(this).html('<i class="fa fa-check-circle"></i> Tampilkan Afval Selesai Faktur');
                    $(this).removeClass('btn-success').addClass('btn-info');
                }
                table.ajax.reload();
            });

            $('#btnAdd').on('click', function() {
                $('#modalTambah').modal('show');
            });

            function formatNumber(number, decimals = 2) {
                if (isNaN(number)) return '0.00';
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }).format(number);
            }

            function parseNumber(string) {
                return parseFloat(String(string).replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
            }

            function updateGrandTotal() {
                let totalExclude = 0;
                let totalBerat = 0;
                $('#tabeldetail tbody tr').each(function() {
                    totalBerat += parseNumber($(this).find('td').eq(2).text());
                    totalExclude += parseNumber($(this).find('td').eq(3).text());
                });
                $('#SumTotalExclude').text(formatNumber(totalExclude, 4));
                $('#SumTotalBerat').text(formatNumber(totalBerat, 0));
                const diskon = parseNumber($('#diskon').val());
                const ongkir = parseNumber($('#ongkir').val());
                const grandTotal = (totalExclude - diskon) + ongkir;
                $('#GrandTotalPembelian').val(formatNumber(grandTotal, 4));
            }

            $(document).on('click', '#submitformtambah', function() {
                const name = $('#nama_bahan option:selected').text();
                if (!$('#nama_bahan').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian!',
                        text: 'Pilih bahan terlebih dahulu.',
                    });
                    return;
                }
                const qty = parseFloat($('#jumlah').val());
                const harga = parseFloat($('#harga').val());
                const hargaKaliJumlah = qty * harga;
                const newRow = `
                <tr>
                    <td colspan="2">${name}</td>
                    <td class="text-end">${formatNumber(harga, 4)}</td>
                    <td class="text-end">${formatNumber(qty, 0)}</td>
                    <td class="text-end">${formatNumber(hargaKaliJumlah, 4)}</td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-sm deleteRow">Delete</button>
                    </td>
                </tr>`;
                $('#tabeldetail tbody').append(newRow);
                updateGrandTotal();
                $('#nama_bahan').val('').trigger('change');
                $('#jumlah').val('');
                $('#harga').val('');
            });

            $(document).on('click', '.deleteRow', function() {
                $(this).closest('tr').remove();
                updateGrandTotal();
            });

            $(document).on('keyup change', '#diskon, #ongkir', function() {
                updateGrandTotal();
            });

            $(document).on('click', '.simpansemua', function() {
                const nama_pembeli = $('#nama_suplier').val();
                const alamat = $('#alamat').val();
                const tanggal = $('#tanggal').val();
                const status_faktur = $('#status_faktur').val();
                const notes = $('#notes').val();
                if (!nama_pembeli) {
                    Swal.fire('Perhatian!', 'Nama Pembeli tidak boleh kosong.', 'warning');
                    return;
                }
                if ($('#tabeldetail tbody tr').length === 0) {
                    Swal.fire('Perhatian!', 'Tidak ada item untuk disimpan.', 'warning');
                    return;
                }
                let details = $('#tabeldetail tbody tr').map(function() {
                    const row = $(this).find('td');
                    return {
                        tipe: row.eq(0).text(),
                        harga_satuan: parseNumber(row.eq(1).text()),
                        berat: parseNumber(row.eq(2).text()),
                    };
                }).get();
                const transactionData = {
                    nama: nama_pembeli,
                    alamat: alamat,
                    tanggal: tanggal,
                    status_faktur: status_faktur,
                    notes: notes,
                    details: details
                };
                $.ajax({
                    url: '/createafval',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(transactionData),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data telah berhasil disimpan.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#modalTambah').modal('hide');
                        $('#tabeldetail tbody').empty();
                        $('#nama_suplier').val('');
                        $('#alamat').val('');
                        $('#diskon').val('0');
                        $('#ongkir').val('0');
                        $('#notes').val('');
                        updateGrandTotal();
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorText = 'Terjadi kesalahan saat menyimpan data.';
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            errorText = Object.values(errors).flat().join('<br>');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            html: errorText,
                        });
                    }
                });
            });
        });
    </script>
@endpush
