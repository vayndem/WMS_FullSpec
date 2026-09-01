@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">Retur Pembelian</h4>
                            <p class="mb-0 text-muted">Kelola pengembalian barang ke supplier sebelum LPB ditagih</p>
                        </div>
                        @can('create', App\Models\ReturPembelian::class)
                            <button type="button" class="btn btn-primary add-list shadow-sm btn-open-create-modal">
                                <i class="fa-solid fa-plus me-2"></i>Buat Retur Pembelian
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="table-retur-pembelian"
                                    width="100%" cellspacing="0">
                                    <thead class="bg-light text-uppercase font-size-12">
                                        <tr>
                                            <th width="15%" class="py-3 ps-3">No. Retur</th>
                                            <th width="12%" class="py-3">Tanggal</th>
                                            <th width="12%" class="py-3">No. LPB</th>
                                            <th class="py-3">Alasan</th>
                                            <th width="15%" class="text-end py-3">Total Nilai</th>
                                            <th width="12%" class="text-center py-3">Status</th>
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
    </div>

    <div id="modal-container"></div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                let table = $('#table-retur-pembelian').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('retur-pembelian.index') }}"
                    },
                    columns: [{
                            data: 'no_retur',
                            name: 'no_retur',
                            className: 'align-middle ps-3 fw-bold text-primary'
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
                            className: 'align-middle'
                        },
                        {
                            data: 'id_lpb',
                            name: 'lpb.id_lpb',
                            className: 'align-middle'
                        },
                        {
                            data: 'alasan',
                            name: 'alasan',
                            className: 'align-middle'
                        },
                        {
                            data: 'total_nilai',
                            name: 'total_nilai',
                            className: 'text-end align-middle fw-bold',
                            render: function(data) {
                                return 'Rp ' + Number(data).toLocaleString('id-ID');
                            }
                        },
                        {
                            data: 'status_label',
                            name: 'status',
                            className: 'text-center align-middle',
                            render: function(data, type, row) {
                                return row.status === 'POSTED' ?
                                    '<span class="badge bg-success p-2">Aktif</span>' :
                                    '<span class="badge bg-secondary p-2">Dibalik</span>';
                            }
                        }
                    ]
                });

                $(document).on('click', '.btn-open-create-modal', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: "{{ route('retur-pembelian.create') }}",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#createReturModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form.');
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
