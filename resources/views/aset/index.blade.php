@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-2xl font-bold">Aset Tetap</h3>
                <p class="text-base-content/60">Register fisik, nilai buku, dan penyusutan aset perusahaan.</p>
            </div>
            @can('create', App\Models\Aset::class)
                <div class="flex gap-2">
                    <a class="btn btn-outline btn-primary" href="{{ route('kategori-aset.index') }}">Kategori & COA</a>
                    <a class="btn btn-primary" href="{{ route('aset.create') }}"><i class="fa-solid fa-plus"></i> Tambah Aset</a>
                </div>
            @endcan
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="p-4">
                <form class="grid grid-cols-1 gap-2 md:grid-cols-6">
                    <select name="per_page" class="select select-bordered" onchange="this.form.submit()" aria-label="Jumlah data per halaman">
                        @foreach ([10, 20, 50, 100, 'all'] as $size)
                            <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size === 'all' ? 'Semua' : $size }} data</option>
                        @endforeach
                    </select>
                    <input name="q" value="{{ request('q') }}" class="input input-bordered md:col-span-2" placeholder="Cari aset..." onchange="this.form.submit()">
                    <select name="status" class="select select-bordered" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        @foreach (['ACTIVE' => 'Aktif', 'SOLD' => 'Terjual', 'DISPOSED' => 'Dihapus'] as $k => $v)
                            <option value="{{ $k }}" @selected(request('status', 'ACTIVE') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <a class="btn btn-error md:col-span-2" href="{{ route('aset.report.pdf', request()->query()) }}"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="table" data-row-start="{{ $assets->firstItem() ? $assets->firstItem() - 1 : 0 }}">
                    <thead>
                        <tr>
                            <th>No Aset</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                            @if ($financial)
                                <th class="text-end">Harga Perolehan</th>
                                <th class="text-end">Nilai Buku</th>
                            @endif
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assets as $asset)
                            <tr>
                                <td class="font-semibold text-primary">{{ $asset->nomor_aset }}</td>
                                <td>{{ $asset->name }}</td>
                                <td>{{ $asset->category->name }}</td>
                                <td>{{ $asset->location ?: '-' }}</td>
                                <td>{{ $asset->condition }}</td>
                                @if ($financial)
                                    <td class="text-end">Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($asset->book_value, 0, ',', '.') }}</td>
                                @endif
                                <td><span class="badge {{ $asset->status === 'ACTIVE' ? 'badge-success' : 'badge-ghost' }}">{{ $asset->status }}</span></td>
                                <td class="text-end"><a href="{{ route('aset.show', $asset) }}" class="btn btn-outline btn-primary btn-sm">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="py-4 text-center text-base-content/50">Belum ada aset.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $assets->links() }}</div>
        </div>
    </div>
    @include('layouts.template.page-help', [
        'title' => 'Cara menggunakan Aset',
        'items' => [
            'Semua user dapat melihat identitas aset.',
            'Nilai finansial hanya terlihat untuk Purchasing (5) dan Accounting (33).',
            'Hanya Accounting yang dapat membuat, mengubah, menyusutkan, menjual, atau menghapus aset.',
        ],
    ])
@endsection
