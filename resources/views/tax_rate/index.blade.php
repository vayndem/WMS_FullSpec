@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">Master Tarif Pajak</h4>
                    <p class="text-muted mb-0">Tarif bertanggal efektif; transaksi menyimpan snapshot.</p>
                </div><button class="btn btn-primary" id="addRate"><i class="fa-solid fa-plus me-2"></i>Tambah Tarif</button>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="rateTable" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Tarif</th>
                                    <th>Berlaku mulai</th>
                                    <th>Berlaku sampai</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const table = $('#rateTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('tax-rate.index') }}',
                columns: [{
                        data: 'tax_type'
                    }, {
                        data: 'rate',
                        render: v => `${Number(v).toLocaleString('id-ID')}%`
                    }, {
                        data: 'effective_from'
                    }, {
                        data: 'effective_until',
                        defaultContent: '-'
                    },
                    {
                        data: 'is_active',
                        render: v =>
                            `<span class="badge ${v?'bg-success':'bg-secondary'}">${v?'Aktif':'Nonaktif'}</span>`
                    }, {
                        data: 'description',
                        defaultContent: '-'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: r =>
                            `<button class="btn btn-sm btn-outline-primary edit-rate" data-row="${encodeURIComponent(JSON.stringify(r))}">Edit</button>`
                    }
                ]
            });
            async function form(row = null) {
                const result = await Swal.fire({
                    title: row ? 'Edit tarif' : 'Tambah tarif',
                    html: `
   <div class="text-start"><label class="form-label">Jenis</label><select id="tax_type" class="form-select mb-3"><option>PPN</option><option>PPH23</option></select>
   <label class="form-label">Tarif (%)</label><input id="rate" type="number" min="0" max="100" step=".0001" class="form-control mb-3">
   <label class="form-label">Berlaku mulai</label><input id="from" type="date" class="form-control mb-3"><label class="form-label">Berlaku sampai</label><input id="until" type="date" class="form-control mb-3">
   <label class="form-label">Keterangan</label><textarea id="description" class="form-control mb-3"></textarea><div class="form-check"><input id="active" type="checkbox" class="form-check-input"><label class="form-check-label">Aktif</label></div></div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    didOpen: () => {
                        document.getElementById('tax_type').value = row?.tax_type || 'PPN';
                        document.getElementById('rate').value = row?.rate || '';
                        document.getElementById('from').value = row?.effective_from?.substring(0,
                            10) || '';
                        document.getElementById('until').value = row?.effective_until?.substring(0,
                            10) || '';
                        document.getElementById('description').value = row?.description || '';
                        document.getElementById('active').checked = row ? !!row.is_active : true;
                    },
                    preConfirm: () => ({
                        tax_type: document.getElementById('tax_type').value,
                        rate: document.getElementById('rate').value,
                        effective_from: document.getElementById('from').value,
                        effective_until: document.getElementById('until').value || '',
                        description: document.getElementById('description').value,
                        is_active: document.getElementById('active').checked ? 1 : 0
                    })
                });
                if (!result.isConfirmed) return;
                try {
                    await $.ajax({
                        url: row ? '{{ url('tax-rate') }}/' + row.id : '{{ route('tax-rate.store') }}',
                        method: row ? 'PUT' : 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ...result.value
                        }
                    });
                    table.ajax.reload();
                    AppAlert.success('Tarif pajak berhasil disimpan.');
                } catch (xhr) {
                    AppAlert.ajaxError(xhr);
                }
            }
            $('#addRate').on('click', () => form());
            $('#rateTable').on('click', '.edit-rate', function() {
                form(JSON.parse(decodeURIComponent(this.dataset.row)));
            });
        });
    </script>
@endpush
