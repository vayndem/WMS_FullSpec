@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">Purchase Order Jasa</h3>
                <p class="text-base-content/60">PO jasa selalu terpisah dari PO barang.</p>
            </div>
            <div class="flex gap-2">
                @can('viewAny', App\Models\ServiceCategory::class)
                    <a href="{{ route('service-categories.index') }}" class="btn btn-outline btn-primary">Kategori & COA</a>
                @endcan
                <a href="{{ route('service-purchases.create') }}" class="btn btn-primary">+ Buat PO Jasa</a>
            </div>
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="p-4">
                <form class="flex flex-wrap justify-end gap-2">
                    <select name="per_page" class="select select-bordered w-auto" onchange="this.form.submit()" aria-label="Jumlah data per halaman">
                        @foreach ([10, 20, 50, 100, 'all'] as $size)
                            <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size === 'all' ? 'Semua' : $size }} data</option>
                        @endforeach
                    </select>
                    <a class="btn btn-error" href="{{ route('service-purchases.report.pdf', request()->query()) }}"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                    <input name="q" value="{{ request('q') }}" onchange="this.form.submit()" class="input input-bordered max-w-xs" placeholder="Cari PO/supplier...">
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="table" data-row-start="{{ $orders->firstItem() ? $orders->firstItem() - 1 : 0 }}">
                    <thead>
                        <tr>
                            <th>No PO</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th class="text-end">Nilai</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $po)
                            <tr>
                                <td class="font-semibold">{{ $po->no_po }}</td>
                                <td>{{ $po->tanggal }}</td>
                                <td>{{ $po->supplier->nama }}</td>
                                <td class="text-end">Rp {{ number_format($po->service_details_sum_subtotal, 0, ',', '.') }}</td>
                                <td class="text-end"><a class="btn btn-outline btn-primary btn-sm" href="{{ route('service-purchases.show', $po) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-base-content/50">Belum ada PO jasa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $orders->links() }}</div>
        </div>
    </div>
    @include('layouts.template.page-help', [
        'title' => 'PO Jasa',
        'items' => [
            'PO jasa tidak boleh mencampur barang.',
            'Jasa operasional dan produksi menggunakan mapping COA berbeda.',
            'Penerimaan jasa menandai pekerjaan mulai; invoice menandai pekerjaan selesai 100%.',
        ],
    ])
@endsection
