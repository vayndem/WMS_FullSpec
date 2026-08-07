@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="mb-1">Master Bahan</h3>
                    <p class="text-muted mb-0">Lihat posisi stok, layer persediaan, gudang, dan informasi bahan.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @can('create', App\Models\Bahan::class)
                        <a href="{{ route('request.index', ['create' => 1]) }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus me-1"></i>Ajukan Barang Baru
                        </a>
                    @endcan
                    @unless ($financial)
                        <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                            <i class="fa-solid fa-shield-halved me-1"></i>Harga hanya untuk Accounting
                        </span>
                    @endunless
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-5">
                            <label for="filter_kategori" class="form-label">Kategori</label>
                            <select id="filter_kategori" class="form-select">
                                <option value="">Semua kategori</option>
                                @foreach ($kategoris as $kategori)
                                    <option value="{{ $kategori->id }}" @selected($loop->first)>{{ $kategori->katnama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-5">
                            <label for="filter_gudang" class="form-label">Posisi gudang</label>
                            <select id="filter_gudang" class="form-select">
                                <option value="">Semua gudang</option>
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}" @selected($loop->first)>{{ $gudang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary w-100">
                                <i class="fa-solid fa-rotate-left me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="small text-muted"><i class="fa-solid fa-circle-info me-1"></i>Klik Detail untuk melihat
                            seluruh layer stok barang.</div>
                        <div class="d-flex align-items-center gap-2">
                            <label for="material_search" class="visually-hidden">Cari bahan</label>
                            <input id="material_search" class="form-control" type="search" placeholder="Cari bahan..."
                                autocomplete="off">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="table-bahan" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Nama bahan</th>
                                    <th>Kategori</th>
                                    <th>Gudang</th>
                                    <th class="text-end">On hand</th>
                                    <th class="text-end">On purchase</th>
                                    <th class="text-end">Total layer</th>
                                    @if ($financial)
                                        <th class="text-end">Harga rata-rata</th>
                                        <th class="text-end">Nilai persediaan</th>
                                    @endif
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const number = value => Number(value || 0).toLocaleString('id-ID', {
                maximumFractionDigits: 2
            });
            const rupiah = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            const columns = [{
                    data: 'nama',
                    name: 'bahan.nama',
                    className: 'fw-semibold'
                },
                {
                    data: 'kategori_nama',
                    name: 'kategoriBahan.katnama'
                },
                {
                    data: 'gudang_nama',
                    name: 'gudang.nama'
                },
                {
                    data: 'stok_onhand',
                    name: 'bahan.stok_onhand',
                    className: 'text-end',
                    render: (value, type, row) => {
                        if (type !== 'display') return value;
                        const base = `${number(value)} ${row.satuan || ''}`;
                        if (!row.satuan_kecil || Number(row.berat_kecil || 1) <= 1) return base;
                        const small = Number(value || 0) * Number(row.berat_kecil);
                        return `<span class="fw-semibold">${base}</span><small class="d-block text-muted">= ${number(small)} ${row.satuan_kecil}</small>`;
                    }
                },
                {
                    data: 'stok_onpurchase',
                    name: 'bahan.stok_onpurchase',
                    className: 'text-end',
                    render: number
                },
                {
                    data: 'layer_quantity',
                    name: 'layer_quantity',
                    className: 'text-end',
                    render: number
                },
                @if ($financial)
                    {
                        data: 'average_cost',
                        name: 'average_cost',
                        className: 'text-end',
                        render: rupiah
                    }, {
                        data: 'inventory_value',
                        name: 'inventory_value',
                        className: 'text-end fw-semibold',
                        render: rupiah
                    },
                @endif {
                    data: 'stock_status',
                    name: 'stock_status',
                    orderable: false,
                    searchable: false,
                    render: status =>
                        `<span class="badge ${status === 'VALID' ? 'bg-success' : 'bg-danger'}">${status}</span>`
                },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: (id, type, row) => {
                        let buttons = `<a href="{{ url('bahan') }}/${id}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye me-1"></i>Detail</a>`;
                        if (row.can_update) {
                            buttons += ` <a href="{{ url('bahan') }}/${id}/edit" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen me-1"></i>Edit</a>`;
                        }
                        return buttons;
                    }
                }
            ];

            const table = $('#table-bahan').DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 250,
                dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"lip>',
                ajax: {
                    url: "{{ route('bahan.index') }}",
                    data: data => {
                        data.kategori_id = document.getElementById('filter_kategori').value;
                        data.gudang_id = document.getElementById('filter_gudang').value;
                    }
                },
                columns
            });

            let searchTimer;
            document.getElementById('material_search').addEventListener('input', event => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => table.search(event.target.value).draw(), 250);
            });
            ['filter_kategori', 'filter_gudang'].forEach(id => {
                document.getElementById(id).addEventListener('change', () => table.ajax.reload());
            });
            document.getElementById('reset_filter').addEventListener('click', () => {
                const defaultKategori = document.querySelector('#filter_kategori option[value]:not([value=""])');
                const defaultGudang = document.querySelector('#filter_gudang option[value]:not([value=""])');
                document.getElementById('filter_kategori').value = defaultKategori ? defaultKategori.value : '';
                document.getElementById('filter_gudang').value = defaultGudang ? defaultGudang.value : '';
                document.getElementById('material_search').value = '';
                table.search('').ajax.reload();
            });
        });
    </script>
@endpush
