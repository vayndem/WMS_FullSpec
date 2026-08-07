@extends('layouts.app')

@section('content')
    <style>
        .uppercase {
            text-transform: uppercase;
        }

        .modal-custom-width {
            max-width: 95%;
        }

        .detail-row-container {
            background-color: #f8fcf8;
            padding: 15px;
            border: 1px solid #a8d5a8;
            border-radius: 5px;
            margin: 10px 0;
        }

        thead.sticky-header th {
            position: sticky;
            top: 0;
            background-color: #343a40;
            color: white;
            z-index: 10;
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
        }

        .template-detail {
            display: none;
        }
    </style>

    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Daftar Pengajuan Pembelian</h4>
                            <div class="d-flex align-items-center">
                                <select id="filterBulan" class="form-control form-control-sm ms-2" style="width: 130px;">
                                    <option value="">Semua Bulan</option>
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}"
                                            {{ (string) request('bulan', date('n')) === (string) $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                                <select id="filterTahun" class="form-control form-control-sm ms-2" style="width: 85px;">
                                    @php $currentYear = date('Y'); @endphp
                                    @for ($y = $currentYear; $y >= 2024; $y--)
                                        <option value="{{ $y }}"
                                            {{ request('tahun', $currentYear) == $y ? 'selected' : '' }}>{{ $y }}
                                        </option>
                                    @endfor
                                </select>
                                <button class="btn btn-dark btn-sm ms-2" id="btnApplyFilter"><i
                                        class="fa fa-search"></i></button>
                                <button class="btn btn-primary btn-sm ms-2" id="btnAdd" onclick="bukaModalTambah()"><i
                                        class="fa fa-plus-square"></i> Buat Pengajuan</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="pengajuanTable" class="table table-bordered table-hover" style="width: 100%;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-center">No Order</th>
                                        <th class="text-center">Notes</th>
                                        @if ($user['type'] == 5)
                                            <th class="text-center">Total (Rp)</th>
                                        @endif
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width: 18%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pengajuan as $item)
                                        <tr>
                                            <td class="text-center fw-bold {{ $item->status == 1 && $user['type'] != 5 ? 'text-danger btn-keterangan-revisi' : 'text-primary' }}"
                                                style="{{ $item->status == 1 ? 'cursor: pointer;' : '' }}"
                                                data-id="{{ $item->id }}"
                                                data-keterangan="{{ $item->keterangan ?? 'Tidak ada catatan.' }}">
                                                #{{ $item->id }}
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                            <td class="uppercase">{{ $item->no_order ?? '-' }}</td>
                                            <td>{{ $item->notes ?? '-' }}</td>
                                            @if ($user['type'] == 5)
                                                <td class="text-end fw-bold">
                                                    {{ number_format($item->totalinclude, 0, ',', '.') }}</td>
                                            @endif
                                            <td class="text-center">
                                                @if ($item->status == 0)
                                                    <span class="badge bg-warning">Diajukan</span>
                                                @elseif($item->status == 1)
                                                    <span class="badge bg-info">Selesai Proses</span>
                                                @elseif($item->status == 2)
                                                    <span class="badge bg-success">Selesai</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-info btn-sm btn-detail btn-action" type="button"><i
                                                        class="fa fa-caret-square-down"></i></button>
                                                @if ($item->status != 2)
                                                    <button class="btn btn-warning btn-sm btn-edit-modal"
                                                        data-id="{{ $item->id }}"><i class="fa fa-edit"></i></button>
                                                @endif
                                                @if ($item->status >= 1 && $user['type'] == 5)
                                                    <button class="btn btn-secondary btn-sm btn-print"
                                                        data-id="{{ $item->id }}"><i class="fa fa-print"></i></button>
                                                @endif
                                                <div class="template-detail">
                                                    <div class="detail-row-container">
                                                        <h6 class="text-success border-bottom pb-2">Rincian
                                                            #{{ $item->id }}</h6>
                                                        <table class="table table-sm table-striped mt-2"
                                                            style="background-color: #fff; width: 100%;">
                                                            <thead class="bg-success text-white">
                                                                <tr>
                                                                    <th>Nama Bahan</th>
                                                                    <th class="text-center">Qty</th>
                                                                    @if ($user['type'] == 5)
                                                                        <th class="text-end">Harga</th>
                                                                        <th class="text-end">Total</th>
                                                                    @endif
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($item->details as $detail)
                                                                    <tr>
                                                                        <td>{{ $detail->bahan ? $detail->bahan->nama : $detail->id_bahan }}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            {{ number_format($detail->jumlah, 0, ',', '.') }}
                                                                        </td>
                                                                        @if ($user['type'] == 5)
                                                                            <td class="text-end">
                                                                                {{ number_format($detail->harga, 0, ',', '.') }}
                                                                            </td>
                                                                            <td class="text-end">
                                                                                {{ number_format($detail->include, 0, ',', '.') }}
                                                                            </td>
                                                                        @endif
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-custom-width" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Buat Pengajuan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_mode" value="0">
                    <input type="hidden" id="edit_id_pengajuan" value="">

                    <div class="row mb-3">
                        @if ($user['type'] == 5)
                            <div class="col-sm-3">
                                <label>Supplier</label>
                                <div class="input-group">
                                    <input type="hidden" id="id_suplier">
                                    <div class="d-flex">
                                        <button class="btn btn-outline-success" type="button" id="pilihsuplier"><i
                                                class="fa fa-search"></i></button>
                                    </div>
                                    <input type="text" class="form-control" id="nama_suplier" disabled>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <label>Tgl Diproses</label>
                                <input type="date" class="form-control" id="tanggal_diproses">
                            </div>
                        @endif
                        <div class="col-sm-2">
                            <label>No Order</label>
                            <input type="text" class="form-control uppercase" id="no_order"
                                @if ($user['type'] != 5) disabled @endif>
                        </div>
                        <div class="col-sm-2">
                            <label>Tanggal Req</label>
                            <input type="date" class="form-control" id="tanggal" value="{{ date('Y-m-d') }}">
                        </div>
                        @if ($user['type'] == 5)
                            <div class="col-sm-2">
                                <label>Term</label>
                                <input type="text" class="form-control" id="term">
                            </div>
                            <div class="col-sm-1">
                                <label>PPN</label><br>
                                <input type="radio" name="ppn_radio" id="ppn_exclude" value="exclude" checked> <span
                                    style="font-size:10px">Inc</span>
                                <input type="radio" name="ppn_radio" id="ppn_include" value="include"> <span
                                    style="font-size:10px">Exc</span>
                            </div>
                        @endif
                    </div>

                    <div class="row mb-2 bg-white py-2" style="border-bottom: 1px solid #eee;">
                        <div class="col-sm-4">
                            <div class="input-group">
                                <input type="hidden" id="id_bahan">
                                <div class="d-flex">
                                    <button class="btn btn-outline-info" id="pilihbahan" type="button"><i
                                            class="fa fa-search"></i></button>
                                </div>
                                <input type="text" class="form-control" id="nama_bahan"
                                    placeholder="Cari master atau ketik manual...">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" id="satuan" placeholder="Satuan">
                        </div>
                        @if ($user['type'] == 5)
                            <div class="col-sm-2">
                                <input type="text" class="form-control text-end" id="harga"
                                    placeholder="Harga">
                            </div>
                        @endif
                        <div class="col-sm-2">
                            <input type="text" class="form-control text-end" id="jumlah" placeholder="Qty">
                        </div>
                        <div class="col-sm-1">
                            <button class="btn btn-success" id="submitformtambah" type="button">Add</button>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-12">
                            <table id="tabeldetail" class="table table-sm table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="display:none">ID</th>
                                        <th>Nama Bahan</th>
                                        <th>Satuan</th>
                                        <th class="text-end">Qty</th>
                                        @if ($user['type'] == 5)
                                            <th class="text-end">Harga</th>
                                            <th class="text-end">Total</th>
                                        @endif
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-3 pt-3 border-top">
                        <div class="col-md-7">
                            <textarea id="notes" class="form-control" rows="3" placeholder="Notes..."></textarea>
                        </div>
                        @if ($user['type'] == 5)
                            <div class="col-md-5">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-end">Subtotal:</td>
                                        <td class="text-end" id="SumTotalExclude">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-end">PPN:</td>
                                        <td><input type="text" id="SumTotalppn" class="form-control text-end"
                                                readonly></td>
                                    </tr>
                                    <tr>
                                        <td class="text-end">Grand Total:</td>
                                        <td><input type="text" id="GrandTotalPembelian"
                                                class="form-control text-end fw-bold text-primary" readonly>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @else
                            <div style="display:none">
                                <input type="text" id="SumTotalppn" value="0">
                                <input type="text" id="GrandTotalPembelian" value="0">
                                <input type="text" id="diskon" value="0">
                                <input type="text" id="ongkir" value="0">
                            </div>
                        @endif
                    </div>
                </div>
                <div class="sticky-modal-footer">
                    <div></div>
                    <div>
                        <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Tutup</button>
                        <button class="btn btn-info btn-lg simpansemua"><i class="fa fa-save"></i> SIMPAN</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="viewmodalpencarian" style="display: none;"></div>
@endsection

@push('scripts')
    <script src="assets/js/autoNumeric.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const isPurchasing = {{ $user['type'] == 5 ? 'true' : 'false' }};

        function formatNumber(n, d = 0) {
            if (isNaN(n) || n === null) return '0';
            return parseFloat(n).toFixed(d).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function updateFooterTotals() {
            if (!isPurchasing) return;
            let totalExc = 0;
            $('#tabeldetail tbody tr').each(function() {
                let val = $(this).find('td').eq(isPurchasing ? 5 : 3).text();
                totalExc += parseFloat(val.replace(/,/g, '')) || 0;
            });
            let ppn = 0;
            if ($('#ppn_include').is(':checked')) {
                ppn = totalExc * {{ config('app.konstanta_ppn', 11) }} / 100;
            }
            let grandTotal = totalExc + ppn;
            $('#SumTotalExclude').text(formatNumber(totalExc, 2));
            $('#SumTotalppn').val(formatNumber(ppn, 2));
            $('#GrandTotalPembelian').autoNumeric('set', grandTotal);
        }

        function bukaModalTambah() {
            $('#tabeldetail tbody').empty();
            $('#edit_mode').val('0');
            $('#edit_id_pengajuan').val('');
            $('#modalTitle').text('Buat Pengajuan Baru');
            $('#no_order').val('');
            $('#notes').val('');
            $('#tanggal').val("{{ date('Y-m-d') }}");
            if (isPurchasing) {
                $('#id_suplier').val('');
                $('#nama_suplier').val('');
                $('#term').val('');
                $('#tanggal_diproses').val("{{ date('Y-m-d') }}");
                $('#SumTotalExclude').text('0');
                $('#SumTotalppn').val('0');
                $('#GrandTotalPembelian').autoNumeric('set', 0);
                $('#ppn_exclude').prop('checked', true);
            }
            kosongkanbahan();
            $('#modalTambah').modal('show');
        }

        function kosongkanbahan() {
            $('#id_bahan').val('');
            $('#nama_bahan').val('');
            $('#satuan').val('');
            $('#jumlah').autoNumeric('set', '');
            if (isPurchasing) $('#harga').autoNumeric('set', '');
        }

        $(document).ready(function() {
            const table = $('#pengajuanTable').DataTable({
                "order": [
                    [0, "desc"]
                ],
                "destroy": true
            });

            $('#jumlah').autoNumeric('init', {
                aSep: ',',
                mDec: 2
            });
            if (isPurchasing) {
                $('#harga, #GrandTotalPembelian').autoNumeric('init', {
                    aSep: ',',
                    mDec: 2
                });
            }

            $('#nama_bahan').on('input', function() {
                $('#id_bahan').val($(this).val());
            });

            $(document).on('click', '.btn-edit-modal', function() {
                const id = $(this).data('id');
                bukaModalTambah();
                $('#edit_mode').val('1');
                $('#edit_id_pengajuan').val(id);
                $('#modalTitle').text('Edit Pengajuan #' + id);
                $.ajax({
                    url: '/pengajuan/' + id,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            $('#tanggal').val(data.tanggal);
                            $('#no_order').val(data.no_order);
                            $('#notes').val(data.notes);
                            if (isPurchasing) {
                                $('#id_suplier').val(data.id_suplier);
                                $('#nama_suplier').val(data.nama_suplier_asli);
                                $('#term').val(data.term);
                                $('#tanggal_diproses').val(data.tanggal_diproses ? data
                                    .tanggal_diproses.substring(0, 10) :
                                    "{{ date('Y-m-d') }}");
                                if (parseFloat(data.totalppn) > 0) $('#ppn_include').prop(
                                    'checked', true);
                                else $('#ppn_exclude').prop('checked', true);
                            }
                            if (data.details) {
                                data.details.forEach(d => {
                                    let dispName = d.bahan ? d.bahan.nama : d.id_bahan;
                                    let subTotal = d.jumlah * d.harga;
                                    let row = `<tr>
                                        <td style="display:none">${d.id_bahan}</td>
                                        <td>${dispName}</td>
                                        <td>${d.bahan ? d.bahan.satuan : (d.satuan || '-')}</td>
                                        <td class="text-end">${formatNumber(d.jumlah, 2)}</td>
                                        ${isPurchasing ? `<td class="text-end">${formatNumber(d.harga, 2)}</td><td class="text-end">${formatNumber(subTotal, 2)}</td>` : ''}
                                        <td class="text-center"><button class="btn btn-danger btn-sm deleteRow" type="button">Del</button></td>
                                    </tr>`;
                                    $('#tabeldetail tbody').append(row);
                                });
                            }
                            updateFooterTotals();
                        }
                    }
                });
            });

            $(document).on('click', '.btn-print', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const form = $('<form>', {
                    'method': 'POST',
                    'action': "{{ route('pengajuan.cetak') }}",
                    'target': '_blank'
                });
                const token = $('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                });
                const inputId = $('<input>', {
                    'type': 'hidden',
                    'name': 'nomorpo',
                    'value': id
                });

                form.append(token, inputId).appendTo('body').submit().remove();
            });

            $('#submitformtambah').click(function() {
                const id = $('#id_bahan').val();
                const nama = $('#nama_bahan').val();
                const sat = $('#satuan').val();
                const qty = parseFloat($('#jumlah').autoNumeric('get')) || 0;
                const hrg = isPurchasing ? (parseFloat($('#harga').autoNumeric('get')) || 0) : 0;
                if (!id || qty <= 0) return AppAlert.auto('Data tidak valid');
                const sub = qty * hrg;
                const row = `<tr>
                    <td style="display:none">${id}</td>
                    <td>${nama}</td>
                    <td>${sat}</td>
                    <td class="text-end">${formatNumber(qty, 2)}</td>
                    ${isPurchasing ? `<td class="text-end">${formatNumber(hrg, 2)}</td><td class="text-end">${formatNumber(sub, 2)}</td>` : ''}
                    <td class="text-center"><button class="btn btn-danger btn-sm deleteRow" type="button">Del</button></td>
                </tr>`;
                $('#tabeldetail tbody').append(row);
                updateFooterTotals();
                kosongkanbahan();
            });

            $(document).on('click', '.deleteRow', function() {
                $(this).closest('tr').remove();
                updateFooterTotals();
            });

            $('.simpansemua').click(function() {
                const items = [];
                $('#tabeldetail tbody tr').each(function() {
                    const tds = $(this).find('td');
                    let q = parseFloat($(tds[3]).text().replace(/,/g, '')) || 0;
                    let h = isPurchasing ? (parseFloat($(tds[4]).text().replace(/,/g, '')) || 0) :
                        0;
                    let s = q * h;
                    items.push({
                        id_bahan: $(tds[0]).text(),
                        jumlah: q,
                        harga: h,
                        exclude: s,
                        ppn: 0,
                        include: s
                    });
                });
                const isEdit = $('#edit_mode').val() == '1';
                const url = isEdit ? '/pengajuan/' + $('#edit_id_pengajuan').val() : '/pengajuan';
                $.ajax({
                    url: url,
                    type: isEdit ? 'PUT' : 'POST',
                    data: JSON.stringify({
                        _token: '{{ csrf_token() }}',
                        tanggal: $('#tanggal').val(),
                        id_suplier: $('#id_suplier').val(),
                        no_order: $('#no_order').val(),
                        notes: $('#notes').val(),
                        term: isPurchasing ? $('#term').val() : null,
                        ppn: isPurchasing ? ($('#ppn_include').is(':checked') ?
                            {{ config('app.konstanta_ppn', 11) }} : 0) : 0,
                        tanggal_diproses: isPurchasing ? $('#tanggal_diproses').val() :
                            null,
                        totalexclude: isPurchasing ? parseFloat($('#SumTotalExclude').text()
                            .replace(/,/g, '')) : 0,
                        totalppn: isPurchasing ? parseFloat($('#SumTotalppn').val().replace(
                            /,/g, '')) : 0,
                        totalinclude: isPurchasing ? parseFloat($('#GrandTotalPembelian')
                            .autoNumeric('get')) : 0,
                        GrandTotalPembelian: isPurchasing ? parseFloat($(
                            '#GrandTotalPembelian').autoNumeric('get')) : 0,
                        items: items
                    }),
                    contentType: 'application/json',
                    success: function() {
                        location.reload();
                    },
                    error: function() {
                        AppAlert.auto('Gagal menyimpan data');
                    }
                });
            });

            $('#pilihbahan').click(function() {
                $.get("{{ route('showmodalpencarianbahan') }}", function(data) {
                    $('.viewmodalpencarian').html(data.data).show();
                    $('#pilihModal').modal('show');
                });
            });

            $('#pilihsuplier').click(function() {
                $.get("{{ route('showmodalpencariansupplier') }}", function(data) {
                    $('.viewmodalpencarian').html(data.data).show();
                    $('#pilihModal').modal('show');
                });
            });

            $(document).on('click', '.btn-detail', function() {
                const tr = $(this).closest('tr');
                const row = table.row(tr);
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                } else {
                    row.child(tr.find('.template-detail').html()).show();
                    tr.addClass('shown');
                }
            });

            $('input[name="ppn_radio"]').change(function() {
                updateFooterTotals();
            });

            $('#btnApplyFilter').click(function() {
                window.location.href = "?bulan=" + $('#filterBulan').val() + "&tahun=" + $('#filterTahun')
                    .val();
            });
        });
    </script>
@endpush
