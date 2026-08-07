<div class="modal fade" id="createLpbModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-boxes-packing me-2"></i>Buat
                    Penerimaan Barang (LPB)</h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-store-lpb" action="{{ route('lpb.store') }}" method="POST" data-autosave
                data-autosave-key="lpb-create">
                @csrf
                <input type="hidden" name="confirm_over_receive" id="confirm_over_receive" value="0">
                <div class="modal-body">
                    <div class="create-section">
                        <div class="create-section__title"><i class="fa-solid fa-truck-ramp-box"></i>Dokumen Penerimaan
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Nomor LPB</label>
                                <input type="text" class="form-control bg-white" name="id_lpb"
                                    value="{{ $documentNumber }}" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Nomor PO <span class="text-danger">*</span></label>
                                <select class="form-select" name="no_po" id="modal_select_no_po" required
                                    data-app-picker data-placeholder="Cari nomor PO atau supplier...">
                                    <option value="">-- Pilih / Cari PO --</option>
                                    @foreach ($pos as $po)
                                        <option value="{{ $po->no_po }}"
                                            data-subtitle="{{ $po->supplier->nama ?? 'Supplier tidak tersedia' }}"
                                            data-meta="{{ $po->tanggal ?? '-' }} · {{ $po->details->count() }} item"
                                            data-badge="PO Barang">{{ $po->no_po }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Tanggal Terima <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">No. Surat Jalan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="no_sj"
                                    placeholder="Masukkan No. SJ..." required>
                            </div>
                            <div class="col-md-4 mb-3 mb-0">
                                <label class="fw-bold">No. Invoice</label>
                                <input type="text" class="form-control" name="no_invoice"
                                    placeholder="Opsi Tambahan...">
                            </div>
                            <div class="col-md-4 mb-3 mb-0">
                                <label class="fw-bold">Jenis LPB</label>
                                <select class="form-control" name="jenis_lpb">
                                    <option value="1">Reguler</option>
                                    <option value="2">Pengganti / Retur</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 mb-0">
                                <label class="fw-bold">Supplier</label>
                                <input type="text" class="form-control bg-light" id="modal_supplier_nama" readonly
                                    placeholder="Terisi dari PO">
                            </div>
                            <div class="col-md-4 mb-3 mb-0">
                                <label class="fw-bold">Gudang Tujuan PO</label>
                                <input type="text" class="form-control bg-light" id="modal_gudang_nama" readonly
                                    placeholder="Terisi dari PO">
                            </div>
                        </div>

                    </div>
                    <div class="create-section">
                        <h6 class="fw-bold text-dark mb-3"><i
                                class="fa-solid fa-list-check me-2 text-primary"></i>Rincian
                            Item Diterima</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="modal-table-items">
                                <thead class="bg-light text-uppercase font-size-12">
                                    <tr>
                                        <th width="4%" class="text-center">#</th>
                                        <th>Nama Bahan</th>
                                        <th width="18%">Kategori Barang <span class="text-danger">*</span></th>
                                        <th width="10%" class="text-center">Qty PO</th>
                                        <th width="10%" class="text-center">Diterima</th>
                                        <th width="10%" class="text-center">Sisa</th>
                                        <th width="14%" class="text-center">Terima Fisik <span
                                                class="text-danger">*</span></th>
                                        <th width="14%">Lot Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">Pilih Nomor PO terlebih
                                            dahulu.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i
                            class="fa-solid fa-floppy-disk me-1"></i>
                        Simpan LPB</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.listKategori = @json($kategoris);

    $(document).ready(function() {
        $('#modal_select_no_po').on('change', function() {
            let no_po = $(this).val();
            if (!no_po) {
                $('#modal-table-items tbody').html(
                    '<tr><td colspan="8" class="text-center text-muted py-3">Pilih Nomor PO terlebih dahulu.</td></tr>'
                );
                $('#modal_supplier_nama').val('');
                $('#modal_gudang_nama').val('');
                return;
            }

            $.ajax({
                url: "/lpb/po/" + no_po,
                type: "GET",
                dataType: "JSON",
                success: function(res) {
                    if (res.success) {
                        $('#modal_supplier_nama').val(res.po.supplier ? res.po.supplier
                            .nama : '-');
                        $('#modal_gudang_nama').val(res.po.gudang ? res.po.gudang.nama :
                            '-');

                        let rows = '';
                        $.each(res.items, function(i, item) {
                            let kategoriOptions =
                                '<option value="">-- Pilih Kategori --</option>';
                            $.each(window.listKategori, function(idx, kat) {
                                let selected = (item.id_kategori == kat
                                    .id) ? 'selected' : '';
                                kategoriOptions +=
                                    `<option value="${kat.id}" ${selected}>${kat.katnama}</option>`;
                            });

                            rows += `<tr>
                                <td class="text-center align-middle">${i + 1}</td>
                                <td class="align-middle">
                                    <input type="hidden" name="details[${i}][id_bahan]" value="${item.bahan_id}">
                                    <strong>${item.nama_bahan}</strong>
                                </td>
                                <td class="align-middle">
                                    <select class="form-select form-select-sm" name="details[${i}][id_kategori]" required
                                        data-app-picker data-placeholder="Cari kategori...">
                                        ${kategoriOptions}
                                    </select>
                                </td>
                                <td class="text-center align-middle fw-bold">${item.jumlah_po}</td>
                                <td class="text-center align-middle text-primary fw-bold">${item.diterima}</td>
                                <td class="text-center align-middle text-danger fw-bold">${item.sisa}</td>
                                <td>
                                    <input type="number" step="any" min="0.01" class="form-control form-control-sm text-center fw-bold text-success" name="details[${i}][jumlah_barang_diterima]" value="${item.sisa}" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="details[${i}][lot_number]" placeholder="No. Lot">
                                </td>
                            </tr>`;
                        });

                        $('#modal-table-items tbody').html(rows);
                    }
                }
            });
        });

        $('#form-store-lpb').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                dataType: "JSON",
                success: function(res) {
                    if (res.success) {
                        $('#createLpbModal').modal('hide');
                        $('#table-lpb').DataTable().ajax.reload();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let res = xhr.responseJSON;
                        if (res.requires_confirmation) {
                            let msg = res.message + "\n\nKonfirmasi over-receive item:\n";
                            $.each(res.over_items, function(i, item) {
                                msg +=
                                    `- ${item.nama}: Input ${item.input} (Sisa PO: ${item.minta_sisa})\n`;
                            });
                            AppAlert.confirm(msg, {
                                title: 'Penerimaan melebihi PO'
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    $('#confirm_over_receive').val(1);
                                    $('#form-store-lpb').submit();
                                }
                            });
                        } else {
                            AppAlert.ajaxError(xhr);
                        }
                    } else {
                        AppAlert.ajaxError(xhr);
                    }
                }
            });
        });
    });
</script>
