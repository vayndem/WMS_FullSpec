<div class="modal fade" id="jasaFormModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white px-4 py-3">
                <h5 class="modal-title fw-bold" style="letter-spacing: 0.5px;">{{ $title }}</h5>
                <button type="button" class="close text-white layout-btn-close" data-bs-dismiss="modal" aria-label="Close"
                    style="opacity: 0.8;"></button>
            </div>
            <div class="modal-body p-4">
                <form id="jasaForm">
                    <input type="hidden" id="jasa_id" value="{{ isset($jasa) ? $jasa->id : '' }}">

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">Nomor Jasa</label>
                            <input type="text" class="form-control form-control-lg px-3 fs-6 bg-light" id="no_jasa"
                                value="{{ isset($jasa) ? $jasa->no_jasa : '' }}" placeholder="Otomatis (Sistem)"
                                disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">Nama Pelanggan /
                                Rekanan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg px-3 fs-6 uppercase"
                                id="nama" value="{{ isset($jasa) ? $jasa->nama : '' }}" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">No Order</label>
                            <input type="text" class="form-control form-control-lg px-3 fs-6 uppercase"
                                id="no_order" value="{{ isset($jasa) ? $jasa->no_order : '-' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">U/P</label>
                            <input type="text" class="form-control form-control-lg px-3 fs-6 uppercase"
                                id="untukperhatian" value="{{ isset($jasa) ? $jasa->untukperhatian : '-' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">Tanggal</label>
                            <input type="date" class="form-control form-control-lg px-3 fs-6" id="tanggal"
                                value="{{ isset($jasa) ? $jasa->tanggal : \Carbon\Carbon::now()->toDateString() }}">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">Term Pembayaran</label>
                            <input type="text" class="form-control form-control-lg px-3 fs-6" id="term"
                                value="{{ isset($jasa) ? $jasa->term : '' }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">Term Pengiriman</label>
                            <input type="text" class="form-control form-control-lg px-3 fs-6" id="term_pengiriman"
                                value="{{ isset($jasa) ? $jasa->term_pengiriman : 'Tidak Ada' }}">
                        </div>
                    </div>

                    <div class="card border border-light shadow-sm bg-light-50 rounded p-4 mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-4 pb-2 border-bottom"
                            style="letter-spacing: 0.5px;">
                            <i class="fa fa-calculator me-2"></i>Komponen Biaya Jasa
                        </h6>

                        <div class="row mb-3">
                            <div class="col-md-4 mb-3">
                                <label class="small fw-bold text-muted text-uppercase mb-2">Harga Jasa
                                    (Excl)</label>
                                <div class="input-group">
                                    <div class="d-flex"><span
                                            class="input-group-text bg-white px-3">Rp</span></div>
                                    <input type="text"
                                        class="form-control form-control-lg text-end px-3 fw-bold text-dark"
                                        id="totalexclude" value="{{ isset($jasa) ? $jasa->totalexclude : '0' }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="small fw-bold text-muted text-uppercase mb-2">Diskon</label>
                                <div class="input-group">
                                    <div class="d-flex"><span
                                            class="input-group-text bg-white px-3">Rp</span></div>
                                    <input type="text"
                                        class="form-control form-control-lg text-end px-3 text-danger fw-bold"
                                        id="diskon" value="{{ isset($jasa) ? $jasa->diskon : '0' }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="small fw-bold text-muted text-uppercase mb-2">PPN (%)</label>
                                <select id="ppn_rate" class="form-control form-control-lg px-3 fs-6 select2-custom">
                                    <option value="0" {{ isset($jasa) && $jasa->ppn == 0 ? 'selected' : '' }}>0%
                                        (Non-PPN)</option>
                                    <option value="{{ config('app.konstanta_ppn', 11) }}"
                                        {{ (isset($jasa) && $jasa->ppn > 0) || !isset($jasa) ? 'selected' : '' }}>
                                        {{ config('app.konstanta_ppn', 11) }}%</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="small fw-bold text-muted text-uppercase mb-2">Label
                                    Ongkir</label>
                                <input type="text" class="form-control form-control-lg px-3 fs-6" id="inputlabel"
                                    value="{{ isset($jasa) ? $jasa->inputlabel : 'Freight Handling' }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="small fw-bold text-muted text-uppercase mb-2">Biaya
                                    Ongkir</label>
                                <div class="input-group">
                                    <div class="d-flex"><span
                                            class="input-group-text bg-white px-3">Rp</span></div>
                                    <input type="text"
                                        class="form-control form-control-lg text-end px-3 fw-bold text-dark"
                                        id="ongkir" value="{{ isset($jasa) ? $jasa->ongkir : '0' }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="small fw-bold text-muted text-uppercase mb-2">Status
                                    Kontrak</label>
                                <select id="status" class="form-control form-control-lg px-3 fs-6 bg-light"
                                    disabled>
                                    <option value="0" selected>0: Draft</option>
                                    <option value="1" {{ isset($jasa) && $jasa->status == 1 ? 'selected' : '' }}>
                                        1: Sudah di Invoice</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 mb-4">
                        <label class="small fw-bold text-muted text-uppercase mb-2">Notes / Catatan Keterangan
                            Jasa</label>
                        <textarea id="notes" class="form-control px-3 py-2" rows="3"
                            placeholder="Tambahkan catatan internal di sini..." style="resize: none;">{{ isset($jasa) ? $jasa->notes : '' }}</textarea>
                    </div>

                    <div class="row bg-primary text-white p-4 rounded shadow-sm align-items-center mx-0 mb-2">
                        <div class="col-md-6 py-1">
                            <span class="fw-bold text-uppercase tracking-wider"
                                style="font-size: 1rem; opacity: 0.9;">Grand Total Akhir</span>
                        </div>
                        <div class="col-md-6 text-end py-1">
                            <h2 class="fw-bold mb-0" style="font-size: 2.25rem;"><span
                                    style="font-size: 1.25rem; font-weight: 500; opacity: 0.8;"
                                    class="me-2">Rp</span><span id="lblGrandTotal">0.00</span></h2>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer px-4 py-3 bg-light border-top d-flex justify-content-end" style="gap: 10px;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold"
                    data-bs-dismiss="modal" style="border-radius: 6px;">Batal</button>
                <button type="button" id="btnSimpanJasa"
                    class="btn btn-success px-4 py-2 fw-bold shadow-sm" style="border-radius: 6px;">
                    <i class="fa fa-save me-2"></i> SIMPAN TRANSAKSI
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-50 {
        background-color: #fcfcfd;
    }

    .tracking-wider {
        letter-spacing: 1px;
    }

    .fs-6 {
        font-size: 0.95rem !important;
    }

    .form-control-lg {
        height: calc(1.5em + 1rem + 2px);
        padding: 0.5rem 1rem;
        font-size: 0.95rem;
        border-radius: 6px;
    }

    .input-group-text {
        border-top-left-radius: 6px;
        border-bottom-left-radius: 6px;
        color: #6c757d;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .modal-body .mb-3 > label {
        margin-bottom: 0.5rem;
    }
