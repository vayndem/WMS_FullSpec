@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">Daftar Jurnal</h4>
                            <p class="mb-0 text-muted">Kelola header jurnal transaksi dan penyesuaian akuntansi</p>
                        </div>
                        @can('create', App\Models\Jurnal::class)
                            <button type="button" class="btn btn-primary add-list shadow-sm btn-open-create-modal">
                                <i class="fa-solid fa-plus me-2"></i>Buat Jurnal Baru
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-3">
                                    <label for="filterSumberTransaksi" class="form-label fw-bold">Sumber Transaksi</label>
                                    <select id="filterSumberTransaksi" class="form-select">
                                        <option value="">Semua sumber</option>
                                        <option value="MANUAL">Manual</option>
                                        <option value="LPB">LPB</option>
                                        <option value="NPK">NPK</option>
                                        <option value="INVOICE_SUPPLIER">Invoice Supplier</option>
                                        <option value="PELUNASAN_HUTANG">Pelunasan Hutang</option>
                                        <option value="REVERSAL">Reversal</option>
                                        <option value="ASSET_ACQUISITION">Asset Acquisition</option>
                                        <option value="ASSET_DEPRECIATION">Asset Depreciation</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="filterStatusJurnal" class="form-label fw-bold">Status</label>
                                    <select id="filterStatusJurnal" class="form-select">
                                        <option value="">Semua status</option>
                                        <option value="DRAFT">Draft</option>
                                        <option value="POSTED">Posted</option>
                                        <option value="REVERSED">Reversed</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="filterDateFrom" class="form-label fw-bold">Dari Tanggal</label>
                                    <input type="date" id="filterDateFrom" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="filterDateTo" class="form-label fw-bold">Sampai Tanggal</label>
                                    <input type="date" id="filterDateTo" class="form-control">
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <button type="button" class="btn btn-light border w-100" id="resetJurnalFilters">
                                        Reset Filter
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="table-jurnal" width="100%"
                                    cellspacing="0" data-report-url="{{ route('jurnal.report.pdf') }}"
                                    data-filter-columns="0:no_jurnal,1:tanggal,2:sumber_transaksi,3:keterangan,4:total_debit,5:total_kredit">
                                    <thead class="bg-light text-uppercase font-size-12">
                                        <tr>
                                            <th width="15%" class="py-3 ps-3">No. Jurnal</th>
                                            <th width="12%" class="py-3">Tanggal</th>
                                            <th width="15%" class="py-3">Sumber Transaksi</th>
                                            <th class="py-3">Keterangan</th>
                                            <th width="15%" class="text-end py-3">Total Debit</th>
                                            <th width="15%" class="text-end py-3">Total Kredit</th>
                                            <th width="12%" class="text-center py-3 pe-3">Aksi</th>
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
    </div>

    <div id="modal-container"></div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                let table = $('#table-jurnal').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('jurnal.index') }}",
                        data: function(data) {
                            data.sumber_transaksi = $('#filterSumberTransaksi').val();
                            data.status = $('#filterStatusJurnal').val();
                            data.date_from = $('#filterDateFrom').val();
                            data.date_to = $('#filterDateTo').val();
                        }
                    },
                    order: [
                        [1, 'desc']
                    ],
                    columns: [{
                            data: 'no_jurnal',
                            name: 'no_jurnal',
                            className: 'align-middle ps-3 fw-bold text-primary'
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
                            className: 'align-middle'
                        },
                        {
                            data: 'sumber_transaksi',
                            name: 'sumber_transaksi',
                            className: 'align-middle',
                            render: function(data) {
                                const styles = {
                                    MANUAL: 'bg-info-subtle text-info-emphasis',
                                    LPB: 'bg-warning-subtle text-warning-emphasis',
                                    NPK: 'bg-primary-subtle text-primary-emphasis',
                                    INVOICE_LPB: 'bg-primary-subtle text-primary-emphasis',
                                    PEMBAYARAN: 'bg-success-subtle text-success-emphasis',
                                    PEMBAYARAN_HUTANG: 'bg-success-subtle text-success-emphasis',
                                    SERVICE_BAP: 'bg-info-subtle text-info-emphasis',
                                    ASSET_ACQUISITION: 'bg-secondary-subtle text-secondary-emphasis',
                                    ASSET_DEPRECIATION: 'bg-secondary-subtle text-secondary-emphasis'
                                };
                                const label = String(data || '-').replaceAll('_', ' ');
                                return `<span class="badge ${styles[data] || 'bg-secondary-subtle text-secondary-emphasis'} border px-2 py-2">${label}</span>`;
                            }
                        },
                        {
                            data: 'keterangan',
                            name: 'keterangan',
                            className: 'align-middle',
                            render: function(data) {
                                return data ? data : '-';
                            }
                        },
                        {
                            data: 'total_debit',
                            name: 'total_debit',
                            className: 'text-end align-middle fw-bold text-success',
                            render: function(data) {
                                return 'Rp ' + Number(data).toLocaleString('id-ID');
                            }
                        },
                        {
                            data: 'total_kredit',
                            name: 'total_kredit',
                            className: 'text-end align-middle fw-bold text-danger',
                            render: function(data) {
                                return 'Rp ' + Number(data).toLocaleString('id-ID');
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-3',
                            render: function(data) {
                                let btnShow = `
                                    <button type="button" class="btn btn-sm btn-outline-info me-1 btn-show" data-id="${data.id}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                `;
                                let btnEdit = '';
                                let btnDelete = '';
                                let btnPost = '';
                                let btnReverse = '';

                                if (data.can_update) {
                                    btnEdit = `
                                        <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-edit" data-id="${data.id}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    `;
                                }
                                if (data.can_delete) {
                                    btnDelete = `
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${data.id}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    `;
                                }
                                if (data.can_post) {
                                    btnPost =
                                        `<button type="button" class="btn btn-sm btn-success me-1 btn-post" data-id="${data.id}" title="Posting"><i class="fa-solid fa-check"></i></button>`;
                                }
                                if (data.can_reverse) {
                                    btnReverse =
                                        `<button type="button" class="btn btn-sm btn-outline-danger me-1 btn-reverse" data-id="${data.id}" title="Balik jurnal"><i class="fa-solid fa-rotate-left"></i></button>`;
                                }
                                return btnShow + btnPost + btnReverse + btnEdit + btnDelete;
                            }
                        }
                    ]
                });

                $('#filterSumberTransaksi, #filterStatusJurnal, #filterDateFrom, #filterDateTo').on('change', function() {
                    table.ajax.reload(null, true);
                });

                $('#resetJurnalFilters').on('click', function() {
                    $('#filterSumberTransaksi').val('');
                    $('#filterStatusJurnal').val('');
                    $('#filterDateFrom').val('');
                    $('#filterDateTo').val('');
                    table.ajax.reload(null, true);
                });

                $(document).on('click', '.btn-open-create-modal', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: "{{ route('jurnal.create') }}",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#createJurnalModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form.');
                        }
                    });
                });

                $(document).on('submit', '#form-store-jurnal', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "POST",
                        data: $(this).serialize(),
                        dataType: "JSON",
                        success: function(res) {
                            if (res.success) {
                                $('#createJurnalModal').modal('hide');
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            AppAlert.ajaxError(xhr);
                        }
                    });
                });

                $(document).on('click', '.btn-edit', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    $.ajax({
                        url: "/jurnal/" + id + "/edit",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#editJurnalModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form edit.');
                        }
                    });
                });

                $(document).on('submit', '#form-update-jurnal', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "PUT",
                        data: $(this).serialize(),
                        dataType: "JSON",
                        success: function(res) {
                            if (res.success) {
                                $('#editJurnalModal').modal('hide');
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            AppAlert.ajaxError(xhr);
                        }
                    });
                });

                $(document).on('click', '.btn-show', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    $.ajax({
                        url: "/jurnal/" + id,
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(res) {
                            if (res.success) {
                                renderShowModal(res.data);
                            }
                        },
                        error: function() {
                            AppAlert.auto('Gagal mengambil data detail jurnal.');
                        }
                    });
                });

                $(document).on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    AppAlert.confirm("Hapus jurnal draft ini beserta detailnya?").then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "/jurnal/" + id,
                                type: "DELETE",
                                data: {
                                    _token: "{{ csrf_token() }}"
                                },
                                success: function(res) {
                                    if (res.success) {
                                        table.ajax.reload();
                                    }
                                },
                                error: function() {
                                    AppAlert.auto("Gagal menghapus data.");
                                }
                            });
                        }
                    });
                });
                $(document).on('click', '.btn-post', function() {
                    const id = $(this).data('id');
                    AppAlert.confirm('Setelah diposting jurnal akan terkunci. Lanjutkan?').then(result => {
                        if (!result.isConfirmed) return;
                        $.post(`/jurnal/${id}/post`, {
                                _token: "{{ csrf_token() }}"
                            })
                            .done(res => {
                                AppAlert.success(res.message);
                                table.ajax.reload();
                            })
                            .fail(xhr => AppAlert.error(xhr.responseJSON?.message ||
                                'Gagal memposting jurnal.'));
                    });
                });
                $(document).on('click', '.btn-reverse', function() {
                    const id = $(this).data('id');
                    AppAlert.confirm('Buat jurnal pembalik? Transaksi sumber tidak otomatis dibatalkan.').then(
                        result => {
                            if (!result.isConfirmed) return;
                            $.post(`/jurnal/${id}/reverse`, {
                                    _token: "{{ csrf_token() }}"
                                })
                                .done(res => {
                                    AppAlert.success(res.message);
                                    table.ajax.reload();
                                })
                                .fail(xhr => AppAlert.error(xhr.responseJSON?.message ||
                                    'Gagal membalik jurnal.'));
                        });
                });

                function renderShowModal(data) {
                    let detailsHtml = '';
                    if (data.details && data.details.length > 0) {
                        data.details.forEach(function(item, i) {
                            let coaInfo = item.coa ? `${item.coa.kode_akun} - ${item.coa.nama_akun}` : '-';
                            detailsHtml += `
                                <tr>
                                    <td class="text-center align-middle">${i + 1}</td>
                                    <td class="align-middle fw-bold">${coaInfo}</td>
                                    <td class="align-middle">${item.keterangan ? item.keterangan : '-'}</td>
                                    <td class="text-end align-middle text-success fw-bold">Rp ${Number(item.debit).toLocaleString('id-ID')}</td>
                                    <td class="text-end align-middle text-danger fw-bold">Rp ${Number(item.kredit).toLocaleString('id-ID')}</td>
                                </tr>
                            `;
                        });
                    } else {
                        detailsHtml =
                            '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada item rincian jurnal.</td></tr>';
                    }

                    let modalHtml = `
                        <div class="modal fade" id="showJurnalModal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-book me-2"></i>Jurnal: ${data.no_jurnal}</h5>
                                        <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row mb-3">
                                            <div class="col-md-4"><small class="text-muted d-block">Tanggal</small><span class="fw-bold h6">${data.tanggal}</span></div>
                                            <div class="col-md-4"><small class="text-muted d-block">Sumber Transaksi</small><span class="fw-bold h6">${data.sumber_transaksi}</span></div>
                                            <div class="col-md-4"><small class="text-muted d-block">Reff ID</small><span class="fw-bold h6">${data.reff_id ?? '-'}</span></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6"><small class="text-muted d-block">Total Debit</small><span class="fw-bold text-success h5">Rp ${Number(data.total_debit).toLocaleString('id-ID')}</span></div>
                                            <div class="col-md-6"><small class="text-muted d-block">Total Kredit</small><span class="fw-bold text-danger h5">Rp ${Number(data.total_kredit).toLocaleString('id-ID')}</span></div>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Keterangan Header</small>
                                            <span class="fw-bold">${data.keterangan ?? '-'}</span>
                                        </div>

                                        <hr>
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-ol me-2 text-info"></i>Rincian Entri Jurnal</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm mb-0">
                                                <thead class="bg-light font-size-12">
                                                    <tr>
                                                        <th width="5%" class="text-center">#</th>
                                                        <th width="40%">Akun (COA)</th>
                                                        <th>Keterangan Baris</th>
                                                        <th width="20%" class="text-end">Debit</th>
                                                        <th width="20%" class="text-end">Kredit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>${detailsHtml}</tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    $('#modal-container').html(modalHtml);
                    $('#showJurnalModal').modal('show');
                }
            });
        </script>
    @endpush
@endsection
