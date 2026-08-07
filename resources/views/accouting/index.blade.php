<div class="card border-0 shadow-sm rounded-lg">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="fw-bold text-secondary mb-0">
            <i class="fa fa-filter me-2 text-primary"></i>Filter Laporan Pemakaian Bahan
        </h5>
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-xl-3 col-md-6 mb-3">
                <label class="small fw-bold text-muted text-uppercase tracking-wider mb-2 d-block">Tanggal
                    Awal</label>
                <div class="input-group">
                    <div class="d-flex">
                        <span class="input-group-text bg-light border-right-0 text-muted"><i
                                class="fa fa-calendar-alt"></i></span>
                    </div>
                    <input type="date" id="tgl_awal" class="form-control border-left-0 bg-light-focus"
                        value="{{ date('Y-m-01') }}">
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <label class="small fw-bold text-muted text-uppercase tracking-wider mb-2 d-block">Tanggal
                    Akhir</label>
                <div class="input-group">
                    <div class="d-flex">
                        <span class="input-group-text bg-light border-right-0 text-muted"><i
                                class="fa fa-calendar-alt"></i></span>
                    </div>
                    <input type="date" id="tgl_akhir" class="form-control border-left-0 bg-light-focus"
                        value="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <label class="small fw-bold text-muted text-uppercase tracking-wider mb-2 d-block">Kategori
                    Bahan</label>
                <div class="dropdown">
                    <button
                        class="btn btn-light border w-100 dropdown-toggle text-start d-flex justify-content-between align-items-center shadow-sm-none"
                        type="button" id="dropdownKategori" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false" style="height: calc(1.5em + .75rem + 2px);">
                        <span id="dropdownLabel" class="text-truncate">Semua Kategori</span>
                    </button>
                    <div class="dropdown-menu shadow-lg border-0 p-3" aria-labelledby="dropdownKategori"
                        style="max-height: 300px; overflow-y: auto; width: 100%; min-width: 250px; border-radius: 8px;">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="small fw-bold text-muted">Pilih Kategori</span>
                            <button type="button" class="btn btn-link p-0 small text-decoration-none"
                                id="clearKategori">Reset</button>
                        </div>
                        @foreach ($kategoriList as $kat)
                            <div class="form-check py-1 px-3 rounded hover-bg-light cursor-pointer">
                                <input type="checkbox" class="form-check-input cb-kategori" id="kat_{{ $kat->katid }}"
                                    value="{{ $kat->katid }}" data-nama="{{ $kat->katnama }}">
                                <label class="form-check-label small w-100 cursor-pointer text-secondary"
                                    for="kat_{{ $kat->katid }}">{{ $kat->katnama }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="row g-0">
                    <div class="col-7 pe-1">
                        <button type="button"
                            class="btn btn-primary w-100 d-flex align-items-center justify-content-center shadow-sm"
                            onclick="prosesFilterData()"
                            style="height: calc(1.5em + .75rem + 2px); border-radius: 6px; font-weight: 600;">
                            <i class="fa fa-search me-2"></i> Cari Data
                        </button>
                    </div>
                    <div class="col-5 ps-1">
                        <button type="button"
                            class="btn btn-success w-100 d-flex align-items-center justify-content-center shadow-sm"
                            onclick="exportDataExcel()"
                            style="height: calc(1.5em + .75rem + 2px); border-radius: 6px; font-weight: 600;">
                            <i class="fa fa-file-excel me-2"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4 border-light">

        <div class="table-responsive d-none shadow-sm rounded border" id="wrapperTabel">
            <table class="table table-hover mb-0" id="tableLaporanPemakaian">
                <thead class="bg-dark text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <tr>
                        <th class="text-center align-middle py-3" style="width: 60px;">No</th>
                        <th class="align-middle py-3">Nama Bahan</th>
                        <th class="align-middle py-3">Kategori</th>
                        <th class="text-center align-middle py-3" style="width: 100px;">Satuan</th>
                        <th class="text-end align-middle py-3" style="width: 140px;">Total Keluar</th>
                        <th class="text-end align-middle py-3" style="width: 160px;">Harga Satuan</th>
                        <th class="text-end align-middle py-3" style="width: 180px;">Total Nominal</th>
                    </tr>
                </thead>
                <tbody id="bodyTabelPemakaian" class="text-secondary" style="font-size: 14px;">
                </tbody>
                <tfoot id="footTabelPemakaian" class="bg-light" style="font-size: 14px;">
                </tfoot>
            </table>
        </div>

        <div id="loadingStatus" class="text-center d-none py-5">
            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted fw-bold" style="font-size: 14px;">Memproses data transaksi pemakaian...
            </p>
        </div>
    </div>
</div>

<style>
    .bg-light-focus:focus {
        background-color: #fff !important;
        border-color: #bac8f3 !important;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
    }

    .hover-bg-light:hover {
        background-color: #f8f9fc;
    }

    .cursor-pointer {
        cursor: pointer !important;
    }

    .tracking-wider {
        letter-spacing: 0.05em;
    }

    #tableLaporanPemakaian tbody tr {
        transition: background-color 0.15s ease-in-out;
    }

    #tableLaporanPemakaian th,
    #tableLaporanPemakaian td {
        border-color: #e3e6f0;
    }
