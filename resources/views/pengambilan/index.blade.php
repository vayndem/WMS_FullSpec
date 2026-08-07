@extends('layouts.app')

@section('content')
    <style>
        tr.master-row {
            border-top: 2px solid #007bff;
            background-color: #f0f2f5 !important;
            font-weight: bold;
            color: #333;
        }

        #table_all tbody tr:first-child.master-row {
            border-top: none;
        }

        tr.detail-row td:first-child {
            padding-left: 40px !important;
            border-left: 2px dotted #ccc;
        }

        tr.detail-row {
            background-color: #ffffff !important;
        }

        #table_all_processing {
            z-index: 1051;
        }

        .modal-xl-custom {
            max-width: 90% !important;
        }

        .info-card-flat {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
    </style>
    <div class="content-page">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 py-3">
                <h4 class="fw-bold text-dark mb-0">{{ $data['title'] }}</h4>
                <button type="button" class="btn btn-primary btn-sm font-weight-500 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modaltambah" id="tambah" name="tambah">
                    <i class="fa-solid fa-circle-plus me-1"></i> Tambah Pengambilan
                </button>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <select class="form-control col-md-2 col-sm-4 text-center me-2" id="npkflag" name="npkflag">
                        <option value="1">Planning</option>
                        <option value="2">Kirim</option>
                    </select>
                    <input type="month" id="periodenpk" name="periodenpk" value="<?php echo date('Y-m'); ?>"
                        class="form-control col-md-2 col-sm-4 float-start">
                    <button type="button" class="btn btn-success btn-sm ms-2 shadow-sm" id="exportexcel">
                        <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
                <div class="table-responsive" style="font-size:10pt">
                    <table id="table_all" class="table table-hover display compact responsive table-sm w-100">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>NPK / Barang</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                                <th>Satuan</th>
                                <th style="width: 100px;">#</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modaltambah" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-xl-custom modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-bottom-0">
                    <h5 class="modal-title fw-bold" id="staticBackdropLabel">Form {{ $data['title'] }}</h5>
                    <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="post" id="entrydata" name="entrydata" class="mb-4">
                        <input type="hidden" id="flag" name="flag" value="{{ $jenis }}" readonly>
                        <input type="hidden" id="id_gudang_asal" name="id_gudang_asal">

                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="mb-3 row align-items-center mb-3">
                                    <label for="nomor"
                                        class="col-sm-3 col-form-label font-weight-600 text-secondary">Nomor Dokumen</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control bg-light" id="nomor" name="nomor"
                                            readonly placeholder="Otomatis Sistem">
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center mb-3">
                                    <label for="tanggal"
                                        class="col-sm-3 col-form-label font-weight-600 text-secondary">Tanggal</label>
                                    <div class="col-sm-9">
                                        <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center mb-3">
                                    <label for="operator"
                                        class="col-sm-3 col-form-label font-weight-600 text-secondary">Nama Operator</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="operator" name="operator"
                                            placeholder="Masukkan nama PIC operator" required>
                                    </div>
                                </div>
                                <div class="mb-3 row mb-3">
                                    <label for="keterangan"
                                        class="col-sm-3 col-form-label font-weight-600 text-secondary">Keterangan</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="keterangan" name="keterangan" rows="2"
                                            placeholder="Catatan peruntukan barang..." required></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12 border-left pl-md-4">
                                <div class="mb-3 row mb-2">
                                    <label for="barang"
                                        class="col-sm-3 col-form-label font-weight-600 text-secondary">Pilih Barang</label>
                                    <div class="col-sm-9">
                                        <select class="select2 form-control" name="barang" id="barang" required>
                                            <option value="0" selected>--Pilih Barang--</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row mb-3">
                                    <div class="col-sm-9 offset-sm-3">
                                        <div class="info-card-flat p-2 d-flex flex-column font-size-12 gap-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-secondary">Stok: <span id="stokOnhandLabel"
                                                        class="fw-bold text-dark">0</span></span>
                                                <span id="previewKonversi" class="text-primary fw-bold"
                                                    style="display: none;"></span>
                                            </div>
                                            <div id="wrapperGudangAsal" class="text-secondary mt-1"
                                                style="display: none;">
                                                Disimpan di: <span id="gudangAsalLabel"
                                                    class="fw-bold text-dark"></span>
                                            </div>
                                            <div id="warningTracking" class="text-danger fw-bold mt-1"
                                                style="display: none;">
                                                ini menandakan keluar nya tidak di track
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 row align-items-center mb-3">
                                    <label for="id_gudang_tujuan"
                                        class="col-sm-3 col-form-label font-weight-600 text-secondary">Gudang
                                        Tujuan</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" name="id_gudang_tujuan" id="id_gudang_tujuan"
                                            required>
                                            <option value="">--Pilih Gudang Tujuan--</option>
                                            @foreach ($gudang as $g)
                                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3 row align-items-center mb-4">
                                    <label for="jumlah_tampilan"
                                        class="col-sm-3 col-form-label font-weight-600 text-secondary">Jumlah Ambil</label>
                                    <div class="col-sm-5">
                                        <div class="input-group">
                                            <input type="number" class="form-control text-end" id="jumlah_tampilan"
                                                required step="0.01" placeholder="0">
                                            <div class="d-flex">
                                                <span class="input-group-text bg-white fw-bold"
                                                    id="satuanLabel">Satuan</span>
                                            </div>
                                        </div>
                                        <input type="hidden" id="jumlah" name="jumlah">
                                    </div>
                                    <div class="col-sm-4 text-end">
                                        <button id="simpandata" type="submit"
                                            class="btn btn-primary w-100 shadow-sm">
                                            <i class="fa fa-save me-1"></i> Simpan Item
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h6 class="fw-bold text-dark mb-2"><i class="fa fa-list me-1 text-primary"></i> Daftar
                        Keranjang Detail Items</h6>
                    <div class="table-responsive border rounded" style="font-size:10pt">
                        <table id="table_detailnpk"
                            class="table table-striped table-hover display compact responsive table-sm mb-0 w-100">
                            <thead class="bg-light text-secondary text-uppercase font-size-11">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Nama Items Barang</th>
                                    <th style="width: 150px;" class="text-end">Jumlah Besar</th>
                                    <th style="width: 120px;">Satuan</th>
                                    <th style="width: 80px;" class="text-center">#</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 py-3">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup
                        Halaman</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        function listdata(flagx) {
            if ($.fn.DataTable.isDataTable('#table_all')) {
                $('#table_all').DataTable().destroy();
                $('#table_all tbody').empty();
            }

            $('#table_all tbody').html(
                '<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>'
            );

            const params = {
                flag: $('#npkflag').val(),
                jenis: $('#flag').val(),
                periode: $('#periodenpk').val(),
            };

            $.ajax({
                url: "{{ route('listnpkplanning') }}",
                type: "GET",
                data: params,
                success: function(response) {
                    let flattenedData = [];
                    let lastNpk = null;

                    if (response.data && response.data.length > 0) {
                        response.data.forEach(item => {
                            if (item.kode !== lastNpk) {
                                flattenedData.push({
                                    row_type: 'master',
                                    display_name: item.kode,
                                    tanggal: (flagx == 1) ? item.tanggal : item.tgl_terkirim,
                                    keterangan: item.keterangan,
                                    jumlah: null,
                                    satuan: null,
                                    action: item.action
                                });
                                lastNpk = item.kode;
                            }

                            flattenedData.push({
                                row_type: 'detail',
                                display_name: item.nama,
                                tanggal: null,
                                keterangan: null,
                                jumlah: item.jumlah,
                                satuan: item.satuan,
                                action: null
                            });
                        });
                    }

                    $('#table_all').DataTable({
                        destroy: true,
                        data: flattenedData,
                        processing: false,
                        serverSide: false,
                        responsive: true,
                        columns: [{
                                data: 'display_name'
                            },
                            {
                                data: 'tanggal'
                            },
                            {
                                data: 'keterangan'
                            },
                            {
                                data: 'jumlah',
                                className: 'text-end'
                            },
                            {
                                data: 'satuan'
                            },
                            {
                                data: 'action',
                                orderable: false,
                                searchable: false
                            }
                        ],
                        ordering: false,
                        paging: true,
                        searching: true,
                        info: true,
                        createdRow: function(row, data) {
                            if (data.row_type === 'master') {
                                $(row).addClass('master-row');
                                $('td:eq(3)', row).html('');
                                $('td:eq(4)', row).html('');
                            } else {
                                $(row).addClass('detail-row');
                                $('td:eq(1)', row).html('');
                                $('td:eq(2)', row).html('');
                                $('td:eq(5)', row).html('');
                            }
                        }
                    });
                },
                error: function() {
                    $('#table_all tbody').html(
                        '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const npkflag = document.getElementById('npkflag');
            const periodenpk = document.getElementById('periodenpk');
            const exportBtn = document.getElementById('exportexcel');

            function toggleFilters() {
                const isKirim = npkflag.value === '2';
                periodenpk.style.display = isKirim ? 'block' : 'none';
                exportBtn.style.display = isKirim ? 'block' : 'none';
            }

            toggleFilters();
            npkflag.addEventListener('change', toggleFilters);
        });

        $('#periodenpk').on('change', function() {
            listdata($('#npkflag').val());
        });

        function databarang() {
            let kategorix = $('#flag').val();
            $.ajax({
                type: 'GET',
                url: '{{ route('reloadbarang') }}',
                dataType: 'json',
                data: {
                    kategori: kategorix
                },
                success: function(data) {
                    $('#barang').empty().append(`<option value="">--Pilih Barang--</option>`);
                    $.each(data, function(index, brg) {
                        $('#barang').append(
                            `<option value="${brg.id}" data-satuan="${brg.satuan}" data-satuan-kecil="${brg.satuan_kecil}" data-berat-kecil="${brg.berat_kecil}" data-gudang="${brg.gudang}" data-nama-gudang="${brg.nama_gudang_asal || ''}" data-stok="${brg.stok_onhand}">${brg.nama}</option>`
                        );
                    });
                }
            });
        }

        function detailnpk(kodex) {
            $('#table_detailnpk').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                destroy: true,
                bFilter: false,
                bLengthChange: false,
                bPaginate: false,
                ordering: false,
                bInfo: false,
                autoWidth: false,
                ajax: {
                    url: "{{ route('listnpkplanning') }}",
                    type: "GET",
                    data: {
                        kode: kodex
                    }
                },
                columns: [{
                        "data": "DT_RowIndex",
                        "searchable": false,
                        "width": "50px",
                        className: "text-center"
                    },
                    {
                        "data": "nama"
                    },
                    {
                        "data": "jumlah",
                        className: "text-end",
                        "width": "150px"
                    },
                    {
                        "data": "satuan",
                        "width": "120px"
                    },
                    {
                        "data": "action",
                        orderable: false,
                        searchable: false,
                        className: "text-center",
                        "width": "80px"
                    }
                ],
            });
        }

        function checkTrackingGudang() {
            let selectedBrg = $('#barang').find('option:selected');
            let idGudangAsal = selectedBrg.data('gudang');
            let idGudangTujuan = $('#id_gudang_tujuan').val();

            if (idGudangAsal !== undefined && idGudangAsal !== '' && idGudangTujuan !== '') {
                if (parseInt(idGudangAsal) === parseInt(idGudangTujuan)) {
                    $('#warningTracking').show();
                } else {
                    $('#warningTracking').hide();
                }
            } else {
                $('#warningTracking').hide();
            }
        }

        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                dropdownAutoWidth: true,
                dropdownParent: $('#modaltambah')
            });
            listdata($('#npkflag').val());
            databarang();

            $('#barang').on('change', function() {
                let selected = $(this).find('option:selected');
                let satuanBesar = selected.data('satuan') || '';
                let satuanKecil = selected.data('satuanKecil') || '';
                let beratKecil = parseFloat(selected.data('beratKecil')) || 0;
                let stok = selected.data('stok') || 0;
                let idGudangAsal = selected.data('gudang');
                let namaGudangAsal = selected.data('namaGudang') || '';

                $('#stokOnhandLabel').text(stok);
                $('#id_gudang_asal').val(idGudangAsal);

                if (namaGudangAsal !== '') {
                    $('#gudangAsalLabel').text(namaGudangAsal);
                    $('#wrapperGudangAsal').show();
                } else {
                    $('#wrapperGudangAsal').hide();
                }

                if (idGudangAsal !== undefined && idGudangAsal !== '') {
                    $('#id_gudang_tujuan').val(idGudangAsal).trigger('change');
                } else {
                    $('#id_gudang_tujuan').val('');
                }

                if (beratKecil > 0 && satuanKecil) {
                    $('#satuanLabel').text(satuanKecil);
                    $('#previewKonversi').text(`1 ${satuanBesar} = ${beratKecil} ${satuanKecil}`).show();
                } else {
                    $('#satuanLabel').text(satuanBesar || 'Satuan');
                    $('#previewKonversi').hide().text('');
                }
                $('#jumlah_tampilan').val('');
                $('#jumlah').val('');
                checkTrackingGudang();
            });

            $('#id_gudang_tujuan').on('change', function() {
                checkTrackingGudang();
            });
        });

        const bntSimpan = document.querySelector('#simpandata');
        const formSimpan = document.querySelector('#entrydata');
        formSimpan.addEventListener('submit', async function(event) {
            event.preventDefault();

            let selectedBrg = $('#barang').find('option:selected');
            let beratKecil = parseFloat(selectedBrg.data('beratKecil')) || 0;
            let userInput = parseFloat(document.getElementById('jumlah_tampilan').value) || 0;
            let stokTersedia = parseFloat(selectedBrg.data('stok')) || 0;

            let jumlahFinal = userInput;
            if (beratKecil > 0) {
                jumlahFinal = parseFloat((userInput / beratKecil).toFixed(2));
            }

            document.getElementById('jumlah').value = jumlahFinal;

            if (jumlahFinal > stokTersedia) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stok Tidak Cukup!',
                    text: 'Jumlah pengambilan setelah konversi (' + jumlahFinal +
                        ') melebihi stok yang ada (' + stokTersedia + ').',
                });
                return;
            }

            if ($('#id_gudang_tujuan').val() == "") {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Silakan pilih Gudang Tujuan terlebih dahulu.'
                });
                return;
            }

            bntSimpan.disabled = true;
            bntSimpan.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';

            const data = new FormData(formSimpan);
            const response = await fetch('{{ url('addpengambilan') }}', {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": '{{ csrf_token() }}'
                },
                body: data
            });
            const result = await response.json();
            if (result.status == "ok") {
                $('#barang').val('').trigger('change');
                $('#jumlah_tampilan').val('');
                $('#jumlah').val('');
                listdata($('#npkflag').val());
                detailnpk(result.kodenpkplanning);
                $('#nomor').val(result.kodenpkplanning);
                Swal.fire({
                    icon: 'success',
                    title: result.message,
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: result.message,
                    showConfirmButton: false,
                    timer: 1500
                });
            }
            bntSimpan.disabled = false;
            bntSimpan.innerHTML = 'Simpan';
        });

        $(document).on('click', '.editnpk', function() {
            $('#nomor').val($(this).data('kode'));
            $('#tanggal').val($(this).data('tanggal'));
            $('#keterangan').val($(this).data('keterangan'));
            $('#operator').val($(this).data('operator'));
            $('#id_gudang_tujuan').val($(this).data('gudang_tujuan') || '').trigger('change');
            detailnpk($(this).data('kode'));
            $('#modaltambah').modal('show');
        });

        $(document).on('click', '.hapusdetail', async function() {
            let kodex = $(this).data('id');
            const confirmation = await Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            });

            if (confirmation.isConfirmed) {
                const response = await fetch(`/deletenpkplanning/${kodex}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const result = await response.json();
                if (result.succes == 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: result.pesan,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    $('#table_detailnpk').DataTable().ajax.reload(null, false);
                    listdata($('#npkflag').val());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: result.pesan
                    });
                }
            }
        });

        $('#tambah').on('click', function() {
            cleardata();
        });

        function cleardata() {
            $('#nomor').val('');
            $('#tanggal').val('');
            $('#keterangan').val('');
            $('#operator').val('');
            $('#barang').val('').trigger('change');
            $('#id_gudang_tujuan').val('');
            $('#id_gudang_asal').val('');
            $('#jumlah_tampilan').val('');
            $('#jumlah').val('');
            $('#previewKonversi').hide().text('');
            $('#satuanLabel').text('Satuan');
            $('#wrapperGudangAsal').hide();
            $('#gudangAsalLabel').text('');
            $('#warningTracking').hide();
            detailnpk(0);
        }

        $('#npkflag').on('change', function() {
            listdata($(this).val());
        });

        $(document).on('click', '.proses', function() {
            const kode_npk = $(this).data('kode');
            const tanggal_db = $(this).data('tanggal');

            Swal.fire({
                title: 'Tanggal Kirim untuk NPK: ' + kode_npk,
                html: `<input type="date" id="tanggalKirim" class="swal2-input" value="${tanggal_db}" min="${tanggal_db}">`,
                showCancelButton: true,
                confirmButtonText: 'Proses Semua',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    return {
                        tanggalKirim: document.getElementById('tanggalKirim').value,
                        kode: kode_npk,
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const {
                        tanggalKirim,
                        kode
                    } = result.value;

                    if (!tanggalKirim) {
                        Swal.fire('Error', 'Tanggal kirim harus diisi', 'error');
                        return;
                    }
                    $.ajax({
                        url: '{{ route('npkkirim') }}',
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            kode: kode,
                            tanggal_kirim: tanggalKirim,
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Sukses!',
                                    text: response.message,
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                setTimeout(function() {
                                    listdata($('#npkflag').val());
                                }, 500);
                            } else {
                                Swal.fire('Error', response.message || 'Gagal menyimpan data',
                                    'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                        }
                    });
                }
            });
        });

        $(document).on('click', '#exportexcel', function() {
            const periode = $('#periodenpk').val();
            const flag = $('#npkflag').val();
            const jenis = $('#flag').val();
            const url = `{{ url('exportnpk') }}?periode=${periode}&flag=${flag}&jenis=${jenis}`;
            window.location.href = url;
        });
    </script>
@endpush
