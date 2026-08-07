<base href="/">

@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .filter-card {
            border: 2px solid #4e73df;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 0 #4e73df;
        }

        .btn-flat-info {
            background-color: #ffffff;
            color: #36b9cc;
            border: 3px solid #36b9cc;
            border-radius: 8px;
            font-weight: 700;
        }

        .btn-flat-info:hover {
            background-color: #36b9cc;
            color: #ffffff;
        }

        .item-card {
            transition: transform 0.15s ease-in-out;
            border: 2px solid #4e73df !important;
            border-radius: 12px !important;
            background: #ffffff;
            box-shadow: 0 6px 0 #4e73df;
            overflow: hidden;
        }

        .item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 0 #4e73df;
        }

        .card-header-flat {
            background-color: #4e73df;
            border-bottom: 2px solid #4e73df;
        }

        .btn-flat-primary {
            background-color: #4e73df;
            color: #ffffff;
            border: 2px solid #2e59d9;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 3px 0 #2e59d9;
        }

        .btn-flat-primary:hover {
            background-color: #2e59d9;
            color: #ffffff;
        }

        .btn-flat-dark {
            background-color: #5a5c69;
            color: #ffffff;
            border: 2px solid #3a3b45;
            border-radius: 8px;
            box-shadow: 0 3px 0 #3a3b45;
        }

        .btn-flat-dark:hover {
            background-color: #3a3b45;
            color: #ffffff;
        }

        .btn-flat-warning {
            background-color: #f6c23e;
            color: #ffffff;
            border: 2px solid #f4b619;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 3px 0 #f4b619;
        }

        .btn-flat-warning:hover {
            background-color: #f4b619;
            color: #ffffff;
        }

        .btn-flat-danger {
            background-color: #e74a3b;
            color: #ffffff;
            border: 2px solid #be2617;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 3px 0 #be2617;
        }

        .btn-flat-danger:hover {
            background-color: #be2617;
            color: #ffffff;
        }

        .btn-flat-success {
            background-color: #1cc88a;
            color: #ffffff;
            border: 2px solid #17a673;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 3px 0 #17a673;
        }

        .btn-flat-success:hover {
            background-color: #17a673;
            color: #ffffff;
        }

        .btn-flat-outline {
            background-color: transparent;
            color: #36b9cc;
            border: 2px solid #36b9cc;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 3px 0 #36b9cc;
        }

        .btn-flat-outline:hover {
            background-color: #36b9cc;
            color: #ffffff;
        }

        .form-flat {
            border: 2px solid #d1d3e2;
            border-radius: 8px;
            color: #6e707e;
            font-weight: 600;
        }

        .form-flat:focus {
            border-color: #4e73df;
            box-shadow: none;
        }

        .progress-flat {
            height: 14px;
            border: 2px solid #5a5c69;
            border-radius: 10px;
            background-color: #eaecf4;
            overflow: hidden;
        }

        .progress-flat-bar {
            border-radius: 10px;
        }

        .bg-flat-success {
            background-color: #1cc88a;
        }

        .bg-flat-warning {
            background-color: #f6c23e;
        }

        .bg-flat-danger {
            background-color: #e74a3b;
        }

        .badge-flat {
            border: 2px solid #4e73df;
            color: #4e73df;
            background: #f8f9fc;
            font-weight: 700;
            border-radius: 8px;
        }

        .badge-flat-success {
            border: 2px solid #1cc88a;
            color: #1cc88a;
            background: #f8f9fc;
            font-weight: 700;
            border-radius: 8px;
        }

        .modal-content-flat {
            border: 3px solid #4e73df;
            border-radius: 16px;
        }

        #reader {
            width: 100% !important;
            height: 100% !important;
            border: none !important;
        }

        #reader video {
            object-fit: cover !important;
            width: 100% !important;
            height: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            border: 2px solid #4e73df !important;
            border-radius: 8px !important;
            height: calc(2.25rem + 10px) !important;
            padding: 6px 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #6e707e !important;
            font-weight: 600 !important;
            line-height: 28px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }

        .select2-dropdown {
            border: 2px solid #4e73df !important;
            border-radius: 8px !important;
        }
    </style>

    <div class="content-page">
        <div class="container-fluid p-4">

            <div class="card filter-card mb-4 mt-2">
                <div class="card-body p-3 bg-white">
                    <form action="{{ route('bahan_produksi.gudang', $gudangAktif->id) }}" method="GET" id="filterForm">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="fw-bold text-primary small text-uppercase">Bulan</label>
                                <select name="bulan" class="form-control form-flat"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">Semua Bulan</option>
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="fw-bold text-primary small text-uppercase">Tahun</label>
                                <select name="tahun" class="form-control form-flat"
                                    onchange="document.getElementById('filterForm').submit()">
                                    @for ($y = date('Y'); $y >= date('Y') - 4; $y--)
                                        <option value="{{ $y }}"
                                            {{ request('tahun', date('Y')) == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-primary small text-uppercase">Pencarian Barang</label>
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control form-flat"
                                        placeholder="Cari berdasarkan kode, nama barang, atau deskripsi..."
                                        value="{{ request('search') }}">
                                    @if (request('search') || request('bulan'))
                                        <div class="d-flex">
                                            <a href="{{ route('bahan_produksi.gudang', $gudangAktif->id) }}"
                                                class="btn btn-flat-dark d-flex align-items-center">RESET</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-none border-0 mb-4 bg-transparent">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="m-0 fw-bold text-primary text-uppercase tracking-wider">Daftar Bahan On-Going:
                        {{ $gudangAktif->nama }}</h3>
                    <button class="btn btn-flat-primary px-4 py-2 mt-2 mt-sm-0" data-bs-toggle="modal"
                        data-bs-target="#modalScanner">
                        <i class="fas fa-qrcode me-2"></i> BUKA SCANNER
                    </button>
                </div>
            </div>

            <div id="itemsContainer">
                @forelse ($data as $idBahan => $groupItems)
                    @php
                        $firstItem = $groupItems->first();
                        $namaGroupBarang = $firstItem->detailBahan->nama ?? 'Bahan Tidak Diketahui';
                    @endphp
                    <div class="shipping-group">
                        <div class="col-12 p-0 mb-3 mt-4">
                            <h5 class="fw-bold text-dark d-flex align-items-center m-0">
                                <span class="badge badge-flat px-3 py-2 text-uppercase">
                                    <i class="fas fa-box me-2"></i> {{ $namaGroupBarang }}
                                </span>
                                <div class="flex-grow-1 ms-3" style="border-top: 2px dashed #4e73df;"></div>
                            </h5>
                        </div>

                        <div class="row">
                            @foreach ($groupItems as $item)
                                @php
                                    $namaBarang = $item->detailBahan->nama ?? 'Bahan';
                                    $deskripsi = is_array($item->untuk_npk)
                                        ? implode(', ', $item->untuk_npk)
                                        : $item->untuk_npk;
                                    $searchBlob = strtolower($item->kode . ' ' . $namaBarang . ' ' . $deskripsi);

                                    $faktorKonversi = floatval($item->detailBahan->berat_kecil ?? 1);
                                    $satuanKecil = $item->detailBahan->satuan_kecil ?? $item->satuan;

                                    $jumlahKecil = $item->jumlah * $faktorKonversi;
                                    $dipakaiKecil = $item->dipakai * $faktorKonversi;

                                    $persen = $item->jumlah > 0 ? ($item->dipakai / $item->jumlah) * 100 : 0;
                                    $warna =
                                        $persen > 85
                                            ? 'bg-flat-danger'
                                            : ($persen > 50
                                                ? 'bg-flat-warning'
                                                : 'bg-flat-success');
                                @endphp
                                <div class="col-xl-4 col-md-6 mb-4 item-search-card" data-search="{{ $searchBlob }}">
                                    <div class="card h-100 item-card position-relative shadow-none">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span
                                                    class="fw-bold text-dark mb-0 pe-2 text-uppercase tracking-tight small">
                                                    Pengiriman: {{ $item->kode }}
                                                </span>
                                                <div class="d-flex style-gap" style="gap: 6px;">
                                                    <button type="button"
                                                        class="btn btn-sm btn-flat-warning px-3 py-2 text-uppercase"
                                                        data-bs-toggle="modal" data-bs-target="#modalDesc{{ $item->id }}">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                    <a href="{{ route('bahan_produksi.generate', $item->id) }}"
                                                        target="_blank" class="btn btn-sm btn-flat-dark px-3 py-2">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <span
                                                    class="small fw-bold text-muted text-uppercase d-block mb-1">Keterangan
                                                    / NPK:</span>
                                                <p class="text-secondary small fw-semibold bg-light p-2 border mb-0"
                                                    style="border: 1px solid #d1d3e2 !important; border-radius: 8px;">
                                                    <i class="fas fa-info-circle text-info me-1"></i>
                                                    {{ $deskripsi ?: '-' }}
                                                </p>
                                            </div>

                                            <div class="row g-0 rounded mb-3 text-center"
                                                style="border: 2px solid #4e73df; border-radius: 8px; overflow:hidden;">
                                                <div class="col-6 bg-light py-2" style="border-right: 2px solid #4e73df;">
                                                    <div class="small fw-bold text-muted text-uppercase">Rencana
                                                    </div>
                                                    <div class="h4 fw-bold text-primary mb-0">
                                                        {{ number_format($jumlahKecil, 2) }}
                                                    </div>
                                                    <span class="badge badge-flat px-2 py-0 mt-1 small text-dark"
                                                        style="border-color:#d1d3e2;">{{ $satuanKecil }}</span>
                                                </div>
                                                <div class="col-6 bg-white py-2">
                                                    <div class="small fw-bold text-muted text-uppercase">Terpakai
                                                    </div>
                                                    <div class="h4 fw-bold text-danger mb-0">
                                                        {{ number_format($dipakaiKecil, 2) }}
                                                    </div>
                                                    <span class="badge badge-flat px-2 py-0 mt-1 small text-dark"
                                                        style="border-color:#d1d3e2;">{{ $satuanKecil }}</span>
                                                </div>
                                            </div>

                                            <div class="progress progress-flat mb-1">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated progress-flat-bar {{ $warna }}"
                                                    style="width: {{ min($persen, 100) }}%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between small fw-bold text-dark">
                                                <span class="text-muted">PROGRES</span>
                                                <span class="text-primary">{{ number_format($persen, 1) }}%</span>
                                            </div>
                                        </div>

                                        <div class="card-footer bg-light border-0 d-flex justify-content-between p-3"
                                            style="border-top: 2px solid #4e73df !important;">
                                            <button type="button"
                                                class="btn btn-sm btn-flat-primary px-3 py-2 text-uppercase text-white btn-trigger-edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalTerpakai{{ $item->id }}">EDIT</button>

                                            <button type="button"
                                                class="btn btn-sm btn-flat-success px-3 py-2 text-uppercase text-white btn-kembali-gudang"
                                                data-id-produksi="{{ $item->id }}"
                                                data-max-kembali="{{ $jumlahKecil - $dipakaiKecil }}"
                                                data-nama-barang="{{ $namaBarang }}"
                                                data-satuan="{{ $satuanKecil }}">
                                                <i class="fas fa-warehouse text-white"></i> KEMBALI
                                            </button>

                                            <button type="button"
                                                class="btn btn-sm btn-flat-info px-3 py-2 text-uppercase"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalHistory{{ $item->id }}">HISTORY</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div id="noDataMessage" class="text-center py-5 filter-card bg-white mt-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="fw-bold text-dark mb-0">Data bahan produksi tidak ditemukan.</h5>
                    </div>
                @endforelse

                <div id="noResultsMessage" class="text-center py-5 filter-card bg-white mt-4 d-none">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark mb-0">Hasil pencarian tidak ditemukan.</h5>
                </div>
            </div>

        </div>
    </div>

    @foreach ($data as $groupItems)
        @foreach ($groupItems as $item)
            @php
                $faktorKonversi = floatval($item->detailBahan->berat_kecil ?? 1);
                $satuanKecil = $item->detailBahan->satuan_kecil ?? $item->satuan;

                $jumlahKecil = $item->jumlah * $faktorKonversi;
                $dipakaiKecil = $item->dipakai * $faktorKonversi;
                $sisaRencanaKecil = max($jumlahKecil - $dipakaiKecil, 0);

                $persen = $item->jumlah > 0 ? ($item->dipakai / $item->jumlah) * 100 : 0;
                $warna = $persen > 85 ? 'bg-flat-danger' : ($persen > 50 ? 'bg-flat-warning' : 'bg-flat-success');
            @endphp
            <div class="modal fade modal-edit-pemakaian" id="modalTerpakai{{ $item->id }}" tabindex="-1"
                role="dialog" aria-hidden="true" data-terpakai-awal="{{ $dipakaiKecil }}"
                data-jumlah-rencana="{{ $jumlahKecil }}">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content modal-content-flat shadow-none">
                        <form class="form-swal-confirm" action="{{ route('bahan_produksi.update', $item->id) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header card-header-flat text-white border-0 py-3">
                                <h5 class="modal-title fw-bold text-uppercase d-flex align-items-center">
                                    <i class="fas fa-layer-group me-2"></i> Log Pemakaian Bahan
                                </h5>
                                <button type="button" class="btn-close btn-close-white "
                                    data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body px-4 py-4 bg-light">
                                <div class="mb-3 mb-3">
                                    <label class="fw-bold text-dark small text-uppercase tracking-wider">Jumlah
                                        Tambahan Terpakai ({{ $satuanKecil }})</label>
                                    <div class="input-group"
                                        style="border: 2px solid #4e73df; border-radius: 8px; overflow: hidden;">
                                        <div class="d-flex">
                                            <span class="input-group-text bg-white border-0 text-primary fw-bold">
                                                <i class="fas fa-weight-hanging"></i>
                                            </span>
                                        </div>
                                        <input type="number" step="0.01" name="dipakai_kecil"
                                            class="form-control border-0 fw-bold input-jumlah-pakai"
                                            style="font-size: 1.1rem;" value="0" min="0"
                                            max="{{ $sisaRencanaKecil }}" required>
                                    </div>
                                    <small class="text-dark fw-bold mt-1 d-block">Maksimal rencana tambahan: <span
                                            class="text-danger label-sisa-rencana">{{ number_format($sisaRencanaKecil, 2) }}
                                            {{ $satuanKecil }}</span></small>
                                </div>

                                <div class="mb-4 p-3 bg-white border rounded"
                                    style="border: 2px solid #d1d3e2 !important;">
                                    <div class="progress progress-flat mb-1">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated progress-flat-bar modal-progress-bar {{ $warna }}"
                                            style="width: {{ min($persen, 100) }}%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small fw-bold text-dark">
                                        <span class="text-muted">PROGRES LIVE</span>
                                        <span
                                            class="text-primary modal-progress-text">{{ number_format($persen, 1) }}%</span>
                                    </div>
                                </div>

                                <div class="mb-3 mb-3">
                                    <label class="fw-bold text-dark small text-uppercase tracking-wider">Data
                                        Pesanan</label>
                                    <select name="data_pesanan" class="form-control select-pesanan-ajax"
                                        style="width: 100%;">
                                        <option value="">-- Cari Kode Batch / Nama Cetakan --</option>
                                    </select>
                                </div>
                                <div class="mb-3 mb-0">
                                    <label
                                        class="fw-bold text-dark small text-uppercase tracking-wider">Keterangan /
                                        Catatan</label>
                                    <textarea name="keterangan" class="form-control form-flat" rows="3"
                                        placeholder="Tambahkan catatan detail pemakaian produksi di sini..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-white border-0 px-4 pb-4 pt-0">
                                <button type="submit"
                                    class="btn btn-flat-warning w-100 py-3 text-uppercase text-white">
                                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalHistory{{ $item->id }}" tabindex="-1" role="dialog"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content modal-content-flat shadow-none">
                        <div class="modal-header bg-info text-white border-0 py-3" style="background-color: #36b9cc;">
                            <h5 class="modal-title fw-bold text-uppercase d-flex align-items-center">
                                <i class="fas fa-history me-2"></i> Riwayat Pemakaian:
                                {{ $item->detailBahan->nama ?? 'Bahan' }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white "
                                data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4 bg-white" style="max-height: 400px; overflow-y: auto;">
                            <div class="table-responsive">
                                <table class="table table-bordered table-flat table-hover m-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 70px;">NO</th>
                                            <th class="text-center">TANGGAL</th>
                                            <th class="text-center">TERPAKAI</th>
                                            <th class="text-center">DATA PESANAN</th>
                                            <th class="text-center">KETERANGAN</th>
                                            <th class="text-center">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($item->detailPemakaian as $index => $history)
                                            @php
                                                $historyDipakaiKecil = $history->dipakai * $faktorKonversi;
                                            @endphp
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="text-center">{{ $history->created_at->format('d M Y H:i') }}
                                                </td>
                                                <td
                                                    class="text-end fw-bold {{ $history->dipakai < 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $history->dipakai < 0 ? '' : '+' }}{{ number_format($historyDipakaiKecil, 2) }}
                                                    {{ $satuanKecil }}
                                                </td>
                                                <td class="text-center fw-bold text-primary">
                                                    {{ $history->data_pesanan ?: '-' }}</td>
                                                <td>
                                                    @if ($history->dipakai < 0)
                                                        <span
                                                            class="badge bg-flat-success text-white px-2 py-1">KOREKSI/RETURN</span>
                                                    @endif
                                                    {{ $history->keterangan ?: '-' }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($history->dipakai > 0)
                                                        <button type="button"
                                                            class="btn btn-sm btn-flat-danger px-2 py-1 btn-return-item"
                                                            data-id-detail="{{ $history->id }}"
                                                            data-max-return="{{ $historyDipakaiKecil }}"
                                                            data-pesanan="{{ $history->data_pesanan }}"
                                                            data-satuan="{{ $satuanKecil }}">
                                                            <i class="fas fa-undo-alt"></i> RETURN
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-flat-dark px-2 py-1"
                                                            disabled>
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat
                                                    pemakaian untuk bahan ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-flat-dark px-4 py-2 text-uppercase"
                                data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalDesc{{ $item->id }}" tabindex="-1" role="dialog"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content modal-content-flat shadow-none">
                        <form class="form-swal-confirm" action="{{ route('bahan_produksi.update', $item->id) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header card-header-flat text-white border-0 py-3">
                                <h5 class="modal-title fw-bold text-uppercase">
                                    <i class="fas fa-edit me-2"></i> Edit Deskripsi / NPK
                                </h5>
                                <button type="button" class="btn-close btn-close-white "
                                    data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body py-4 bg-light">
                                <div class="mb-3 mb-0">
                                    <label class="fw-bold text-dark small text-uppercase tracking-wider">Isi
                                        Deskripsi / NPK</label>
                                    <textarea name="untuk_npk" class="form-control form-flat" rows="5" required>{{ is_array($item->untuk_npk) ? implode(', ', $item->untuk_npk) : $item->untuk_npk }}</textarea>
                                    <small class="text-muted mt-1 d-block">Gunakan koma (,) untuk memisahkan item jika
                                        lebih dari satu.</small>
                                </div>
                            </div>
                            <div class="modal-footer bg-white border-0">
                                <button type="submit" class="btn btn-flat-primary w-100 py-3 text-uppercase">
                                    <i class="fas fa-check-circle me-2"></i> Update Deskripsi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach

    <div class="modal fade" id="modalScanner" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-flat shadow-none">
                <div class="modal-header card-header-flat text-white border-0">
                    <h5 class="modal-title fw-bold text-uppercase">Scan QR Code Barang</h5>
                    <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                        id="stopScanner"></button>
                </div>
                <div class="modal-body p-0 bg-dark">
                    <div id="reader"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            if (typeof $.fn.modal === 'object') {
                $.fn.modal.Constructor.prototype.enforceFocus = function() {};
            }

            const searchInput = document.getElementById('searchInput');
            let dataPesananCache = null;

            function initSelect2Pesanan($selectElement) {
                if ($selectElement.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $selectElement.select2({
                    placeholder: "-- Cari Kode Batch / Nama Cetakan --",
                    allowClear: true,
                    dropdownParent: $selectElement.closest('.modal')
                });

                if (!dataPesananCache) {
                    $.get("{{ route('bahan_produksi.get_data') }}", function(res) {
                        if (res.success && res.data) {
                            dataPesananCache = res.data;
                            populateSelect($selectElement, dataPesananCache);
                        }
                    });
                } else {
                    populateSelect($selectElement, dataPesananCache);
                }
            }

            function populateSelect($el, items) {
                $el.empty().append('<option value="">-- Cari Kode Batch / Nama Cetakan --</option>');
                items.forEach(function(row) {
                    const optionText = row.kode_batch + ' - ' + row.nama_cetakan;
                    const newOption = new Option(optionText, row.kode_batch, false, false);
                    $el.append(newOption);
                });
                $el.trigger('change');
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const filterValue = this.value.toLowerCase().trim();
                    const itemCards = document.querySelectorAll('.item-search-card');
                    const groups = document.querySelectorAll('.shipping-group');
                    let totalVisible = 0;

                    itemCards.forEach(card => {
                        const searchBlob = card.getAttribute('data-search');
                        if (searchBlob.includes(filterValue)) {
                            card.classList.remove('d-none');
                            totalVisible++;
                        } else {
                            card.classList.add('d-none');
                        }
                    });

                    groups.forEach(group => {
                        const visibleCardsInGroup = group.querySelectorAll(
                            '.item-search-card:not(.d-none)').length;
                        if (visibleCardsInGroup === 0) {
                            group.classList.add('d-none');
                        } else {
                            group.classList.remove('d-none');
                        }
                    });

                    const noResultsMessage = document.getElementById('noResultsMessage');
                    if (noResultsMessage) {
                        if (totalVisible === 0 && filterValue !== '') {
                            noResultsMessage.classList.remove('d-none');
                        } else {
                            noResultsMessage.classList.add('d-none');
                        }
                    }
                });
            }

            $('.modal-edit-pemakaian').on('input', '.input-jumlah-pakai', function() {
                const $modal = $(this).closest('.modal-edit-pemakaian');
                const terpakaiAwal = parseFloat($modal.data('terpakai-awal')) || 0;
                const jumlahRencana = parseFloat($modal.data('jumlah-rencana')) || 0;
                const inputTambahan = parseFloat($(this).val()) || 0;

                const totalTerpakaiLive = terpakaiAwal + inputTambahan;

                let persenLive = 0;
                if (jumlahRencana > 0) {
                    persenLive = (totalTerpakaiLive / jumlahRencana) * 100;
                }

                if (persenLive > 100) persenLive = 100;

                const $progressBar = $modal.find('.modal-progress-bar');
                const $progressText = $modal.find('.modal-progress-text');

                $progressBar.css('width', persenLive + '%');
                $progressText.text(persenLive.toFixed(1) + '%');

                $progressBar.removeClass('bg-flat-success bg-flat-warning bg-flat-danger');
                if (persenLive > 85) {
                    $progressBar.addClass('bg-flat-danger');
                } else if (persenLive > 50) {
                    $progressBar.addClass('bg-flat-warning');
                } else {
                    $progressBar.addClass('bg-flat-success');
                }
            });

            $('.btn-trigger-edit').on('click', function() {
                const targetModalId = $(this).attr('data-bs-target');
                const $modal = $(targetModalId);

                $modal.find('.input-jumlah-pakai').val(0).trigger('input');

                const $select = $modal.find('.select-pesanan-ajax');
                initSelect2Pesanan($select);
            });

            $('.modal-edit-pemakaian').on('shown.bs.modal', function() {
                const $select = $(this).find('.select-pesanan-ajax');
                initSelect2Pesanan($select);
            });

            const html5QrCode = new Html5Qrcode("reader");
            $('#modalScanner').on('shown.bs.modal', function() {
                html5QrCode.start({
                        facingMode: "environment"
                    }, {
                        fps: 10,
                        aspectRatio: 1.0
                    },
                    (decodedText) => {
                        html5QrCode.stop().then(() => {
                            $('#modalScanner').modal('hide');
                            verifyScan(decodedText);
                        });
                    }
                ).catch(err => {});
            });

            $('#modalScanner').on('hidden.bs.modal', function() {
                html5QrCode.stop().catch(err => {});
            });

            function verifyScan(data) {
                $.post("{{ route('bahan_produksi.verify_scan') }}", {
                    _token: "{{ csrf_token() }}",
                    scanned_data: data
                }).done(function(res) {
                    if (res.success) {
                        const $targetModal = $('#modalTerpakai' + res.item_id);
                        $targetModal.modal('show');
                        $targetModal.find('.input-jumlah-pakai').val(0).trigger('input');
                    }
                }).fail(function(err) {
                    Swal.fire({
                        title: 'Error',
                        text: err.responseJSON.message,
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                });
            }

            $(document).on('click', '.btn-return-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const detailId = $(this).data('id-detail');
                const maxReturn = parseFloat($(this).data('max-return'));
                const dataPesanan = $(this).data('pesanan');
                const satuan = $(this).data('satuan');
                const $modalAsal = $(this).closest('.modal');
                $modalAsal.modal('hide');
                setTimeout(() => {
                    Swal.fire({
                        title: 'Return / Koreksi Pemakaian',
                        html: `<p class="mb-2 text-start small fw-bold text-muted">Pesanan: ${dataPesanan}</p>
                                <input type="number" id="swal-input-return" class="form-control fw-bold text-center" 
                                step="0.01" min="0.01" max="${maxReturn}" value="0.00" style="font-size: 1.5rem; border: 2px solid #e74a3b;">
                                <small class="text-danger fw-bold d-block mt-2">Maksimal yang bisa dikembalikan: ${maxReturn} ${satuan}</small>
                                <textarea id="swal-input-ket" class="form-control mt-3" rows="2" placeholder="Alasan pengembalian/koreksi bahan..."></textarea>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74a3b',
                        cancelButtonColor: '#5a5c69',
                        confirmButtonText: 'PROSES RETURN',
                        cancelButtonText: 'BATAL',
                        focusConfirm: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            const inputReturn = document.getElementById(
                                'swal-input-return');
                            if (inputReturn) {
                                inputReturn.focus();
                                inputReturn.select();
                            }
                        },
                        preConfirm: () => {
                            const inputEl = document.getElementById(
                                'swal-input-return');
                            const ketEl = document.getElementById('swal-input-ket');

                            const jumlahReturn = inputEl ? parseFloat(inputEl.value) :
                                0;
                            const keteranganReturn = ketEl ? ketEl.value : '';

                            if (isNaN(jumlahReturn) || jumlahReturn <= 0) {
                                Swal.showValidationMessage(
                                    'Jumlah return harus lebih dari 0');
                                return false;
                            }
                            if (jumlahReturn > maxReturn) {
                                Swal.showValidationMessage(
                                    `Jumlah return melebihi batas maksimum (${maxReturn})`
                                );
                                return false;
                            }
                            return {
                                jumlah: jumlahReturn,
                                keterangan: keteranganReturn
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ route('bahan_produksi.return') }}",
                                type: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    id_detail: detailId,
                                    jumlah: result.value.jumlah,
                                    keterangan: result.value.keterangan
                                },
                                success: function(res) {
                                    if (res.success) {
                                        Swal.fire({
                                            title: 'Berhasil',
                                            text: res.message,
                                            icon: 'success',
                                            confirmButtonColor: '#4e73df'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    }
                                },
                                error: function(err) {
                                    Swal.fire('Gagal', err.responseJSON
                                            .message ||
                                            'Terjadi kesalahan sistem', 'error')
                                        .then(() => {
                                            $modalAsal.modal('show');
                                        });
                                }
                            });
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            $modalAsal.modal('show');
                        }
                    });
                }, 200);
            });

            $(document).on('click', '.btn-kembali-gudang', function(e) {
                e.preventDefault();

                const produksiId = $(this).data('id-produksi');
                const maxKembali = parseFloat($(this).data('max-kembali')) || 0;
                const namaBarang = $(this).data('nama-barang');
                const satuan = $(this).data('satuan');

                if (maxKembali <= 0) {
                    Swal.fire('Info', 'Tidak ada sisa bahan produksi yang bisa dikembalikan ke gudang.',
                        'info');
                    return;
                }

                Swal.fire({
                    title: 'Kembalikan Sisa Bahan Baku',
                    html: `<p class="mb-2 text-start small fw-bold text-muted">Barang: ${namaBarang}</p>
                            <input type="number" id="swal-input-kembali" class="form-control fw-bold text-center" 
                            step="0.01" min="0.01" max="${maxKembali}" value="${maxKembali.toFixed(2)}" style="font-size: 1.5rem; border: 2px solid #1cc88a;">
                            <small class="text-success fw-bold d-block mt-2">Maksimal sisa bahan baku: ${maxKembali.toFixed(2)} ${satuan}</small>
                            <textarea id="swal-input-ket-kembali" class="form-control mt-3" rows="2" placeholder="Alasan/catatan pengembalian fisik barang ke gudang utama..."></textarea>`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#1cc88a',
                    cancelButtonColor: '#5a5c69',
                    confirmButtonText: 'KEMBALIKAN KE GUDANG',
                    cancelButtonText: 'BATAL',
                    focusConfirm: false,
                    allowOutsideClick: false,
                    preConfirm: () => {
                        const inputEl = document.getElementById('swal-input-kembali');
                        const ketEl = document.getElementById('swal-input-ket-kembali');

                        const jumlahKembali = inputEl ? parseFloat(inputEl.value) : 0;
                        const keteranganKembali = ketEl ? ketEl.value : '';

                        if (isNaN(jumlahKembali) || jumlahKembali <= 0) {
                            Swal.showValidationMessage(
                                'Jumlah pengembalian harus lebih dari 0');
                            return false;
                        }
                        if (jumlahKembali > maxKembali) {
                            Swal.showValidationMessage(
                                `Jumlah melebihi sisa bahan produksi (${maxKembali.toFixed(2)})`
                            );
                            return false;
                        }
                        return {
                            jumlah: jumlahKembali,
                            keterangan: keteranganKembali
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('bahan_produksi.kembali') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                id_produksi: produksiId,
                                jumlah: result.value.jumlah,
                                keterangan: result.value.keterangan
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire({
                                        title: 'Berhasil',
                                        text: res.message,
                                        icon: 'success',
                                        confirmButtonColor: '#1cc88a'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                }
                            },
                            error: function(err) {
                                Swal.fire('Gagal', err.responseJSON.message ||
                                    'Terjadi kesalahan sistem', 'error');
                            }
                        });
                    }
                });
            });

            $(document).on('submit', '.form-swal-confirm', function(e) {
                e.preventDefault();
                var currentForm = this;
                Swal.fire({
                    title: 'Konfirmasi Simpan',
                    text: "Apakah data yang Anda masukkan sudah benar?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4e73df',
                    cancelButtonColor: '#e74a3b',
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        currentForm.submit();
                    }
                });
            });
        });
    </script>
@endsection
