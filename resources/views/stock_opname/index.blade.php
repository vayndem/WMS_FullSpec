@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Stock Opname</h3>
                    <p class="text-muted mb-0">Hitung fisik, approval accounting, lalu posting stok dan jurnal.</p>
                </div>
                @can('create', App\Models\StockOpname::class)
                    <a class="btn btn-primary" href="{{ route('stock-opname.create') }}"><i class="fa-solid fa-plus me-1"></i>Buat
                        Opname</a>
                @endcan
                <a class="btn btn-success ms-2" href="{{ route('stock-opname.export.excel') }}"><i
                        class="fa-solid fa-file-excel me-1"></i>Export Excel Opname</a>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="opname-table" class="table table-hover align-middle w-100"
                            data-report-url="{{ route('stock-opname.report.pdf') }}"
                            data-filter-columns="0:number,1:cutoff_at,2:warehouse_name,4:status">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Cut-off</th>
                                    <th>Gudang</th>
                                    <th>Item</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(function() {
                const token = "{{ csrf_token() }}";
                const statusBadge = {
                    DRAFT: 'secondary',
                    REJECTED: 'danger',
                    SUBMITTED: 'warning',
                    APPROVED: 'info',
                    POSTED: 'success'
                };
                const table = $('#opname-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('stock-opname.index') }}",
                    order: [
                        [1, 'desc']
                    ],
                    columns: [{
                            data: 'number',
                            name: 'number',
                            className: 'fw-semibold text-primary'
                        }, {
                            data: 'cutoff_at',
                            name: 'cutoff_at'
                        },
                        {
                            data: 'warehouse_name',
                            name: 'warehouse_name'
                        }, {
                            data: 'details_count',
                            name: 'details_count',
                            className: 'text-center'
                        },
                        {
                            data: 'status',
                            name: 'status',
                            render: s => `<span class="badge bg-${statusBadge[s]||'secondary'}">${s}</span>`
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-end text-nowrap',
                            render: r => {
                                let b =
                                    `<button class="btn btn-sm btn-outline-primary detail-modal" data-id="${r.id}" title="Detail selisih"><i class="fa-solid fa-eye"></i></button> <a class="btn btn-sm btn-outline-secondary" href="/stock-opname/${r.id}" title="Halaman lengkap"><i class="fa-solid fa-arrow-up-right-from-square"></i></a> <a class="btn btn-sm btn-outline-danger" target="_blank" href="/stock-opname/${r.id}/pdf"><i class="fa-solid fa-file-pdf"></i></a> `;
                                if (r.can_update) b +=
                                    `<a class="btn btn-sm btn-outline-warning" href="/stock-opname/${r.id}/edit"><i class="fa-solid fa-pen"></i></a> `;
                                if (r.can_submit) b +=
                                    `<button class="btn btn-sm btn-primary action" data-action="submit" data-id="${r.id}">Submit</button> `;
                                if (r.can_approve) b +=
                                    `<a class="btn btn-sm btn-success" href="/stock-opname/${r.id}">Isi Harga & Konfirmasi</a> <button class="btn btn-sm btn-outline-danger action-note" data-action="reject" data-id="${r.id}">Reject</button> `;
                                if (r.can_post) b +=
                                    `<button class="btn btn-sm btn-success action" data-action="post" data-id="${r.id}">Post</button> `;
                                if (r.can_delete) b +=
                                    `<button class="btn btn-sm btn-outline-danger action" data-action="delete" data-id="${r.id}"><i class="fa-solid fa-trash"></i></button>`;
                                return b;
                            }
                        }
                    ]
                });

                function run(id, action, data = {}) {
                    const method = action === 'delete' ? 'DELETE' : 'POST';
                    const url = action === 'delete' ? `/stock-opname/${id}` : `/stock-opname/${id}/${action}`;
                    return $.ajax({
                        url,
                        type: method,
                        data: {
                            _token: token,
                            ...data
                        }
                    })
                }
                $(document).on('click', '.action', function() {
                    const {
                        id,
                        action
                    } = $(this).data();
                    AppAlert.confirm(`${action.toUpperCase()} stock opname ini?`).then(x => {
                        if (!x.isConfirmed) return;
                        run(id, action).done(r => {
                            AppAlert.success(r.message);
                            table.ajax.reload()
                        }).fail(e => AppAlert.error(e.responseJSON?.message || 'Proses gagal.'))
                    })
                });
                $(document).on('click', '.action-note', function() {
                    const {
                        id,
                        action
                    } = $(this).data();
                    Swal.fire({
                        title: action === 'approve' ? 'Approve opname?' : 'Reject opname?',
                        input: 'textarea',
                        inputLabel: 'Catatan',
                        showCancelButton: true,
                        confirmButtonText: 'Simpan'
                    }).then(x => {
                        if (!x.isConfirmed) return;
                        run(id, action, {
                            approval_note: x.value || ''
                        }).done(r => {
                            AppAlert.success(r.message);
                            table.ajax.reload()
                        }).fail(e => AppAlert.error(e.responseJSON?.message || 'Proses gagal.'))
                    })
                });
                $(document).on('click', '.detail-modal', function() {
                    $.get(`/stock-opname/${$(this).data('id')}/detail-data`).done(r => {
                        const esc = value => $('<div>').text(value ?? '').html();
                        const money = n => new Intl.NumberFormat('id-ID', {
                            style: 'currency', currency: 'IDR', maximumFractionDigits: 2
                        }).format(n || 0);
                        const rows = r.items.map((x, i) => {
                            const tone = x.direction === 'PLUS' ? 'text-success' :
                                (x.direction === 'MINUS' ? 'text-danger' : 'text-muted');
                            return `<tr><td>${i+1}</td><td class="fw-semibold">${esc(x.name)}</td>
                                <td class="text-end">${x.system_quantity} ${esc(x.unit)}</td>
                                <td class="text-end">${x.physical_quantity} ${esc(x.unit)}</td>
                                <td class="text-end fw-bold ${tone}">${x.difference_quantity}</td>
                                ${r.financial ? `<td class="text-end">${money(x.unit_cost)}</td><td class="text-end">${money(x.difference_value)}</td>` : ''}
                                <td>${esc(x.reason || '-')}</td></tr>`;
                        }).join('');
                        Swal.fire({
                            title: `Detail ${esc(r.number)}`,
                            width: 'min(1100px, 96vw)',
                            html: `<div class="text-start small text-muted mb-3">${esc(r.warehouse)} · ${esc(r.status)}</div>
                                <div class="table-responsive"><table class="table table-sm align-middle">
                                <thead><tr><th>No.</th><th>Barang</th><th class="text-end">Sistem</th>
                                <th class="text-end">Fisik</th><th class="text-end">Selisih</th>
                                ${r.financial ? '<th class="text-end">Harga</th><th class="text-end">Nilai</th>' : ''}
                                <th>Alasan</th></tr></thead><tbody>${rows}</tbody></table></div>`,
                            confirmButtonText: 'Tutup'
                        });
                    }).fail(e => AppAlert.ajaxError(e));
                });
            });
        </script>
    @endpush
@endsection
