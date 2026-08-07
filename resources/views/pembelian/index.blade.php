@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">Daftar Transaksi Pembelian</h4>
                            <p class="mb-0 text-muted">Kelola seluruh riwayat dan pengajuan Purchase Order (PO)</p>
                        </div>
                        @can('create', App\Models\Pembelian::class)
                            <button type="button" class="btn btn-primary add-list shadow-sm" id="btnTambahPembelian">
                                <i class="fa-solid fa-plus me-2"></i>Tambah Pembelian
                            </button>
                        @endcan
                    </div>
                </div>

                @if (session('success'))
                    <div class="col-lg-12">
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                @endif

                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="filter_bulan" class="fw-bold">Filter Bulan</label>
                                    <select id="filter_bulan" class="form-control">
                                        <option value="0">Semua Bulan</option>
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ sprintf('%02d', $m) }}"
                                                {{ date('m') == $m ? 'selected' : '' }}>
                                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filter_tahun" class="fw-bold">Filter Tahun</label>
                                    <input type="number" id="filter_tahun" class="form-control"
                                        value="{{ date('Y') }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button id="btn_filter" class="btn btn-info w-100 shadow-sm">
                                        <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="table-pembelian" width="100%"
                                    cellspacing="0" data-report-url="{{ route('pembelian.report.pdf') }}"
                                    data-filter-columns="1:no_po,2:tanggal,3:nama,4:grand_total,5:status">
                                    <thead class="bg-light text-uppercase font-size-12">
                                        <tr>
                                            <th width="5%" class="text-center py-3"></th>
                                            <th width="18%" class="py-3 ps-2">No PO</th>
                                            <th width="13%" class="py-3">Tanggal</th>
                                            <th class="py-3">Supplier</th>
                                            <th width="18%" class="py-3 text-end">Grand Total</th>
                                            <th width="10%" class="text-center py-3">Status</th>
                                            <th width="18%" class="text-center py-3 pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahPembelian" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold text-white" id="modalPembelianTitle"><i
                            class="fa-solid fa-cart-shopping me-2"></i>Buat Transaksi Pembelian Baru</h5>
                    <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="formPembelian">
                    @csrf
                    <input type="hidden" name="_method" id="form_method" value="POST">
                    <input type="hidden" id="edit_no_po" value="">

                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Nomor PO</label>
                                <input type="text" name="no_po" id="input_no_po" class="form-control bg-white"
                                    value="{{ $documentNumber }}" readonly>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" id="input_tanggal" class="form-control"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Supplier</label>
                                <div class="input-group">
                                    <input type="hidden" name="supplier_id" id="input_supplier_id" required>
                                    <input type="text" id="input_supplier_nama" class="form-control bg-white"
                                        placeholder="Pilih Supplier..." readonly required>
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-info" id="btnBukaModalSupplier">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">No. SO / Order</label>
                                <input type="text" name="no_order" id="input_no_order" class="form-control"
                                    value="-">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Untuk Perhatian (ATTN)</label>
                                <input type="text" name="untuk_perhatian" id="input_untuk_perhatian"
                                    class="form-control" value="-">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Term Pembayaran</label>
                                <input type="text" name="term" id="input_term" class="form-control"
                                    value="-">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Pilihan PPN (11%)</label>
                                <select name="is_ppn" id="input_is_ppn" class="form-control">
                                    <option value="0">Non-PPN</option>
                                    <option value="1">Gunakan PPN (11%)</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Diskon (Rp)</label>
                                <input type="number" step="any" name="diskon" id="input_diskon"
                                    class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Ongkir / Handling (Rp)</label>
                                <input type="number" step="any" name="ongkir" id="input_ongkir"
                                    class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-12 mb-3 mb-0">
                                <label class="fw-bold">Catatan / Notes</label>
                                <textarea name="notes" id="input_notes" class="form-control" rows="2">-</textarea>
                            </div>
                        </div>

                        <div class="collapse mb-4" id="modalCariSupplier">
                            <div class="create-section supplier-picker-panel">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <i class="fa-solid fa-truck-field text-primary me-2"></i>
                                            Pilih Supplier
                                        </h6>
                                        <small class="text-muted">Cari berdasarkan nama, telepon, atau alamat
                                            supplier.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="collapse"
                                        data-bs-target="#modalCariSupplier">
                                        <i class="fa-solid fa-xmark me-1"></i>Tutup
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered align-middle mb-0"
                                        id="table-modal-supplier" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Nama Supplier</th>
                                                <th>Telepon / HP</th>
                                                <th>Alamat</th>
                                                <th width="90" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Gudang Tujuan <span class="text-danger">*</span></label>
                                <select name="gudang_id" id="input_gudang_id" class="form-select" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->kode }} - {{ $gudang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="collapse mb-4" id="modalCariPermintaan">
                            <div class="create-section request-picker-panel">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>
                                            Pilih Item Permintaan (Request ACC)
                                        </h6>
                                        <small class="text-muted">Cari berdasarkan kode request atau nama barang, lalu
                                            pilih item yang akan dibeli.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="collapse"
                                        data-bs-target="#modalCariPermintaan">
                                        <i class="fa-solid fa-xmark me-1"></i>Tutup
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered align-middle mb-0"
                                        id="table-modal-request" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Kode Request</th>
                                                <th>Nama Barang / Bahan</th>
                                                <th>Target ACC</th>
                                                <th>Realisasi</th>
                                                <th>Referensi Harga</th>
                                                <th>Sisa Kuota</th>
                                                <th width="90" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-8 border-right">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list me-2"></i>Detail Item
                                        Pembelian</h6>
                                    <button type="button" class="btn btn-info btn-sm shadow-sm"
                                        id="btnBukaModalRequest">
                                        <i class="fa-solid fa-list-check me-1"></i> Cari dari Permintaan (Request)
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="table-items">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Item / Bahan</th>
                                                <th width="140">Jumlah Beli</th>
                                                <th width="180">Harga Satuan (Rp)</th>
                                                <th width="180" class="text-end">Subtotal (Rp)</th>
                                                <th width="60" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-items">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card bg-light border-0 p-3 shadow-sm h-100">
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i
                                            class="fa-solid fa-calculator me-2"></i>Ringkasan Totals</h6>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Total Exclude (DPP):</span>
                                        <span class="fw-bold text-dark" id="preview_exclude">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">PPN (11%):</span>
                                        <span class="fw-bold text-info" id="preview_ppn">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Total Include PPN:</span>
                                        <span class="fw-bold text-dark" id="preview_include">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Diskon:</span>
                                        <span class="fw-bold text-danger" id="preview_diskon">- Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Ongkir / Handling:</span>
                                        <span class="fw-bold text-success" id="preview_ongkir">+ Rp 0</span>
                                    </div>

                                    <hr class="my-3">

                                    <div
                                        class="p-3 bg-primary text-white rounded d-flex justify-content-between align-items-center">
                                        <span class="fw-bold font-size-14">Grand Total:</span>
                                        <span class="fw-bold font-size-18 text-white" id="preview_grand_total">Rp
                                            0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSimpanPembelian"><i
                                class="fa-solid fa-floppy-disk me-2"></i>Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 2
                }).format(number);
            }

            function hitungTotals() {
                let totalExclude = 0;

                $('#tbody-items tr').each(function() {
                    let qty = parseFloat($(this).find('.input-jumlah').val()) || 0;
                    let harga = parseFloat($(this).find('.input-harga').val()) || 0;
                    let subtotal = qty * harga;

                    $(this).find('.subtotal-item').text(formatRupiah(subtotal));
                    totalExclude += subtotal;
                });

                let isPpn = $('#input_is_ppn').val() == '1';
                let totalPpn = isPpn ? (totalExclude * 0.11) : 0;
                let totalInclude = totalExclude + totalPpn;

                let diskon = parseFloat($('#input_diskon').val()) || 0;
                let ongkir = parseFloat($('#input_ongkir').val()) || 0;

                let grandTotal = (totalInclude - diskon) + ongkir;
                if (grandTotal < 0) grandTotal = 0;

                $('#preview_exclude').text(formatRupiah(totalExclude));
                $('#preview_ppn').text(formatRupiah(totalPpn));
                $('#preview_include').text(formatRupiah(totalInclude));
                $('#preview_diskon').text('- ' + formatRupiah(diskon));
                $('#preview_ongkir').text('+ ' + formatRupiah(ongkir));
                $('#preview_grand_total').text(formatRupiah(grandTotal));
            }

            function formatChildRow(d) {
                let detailsHtml = '';
                if (d.details && d.details.length > 0) {
                    d.details.forEach(function(item) {
                        let namaBahan = item.bahan ? item.bahan.nama : 'Bahan #' + item.bahan_id;
                        let subtotal = item.include || (item.jumlah * item.harga);
                        detailsHtml += `
                            <tr>
                                <td>${namaBahan}</td>
                                <td class="text-center fw-bold">${item.jumlah}</td>
                                <td class="text-end">${formatRupiah(item.harga)}</td>
                                <td class="text-end fw-bold text-primary">${formatRupiah(subtotal)}</td>
                            </tr>
                        `;
                    });
                } else {
                    detailsHtml = '<tr><td colspan="4" class="text-center text-muted">Tidak ada detail item.</td></tr>';
                }

                return `
                    <div class="p-3 bg-light rounded border m-2">
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-boxes-packing me-2"></i>Detail Item yang Dibeli (No PO: ${d.no_po})</h6>
                        <table class="table table-sm table-bordered bg-white mb-0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Nama Barang / Bahan</th>
                                    <th width="15%" class="text-center">Jumlah Beli</th>
                                    <th width="20%" class="text-end">Harga Satuan</th>
                                    <th width="20%" class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${detailsHtml}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            $(document).ready(function() {
                let itemIndex = 0;

                let table = $('#table-pembelian').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('pembelian.index') }}",
                        data: function(d) {
                            d.bulan = $('#filter_bulan').val();
                            d.tahun = $('#filter_tahun').val();
                        }
                    },
                    columns: [{
                            className: 'dt-control text-center align-middle',
                            orderable: false,
                            data: null,
                            defaultContent: '<button type="button" class="btn btn-sm btn-outline-primary btn-expand"><i class="fa-solid fa-chevron-right"></i></button>'
                        },
                        {
                            data: 'no_po',
                            name: 'no_po',
                            className: 'align-middle ps-2 fw-bold text-primary'
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
                            className: 'align-middle'
                        },
                        {
                            data: 'nama',
                            name: 'supplier.nama',
                            className: 'align-middle'
                        },
                        {
                            data: 'grand_total',
                            name: 'grand_total',
                            className: 'align-middle text-end fw-bold',
                            render: function(data) {
                                return formatRupiah(data);
                            }
                        },
                        {
                            data: 'status',
                            name: 'status',
                            className: 'text-center align-middle',
                            render: function(data) {
                                return data == 2 ?
                                    '<span class="badge bg-danger px-3 py-1">Closed</span>' :
                                    '<span class="badge bg-success px-3 py-1">Open</span>';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle pe-4',
                            render: function(data) {
                                let btnCetak = `
                                    <a href="{{ url('pembelian') }}/${data.no_po}/cetak" target="_blank" class="btn btn-sm btn-info shadow-sm" title="Cetak PO">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                `;

                                if (data.kunci != 0) {
                                    return `${btnCetak} <span class="badge bg-secondary ms-1"><i class="fa-solid fa-lock"></i> Locked</span>`;
                                }

                                return `
                                    ${btnCetak}
                                    <button type="button" class="btn btn-sm btn-warning shadow-sm btn-edit-po ms-1" data-nopo="${data.no_po}" title="Edit PO">
                                        <i class="fa-solid fa-pen-to-square text-white"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger shadow-sm btn-delete-po ms-1" data-nopo="${data.no_po}" title="Hapus PO">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                `;
                            }
                        }
                    ]
                });

                $('#table-pembelian tbody').on('click', 'button.btn-expand', function() {
                    let tr = $(this).closest('tr');
                    let row = table.row(tr);
                    let icon = $(this).find('i');

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                        icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    } else {
                        row.child(formatChildRow(row.data())).show();
                        tr.addClass('shown');
                        icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    }
                });

                $('#btn_filter').click(function() {
                    table.draw();
                });

                $('#btnTambahPembelian').click(function() {
                    $('#formPembelian')[0].reset();
                    $('#form_method').val('POST');
                    $('#edit_no_po').val('');
                    $('#input_supplier_id').val('');
                    $('#input_supplier_nama').val('');
                    $('#tbody-items').empty();
                    $('#modalPembelianTitle').html(
                        '<i class="fa-solid fa-cart-shopping me-2"></i>Buat Transaksi Pembelian Baru');
                    $('#btnSimpanPembelian').html(
                        '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Transaksi');
                    itemIndex = 0;
                    hitungTotals();
                    $('#modalTambahPembelian').modal('show');
                });

                if (new URLSearchParams(window.location.search).get('create') === '1') {
                    setTimeout(() => $('#btnTambahPembelian').trigger('click'), 100);
                }

                let tableSupplier = $('#table-modal-supplier').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('supplier.dataTable') }}",
                    columns: [{
                            data: 'nama',
                            name: 'nama'
                        },
                        {
                            data: 'telepon',
                            name: 'telepon',
                            defaultContent: '-'
                        },
                        {
                            data: 'alamat',
                            name: 'alamat',
                            defaultContent: '-'
                        },
                        {
                            data: 'aksi',
                            name: 'aksi',
                            orderable: false,
                            searchable: false,
                            className: 'text-center'
                        }
                    ]
                });

                $('#btnBukaModalSupplier').click(function() {
                    bootstrap.Collapse.getOrCreateInstance('#modalCariSupplier', {
                        toggle: false
                    }).show();
                    tableSupplier.ajax.reload(function() {
                        tableSupplier.columns.adjust();
                    }, false);
                    document.querySelector('#modalCariSupplier')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                });

                $(document).on('click', '.btn-pilih-supplier', function() {
                    let id = $(this).data('id');
                    let nama = $(this).data('nama');

                    $('#input_supplier_id').val(id);
                    $('#input_supplier_nama').val(nama);
                    bootstrap.Collapse.getOrCreateInstance('#modalCariSupplier', {
                        toggle: false
                    }).hide();
                });

                let tableRequest = $('#table-modal-request').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('requestdetail.index') }}",
                    columns: [{
                            data: 'no_request',
                            name: 'request.no_request',
                            className: 'fw-bold text-primary',
                            orderable: false
                        },
                        {
                            data: 'bahan',
                            name: 'nama_barang'
                        },
                        {
                            data: 'jumlah_order',
                            name: 'jumlah_acc'
                        },
                        {
                            data: 'realisasi',
                            name: 'realisasi'
                        },
                        {
                            data: 'harga_referensi',
                            name: 'harga_referensi',
                            orderable: false,
                            searchable: false,
                            render: data =>
                                `${formatRupiah(data)}<small class="d-block text-muted">Rata-rata 5 LPB terakhir</small>`
                        },
                        {
                            data: null,
                            render: function(data) {
                                return data.jumlah_order - data.realisasi;
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center',
                            render: function(data) {
                                let sisa = data.jumlah_order - data.realisasi;
                                return `<button type="button" class="btn btn-sm btn-success btn-pilih-req" 
                                    data-id="${data.id_permintaan}" 
                                    data-bahan="${data.id_bahan}" 
                                    data-nama="${data.bahan}" 
                                    data-harga="${data.harga_referensi || 0}"
                                    data-max="${sisa}">
                                    <i class="fa-solid fa-check me-1"></i> Pilih
                                </button>`;
                            }
                        }
                    ]
                });

                $('#btnBukaModalRequest').click(function() {
                    bootstrap.Collapse.getOrCreateInstance('#modalCariPermintaan', {
                        toggle: false
                    }).show();
                    tableRequest.ajax.reload(function() {
                        tableRequest.columns.adjust();
                    }, false);
                    document.querySelector('#modalCariPermintaan')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                });

                $(document).on('click', '.btn-pilih-req', function() {
                    let reqId = $(this).data('id');
                    let bahanId = $(this).data('bahan');
                    let nama = $(this).data('nama');
                    let harga = $(this).data('harga') || 0;
                    let max = $(this).data('max');

                    let rowHtml = `
                        <tr id="row-item-${itemIndex}">
                            <td class="align-middle">
                                <input type="hidden" name="details[${itemIndex}][request_detail_id]" value="${reqId}">
                                <input type="hidden" name="details[${itemIndex}][bahan_id]" value="${bahanId}">
                                <strong class="text-dark">${nama}</strong>
                                <br><small class="text-muted">Maksimal Beli: ${max} unit</small>
                            </td>
                            <td class="align-middle">
                                <input type="number" step="any" name="details[${itemIndex}][jumlah]" class="form-control input-jumlah" value="${max}" max="${max}" min="0.01" required>
                            </td>
                            <td class="align-middle">
                                <input type="number" step="any" name="details[${itemIndex}][harga]" class="form-control input-harga" value="${harga}" min="0" required>
                            </td>
                            <td class="align-middle text-end fw-bold subtotal-item">
                                ${formatRupiah(max * harga)}
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-danger btn-sm btn-hapus-row" data-bs-target="#row-item-${itemIndex}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    $('#tbody-items').append(rowHtml);
                    itemIndex++;
                    hitungTotals();
                });

                $(document).on('click', '.btn-hapus-row', function() {
                    let target = $(this).attr('data-bs-target');
                    $(target).remove();
                    hitungTotals();
                });

                $(document).on('input change',
                    '.input-jumlah, .input-harga, #input_is_ppn, #input_diskon, #input_ongkir',
                    function() {
                        hitungTotals();
                    });

                $(document).on('click', '.btn-edit-po', function() {
                    let noPo = $(this).data('nopo');

                    $.ajax({
                        url: "{{ url('pembelian') }}/" + noPo,
                        type: "GET",
                        success: function(res) {
                            let data = res.data;
                            if (data.kunci != 0) {
                                AppAlert.auto('Data tidak dapat diubah karena sudah dikunci.');
                                return;
                            }

                            $('#form_method').val('PUT');
                            $('#edit_no_po').val(data.no_po);
                            $('#input_no_po').val(data.no_po);
                            $('#input_tanggal').val(data.tanggal);
                            $('#input_supplier_id').val(data.supplier_id);
                            $('#input_gudang_id').val(data.gudang_id);
                            $('#input_supplier_nama').val(data.supplier ? data.supplier.nama : '-');
                            $('#input_no_order').val(data.no_order);
                            $('#input_untuk_perhatian').val(data.untuk_perhatian);
                            $('#input_term').val(data.term);
                            $('#input_is_ppn').val(data.ppn > 0 ? '1' : '0');
                            $('#input_diskon').val(data.diskon);
                            $('#input_ongkir').val(data.ongkir);
                            $('#input_notes').val(data.notes);

                            $('#tbody-items').empty();
                            itemIndex = 0;

                            if (data.details && data.details.length > 0) {
                                data.details.forEach(function(detail) {
                                    let namaBahan = detail.bahan ? detail.bahan.nama :
                                        'Bahan #' + detail.bahan_id;
                                    let reqId = detail.request_detail_id || '';
                                    let maxAllowed = detail.jumlah;

                                    if (detail.request_detail) {
                                        let sisaKuota = detail.request_detail.jumlah_acc -
                                            detail.request_detail.realisasi;
                                        maxAllowed = sisaKuota + detail.jumlah;
                                    }

                                    let rowHtml = `
                                        <tr id="row-item-${itemIndex}">
                                            <td class="align-middle">
                                                <input type="hidden" name="details[${itemIndex}][request_detail_id]" value="${reqId}">
                                                <input type="hidden" name="details[${itemIndex}][bahan_id]" value="${detail.bahan_id}">
                                                <strong class="text-dark">${namaBahan}</strong>
                                                ${reqId ? `<br><small class="text-muted">Maksimal Beli: ${maxAllowed} unit</small>` : ''}
                                            </td>
                                            <td class="align-middle">
                                                <input type="number" step="any" name="details[${itemIndex}][jumlah]" class="form-control input-jumlah" value="${detail.jumlah}" ${reqId ? `max="${maxAllowed}"` : ''} min="0.01" required>
                                            </td>
                                            <td class="align-middle">
                                                <input type="number" step="any" name="details[${itemIndex}][harga]" class="form-control input-harga" value="${detail.harga}" min="0" required>
                                            </td>
                                            <td class="align-middle text-end fw-bold subtotal-item">
                                                ${formatRupiah(detail.jumlah * detail.harga)}
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-danger btn-sm btn-hapus-row" data-bs-target="#row-item-${itemIndex}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                    $('#tbody-items').append(rowHtml);
                                    itemIndex++;
                                });
                            }

                            $('#modalPembelianTitle').html(
                                '<i class="fa-solid fa-pen-to-square me-2"></i>Edit Transaksi Pembelian (' +
                                data.no_po + ')');
                            $('#btnSimpanPembelian').html(
                                '<i class="fa-solid fa-floppy-disk me-2"></i>Update Transaksi');
                            hitungTotals();
                            $('#modalTambahPembelian').modal('show');
                        },
                        error: function(err) {
                            AppAlert.auto(err.responseJSON?.message ||
                                'Gagal mengambil data pembelian.');
                        }
                    });
                });

                $(document).on('click', '.btn-delete-po', function() {
                    let noPo = $(this).data('nopo');
                    AppAlert.confirm('Hapus transaksi PO (' + noPo + ')?').then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('pembelian') }}/" + noPo,
                                type: "DELETE",
                                data: {
                                    _token: "{{ csrf_token() }}"
                                },
                                success: function(res) {
                                    table.draw();
                                    AppAlert.auto(res.message);
                                },
                                error: function(err) {
                                    AppAlert.auto(err.responseJSON?.message ||
                                        'Gagal menghapus data pembelian.');
                                }
                            });
                        }
                    });
                });

                $('#formPembelian').submit(function(e) {
                    e.preventDefault();

                    if (!$('#input_supplier_id').val()) {
                        AppAlert.auto('Harap pilih supplier terlebih dahulu.');
                        return;
                    }

                    if ($('#tbody-items tr').length === 0) {
                        AppAlert.auto('Harap pilih minimal 1 item detail dari permintaan.');
                        return;
                    }

                    let isEdit = $('#form_method').val() === 'PUT';
                    let editNoPo = $('#edit_no_po').val();
                    let targetUrl = isEdit ? "{{ url('pembelian') }}/" + editNoPo :
                        "{{ route('pembelian.store') }}";

                    let btnSubmit = $('#btnSimpanPembelian');
                    btnSubmit.prop('disabled', true).html(
                        '<i class="fa-solid fa-spinner fa-spin me-2"></i>Menyimpan...');

                    $.ajax({
                        url: targetUrl,
                        type: "POST",
                        data: $(this).serialize(),
                        success: function(res) {
                            $('#modalTambahPembelian').modal('hide');
                            $('#formPembelian')[0].reset();
                            $('#input_supplier_id').val('');
                            $('#input_supplier_nama').val('');
                            $('#tbody-items').empty();
                            if (!isEdit && res.next_document_number) {
                                $('#input_no_po').val(res.next_document_number);
                            }
                            hitungTotals();
                            table.draw();
                            AppAlert.auto(res.message);
                        },
                        error: function(err) {
                            let msg = err.responseJSON?.message ||
                                'Terjadi kesalahan saat menyimpan data.';
                            AppAlert.auto(msg);
                        },
                        complete: function() {
                            btnSubmit.prop('disabled', false).html(isEdit ?
                                '<i class="fa-solid fa-floppy-disk me-2"></i>Update Transaksi' :
                                '<i class="fa-solid fa-floppy-disk me-2"></i>Simpan Transaksi');
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
