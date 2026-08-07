<div class="current-modal">
    <style>
        .modal-custom-width {
            width: min(1360px, calc(100vw - 2.25rem)) !important;
            max-width: 1360px !important;
            margin: .875rem auto !important;
        }

        .sticky-modal-footer {
            position: sticky;
            bottom: 0;
            background-color: #f8f9fa;
            z-index: 1020;
            border-top: 1px solid #dee2e6;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal {
            overflow-y: auto !important;
        }

        #pilihModal {
            z-index: 1060 !important;
        }

        .modal-backdrop {
            z-index: 1040 !important;
        }

        #Modaltambah {
            z-index: 1050 !important;
        }
    </style>

    <div class="modal fade" id="Modaltambah" data-bs-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-custom-width" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">EDIT PO: {{ $noPo }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <div class="input-group">
                                <input type="hidden" id="id_suplier" value="{{ $po->id_suplier }}">
                                <div class="d-flex">
                                    <button class="btn btn-outline-success" type="button" id="btn_pilih_suplier">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <input type="text" class="form-control" id="nama_suplier"
                                    value="{{ $po->supplier->nama ?? '' }}" disabled style="background-color: #e3f2fd;">
                            </div>
                        </div>
                        <div class="col-sm-2"><input type="text" class="form-control" id="no_order"
                                value="{{ $po->no_order }}" placeholder="No Order"></div>
                        <div class="col-sm-2"><input type="text" class="form-control" id="untukperhatian"
                                value="{{ $po->untukperhatian }}" placeholder="UP"></div>
                        <div class="col-sm-2"><input type="date" class="form-control" id="tgl"
                                value="{{ $po->tanggal }}"></div>
                        <div class="col-sm-2"><input type="text" class="form-control" id="term"
                                value="{{ $po->term }}" placeholder="Term"></div>
                        <div class="col-sm-1" style="border: 1px solid #ff6347; border-radius: 5px; padding: 2px;">
                            <label style="font-size:10px; display:block; text-align:center;">PPN 11%</label>
                            <input type="radio" name="ppn_mode" class="ppn-trigger" id="ppn_inc" value="include"
                                {{ $po->ppn == 0 ? 'checked' : '' }}> Inc
                            <input type="radio" name="ppn_mode" class="ppn-trigger" id="ppn_exc" value="exclude"
                                {{ $po->ppn > 0 ? 'checked' : '' }}> Exc
                        </div>
                    </div>

                    <div class="row mb-2 bg-light py-2">
                        <div class="col-sm-3">
                            <div class="input-group">
                                <input type="hidden" id="id_bahan">
                                <div class="d-flex">
                                    <button class="btn btn-info" type="button" id="btn_cari_bahan_edit">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <input type="text" class="form-control" id="nama_bahan" placeholder="Pilih Bahan"
                                    disabled>
                            </div>
                        </div>
                        <div class="col-sm-2"><input type="text" class="form-control" id="satuan" disabled></div>
                        <div class="col-sm-2"><input type="text" class="form-control text-end" id="harga_row"
                                placeholder="Harga"></div>
                        <div class="col-sm-2"><input type="text" class="form-control text-end" id="qty_row"
                                placeholder="Qty"></div>
                        <div class="col-sm-1"><button class="btn btn-success" id="add_row_edit">Add</button></div>
                    </div>

                    <table id="table_detail_edit" class="table table-sm table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th style="display:none">ID</th>
                                <th>Nama Bahan</th>
                                <th>Satuan</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($details as $dt)
                                <tr>
                                    <td style="display:none">{{ $dt->id_bahan }}</td>
                                    <td>{{ $dt->nama }}</td>
                                    <td>{{ $dt->satuan }}</td>
                                    <td class="text-end">{{ number_format($dt->harga, 2) }}</td>
                                    <td class="text-end">{{ number_format($dt->jumlah, 2) }}</td>
                                    <td class="text-end">{{ number_format($dt->exclude, 2) }}</td>
                                    <td class="text-center"><button
                                            class="btn btn-danger btn-sm btn-del-row">Del</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="row">
                        <div class="col-md-7">
                            <textarea id="notes" class="form-control" rows="3">{{ $po->notes }}</textarea>
                        </div>
                        <div class="col-md-5">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-end">Total Excl:</td>
                                    <td class="text-end fw-bold" id="label_excl">
                                        {{ number_format($po->totalexclude, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end">Diskon:</td>
                                    <td><input type="text" id="diskon" class="form-control text-end"
                                            value="{{ $po->diskon }}"></td>
                                </tr>
                                <tr>
                                    <td class="text-end">PPN:</td>
                                    <td><input type="text" id="ppn_val" class="form-control text-end" readonly
                                            value="{{ number_format($po->totalppn, 2) }}"></td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <input type="text" id="inputlabel"
                                            value="{{ $po->inputlabel ?? 'Freight Handling' }}"
                                            class="form-control form-control-sm text-end border-0 bg-transparent fw-bold">
                                    </td>
                                    <td><input type="text" id="ongkir" class="form-control text-end"
                                            value="{{ $po->ongkir }}"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="sticky-modal-footer">
                    <h4 class="text-primary">GRAND TOTAL: <span
                            id="label_gt">{{ number_format($po->GrandTotalPembelian, 2) }}</span></h4>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button class="btn btn-info" id="btn_update_save"><i class="fa fa-save"></i> UPDATE
                            TRANSAKSI</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    SumTotalExclude = {{ (float) $po->totalexclude }};
    SumTotalppn = {{ (float) $po->totalppn }};
    GrandTotalPembelian = {{ (float) $po->GrandTotalPembelian }};
    PP_CONST = {{ (float) config('app.konstanta_ppn') }};

    function runRecalculate() {
        SumTotalExclude = 0;
        let diskon = parseFloat($('#diskon').autoNumeric('get')) || 0;
        let ongkir = parseFloat($('#ongkir').autoNumeric('get')) || 0;

        $('#table_detail_edit tbody tr').each(function() {
            let rowVal = parseFloat($(this).find('td:eq(5)').text().replace(/,/g, '')) || 0;
            SumTotalExclude += rowVal;
        });

        if ($('#ppn_exc').is(':checked')) {
            SumTotalppn = (SumTotalExclude - diskon) * PP_CONST / 100;
        } else {
            SumTotalppn = 0;
        }

        GrandTotalPembelian = SumTotalExclude + SumTotalppn - diskon + ongkir;

        $('#label_excl').text(formatNumber(SumTotalExclude, 2));
        $('#ppn_val').val(formatNumber(SumTotalppn, 2));
        $('#label_gt').text(formatNumber(GrandTotalPembelian, 2));
    }

    $(document).ready(function() {
        $('#harga_row, #qty_row, #diskon, #ongkir').autoNumeric('init', {
            aSep: ',',
            mDec: 2
        });

        $('.ppn-trigger, #diskon, #ongkir').on('change keyup', function() {
            runRecalculate();
        });

        $('#btn_pilih_suplier').on('click', function() {
            $.ajax({
                type: "get",
                url: "{{ route('showmodalpencariansupplier') }}",
                success: function(response) {
                    $('#tempat-modal').empty().html(response.data);
                    $('#pilihModal').modal({
                        backdrop: false
                    });
                    $('#pilihModal').modal('show');
                }
            });
        });

        $('#btn_cari_bahan_edit').on('click', function() {
            $.ajax({
                url: "{{ route('showmodalpencarianbahan') }}",
                data: {
                    jenis: "{{ $jenis }}"
                },
                success: function(res) {
                    $('#tempat-modal').empty().html(res.data);
                    $('#pilihModal').modal({
                        backdrop: false
                    });
                    $('#pilihModal').modal('show');
                }
            });
        });

        $(document).on('click', '.pilih-bahan-master', function() {
            setTimeout(function() {
                $('#qty_row').focus().select();
            }, 300);
        });

        $('#qty_row').on('keydown', function(e) {
            if (e.which == 13) {
                e.preventDefault();
                $('#add_row_edit').click();
                setTimeout(function() {
                    $('#btn_cari_bahan_edit').focus();
                }, 100);
            }
        });

        $('#add_row_edit').click(function() {
            let idB = $('#id_bahan').val();
            let nmB = $('#nama_bahan').val();
            let sat = $('#satuan').val();
            let hrg = parseFloat($('#harga_row').autoNumeric('get')) || 0;
            let qty = parseFloat($('#qty_row').autoNumeric('get')) || 0;
            if (!idB || qty <= 0) return;

            let total = (hrg * qty).toFixed(2);
            $('#table_detail_edit tbody').append(
                `<tr><td style="display:none">${idB}</td><td>${nmB}</td><td>${sat}</td><td class="text-end">${formatNumber(hrg, 2)}</td><td class="text-end">${formatNumber(qty, 2)}</td><td class="text-end">${formatNumber(total, 2)}</td><td class="text-center"><button class="btn btn-danger btn-sm btn-del-row">Del</button></td></tr>`
            );

            runRecalculate();
            $('#id_bahan').val('');
            $('#nama_bahan').val('');
            $('#qty_row').val('');
            $('#harga_row').val('');
            $('#satuan').val('');
        });

        $(document).on('click', '.btn-del-row', function() {
            $(this).closest('tr').remove();
            runRecalculate();
        });

        $('#btn_update_save').click(async function() {
            let items = [];
            $('#table_detail_edit tbody tr').each(function() {
                let td = $(this).find('td');
                let rowExcl = parseFloat($(td[5]).text().replace(/,/g, ''));
                let rowPpn = $('#ppn_exc').is(':checked') ? (rowExcl * PP_CONST / 100) : 0;
                items.push({
                    id_bahan: $(td[0]).text(),
                    id_permintaan: 0,
                    harga: parseFloat($(td[3]).text().replace(/,/g, '')),
                    qty: parseFloat($(td[4]).text().replace(/,/g, '')),
                    sumhargaexcl: rowExcl,
                    nominalppn: rowPpn,
                    sumhargaincl: rowExcl + rowPpn
                });
            });

            const payload = {
                edit: 2,
                no_po: "{{ $noPo }}",
                jenis: "{{ $po->jenis }}",
                tanggal: $('#tgl').val(),
                id_suplier: $('#id_suplier').val(),
                no_order: $('#no_order').val() || '-',
                untukperhatian: $('#untukperhatian').val() || '-',
                term: $('#term').val(),
                notes: $('#notes').val() || '-',
                inputlabel: $('#inputlabel').val() || '-',
                SumTotalExclude: SumTotalExclude,
                SumTotalppn: SumTotalppn,
                SumTotalInclude: SumTotalExclude + SumTotalppn,
                diskon: parseFloat($('#diskon').autoNumeric('get')) || 0,
                ongkir: parseFloat($('#ongkir').autoNumeric('get')) || 0,
                GrandTotalPembelian: GrandTotalPembelian,
                data: items
            };

            console.log("PAYLOAD SEBELUM DIKIRIM:", payload);

            if (items.length === 0) {
                Swal.fire('Error', 'Detail tidak boleh kosong', 'error');
                return;
            }

            $(this).prop('disabled', true);
            try {
                const res = await fetch('purchasing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    $('#Modaltambah').modal('hide');
                    setTimeout(() => {
                        table.ajax.reload();
                        Swal.fire('Ok', 'Berhasil update', 'success');
                    }, 500);
                } else {
                    Swal.fire('Error', 'Gagal update data (Status 400)', 'error');
                }
            } finally {
                $(this).prop('disabled', false);
            }
        });
    });
</script>
