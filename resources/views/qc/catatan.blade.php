@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid p-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h3 class="m-0 fw-bold text-primary">Riwayat Catatan Kualitas QC</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label>Mulai Tanggal:</label>
                            <input type="date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>Sampai Tanggal:</label>
                            <input type="date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>No. PO:</label>
                            <input type="text" id="filter_po" class="form-control" placeholder="Cari No. PO...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="btnFilter" class="btn btn-primary me-2"><i class="fas fa-search"></i>
                                Filter</button>
                            <button id="btnReset" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="tableCatatan" class="table table-bordered table-striped table-hover table-sm"
                            width="100%">
                            <thead class="bg-primary text-white text-center">
                                <tr>
                                    <th rowspan="2" class="align-middle">No</th>
                                    <th rowspan="2" class="align-middle">Tgl Terima</th>
                                    <th rowspan="2" class="align-middle">ID LPB / PO</th>
                                    <th rowspan="2" class="align-middle">Nama Barang</th>
                                    <th colspan="6">Indikator Masalah QC</th>
                                    <th rowspan="2" class="align-middle">Notes</th>
                                </tr>
                                <tr>
                                    <th>Spesifikasi <br>Tidak Sesuai</th>
                                    <th>Qty <br>Kurang</th>
                                    <th>Rusak</th>
                                    <th>Expired</th>
                                    <th>Cover <br>Rusak</th>
                                    <th>Kemasan <br>Bocor</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetailCatatan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Detail Catatan QC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm no-border">
                        <tr>
                            <th width="40%">Item</th>
                            <td id="det_item"></td>
                        </tr>
                        <tr>
                            <th>No PO</th>
                            <td id="det_po"></td>
                        </tr>
                        <tr>
                            <th>ID LPB</th>
                            <td id="det_lpb"></td>
                        </tr>
                        <tr>
                            <th>Keterangan</th>
                            <td id="det_notes"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const renderCheck = (val) => val == 1 ? '<i class="fas fa-check-circle text-danger"></i>' :
                '<i class="fas fa-minus text-muted"></i>';

            var table = $('#tableCatatan').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "/catatan-customer",
                    dataSrc: 'data',
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.no_po = $('#filter_po').val();
                    }
                },
                columns: [{
                        data: null,
                        className: 'text-center',
                        render: (d, t, r, m) => m.row + 1
                    },
                    {
                        data: 'tanggal_terima',
                        className: 'text-center'
                    },
                    {
                        data: null,
                        render: (r) =>
                            `<b>${r.id_lpb}</b><br><small class="text-primary">${r.no_po}</small>`
                    },
                    {
                        data: 'nama_bahan'
                    },
                    {
                        data: 'salah_spesifikasi',
                        className: 'text-center',
                        render: renderCheck
                    },
                    {
                        data: 'jumlah_kurang',
                        className: 'text-center',
                        render: renderCheck
                    },
                    {
                        data: 'rusak',
                        className: 'text-center',
                        render: renderCheck
                    },
                    {
                        data: 'tidak_layak',
                        className: 'text-center',
                        render: renderCheck
                    },
                    {
                        data: 'cover_rusak',
                        className: 'text-center',
                        render: renderCheck
                    },
                    {
                        data: 'kemasan_bocor',
                        className: 'text-center',
                        render: renderCheck
                    },
                    {
                        data: 'notes',
                        render: (data) => data ?
                            `<span class="text-truncate d-inline-block" style="max-width: 150px;">${data}</span>` :
                            '-'
                    }
                ],
                language: {
                    emptyTable: "Tidak ada riwayat catatan QC"
                },
                dom: 'Bfrtip',
                buttons: ['excel', 'pdf', 'print']
            });

            $('#btnFilter').on('click', function() {
                table.ajax.reload();
            });

            $('#btnReset').on('click', function() {
                $('#start_date, #end_date, #filter_po').val('');
                table.ajax.reload();
            });

            $('#tableCatatan tbody').on('click', 'tr', function() {
                let data = table.row(this).data();
                if (data) {
                    $('#det_item').text(data.nama_bahan);
                    $('#det_po').text(data.no_po);
                    $('#det_lpb').text(data.id_lpb);
                    $('#det_notes').text(data.notes || '-');
                    $('#modalDetailCatatan').modal('show');
                }
            });
        });
    </script>
@endpush
