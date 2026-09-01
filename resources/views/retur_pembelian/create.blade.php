<div class="modal fade" id="createReturModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                    <i class="fa-solid fa-rotate-left me-2"></i>Buat Retur Pembelian Baru
                </h5>
                <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-store-retur" action="{{ route('retur-pembelian.store') }}" method="POST">
                @csrf
                <input type="hidden" name="no_retur" value="{{ $documentNumber }}">
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white rounded-lg">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold text-dark small text-uppercase">Nomor Retur</label>
                                <input type="text" class="form-control bg-light" value="{{ $documentNumber }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-dark small text-uppercase">Pilih LPB (belum ditagih) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="select_lpb" required
                                    data-app-picker data-placeholder="Cari nomor LPB, PO, atau supplier...">
                                    <option value=""></option>
                                    @foreach ($lpbs as $lpb)
                                        <option value="{{ $lpb->id_lpb }}" data-id="{{ $lpb->id }}">
                                            {{ $lpb->id_lpb }} — {{ $lpb->pembelian->supplier->nama ?? '-' }}
                                            ({{ $lpb->tanggal?->format('d-m-Y') }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="lpb_id" id="input_lpb_id">
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-dark small text-uppercase">Tanggal Retur <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-dark small text-uppercase">Alasan Retur <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="alasan"
                                    placeholder="Contoh: barang rusak, tidak sesuai spesifikasi" required maxlength="1000">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-3 mb-0 bg-white rounded-lg">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Barang yang Diretur
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="table-retur-items">
                                <thead class="bg-light text-uppercase font-size-12">
                                    <tr>
                                        <th width="4%" class="text-center">#</th>
                                        <th>Nama Bahan</th>
                                        <th width="15%" class="text-center">Tersedia untuk Retur</th>
                                        <th width="18%" class="text-end">Harga Satuan</th>
                                        <th width="20%" class="text-center">Jumlah Retur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Pilih LPB terlebih dahulu.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-3 px-4">
                    <button type="button" class="btn btn-light border fw-bold px-4"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btn-submit-retur">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Retur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        function formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        }

        $('#select_lpb').on('change', function() {
            const idLpb = $(this).val();
            const idNumeric = $(this).find(':selected').data('id');
            $('#input_lpb_id').val(idNumeric || '');

            if (!idLpb) {
                $('#table-retur-items tbody').html(
                    '<tr><td colspan="5" class="text-center text-muted py-3">Pilih LPB terlebih dahulu.</td></tr>'
                );
                return;
            }

            $.getJSON("{{ route('retur-pembelian.get-lpb-detail', ':id_lpb') }}".replace(':id_lpb', idLpb))
                .done(function(res) {
                    if (!res.success || !res.items.length) {
                        $('#table-retur-items tbody').html(
                            '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada barang yang masih tersedia untuk diretur pada LPB ini.</td></tr>'
                        );
                        return;
                    }
                    let rows = res.items.map(function(item, i) {
                        return `<tr>
                            <td class="text-center align-middle">${i + 1}</td>
                            <td class="align-middle fw-bold">${item.nama_bahan}</td>
                            <td class="text-center align-middle fw-bold text-success">${item.jumlah_tersedia_retur}</td>
                            <td class="text-end align-middle">${formatRupiah(item.harga)}</td>
                            <td class="text-center align-middle">
                                <input type="number" step="any" min="0" max="${item.jumlah_tersedia_retur}"
                                    class="form-control form-control-sm text-end input-jumlah-retur"
                                    name="details[${i}][jumlah_retur]" value="0">
                                <input type="hidden" name="details[${i}][lpb_detail_id]" value="${item.id}">
                            </td>
                        </tr>`;
                    });
                    $('#table-retur-items tbody').html(rows.join(''));
                })
                .fail(function(xhr) {
                    AppAlert.ajaxError(xhr);
                });
        });

        $('#form-store-retur').on('submit', function(e) {
            e.preventDefault();
            if (!$('#input_lpb_id').val()) {
                AppAlert.warning('Pilih LPB terlebih dahulu.');
                return;
            }
            const hasQty = $('.input-jumlah-retur').toArray().some(el => parseFloat(el.value) > 0);
            if (!hasQty) {
                AppAlert.warning('Isi jumlah retur untuk minimal satu barang.');
                return;
            }

            let btn = $('#btn-submit-retur');
            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                success: function(res) {
                    if (res.success) {
                        $('#createReturModal').modal('hide');
                        $('#table-retur-pembelian').DataTable().ajax.reload();
                        AppAlert.auto(res.message);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Simpan Retur');
                    AppAlert.ajaxError(xhr);
                }
            });
        });
    });
</script>
