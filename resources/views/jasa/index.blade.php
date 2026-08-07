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

        .custom-table-border th,
        .custom-table-border td {
            border: 1px solid #ede5e5e6;
            padding: 2px;
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
                                            {{ $year }}</option>
                                    @endfor
                                </select>
                                <button class="btn btn-primary btn-sm" id="btnAdd"><i
                                        class="fa fa-plus-square"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="jasaTable" class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f8f9fa; color: #333;">
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">Nomor Jasa
                                        </th>
                                        <th
                                            style="border: 1px solid #dee2e6; padding: 10px; text-align: center; width: 250px;">
                                            Nama</th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">Tanggal
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">Exclude
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">PPN</th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">Include
                                        </th>
                                        <th style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">Action
                                        </th>
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

    <div class="viewmodal" style="display: none;"></div>

    <div class="modal fade" id="modal-preview" tabindex="-1" role="dialog" aria-labelledby="modal-preview-label"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-preview-label">Detail Invoice Jasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
@endsection

@push('scripts')
    <script>
        let table = [];
        $(document).ready(function() {
            table = $('#jasaTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('getDataJasa') }}",
                    data: function(d) {
                        d.bulan = $('#filterMonth').val();
                        d.tahun = $('#filterYear').val();
                    }
                },
                columns: [{
                        data: 'no_jasa',
                        name: 'no_jasa',
                        render: function(data, type, row) {
                            let btnClass = row.status == 1 ? "btn-warning" : "btn-success";
                            return `<button class="btn ${btnClass} btn-sm btn-edit-jasa" data-id="${row.id}">${data}</button>`;
                        }
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal'
                    },
                    {
                        data: 'totalexclude',
                        render: function(data) {
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'totalppn',
                        render: function(data) {
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'totalinclude',
                        render: function(data) {
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'no_jasa',
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-primary btn-sm btn-lihat" data-no-jasa="${data}"><i class="fa fa-eye"></i></button>
                                <button class="btn btn-success btn-sm btn-cetak" data-no-jasa="${data}"><i class="fa fa-print"></i></button>
                            `;
                        },
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#filterMonth, #filterYear').on('change', function() {
                table.ajax.reload();
            });

            $('#btnAdd').click(function(e) {
                e.preventDefault();
                $.ajax({
                    type: "get",
                    url: "/jasa/create",
                    dataType: "json",
                    success: function(response) {
                        if (response.data) {
                            $('.viewmodal').html(response.data).show();
                            $('#jasaFormModal').modal('show');
                        }
                    }
                });
            });

            $('#jasaTable').on('click', '.btn-edit-jasa', function() {
                let id = $(this).data('id');
                $.ajax({
                    type: "get",
                    url: `/jasa/${id}/edit`,
                    dataType: "json",
                    success: function(response) {
                        if (response.data) {
                            $('.viewmodal').html(response.data).show();
                            $('#jasaFormModal').modal('show');
                        }
                    }
                });
            });

            $('#jasaTable').on('click', '.btn-lihat', async function() {
                const nomorjasa = $(this).data('no-jasa');
                $('#modal-preview').modal('show');
                $.ajax({
                    url: '/jasa/lihatcetak',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        nomorjasa: nomorjasa
                    },
                    success: function(html) {
                        $('#preview-content').html(html);
                    }
                });
            });

            $('#jasaTable').on('click', '.btn-cetak', async function() {
                const nomorjasa = $(this).data('no-jasa');
                window.open(`/jasa/cetakdirect?nomorjasa=${encodeURIComponent(nomorjasa)}`, '_blank');
            });
        });
    </script>
@endpush
