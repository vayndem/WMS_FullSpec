<div class="current-modal">
    <style>
        .modal-custom-width {
            width: min(1360px, calc(100vw - 2.25rem));
            max-width: 1360px;
            margin: .875rem auto;
        }

        .modal-custom2-width {
            width: min(1080px, calc(100vw - 2.5rem));
            max-width: 1080px;
            margin: 1rem auto;
        }

        thead.sticky-header th {
            position: sticky;
            top: 0;
            background-color: #343a40;
            color: white;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
        }

        .modal-open .modal {
            overflow-x: hidden;
            overflow-y: auto;
        }

        .form-control:focus {
            background-color: #fff !important;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        .sticky-modal-footer {
            position: sticky;
            bottom: 0;
            background-color: #f8f9fa;
            z-index: 100;
            border-top: 1px solid #dee2e6;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="modal fade" id="exampleModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-custom-width" role="document">
            <div class="modal-content">
                <div class="modal-header" style="display: flex; justify-content: space-between;">
                    <div style="flex: 1;">
                        <h6 class="modal-title" id="staticBackdropLabel">{{ $title }}
                        </h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-sm-3 d-flex align-items-center">
                            <div class="input-group">
                                <input type="hidden" name="id_suplier" id="id_suplier">
                                <div class="d-flex">
                                    <button class="btn btn-outline-success" title="pilih suplier" type="button"
                                        data-bs-toggle="modal" id="pilihsuplier">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <input type="text" class="form-control uppercase" name="nama_suplier"
                                    id="nama_suplier" placeholder="Nama Suplier" disabled
                                    style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9;">
                            </div>
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">
                            <label for="no_order" class="me-2" style="width: 80px; text-align: right;">No
                                Order</label>
                            <input type="text" class="form-control uppercase" name="no_order" id="no_order"
                                placeholder="No Order"
                                style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9;">
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">
                            <label for="untukperhatian" class="me-2" style="text-align: right;">U/P</label>
                            <input type="text" class="form-control uppercase" name="untukperhatian"
                                id="untukperhatian" placeholder="UP"
                                style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9;">
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">
                            <label for="tanggal" class="me-2" style="text-align: right;">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal"
                                value="{{ \Carbon\Carbon::now()->toDateString() }}"
                                style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9;">
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">
                            <label for="term" class="me-2" style="text-align: right;">Term</label>
                            <input type="text" class="form-control" id="term" name="term"
                                style="width: 150px; background-color: #e8f5e9; color: #0d6efd; border: 1px solid #a5d6a7; padding: 5px;">
                        </div>
                        <div class="col-sm-1 d-flex flex-column justify-content-center"
                            style="border: 2px solid #ff6347; padding: 5px; border-radius: 8px;">
                            <label for="ppn"
                                style="font-size: 12px; color: #ff6347; font-weight: bold; margin-bottom:0;">PPN
                                {{ config('app.konstanta_ppn') }}%</label>
                            <div class="d-flex justify-content-center">
                                <div class="form-check form-check-inline me-1">
                                    <input class="form-check-input" type="radio" name="ppn" id="ppn_include"
                                        value="include">
                                    <label class="form-check-label" for="ppn_include"
                                        style="font-size: 12px;">Inc</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ppn" id="ppn_exclude"
                                        value="exclude" checked>
                                    <label class="form-check-label" for="ppn_exclude"
                                        style="font-size: 12px;">Exc</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2 bg-white py-2" style="border-bottom: 1px solid #eee;">
                        <div class="col-sm-3 d-flex align-items-center">
                            <div class="input-group">
                                <input type="hidden" name="id_bahan" id="id_bahan">
                                <input type="hidden" name="id_permintaan" id="id_permintaan">
                                <div class="d-flex">
                                    <button class="btn btn-outline-info" title="cari bahan" type="button"
                                        data-bs-toggle="modal" id="pilihbahan">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <div class="d-flex">
                                    <button class="btn btn-outline-success" title="cari permintaan" type="button"
                                        data-bs-toggle="modal" id="tampilkanpermintaan">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                                <input type="text" class="form-control uppercase" name="nama_bahan"
                                    id="nama_bahan" placeholder="Bahan" disabled
                                    style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9;">
                            </div>
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">
                            <label class="me-2 text-end" style="width:60px">Satuan</label>
                            <input type="text" class="form-control" id="satuan" disabled
                                style="background-color: #e3f2fd;">
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">
                            <label class="me-2 text-end" style="width:60px">Harga</label>
                            <input type="text" class="form-control text-end" id="harga"
                                style="background-color: #e3f2fd;">
                        </div>
                        <div class="col-sm-2 d-flex align-items-center">
                            <label class="me-2 text-end" style="width:60px">Qty</label>
                            <input type="text" class="form-control text-end" id="jumlah"
                                style="background-color: #e3f2fd;">
                        </div>
                        <div class="col-sm-1">
                            <button class="btn btn-success" id="submitformtambah">Submit</button>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-12">
                            <table id="tabeldetail"
                                class="table table-sm table-striped table-hover custom-table-border mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="display: none">No</th>
                                        <th style="display: none">Id_bahan</th>
                                        <th style="display: none">Id_permintaan</th>
                                        <th colspan="2">Nama Bahan</th>
                                        <th>Satuan</th>
                                        <th style="text-align: right">Harga</th>
                                        <th style="text-align: right">Qty</th>
                                        <th style="text-align: right">Tot(Excl)</th>
                                        <th style="display: none">PPN</th>
                                        <th style="display: none">Tot (Inc)</th>
                                        <th style="text-align: center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3 pt-3 border-top">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label for="notes" class="fw-bold">Notes:</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-end align-middle">Total (Excl):</td>
                                    <td>
                                        <div class="fw-bold text-end" id="SumTotalExclude"
                                            style="padding: .375rem .75rem;">0</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle">Diskon:</td>
                                    <td>
                                        <input type="text" id="diskon" name="diskon" value="0"
                                            class="form-control text-end" placeholder="0">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle">PPN:</td>
                                    <td>
                                        <input type="text" id="SumTotalppn" name="SumTotalppn" value="0"
                                            class="form-control text-end" readonly style="background-color: #eee;">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle">
                                        <input type="text" id="inputlabel" name="inputlabel"
                                            value="Freight Handling"
                                            class="form-control form-control-sm text-end border-0"
                                            style="background: transparent; font-weight:bold;">
                                    </td>
                                    <td>
                                        <input type="text" id="ongkir" name="ongkir" value="0"
                                            class="form-control text-end" placeholder="0">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="sticky-modal-footer">
                    <div class="d-flex align-items-center">
                        <span class="me-2 fw-bold" style="font-size: 1.2rem;">GRAND TOTAL:</span>
                        <input type="text" id="GrandTotalPembelian" name="GrandTotalPembelian" value="0"
                            class="form-control form-control-lg text-end fw-bold text-primary" readonly
                            style="width: 250px; background-color: transparent; border: none; font-size: 1.5rem;">
                    </div>

                    <div>
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-info btn-lg simpansemua">
                            <i class="fa fa-save me-1"></i> SIMPAN TRANSAKSI
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="viewmodalpencarian" style="display: none;"></div>
    <div class="modal fade" id="modalPermintaan" tabindex="-1" role="dialog"
        aria-labelledby="modalPermintaanLabel" aria-hidden="true">
        <div class="modal-dialog modal-custom2-width" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalPermintaanLabel">Permintaan</h5>
                    <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Bahan</th>
                                <th>Satuan</th>
                                <th>Jumlah Order</th>
                                <th>Realisasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="permintaanTableBody">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function kirimDataDariTabel() {
            var dataFromTable2 = [];

            $('#tabeldetail tbody tr').each(function(index, element) {
                var rowData = $(this).find('td');
                var baris_id_bahan = $(rowData[1]).text();
                var baris_id_permintaan = $(rowData[2]).text();
                var baris_harga = $(rowData[5]).text();
                var baris_qty = $(rowData[6]).text();

                var baris_sumhargaexcl = $(rowData[7]).text();
                var baris_nominalppn = $(rowData[8]).text();
                var baris_sumhargaincl = $(rowData[9]).text();

                dataFromTable2.push({
                    id_bahan: baris_id_bahan.trim(),
                    id_permintaan: baris_id_permintaan.trim(),
                    harga: parseFloat(baris_harga.replace(/,/g, '')) || 0,
                    qty: parseFloat(baris_qty.replace(/,/g, '')) || 0,
                    sumhargaexcl: parseFloat(baris_sumhargaexcl.replace(/,/g, '')) || 0,
                    nominalppn: parseFloat(baris_nominalppn.replace(/,/g, '')) || 0,
                    sumhargaincl: parseFloat(baris_sumhargaincl.replace(/,/g, '')) || 0
                });
            });

            var tanggal = $("#tanggal").val();
            var id_suplier = $("#id_suplier").val();
            if ($("#no_order").val() == '') {
                var no_order = '-';
            } else {
                var no_order = $("#no_order").val();
            }
            var untukperhatian = $("#untukperhatian").val() == '' ? '-' : $("#untukperhatian").val();
            var term = $('#term').val();
            var inputlabel = $("#inputlabel").val().toLowerCase().replace(/\s+/g, '') === 'freighthandling' ? '-' : $(
                "#inputlabel").val();
            $('.simpansemua').prop('disabled', true);

            try {
                const response = await fetch('purchasing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                    },
                    body: JSON.stringify({
                        edit: 0,
                        tanggal: tanggal,
                        id_suplier: id_suplier,
                        no_order: no_order,
                        untukperhatian: untukperhatian,
                        term: term,
                        jenis: {{ $jenis }},
                        notes: $('#notes').val() == '' ? '-' : $('#notes').val(),
                        SumTotalExclude: SumTotalExclude,
                        SumTotalppn: SumTotalppn,
                        SumTotalInclude: SumTotalInclude,
                        diskon: diskon,
                        ongkir: ongkir,
                        inputlabel: inputlabel,
                        GrandTotalPembelian: GrandTotalPembelian,
                        data: dataFromTable2 
                    })
                });

                if (!response.ok) {
                    const errorResponse = await response.text(); 
                    console.error('Error response:', errorResponse);
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                console.log('Data berhasil dikirim:', result);
                $('#exampleModal').modal('hide');
                table.ajax.reload();
                resetform();
            } catch (error) {
                console.error('Ada masalah dengan pengiriman data:', error);
            } finally {
                $('.simpansemua').prop('disabled', false);
            }
        }

        function resetform() {
            counter = 1;
            totalHarga = 0;
            totalHargadanppn = 0;
            GrandTotalPembelian = 0;

            SumTotalExclude = 0;
            SumTotalppn = 0;
            SumTotalInclude = 0;
            diskon = 0;
            ongkir = 0;
            GrandTotalPembelian = 0;
            $('#tabeldetail tbody').empty(); // Kosongkan tabel visual juga
        }

        function updateFooterTotals() {
            SumTotalExclude = 0;
            SumTotalppn = 0;
            SumTotalInclude = 0;
            diskon = parseFloat($('#diskon').autoNumeric('get')) || 0;
            ongkir = parseFloat($('#ongkir').autoNumeric('get')) || 0;

            $('#tabeldetail tbody tr').each(function() {
                SumTotalExclude += parseFloat($(this).find('td:eq(7)').text().replace(/,/g, '')) || 0;
            });

            SumTotalppn = (SumTotalExclude - diskon) * nilaippn / 100;
            SumTotalInclude = SumTotalExclude + SumTotalppn;

            $('#SumTotalExclude').text(formatNumber(SumTotalExclude, 2));
            $('#SumTotalppn').val(formatNumber(SumTotalppn, 2));

            GrandTotalPembelian = SumTotalExclude + SumTotalppn - diskon + ongkir;
            $('#GrandTotalPembelian').autoNumeric('set', GrandTotalPembelian);
        }

        function tampilkanpermintaan() {
            $.ajax({
                url: '/purchasing',
                type: 'GET',
                data: {
                    jenis: "{{ $jenis }}"
                },
                success: function(response) {
                    var tableBody = $('#permintaanTableBody');
                    tableBody.empty();

                    response.data.forEach(function(item, index) {
                        var row = `<tr id="row-${item.id}">
                        <td>${index + 1}</td>
                        <td>${item.bahan}</td>
                        <td>${item.satuan}</td>
                        <td class="text-end">${item.jumlah_order}</td>
                        <td class="text-end">${item.realisasi}</td>
                        <td>
                            <button class="btn btn-primary btn-sm pilih-permintaan"
                                data-id="${item.id}"
                                data-id_bahan="${item.id_bahan}"
                                data-nama="${item.bahan}"
                                data-satuan="${item.satuan}"
                                data-harga="${item.harga}">
                                Pilih
                            </button>
                        </td>
                    </tr>`;
                        tableBody.append(row);
                    });
                },
                error: function() {
                    AppAlert.auto('Gagal mengambil data.');
                }
            });
        }

        function kosongkanheader() {
            $('#id_suplier').val('');
            $('#nama_suplier').val('');
            $('#no_order').val('');
            $('#untukperhatian').val('');
            $('#inputlabel').val('Freight Handling');
        }

        function kosongkanbahan() {
            $('#nama_bahan').val('');
            $('#satuan').val('');
            $('#id_bahan').val('0');
            $('#id_permintaan').val('0');
            $('#harga').val('0');
            $('#jumlah').val('');
        }

        $(document).ready(function() {
            tampilkanpermintaan();

            // Init AutoNumeric
            $('#harga, #jumlah, #diskon, #ongkir, #GrandTotalPembelian').autoNumeric('init', {
                aSep: ',',
                mDec: 2
            });

            // UX: ENTER di kolom Qty -> Submit -> Fokus balik ke Tombol Cari
            $('#jumlah').on('keydown', function(e) {
                if (e.which == 13) { // Tombol Enter
                    e.preventDefault();
                    // Validasi sederhana
                    let qty = parseFloat($(this).autoNumeric('get')) || 0;
                    if (qty <= 0) return;

                    $('#submitformtambah').click(); // Simpan

                    setTimeout(function() {
                        $('#pilihbahan').focus();
                    }, 100);
                }
            });

            $('#pilihsuplier').on('click', function() {
                kosongkanheader();
                $.ajax({
                    type: "get",
                    url: "{{ route('showmodalpencariansupplier') }}",
                    dataType: "json",
                    beforeSend: function() {
                        $('#pilihsuplier').prop('disabled', true).html(
                            '<i class="fa fa-spin fa-spinner"></i>');
                    },
                    complete: function() {
                        $('#pilihsuplier').prop('disabled', false).html(
                            '<i class="fa fa-search"></i>');
                    },
                    success: function(response) {
                        if (response.data) {
                            $('.viewmodalpencarian').html(response.data).show();
                            $('#pilihModal').modal('show');
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        AppAlert.auto(xhr.status + '\n' + thrownError);
                    }
                });
            });

            $('#ongkir, #diskon').on('keyup', function() {
                updateFooterTotals();
            });

            $('#pilihbahan').on('click', function() {
                kosongkanbahan();
                $.ajax({
                    type: "get",
                    url: "{{ route('showmodalpencarianbahan') }}",
                    data: {
                        jenis: "{{ $jenis }}"
                    },
                    dataType: "json",
                    beforeSend: function() {
                        $('#pilihbahan').prop('disabled', true).html(
                            '<i class="fa fa-spin fa-spinner"></i>');
                    },
                    complete: function() {
                        $('#pilihbahan').prop('disabled', false).html(
                            '<i class="fa fa-search"></i>');
                    },
                    success: function(response) {
                        if (response.data) {
                            $('.viewmodalpencarian').html(response.data).show();
                            $('#pilihModal').modal('show');
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        AppAlert.auto(xhr.status + '\n' + thrownError);
                    }
                });
            });

            // UX: Handler saat memilih bahan dari permintaan
            $(document).on('click', '.pilih-permintaan', function() {
                let selectedRow = $(this).closest('tr');
                let id_permintaan = $(this).data('id');
                let id_bahan = $(this).data('id_bahan');
                let nama_bahan = $(this).data('nama');
                let satuan = $(this).data('satuan');
                let harga = $(this).data('harga');

                $('#id_permintaan').val(id_permintaan);
                $('#id_bahan').val(id_bahan);
                $('#nama_bahan').val(nama_bahan);
                $('#satuan').val(satuan);
                $('#harga').val(harga);

                $('#submitformtambah').data('selectedRow', selectedRow);
                $('#modalPermintaan').modal('hide');

                // UX Feature: Otomatis fokus ke input jumlah setelah pilih bahan
                setTimeout(function() {
                    $('#jumlah').focus().select();
                }, 300);
            });

            // UX: Handler saat memilih bahan dari Master Bahan (Asumsi class tombolnya .pilih-bahan-master di modal lain)
            // Tambahkan logic serupa jika Anda punya class berbeda di modal pencarian master
            $(document).on('click', '.pilih-bahan-master', function() {
                setTimeout(function() {
                    $('#jumlah').focus().select();
                }, 300);
            });

            $('#submitformtambah').click(function(e) {
                e.preventDefault();
                id_bahan = $('#id_bahan').val();
                name = $('#nama_bahan').val();
                satuan = $('#satuan').val();
                id_permintaan = $('#id_permintaan').val();

                if (id_bahan == '0' || id_bahan == '') {
                    // Ganti alert browser dengan SweetAlert agar UX lebih halus
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Silakan pilih bahan terlebih dahulu',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    qty = parseFloat($('#jumlah').autoNumeric('get')) || 0;
                    harga = parseFloat($('#harga').autoNumeric('get')) || 0;
                    hargakalijumlah = (qty * harga).toFixed(2);

                    if ($('#ppn_include').is(':checked')) {
                        nilaippn = 0;
                        nominalppn = 0
                        totalHargadanppn = hargakalijumlah;
                    } else if ($('#ppn_exclude').is(':checked')) {
                        nilaippn = {{ config('app.konstanta_ppn') }};
                        nominalppn = parseFloat(hargakalijumlah) * parseFloat(nilaippn) / 100;
                        totalHargadanppn = parseFloat(hargakalijumlah) + parseFloat(nominalppn);
                    }

                    let newRow = $(`
                    <tr>
                        <td style="display: none">${counter}</td>
                        <td style="display: none">${id_bahan}</td>
                        <td style="display: none">${id_permintaan}</td>
                        <td colspan="2">${name}</td>
                        <td>${satuan}</td>
                        <td style="text-align: right;">${formatNumber(harga,2)}</td>
                        <td style="text-align: right;">${formatNumber(qty, 2)}</td>
                        <td style="text-align: right;">${formatNumber(hargakalijumlah, 2)}</td>
                        <td style="display: none">${formatNumber(nominalppn, 2)}</td>
                        <td style="display: none">${formatNumber(totalHargadanppn, 2)}</td>
                        <td style="text-align: center;">
                            <button class="btn btn-danger btn-sm deleteRow">Delete</button>
                        </td>
                    </tr>
                `);

                    $('#tabeldetail tbody').append(newRow);

                    counter++;
                    updateFooterTotals();
                    kosongkanbahan();

                    let selectedRow = $(this).data('selectedRow');
                    if (selectedRow) {
                        selectedRow.hide();
                        newRow.data('hiddenRow', selectedRow);
                    }
                }
            });

            $('.simpansemua').click(function(e) {
                e.preventDefault();
                if ($('#id_suplier').val() == '') {
                    Swal.fire({
                        icon: "error",
                        title: "Maaf",
                        text: "Supplier Belum Diisi",
                    });
                } else if (GrandTotalPembelian < 1) {
                    Swal.fire({
                        icon: "error",
                        title: "Maaf",
                        text: "Belum ada input bahan",
                    });
                } else {
                    kirimDataDariTabel();
                }
            });

            $(document).on('click', '.deleteRow', function() {
                let row = $(this).closest('tr');
                let hiddenRow = row.data('hiddenRow');

                if (hiddenRow) {
                    hiddenRow.show();
                }

                row.remove();
                updateFooterTotals();
            });

            $('#tampilkanpermintaan').on('click', function() {
                $('#modalPermintaan').modal('show');
            });
        });
    </script>
