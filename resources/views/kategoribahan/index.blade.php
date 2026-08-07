@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Kategori Bahan</h4>
                    <p class="text-muted mb-0">Mapping akun wajib untuk setiap alur persediaan.</p>
                </div>
                @can('create', App\Models\KategoriBahan::class)
                    <button class="btn btn-primary btn-form" data-url="{{ route('kategori-bahan.create') }}"><i
                            class="fa-solid fa-plus me-1"></i> Tambah Kategori</button>
                @endcan
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="category-table" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Tipe</th>
                                    <th>Persediaan</th>
                                    <th>Pemakaian</th>
                                    <th>GRNI</th>
                                    <th>Selisih -</th>
                                    <th>Selisih +</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="category-modal"></div>
    @push('scripts')
        <script>
            $(function() {
                const table = $('#category-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('kategori-bahan.index') }}",
                    columns: [{
                            data: 'katnama',
                            name: 'katnama'
                        }, {
                            data: 'tipe_pembebanan_nama',
                            name: 'tipePembebanan.nama_tipe'
                        },
                        {
                            data: 'coa_persediaan_label',
                            orderable: false
                        }, {
                            data: 'coa_beban_label',
                            orderable: false
                        }, {
                            data: 'coa_clearing_lpb_label',
                            orderable: false
                        }, {
                            data: 'coa_beban_selisih_opname_label',
                            orderable: false
                        }, {
                            data: 'coa_koreksi_opname_label',
                            orderable: false
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-end',
                            render: r => (r.can_update ?
                                `<button class="btn btn-sm btn-outline-primary btn-form" data-url="/kategori-bahan/${r.id}/edit"><i class="fa-solid fa-pen"></i></button> ` :
                                '') + (r.can_delete ?
                                `<button class="btn btn-sm btn-outline-danger btn-delete" data-id="${r.id}"><i class="fa-solid fa-trash"></i></button>` :
                                '')
                        }
                    ]
                });
                $(document).on('click', '.btn-form', function() {
                    $.get($(this).data('url')).done(html => {
                        $('#category-modal').html(html);
                        bootstrap.Modal.getOrCreateInstance(document.querySelector(
                            '#category-modal .modal')).show()
                    }).fail(x => AppAlert.error(x.responseJSON?.message || 'Form gagal dimuat.'))
                });
                $(document).on('submit', '.category-form', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: this.action,
                        type: 'POST',
                        data: $(this).serialize()
                    }).done(r => {
                        bootstrap.Modal.getInstance(this.closest('.modal')).hide();
                        table.ajax.reload();
                        AppAlert.success(r.message)
                    }).fail(x => AppAlert.error(x.responseJSON?.message || 'Data gagal disimpan.'))
                });
                $(document).on('click', '.btn-delete', function() {
                    const id = $(this).data('id');
                    AppAlert.confirm('Hapus kategori yang belum dipakai?').then(r => {
                        if (!r.isConfirmed) return;
                        $.ajax({
                            url: `/kategori-bahan/${id}`,
                            type: 'DELETE',
                            data: {
                                _token: "{{ csrf_token() }}"
                            }
                        }).done(x => {
                            table.ajax.reload();
                            AppAlert.success(x.message)
                        }).fail(x => AppAlert.error(x.responseJSON?.message ||
                            'Kategori tidak dapat dihapus.'))
                    })
                });
            });
        </script>
    @endpush
@endsection
