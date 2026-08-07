@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h4 class="mb-1">Kunci Periode Akuntansi</h4>
                <p class="text-muted mb-0">Cegah transaksi mengubah periode yang sudah ditutup.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lockModal"><i
                    class="fa-solid fa-lock me-2"></i>Kunci Periode</button>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="periodTable" class="table table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th>Mulai</th>
                                <th>Sampai</th>
                                <th>Status</th>
                                <th>Alasan</th>
                                <th>Dikunci oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="modal fade" id="lockModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="lockForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Kunci Periode</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="rounded-3 border border-warning-subtle bg-warning-subtle text-warning-emphasis p-3 mb-3"><i
                            class="fa-solid fa-triangle-exclamation me-2"></i>Semua transaksi bertanggal dalam rentang ini
                        akan ditolak.</div>
                    <div class="row g-3">
                        <div class="col-sm-6"><label class="form-label">Tanggal mulai</label><input name="period_start"
                                type="date" class="form-control" required></div>
                        <div class="col-sm-6"><label class="form-label">Tanggal akhir</label><input name="period_end"
                                type="date" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Alasan closing</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light"
                        data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Kunci</button></div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const table = $('#periodTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('period-lock.index') }}',
                columns: [{
                        data: 'period_start'
                    }, {
                        data: 'period_end'
                    },
                    {
                        data: 'status',
                        render: s =>
                            `<span class="badge ${s==='LOCKED'?'bg-danger':'bg-success'}">${s}</span>`
                    },
                    {
                        data: 'reason'
                    }, {
                        data: 'locked_by_name',
                        defaultContent: '-'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: r => r.can_unlock ?
                            `<button class="btn btn-sm btn-outline-primary unlock" data-id="${r.id}"><i class="fa-solid fa-lock-open me-1"></i>Buka</button>` :
                            '-'
                    }
                ]
            });
            $('#lockForm').on('submit', async function(e) {
                e.preventDefault();
                try {
                    await $.ajax({
                        url: '{{ route('period-lock.store') }}',
                        method: 'POST',
                        data: $(this).serialize()
                    });
                    bootstrap.Modal.getInstance(document.getElementById('lockModal')).hide();
                    this.reset();
                    table.ajax.reload();
                    AppAlert.success('Periode berhasil dikunci.');
                } catch (xhr) {
                    AppAlert.ajaxError(xhr);
                }
            });
            $('#periodTable').on('click', '.unlock', async function() {
                const result = await Swal.fire({
                    title: 'Buka kembali periode?',
                    input: 'textarea',
                    inputLabel: 'Alasan wajib diisi',
                    showCancelButton: true,
                    confirmButtonText: 'Buka periode',
                    cancelButtonText: 'Batal',
                    inputValidator: v => !v?.trim() ? 'Alasan wajib diisi' : undefined
                });
                if (!result.isConfirmed) return;
                try {
                    await $.ajax({
                        url: '{{ url('period-lock') }}/' + this.dataset.id + '/unlock',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            unlock_reason: result.value
                        }
                    });
                    table.ajax.reload();
                    AppAlert.success('Periode berhasil dibuka.');
                } catch (xhr) {
                    AppAlert.ajaxError(xhr);
                }
            });
        });
    </script>
@endpush
