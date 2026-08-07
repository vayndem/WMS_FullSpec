@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h4>Purchase Order Jasa</h4>
                    <p class="text-muted mb-0">PO jasa selalu terpisah dari PO barang.</p>
                </div>
                <div class="d-flex gap-2">
                    @can('viewAny', App\Models\ServiceCategory::class)
                        <a href="{{ route('service-categories.index') }}" class="btn btn-outline-primary">Kategori & COA</a>
                    @endcan
                    <a href="{{ route('service-purchases.create') }}" class="btn btn-primary">
                        + Buat PO Jasa</a>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form class="d-flex flex-wrap justify-content-end gap-2 mb-3">
                        <select name="per_page" class="form-select w-auto" onchange="this.form.submit()"
                            aria-label="Jumlah data per halaman">
                            @foreach ([10, 20, 50, 100, 'all'] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                    {{ $size === 'all' ? 'Semua' : $size }} data
                                </option>
                            @endforeach
                        </select><a class="btn btn-danger"
                            href="{{ route('service-purchases.report.pdf', request()->query()) }}"><i
                                class="fa-solid fa-file-pdf"></i> PDF</a><input name="q" value="{{ request('q') }}"
                            onchange="this.form.submit()" class="form-control" style="max-width:280px"
                            placeholder="Cari PO/supplier..."></form>
                    <div class="table-responsive">
                        <table class="table table-hover"
                            data-row-start="{{ $orders->firstItem() ? $orders->firstItem() - 1 : 0 }}">
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
                                        <td class="fw-semibold">{{ $po->no_po }}</td>
                                        <td>{{ $po->tanggal }}</td>
                                        <td>{{ $po->supplier->nama }}</td>
                                        <td class="text-end">Rp
                                            {{ number_format($po->service_details_sum_subtotal, 0, ',', '.') }}</td>
                                        <td class="text-end"><a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('service-purchases.show', $po) }}">Detail</a></td>
                                </tr>@empty<tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada PO jasa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>{{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
    @include('layouts.template.page-help', [
        'title' => 'PO Jasa',
        'items' => [
            'PO jasa tidak boleh mencampur barang.',
            'Jasa operasional dan produksi menggunakan mapping COA berbeda.',
            'BAP menandai pekerjaan mulai; invoice menandai pekerjaan selesai 100%.',
        ],
    ])
@endsection
