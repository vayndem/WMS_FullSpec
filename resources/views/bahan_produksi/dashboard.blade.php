@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid p-4">

            <div class="card shadow-none border-0 mb-4 bg-transparent">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="m-0 fw-bold text-primary text-uppercase tracking-wider">
                        <i class="fas fa-tachometer-alt me-2"></i> Ringkasan Stok Gudang Ongoing
                    </h3>
                    <a href="{{ route('bahan_produksi.index') }}" class="btn btn-flat-dark px-4 py-2 mt-2 mt-sm-0">
                        <i class="fas fa-arrow-left me-2"></i> KEMBALI KE LIST
                    </a>
                </div>
            </div>

            <div class="card filter-card mb-4">
                <div class="card-body p-3 bg-white">
                    <form action="{{ route('bahan_produksi.dashboard') }}" method="GET" id="filterDashboardForm">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="fw-bold text-primary small text-uppercase">Filter Gudang</label>
                                <select name="gudang" class="form-control form-flat"
                                    onchange="document.getElementById('filterDashboardForm').submit()">
                                    <option value="">Semua Gudang</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}"
                                            {{ (string) request('gudang', optional($gudangs->first())->id) === (string) $gudang->id ? 'selected' : '' }}>
                                            {{ $gudang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="fw-bold text-primary small text-uppercase">Filter Kategori</label>
                                <select name="kategori" class="form-control form-flat"
                                    onchange="document.getElementById('filterDashboardForm').submit()">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->katid }}"
                                            {{ (string) request('kategori', optional($categories->first())->katid) === (string) $cat->katid ? 'selected' : '' }}>
                                            {{ $cat->katnama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-primary small text-uppercase">Pencarian Nama
                                    Bahan</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control form-flat"
                                        placeholder="Ketik nama bahan dan tekan enter untuk mencari..."
                                        value="{{ request('search') }}">
                                    @if (request('search') || request('kategori') || request('gudang'))
                                        <div class="d-flex">
                                            <a href="{{ route('bahan_produksi.dashboard') }}"
                                                class="btn btn-flat-dark d-flex align-items-center">RESET</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                @forelse ($summary as $row)
                    @php
                        $masterBahan = DB::table('bahan')->where('id', $row->nama)->first();
                        $faktorKonversi = floatval($masterBahan->berat_kecil ?? 1);
                        $satuanKecil = $masterBahan->satuan_kecil ?? $row->satuan;
                        $sisaStokKecil = $row->sisa_stok * $faktorKonversi;
                        $totalMasukKecil = $row->total_masuk * $faktorKonversi;
                        $totalTerpakaiKecil = $row->total_terpakai * $faktorKonversi;

                        $isZero = $sisaStokKecil <= 0;
                        $boxBorderColor = $isZero ? '#858796' : '#1cc88a';
                        $boxBgClass = $isZero ? 'bg-secondary text-white' : 'bg-light text-success';
                        $badgeClass = $isZero ? 'bg-secondary' : 'bg-success';
                        $badgeText = $isZero ? 'KOSONG' : 'LIVE STOK';
                    @endphp

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card item-card h-100 shadow-none p-4 card-brutal-hover"
                            style="border: 2px solid {{ $boxBorderColor }} !important; box-shadow: 0 6px 0 {{ $boxBorderColor }}; cursor: pointer;"
                            data-border-color="{{ $boxBorderColor }}" data-bs-toggle="modal"
                            data-bs-target="#modalDetail{{ $row->nama }}">

                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="pe-2">
                                    <h5 class="fw-bold text-dark text-uppercase m-0">{{ $row->nama_barang }}</h5>
                                    <span
                                        class="badge bg-light border text-muted small mt-1 fw-bold">{{ $row->katnama }}</span>
                                </div>
                                <span
                                    class="badge {{ $badgeClass }} px-3 py-1 text-uppercase fw-bold">{{ $badgeText }}</span>
                            </div>

                            <div class="py-3 text-center rounded mb-3 {{ $boxBgClass }} border"
                                style="border-color: {{ $boxBorderColor }} !important;">
                                <span
                                    class="small fw-bold d-block text-uppercase {{ $isZero ? 'text-white-50' : 'text-muted' }}">
                                    Sisa di Gudang Pakai
                                </span>
                                <h1 class="display-4 fw-bold m-0">{{ number_format($sisaStokKecil, 2) }}</h1>
                                <span
                                    class="badge badge-flat px-3 py-1 mt-1 fw-bold text-dark">{{ $satuanKecil }}</span>
                            </div>

                            <div class="text-center">
                                <small class="text-muted fw-bold text-uppercase">Klik card ini untuk melihat detail
                                    alokasi</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modalDetail{{ $row->nama }}" tabindex="-1" role="dialog"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-xl" role="document" style="max-width: 90%;">
                            <div class="modal-content modal-content-flat shadow-none"
                                style="border-color: {{ $boxBorderColor }};">
                                <div class="modal-header text-white border-0 py-3"
                                    style="background-color: {{ $boxBorderColor }};">
                                    <h5 class="modal-title fw-bold text-uppercase">
                                        <i class="fas fa-search-location me-2"></i> Detail Pelacakan Alokasi:
                                        {{ $row->nama_barang }}
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white "
                                        data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4 bg-light">

                                    <div class="row mb-4">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="p-3 bg-white border rounded text-center"
                                                style="border: 2px solid #4e73df !important; box-shadow: 0 4px 0 #4e73df;">
                                                <span class="small fw-bold text-muted text-uppercase d-block">Total
                                                    Asli Masuk</span>
                                                <h3 class="fw-bold text-primary m-0">
                                                    {{ number_format($totalMasukKecil, 2) }} {{ $satuanKecil }}
                                                </h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 bg-white border rounded text-center"
                                                style="border: 2px solid #e74a3b !important; box-shadow: 0 4px 0 #e74a3b;">
                                                <span class="small fw-bold text-muted text-uppercase d-block">Total
                                                    Keluar Produksi</span>
                                                <h3 class="fw-bold text-danger m-0">
                                                    {{ number_format($totalTerpakaiKecil, 2) }} {{ $satuanKecil }}
                                                </h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-5 mb-4 mb-lg-0">
                                            <h6 class="fw-bold text-dark text-uppercase mb-2">
                                                <i class="fas fa-file-import text-primary me-2"></i> 1. Sumber Dokumen
                                                Masuk:
                                            </h6>
                                            <div class="table-responsive w-100 rounded border bg-white"
                                                style="border: 2px solid #d1d3e2 !important; max-height: 350px; overflow-y: auto;">
                                                <table class="table table-bordered table-flat m-0 w-100">
                                                    <thead class="bg-light position-sticky style-top"
                                                        style="top: 0; z-index: 10;">
                                                        <tr>
                                                            <th class="text-center small fw-bold">TANGGAL</th>
                                                            <th class="text-center small fw-bold">KODE BOK</th>
                                                            <th class="text-center small fw-bold">REFERENSI NPK
                                                            </th>
                                                            <th class="text-center small fw-bold">JUMLAH</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($row->sumber_npk as $sumber)
                                                            @php
                                                                $sumberJumlahKecil = $sumber->jumlah * $faktorKonversi;
                                                            @endphp
                                                            <tr>
                                                                <td class="text-center small text-secondary">
                                                                    {{ $sumber->created_at ? date('d/m/Y', strtotime($sumber->created_at)) : '-' }}
                                                                </td>
                                                                <td
                                                                    class="text-center fw-bold small text-secondary">
                                                                    {{ $sumber->kode ?: '-' }}
                                                                </td>
                                                                <td
                                                                    class="text-center fw-semibold small text-primary">
                                                                    {{ $sumber->npk_string ?: '-' }}
                                                                </td>
                                                                <td class="text-end fw-bold text-primary small">
                                                                    {{ number_format($sumberJumlahKecil, 2) }}
                                                                    {{ $satuanKecil }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-lg-7">
                                            <h6 class="fw-bold text-dark text-uppercase mb-2">
                                                <i class="fas fa-database text-danger me-2"></i> 2. Alokasi Penggunaan
                                                Keluar:
                                            </h6>
                                            <div class="table-responsive w-100 rounded border bg-white"
                                                style="border: 2px solid #d1d3e2 !important; max-height: 350px; overflow-y: auto;">
                                                <table class="table table-bordered table-flat m-0 w-100">
                                                    <thead class="bg-light position-sticky style-top"
                                                        style="top: 0; z-index: 10;">
                                                        <tr>
                                                            <th class="text-center small fw-bold"
                                                                style="width: 50px;">NO</th>
                                                            <th class="text-center small fw-bold">TANGGAL</th>
                                                            <th class="text-center small fw-bold">KODE PESANAN
                                                            </th>
                                                            <th class="text-center small fw-bold">TERPAKAI</th>
                                                            <th class="small fw-bold">CATATAN</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($row->riwayat_detail as $index => $detail)
                                                            @php
                                                                $detailDipakaiKecil =
                                                                    $detail->dipakai * $faktorKonversi;
                                                            @endphp
                                                            <tr>
                                                                <td class="text-center fw-bold small text-muted">
                                                                    {{ $index + 1 }}
                                                                </td>
                                                                <td class="text-center small text-secondary">
                                                                    {{ $detail->created_at ? date('d/m/Y H:i', strtotime($detail->created_at)) : '-' }}
                                                                </td>
                                                                <td
                                                                    class="text-center fw-bold small text-primary">
                                                                    {{ $detail->data_pesanan ?: 'Tanpa Kode' }}
                                                                </td>
                                                                <td
                                                                    class="text-end fw-bold small {{ $detail->dipakai < 0 ? 'text-success' : 'text-danger' }}">
                                                                    {{ $detail->dipakai < 0 ? '' : '+' }}{{ number_format($detailDipakaiKecil, 2) }}
                                                                    {{ $satuanKecil }}
                                                                </td>
                                                                <td class="small text-secondary">{{ $detail->keterangan }}
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5"
                                                                    class="text-center py-4 text-muted small">
                                                                    Belum ada riwayat pemakaian produksi.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer bg-white border-0">
                                    <button type="button" class="btn btn-flat-dark px-4 py-2 text-uppercase"
                                        data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                @empty
                    <div class="col-12 text-center py-5 bg-white filter-card mt-2">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <h5 class="fw-bold text-dark mb-0">Belum ada rangkuman data stok di gudang ini.</h5>
                    </div>
                @endforelse
            </div>

        </div>
    </div>

    <style>
        .item-card {
            transition: transform 0.15s ease-in-out;
            border-radius: 12px !important;
            background: #ffffff;
        }

        .item-card:hover {
            transform: translateY(-4px);
        }

        .filter-card {
            border: 2px solid #4e73df;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 0 #4e73df;
        }

        .style-top {
            top: 0;
            z-index: 10;
        }
    </style>

    <script>
        document.querySelectorAll('.card-brutal-hover').forEach(function(card) {
            var color = card.getAttribute('data-border-color');
            card.addEventListener('mouseenter', function() {
                card.style.boxShadow = '0 10px 0 ' + color;
            });
            card.addEventListener('mouseleave', function() {
                card.style.boxShadow = '0 6px 0 ' + color;
            });
        });
    </script>
@endsection
