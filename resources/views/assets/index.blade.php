@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h4 class="mb-1">Asset Tetap</h4>
                    <p class="text-muted mb-0">Register fisik, nilai buku, dan penyusutan asset perusahaan.</p>
                </div>
                @can('create', App\Models\Asset::class)
                    <div class="d-flex gap-2"><a class="btn btn-outline-primary"
                            href="{{ route('asset-categories.index') }}">Kategori & COA</a><a class="btn btn-primary"
                            href="{{ route('assets.create') }}"><i class="fa-solid fa-plus me-1"></i>Tambah Asset</a></div>
                @endcan
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form class="row g-2 justify-content-end mb-3">
                        <div class="col-auto">
                            <select name="per_page" class="form-select" onchange="this.form.submit()"
                                aria-label="Jumlah data per halaman">
                                @foreach ([10, 20, 50, 100, 'all'] as $size)
                                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                        {{ $size === 'all' ? 'Semua' : $size }} data
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto"><a class="btn btn-danger"
                                href="{{ route('assets.report.pdf', request()->query()) }}"><i
                                    class="fa-solid fa-file-pdf me-1"></i>PDF</a></div>
                        <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control"
                                placeholder="Cari asset..." onchange="this.form.submit()"></div>
                        <div class="col-md-2"><select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua status</option>
                                @foreach (['ACTIVE' => 'Aktif', 'SOLD' => 'Terjual', 'DISPOSED' => 'Dihapus'] as $k => $v)
                                    <option value="{{ $k }}" @selected(request('status', 'ACTIVE') === $k)>{{ $v }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle"
                            data-row-start="{{ $assets->firstItem() ? $assets->firstItem() - 1 : 0 }}">
                            <thead>
                                <tr>
                                    <th>No Asset</th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Kondisi</th>
                                    @if ($financial)
                                        <th class="text-end">Harga Perolehan</th>
                                        <th class="text-end">Nilai Buku</th>
                                    @endif
                                    <th>
                                        Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $asset)
                                    <tr>
                                        <td class="fw-semibold text-primary">{{ $asset->asset_number }}</td>
                                        <td>{{ $asset->name }}</td>
                                        <td>{{ $asset->category->name }}</td>
                                        <td>{{ $asset->location ?: '-' }}</td>
                                        <td>{{ $asset->condition }}</td>
                                        @if ($financial)
                                            <td class="text-end">Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}
                                            </td>
                                            <td class="text-end">Rp {{ number_format($asset->book_value, 0, ',', '.') }}</td>
                                        @endif
                                        <td>
                                            <span
                                                class="badge bg-{{ $asset->status === 'ACTIVE' ? 'success' : 'secondary' }}">{{ $asset->status }}</span>
                                        </td>
                                        <td class="text-end"><a href="{{ route('assets.show', $asset) }}"
                                                class="btn btn-sm btn-outline-primary">Detail</a></td>
                                    </tr>
                                @empty<tr>
                                        <td colspan="10" class="text-center text-muted py-4">Belum ada asset.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>{{ $assets->links() }}
                </div>
            </div>
        </div>
    </div>
    @include('layouts.template.page-help', [
        'title' => 'Cara menggunakan Asset',
        'items' => [
            'Semua user dapat melihat identitas asset.',
            'Nilai finansial hanya terlihat untuk Purchasing (5) dan Accounting (33).',
            'Hanya Accounting yang dapat membuat, mengubah, menyusutkan, menjual, atau menghapus asset.',
        ],
    ])
@endsection
