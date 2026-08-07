<div class="modal fade" id="editInvoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-white py-3 px-4">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Invoice LPB ({{ $invoice->no_invoice }})
                </h5>
                <button type="button" class="btn-close btn-close-white  opacity-100" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-update-invoice" action="{{ route('invoice-lpb.update', $invoice->id) }}" method="POST"
                data-autosave data-autosave-key="invoice-lpb-edit-{{ $invoice->id }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="kode_supplier" value="{{ $invoice->kode_supplier }}">

                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white rounded-lg">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">LPB dalam invoice</label>
                                <select class="form-select" name="lpb_ids[]" multiple required data-app-picker
                                    data-placeholder="Cari dan pilih LPB/BAP supplier...">
                                    @foreach ($lpbs as $lpb)
                                        <option value="{{ $lpb->id }}" @selected($invoice->lpbs->contains('id', $lpb->id))>
                                            {{ $lpb->id_lpb }} — {{ $lpb->pembelian->supplier->nama ?? '-' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">No. Invoice Supplier
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="no_invoice"
                                    value="{{ $invoice->no_invoice }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Supplier</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $invoice->supplier->nama ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="fw-bold text-dark small text-uppercase">Tanggal Invoice <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal"
                                    value="{{ $invoice->tanggal }}" required>
                            </div>
                            <div class="col-md-6 mb-0">
                                <label class="fw-bold text-dark small text-uppercase">Deadline
                                    Pembayaran</label>
                                <input type="date" class="form-control" name="tgl_deadline_pembayaran"
                                    value="{{ $invoice->tgl_deadline_pembayaran }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card border-0 shadow-sm p-3 bg-white rounded-lg h-100">
                                <label class="fw-bold text-dark small text-uppercase">Catatan / Note</label>
                                <textarea class="form-control" name="note" rows="5" placeholder="Catatan opsional...">{{ $invoice->note }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm p-3 bg-white rounded-lg">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-muted">Sub Total:</span>
                                    <span class="fw-bold h6 text-dark mb-0">Rp
                                        {{ number_format($invoice->sub_total, 0, ',', '.') }}</span>
                                </div>
                                <div class="mb-3 mb-2 d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_ppn" id="edit_is_ppn"
                                            value="1" {{ $invoice->ppn > 0 ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="edit_is_ppn">Gunakan
                                            PPN (11%)</label>
                                    </div>
                                    <span class="fw-bold text-muted" id="edit_text_ppn">Rp
                                        {{ number_format($invoice->ppn, 0, ',', '.') }}</span>
                                </div>
                                <div class="mb-3 mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label fw-bold py-0">Diskon:</label>
                                    <div class="col-sm-8">
                                        <input type="number" step="any" min="0"
                                            class="form-control form-control-sm text-end fw-bold" name="diskon"
                                            id="edit_input_diskon" value="{{ $invoice->diskon }}">
                                    </div>
                                </div>
                                <div class="mb-3 mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label fw-bold py-0">Ongkir:</label>
                                    <div class="col-sm-8">
                                        <input type="number" step="any" min="0"
                                            class="form-control form-control-sm text-end fw-bold" name="ongkir"
                                            id="edit_input_ongkir" value="{{ $invoice->ongkir }}">
                                    </div>
                                </div>
                                <div class="alert alert-info mb-2" data-keep-alert>PPh 23 dicatat saat pembayaran.
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="fw-bold text-dark mb-0">Grand Total:</h5>
                                    <h5 class="fw-bold text-primary mb-0" id="edit_text_grand_total">Rp
                                        {{ number_format($invoice->grand_total, 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3 px-4">
                    <button type="button" class="btn btn-light border fw-bold px-4"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white fw-bold px-4 shadow-sm"
                        id="btn-update-invoice">
                        <i class="fa-solid fa-rotate me-1"></i> Perbarui Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let currentSubTotal = {{ $invoice->sub_total }};

        function calculateEditTotals() {
            let isPpn = $('#edit_is_ppn').is(':checked');
            let ppnNominal = isPpn ? Math.round((currentSubTotal * 11) / 100) : 0;
            let diskon = parseFloat($('#edit_input_diskon').val()) || 0;
            let ongkir = parseFloat($('#edit_input_ongkir').val()) || 0;
            let grandTotal = (currentSubTotal + ppnNominal + ongkir) - diskon;

            $('#edit_text_ppn').text('Rp ' + ppnNominal.toLocaleString('id-ID'));
            $('#edit_text_grand_total').text('Rp ' + grandTotal.toLocaleString('id-ID'));
        }

        $('#edit_is_ppn, #edit_input_diskon, #edit_input_ongkir').on('input change',
            function() {
                calculateEditTotals();
            });

        $('#form-update-invoice').on('submit', function(e) {
            e.preventDefault();
            let btn = $('#btn-update-invoice');
            btn.prop('disabled', true).html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Memperbarui...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(res) {
                    if (res.success) {
                        $('#editInvoiceModal').modal('hide');
                        $('#table-invoice-lpb').DataTable().ajax.reload();
                        AppAlert.auto(res.message);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(
                        '<i class="fa-solid fa-rotate me-1"></i> Perbarui Invoice');
                    AppAlert.ajaxError(xhr);
                }
            });
        });
    });
</script>
