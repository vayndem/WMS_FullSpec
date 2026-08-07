@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">Chart of Accounts (COA)</h4>
                            <p class="mb-0 text-muted">Kelola daftar akun dan struktur pengkodean akuntansi</p>
                        </div>
                        @can('create', App\Models\ChartOfAccount::class)
                            <button type="button" class="btn btn-primary add-list shadow-sm btn-open-create-modal">
                                <i class="fa-solid fa-plus me-2"></i>Tambah Akun COA
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="col-lg-12">
                    @can('updateMapping', App\Models\ChartOfAccount::class)
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">Mapping Akuntansi WMS</h5><small class="text-muted">Sistem memakai
                                        mapping ini, bukan ID atau nama akun.</small>
                                </div>
                                <button class="btn btn-outline-primary" data-bs-toggle="collapse"
                                    data-bs-target="#accounting-mapping">Atur Mapping</button>
                            </div>
                            <div id="accounting-mapping" class="collapse">
                                <form id="form-accounting-mapping" action="{{ route('chart-of-accounts.mapping.update') }}"
                                    method="POST" class="card-body">
                                    @csrf @method('PUT')
                                    <h6 class="text-primary">Mapping global</h6>
                                    <div class="row g-3 mb-4">
                                        @foreach (['HUTANG_USAHA' => 'Hutang Supplier', 'PPN_MASUKAN' => 'PPN Masukan', 'HUTANG_PPH23' => 'Hutang PPh 23', 'BIAYA_BANK' => 'Biaya Bank', 'BEBAN_MATERAI' => 'Beban Materai', 'SELISIH_BAYAR' => 'Selisih Bayar', 'BIAYA_ONGKIR' => 'Biaya Angkut', 'DISKON_PEMBELIAN' => 'Diskon Pembelian'] as $key => $label)
                                            <div class="col-md-6 col-xl-3">
                                                <label class="form-label">{{ $label }}</label>
                                                <select class="form-select" name="global[{{ $key }}]" required>
                                                    <option value="">Pilih akun</option>
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}" @selected(($settings[$key] ?? null) == $account->id)>
                                                            {{ $account->kode_akun }} — {{ $account->nama_akun }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                    <h6 class="text-primary">Mapping per kategori bahan</h6>
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Kategori</th>
                                                    <th>Persediaan</th>
                                                    <th>Pemakaian/Beban</th>
                                                    <th>GRNI</th>
                                                    <th>Selisih Opname (-)</th>
                                                    <th>Koreksi Opname (+)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($categories as $category)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $category->katnama }}</td>
                                                        @foreach (['coa_persediaan_id', 'coa_beban_id', 'coa_clearing_lpb_id', 'coa_beban_selisih_opname_id', 'coa_koreksi_opname_id'] as $field)
                                                            <td><select class="form-select"
                                                                    name="categories[{{ $category->id }}][{{ $field }}]"
                                                                    required>
                                                                    <option value="">Pilih akun</option>
                                                                    @foreach ($accounts as $account)
                                                                        <option value="{{ $account->id }}"
                                                                            @selected($category->{$field} == $account->id)>
                                                                            {{ $account->kode_akun }} —
                                                                            {{ $account->nama_akun }}</option>
                                                                    @endforeach
                                                                </select></td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end"><button class="btn btn-primary" type="submit">Simpan Mapping</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endcan
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="table-coa" width="100%"
                                    cellspacing="0" data-report-url="{{ route('chart-of-accounts.report.pdf') }}"
                                    data-filter-columns="1:kode_akun,2:nama_akun,3:kategori_akun,4:posisi_normal,5:keterangan">
                                    <thead class="bg-light text-uppercase font-size-12">
                                        <tr>
                                            <th width="5%" class="text-center py-3 ps-3">#</th>
                                            <th width="15%" class="py-3">Kode Akun</th>
                                            <th width="25%" class="py-3">Nama Akun</th>
                                            <th width="15%" class="py-3">Kategori</th>
                                            <th width="12%" class="text-center py-3">Posisi Normal</th>
                                            <th class="py-3">Keterangan</th>
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
                let table = $('#table-coa').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('chart-of-accounts.index') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle ps-3'
                        },
                        {
                            data: 'kode_akun',
                            name: 'kode_akun',
                            className: 'align-middle fw-bold text-primary'
                        },
                        {
                            data: 'nama_akun',
                            name: 'nama_akun',
                            className: 'align-middle fw-bold'
                        },
                        {
                            data: 'kategori_akun',
                            name: 'kategori_akun',
                            className: 'align-middle',
                            render: function(data) {
                                let badgeClass = 'secondary';
                                if (data === 'ASET') badgeClass = 'info';
                                else if (data === 'LIABILITAS') badgeClass = 'warning';
                                else if (data === 'EKUITAS') badgeClass = 'primary';
                                else if (data === 'PENDAPATAN') badgeClass = 'success';
                                else if (data === 'BEBAN') badgeClass = 'danger';

                                return `<span class="badge badge-${badgeClass} p-2">${data}</span>`;
                            }
                        },
                        {
                            data: 'posisi_normal',
                            name: 'posisi_normal',
                            className: 'text-center align-middle fw-bold',
                            render: function(data) {
                                if (data === 'DEBIT') {
                                    return '<span class="text-success">DEBIT</span>';
                                } else {
                                    return '<span class="text-danger">KREDIT</span>';
                                }
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
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-3',
                            render: function(data) {
                                let btnEdit = '';
                                let btnDelete = '';

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
                                return btnEdit + btnDelete;
                            }
                        }
                    ]
                });

                $(document).on('click', '.btn-open-create-modal', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: "{{ route('chart-of-accounts.create') }}",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#createCoaModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form.');
                        }
                    });
                });

                $(document).on('submit', '#form-store-coa', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "POST",
                        data: $(this).serialize(),
                        dataType: "JSON",
                        success: function(res) {
                            if (res.success) {
                                $('#createCoaModal').modal('hide');
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            AppAlert.auto(xhr.responseJSON?.message || "Gagal menyimpan data.");
                        }
                    });
                });

                $(document).on('click', '.btn-edit', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    $.ajax({
                        url: "/chart-of-accounts/" + id + "/edit",
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(html) {
                            $('#modal-container').html(html);
                            $('#editCoaModal').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message || 'Gagal memuat form edit.');
                        }
                    });
                });

                $(document).on('submit', '#form-update-coa', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "PUT",
                        data: $(this).serialize(),
                        dataType: "JSON",
                        success: function(res) {
                            if (res.success) {
                                $('#editCoaModal').modal('hide');
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            AppAlert.auto(xhr.responseJSON?.message || "Gagal memperbarui data.");
                        }
                    });
                });

                $(document).on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    AppAlert.confirm(
                        "Akun akan dinonaktifkan dan tidak bisa dipakai transaksi baru. Lanjutkan?").then(
                        function(result) {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "/chart-of-accounts/" + id,
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
                $('#form-accounting-mapping').on('submit', function(e) {
                    e.preventDefault();
                    $.ajax({
                            url: this.action,
                            type: 'POST',
                            data: $(this).serialize()
                        })
                        .done(res => AppAlert.success(res.message))
                        .fail(xhr => AppAlert.error(xhr.responseJSON?.message || 'Mapping gagal disimpan.'));
                });
            });
        </script>
    @endpush
@endsection
