@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="{{ route('request.index') }}" class="btn btn-sm btn-light border" title="Kembali">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <h3 class="fw-bold text-dark mb-0">Persetujuan Request Barang</h3>
                    </div>
                    <p class="text-muted mb-0">Periksa jumlah yang disetujui sebelum memproses request.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                    {{ $requestData->no_request }}
                </span>
            </div>

            <form id="formProcessApprove" action="{{ route('request.processApprove', $requestData) }}" method="POST">
                @csrf
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="fw-bold mb-1">Detail Barang</h5>
                                <small class="text-muted">{{ $requestData->details->count() }} item dalam request ini</small>
                            </div>
                            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2">
                                <i class="fa-solid fa-clock me-1"></i>Menunggu tindakan
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            @foreach ($requestData->details as $item)
                                <div class="col-12">
                                    <div class="border rounded-3 p-3 bg-light">
                                        <div class="row align-items-center g-3">
                                            <div class="col-lg-5">
                                                <div class="d-flex align-items-start gap-3">
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                                                        style="width:42px;height:42px">
                                                        <i class="fa-solid fa-box"></i>
                                                    </span>
                                                    <div>
                                                        <h6 class="fw-bold mb-1">{{ $item->nama_barang }}</h6>
                                                        <small class="text-muted d-block">
                                                            {{ $item->kategoriBahan->katnama ?? 'Tanpa kategori' }}
                                                        </small>
                                                        <small class="text-muted">
                                                            Gudang: {{ $item->gudang->nama ?? '-' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <label class="form-label small text-muted mb-1">Jumlah diminta</label>
                                                <div class="fw-semibold">
                                                    {{ number_format((float) $item->jumlah_minta, 2, ',', '.') }}
                                                    {{ $item->satuan }}
                                                </div>
                                            </div>
                                            <div class="col-6 col-lg-4">
                                                <label class="form-label fw-semibold" for="approved-{{ $item->id }}">
                                                    Jumlah disetujui
                                                </label>
                                                <div class="input-group">
                                                    <input id="approved-{{ $item->id }}" type="number" step="any"
                                                        min="0" max="{{ $item->jumlah_minta }}"
                                                        name="items[{{ $item->id }}][jumlah_acc]"
                                                        value="{{ old("items.{$item->id}.jumlah_acc", $item->jumlah_minta) }}"
                                                        class="form-control" required>
                                                    <span class="input-group-text">{{ $item->satuan }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <label class="form-label fw-semibold" for="approval-note">Catatan approver</label>
                            <textarea id="approval-note" name="catatan_approver" class="form-control" rows="3"
                                placeholder="Tambahkan catatan bila diperlukan">{{ old('catatan_approver') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-3 d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="{{ route('request.index') }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-success px-4" id="btnSubmitApprove">
                            <i class="fa-solid fa-check me-2"></i>Setujui Request
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#formProcessApprove').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const button = $('#btnSubmitApprove');
            button.prop('disabled', true)
                .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Memproses...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        confirmButtonColor: '#0d6efd'
                    }).then(() => window.location.href = @json(route('request.index')));
                },
                error: function(xhr) {
                    AppAlert.ajaxError(xhr);
                },
                complete: function() {
                    button.prop('disabled', false)
                        .html('<i class="fa-solid fa-check me-2"></i>Setujui Request');
                }
            });
        });
    </script>
@endpush