</style>

<script src="assets/js/autoNumeric.js"></script>
<script>
    $(document).ready(function() {
        $('#totalexclude, #diskon, #ongkir').autoNumeric('init', {
            aSep: ',',
            mDec: 2
        });

        function hitungGrandTotal() {
            let excl = parseFloat($('#totalexclude').autoNumeric('get')) || 0;
            let diskon = parseFloat($('#diskon').autoNumeric('get')) || 0;
            let ppro = parseFloat($('#ppn_rate').val()) || 0;
            let ongkir = parseFloat($('#ongkir').autoNumeric('get')) || 0;

            let basisPpn = excl - diskon;
            let ppnNominal = basisPpn * ppro / 100;
            let grandTotal = excl - diskon + ppnNominal + ongkir;

            $('#lblGrandTotal').text(Number(grandTotal).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }

        $('#totalexclude, #diskon, #ongkir, #ppn_rate').on('keyup change', function() {
            hitungGrandTotal();
        });

        hitungGrandTotal();

        $('#btnSimpanJasa').click(function(e) {
            e.preventDefault();
            let id = $('#jasa_id').val();
            let method = id ? 'PUT' : 'POST';
            let url = id ? `/jasa/${id}` : '/jasa';

            $(this).prop('disabled', true);

            $.ajax({
                url: url,
                type: method,
                data: {
                    _token: '{{ csrf_token() }}',
                    no_jasa: $('#no_jasa').val(),
                    nama: $('#nama').val(),
                    no_order: $('#no_order').val(),
                    untukperhatian: $('#untukperhatian').val(),
                    tanggal: $('#tanggal').val(),
                    term: $('#term').val(),
                    term_pengiriman: $('#term_pengiriman').val(),
                    totalexclude: parseFloat($('#totalexclude').autoNumeric('get')) || 0,
                    diskon: parseFloat($('#diskon').autoNumeric('get')) || 0,
                    ppn: parseFloat($('#ppn_rate').val()) || 0,
                    inputlabel: $('#inputlabel').val(),
                    ongkir: parseFloat($('#ongkir').autoNumeric('get')) || 0,
                    status: $('#status').val(),
                    notes: $('#notes').val()
                },
                success: function(res) {
                    $('#jasaFormModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Data transaksi berhasil disimpan',
                        timer: 1000,
                        showConfirmButton: false
                    });
                },
                error: function() {
                    $('#btnSimpanJasa').prop('disabled', false);
                }
            });
        });
    });
</script>