</style>

<script>
    function getSelectedKategori() {
        let kategori = [];
        $('.cb-kategori:checked').each(function() {
            kategori.push($(this).val());
        });
        return kategori;
    }

    function updateDropdownLabel() {
        let checked = $('.cb-kategori:checked');
        if (checked.length === 0) {
            $('#dropdownLabel').text('Semua Kategori').removeClass('fw-bold text-primary');
        } else if (checked.length === 1) {
            $('#dropdownLabel').text(checked.first().data('nama')).addClass('fw-bold text-primary');
        } else {
            $('#dropdownLabel').text(checked.length + ' Kategori Terpilih').addClass('fw-bold text-primary');
        }
    }

    $('.cb-kategori').on('change', function() {
        updateDropdownLabel();
    });

    $('#clearKategori').on('click', function() {
        $('.cb-kategori').prop('checked', false);
        updateDropdownLabel();
    });

    function prosesFilterData() {
        const tglAwal = document.getElementById('tgl_awal').value;
        const tglAkhir = document.getElementById('tgl_akhir').value;
        const kategori = getSelectedKategori();

        if (!tglAwal || !tglAkhir) {
            AppAlert.auto('Kedua tanggal harus diisi');
            return;
        }

        $.ajax({
            type: "get",
            url: "{{ route('report.index') }}",
            data: {
                tgl_awal: tglAwal,
                tgl_akhir: tglAkhir,
                kategori: kategori
            },
            dataType: "json",
            beforeSend: function() {
                $('#wrapperTabel').addClass('d-none');
                $('#loadingStatus').removeClass('d-none');
                $('#bodyTabelPemakaian').empty();
                $('#footTabelPemakaian').empty();
            },
            success: function(response) {
                $('#loadingStatus').addClass('d-none');
                $('#wrapperTabel').removeClass('d-none');

                if (response.data && response.data.length > 0) {
                    let totalSemuaNominal = 0;

                    response.data.forEach((item, index) => {
                        const nominalRaw = parseFloat(item.total_nominal) || 0;
                        totalSemuaNominal += nominalRaw;

                        const hargaSatuan = parseFloat(item.harga_satuan).toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        const totalNominal = nominalRaw.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        const totalKeluar = parseFloat(item.total_keluar).toLocaleString('id-ID');

                        const row = `<tr>
                            <td class="text-center py-3">${index + 1}</td>
                            <td class="fw-bold text-dark py-3">${item.nama_bahan}</td>
                            <td class="py-3"><span class="badge bg-light border text-muted px-2 py-1">${item.nama_kategori || '-'}</span></td>
                            <td class="text-center py-3">${item.satuan}</td>
                            <td class="text-end py-3 fw-bold">${totalKeluar}</td>
                            <td class="text-end py-3">Rp ${hargaSatuan}</td>
                            <td class="text-end py-3 text-dark fw-bold">Rp ${totalNominal}</td>
                        </tr>`;
                        $('#bodyTabelPemakaian').append(row);
                    });

                    const grandTotalFormatted = totalSemuaNominal.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    const footerRow = `<tr>
                        <th colspan="6" class="text-end fw-bold py-3 text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">Total Keseluruhan</th>
                        <th class="text-end fw-bold py-3 text-primary" style="font-size: 16px;">Rp ${grandTotalFormatted}</th>
                    </tr>`;
                    $('#footTabelPemakaian').append(footerRow);
                } else {
                    $('#bodyTabelPemakaian').append(
                        `<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa fa-info-circle me-2"></i>Tidak ada data pemakaian pada periode ini.</td></tr>`
                    );
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                $('#loadingStatus').addClass('d-none');
                AppAlert.auto(xhr.status + '\n' + thrownError);
            }
        });
    }

    function exportDataExcel() {
        const tglAwal = document.getElementById('tgl_awal').value;
        const tglAkhir = document.getElementById('tgl_akhir').value;
        const kategori = getSelectedKategori();

        if (!tglAwal || !tglAkhir) {
            AppAlert.auto('Kedua tanggal harus diisi untuk melakukan export');
            return;
        }

        let url = `{{ route('report.export') }}?tgl_awal=${tglAwal}&tgl_akhir=${tglAkhir}`;

        if (kategori.length > 0) {
            kategori.forEach(id => {
                url += `&kategori[]=${id}`;
            });
        }

        window.location.href = url;
    }

    $('.dropdown-menu').on('click', function(e) {
        e.stopPropagation();
    });
</script>
