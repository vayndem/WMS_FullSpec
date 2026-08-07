@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .select2-container {
            width: 100% !important;
            z-index: 1051;
        }

        .select2-container .select2-selection--single {
            height: 38px !important;
            padding: .375rem .75rem;
            border: 1px solid #ced4da;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .swal2-html-container {
            text-align: left !important;
            margin-left: 1.25em !important;
        }

        #adjustmentHistoryTable tbody tr {
            cursor: pointer;
        }
    </style>

    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Histori & Pengajuan Stok Adjustment</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modaltambah">
                        <i class="fas fa-plus me-2"></i>Buat Pengajuan Adjustment
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="adjustmentHistoryTable" class="table table-bordered table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Kode</th>
                                    <th>Operator</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modaltambah" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Buat Pengajuan Stok Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="entrydata" onsubmit="return false;">
                        @csrf
                        <div class="row">
                            <div class="mb-3 col-md-6"><label for="tanggal">Tanggal</label><input type="date"
                                    class="form-control" id="tanggal" name="tanggal" required></div>
                            <div class="mb-3 col-md-6"><label for="operator">Operator</label><input type="text"
                                    class="form-control" id="operator" name="operator" required></div>
                        </div>
                        <div class="mb-3"><label for="keterangan">Keterangan</label><input type="text"
                                class="form-control" id="keterangan" name="keterangan" required></div>
                        <hr>
                        <h6 class="text-primary">Detail Barang</h6>
                        <div class="row align-items-end">
                            <div class="mb-3 col-md-5"><label for="barang">Barang</label><select
                                    class="form-control" id="barang" name="barang" style="width: 100%;"></select></div>
                            <div class="mb-3 col-md-3"><label for="jumlah">Jumlah</label><input type="number"
                                    class="form-control" id="jumlah" name="jumlah"
                                    placeholder="Gunakan - untuk mengurangi"></div>
                            <div class="mb-3 col-md-2"><label>Satuan</label><input id="satuanLabel"
                                    class="form-control" value="-" readonly></div>
                            <div class="mb-3 col-md-2"><label>&nbsp;</label><button type="button" id="tambahItem"
                                    class="btn btn-info w-100">Tambah</button></div>
                        </div>
                        <div class="table-responsive mt-3">
                            <table id="table_detail" class="table table-sm table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Barang</th>
                                        <th class="text-end">Jumlah</th>
                                        <th>Satuan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btn-save-adjustment" class="btn btn-success">Simpan Pengajuan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Kode:</strong> <span id="detailKode"></span></p>
                    <div class="table-responsive">
                        <table id="detailContentTable" class="table table-sm table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            'use strict';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const historyTable = $('#adjustmentHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('gudang.history.data') }}",
                    error: (jqXHR, textStatus, errorThrown) => Swal.fire('Error!',
                        `Gagal memuat data: ${errorThrown}`, 'error')
                },
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                }, {
                    data: 'tanggal',
                    name: 'tanggal',
                    render: data => data ? new Date(data).toLocaleDateString('id-ID') : ''
                }, {
                    data: 'kode',
                    name: 'kode'
                }, {
                    data: 'operator',
                    name: 'operator'
                }, {
                    data: 'status',
                    name: 'status'
                }],
                "createdRow": function(row, data, dataIndex) {
                    $(row).attr('data-kode', data.kode);
                }
            });

            $('#adjustmentHistoryTable tbody').on('click', 'tr', function() {
                const kode = $(this).data('kode');
                if (!kode) return;

                const detailBody = $('#detailContentTable tbody');
                detailBody.html('<tr><td colspan="3" class="text-center">Memuat data...</td></tr>');

                $('#detailModalLabel').text('Detail untuk Kode: ' + kode);
                $('#detailKode').text(kode);
                $('#detailModal').modal('show');

                $.ajax({
                    url: "{{ route('gudang.adjustment.details') }}",
                    type: 'GET',
                    data: {
                        kode: kode
                    },
                    success: function(response) {
                        detailBody.empty();
                        if (response.success && response.data.length > 0) {
                            response.data.forEach(item => { 
                                const itemHtml = `
                                    <tr>
                                        <td>
                                            <strong>${item.nama_barang}</strong><br>
                                            <small class="text-muted">${item.keterangan}</small>
                                        </td>
                                        <td class="text-end">${item.jumlah}</td>
                                        <td>${item.satuan}</td>
                                    </tr>`;
                                detailBody.append(itemHtml);
                            });
                        } else {
                            detailBody.html(
                                '<tr><td colspan="3" class="text-center">Tidak ada detail ditemukan.</td></tr>'
                            );
                        }
                    },
                    error: function() {
                        detailBody.html(
                            '<tr><td colspan="3" class="text-center text-danger">Gagal memuat detail.</td></tr>'
                        );
                    }
                });
            });

            const detailTable = $('#table_detail').DataTable({
                searching: false,
                paging: false,
                info: false,
                ordering: false,
                data: [],
                columns: [{
                    render: (data, type, row, meta) => meta.row + 1
                }, {
                    data: 'nama_barang'
                }, {
                    data: 'jumlah',
                    className: 'text-end'
                }, {
                    data: 'satuan'
                }, {
                    data: null,
                    defaultContent: '<button type="button" class="btn btn-danger btn-sm btn-delete">Hapus</button>',
                    className: 'text-center'
                }]
            });

            $('#barang').select2({
                placeholder: "-- Cari & Pilih Barang --",
                dropdownParent: $('#modaltambah'),
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route('searchadjustment') }}",
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({
                        q: params.term
                    }),
                    processResults: (data) => ({
                        results: data.results
                    }),
                    cache: true
                }
            });

            function updateKeterangan() {
                const tgl = $('#tanggal').val();
                $('#keterangan').val(tgl ? `ADJUST pada tanggal ${new Date(tgl).toLocaleDateString('id-ID')}` :
                    'ADJUST');
            }

            function resetModalForm() {
                $('#entrydata')[0].reset();
                let today = new Date().toISOString().slice(0, 10);
                $('#tanggal').val(today);
                updateKeterangan();
                detailTable.clear().draw();
                $('#barang').val(null).trigger('change');
                $('#satuanLabel').val('-');
            }

            $('#modaltambah').on('show.bs.modal', resetModalForm);
            $('#tanggal').on('change', updateKeterangan);

            $('#barang').on('change', function() {
                const d = $(this).select2('data')[0];
                $('#satuanLabel').val(d ? d.satuan : '-');
            });

            $('#tambahItem').on('click', function() {
                const b = $('#barang').select2('data')[0];
                const j = parseFloat($('#jumlah').val());
                if (!b || !b.id) {
                    Swal.fire('Error', 'Pilih barang dahulu.', 'error');
                    return;
                }
                if (isNaN(j) || j === 0) {
                    Swal.fire('Error', 'Jumlah tidak boleh 0.', 'error');
                    return;
                }
                detailTable.row.add({
                    id_barang: b.id,
                    nama_barang: b.text,
                    jumlah: j,
                    satuan: b.satuan
                }).draw();
                $('#barang').val(null).trigger('change');
                $('#jumlah').val('');
            });

            $('#table_detail tbody').on('click', '.btn-delete', function() {
                detailTable.row($(this).parents('tr')).remove().draw();
            });

            $('#btn-save-adjustment').on('click', function() {
                const details = detailTable.rows().data().toArray();
                if (!$('#tanggal').val() || !$('#operator').val() || !$('#keterangan').val()) {
                    Swal.fire('Error', 'Header harus diisi.', 'error');
                    return;
                }
                if (details.length === 0) {
                    Swal.fire('Error', 'Detail barang kosong.', 'error');
                    return;
                }
                let html =
                    `<h6>Konfirmasi Pengajuan:</h6><p><strong>Tanggal:</strong> ${$('#tanggal').val()}<br><strong>Operator:</strong> ${$('#operator').val()}</p><hr><ul>`;
                details.forEach(i => {
                    html += `<li>${i.nama_barang}: <strong>${i.jumlah} ${i.satuan}</strong></li>`;
                });
                html += '</ul>';
                Swal.fire({
                    title: 'Simpan Pengajuan?',
                    html: html,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal'
                }).then((r) => {
                    if (r.isConfirmed) {
                        const data = {
                            _token: $('input[name="_token"]').val(),
                            tanggal: $('#tanggal').val(),
                            keterangan: $('#keterangan').val(),
                            operator: $('#operator').val(),
                            details: details
                        };
                        $.ajax({
                            url: "{{ route('gudang.stokadjust') }}",
                            type: 'POST',
                            data: JSON.stringify(data),
                            contentType: 'application/json',
                            success: (res) => {
                                if (res.status === 'ok') {
                                    Swal.fire('Sukses!', res.message, 'success').then(
                                        () => {
                                            $('#modaltambah').modal('hide');
                                            historyTable.ajax.reload();
                                        });
                                } else {
                                    Swal.fire('Gagal!', res.message || 'Error.',
                                        'error');
                                }
                            },
                            error: (xhr) => Swal.fire('Gagal!', xhr.responseJSON ? xhr
                                .responseJSON.message : 'Server error.', 'error')
                        });
                    }
                });
            });

            // Fix for modal backdrop issue after SweetAlert2
            $(document).on('hidden.bs.modal', '.modal', function() {
                if ($('.modal.show').length) {
                    $('body').addClass('modal-open');
                } else {
                    $('.modal-backdrop').remove();
                }
            });

        });
    </script>
@endpush
