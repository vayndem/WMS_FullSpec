@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h4>BAP Jasa</h4>
                    <p class="text-muted mb-0">BAP menandai pekerjaan dimulai; invoice menandai pekerjaan selesai.</p>
                </div><a href="{{ route('service-baps.create') }}" class="btn btn-primary">+ Buat BAP</a>
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
                            href="{{ route('service-baps.report.pdf', request()->query()) }}"><i
                                class="fa-solid fa-file-pdf"></i> PDF</a><input name="q" value="{{ request('q') }}"
                            onchange="this.form.submit()" class="form-control" style="max-width:280px"
                            placeholder="Cari nomor BAP...">
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover"
                            data-row-start="{{ $baps->firstItem() ? $baps->firstItem() - 1 : 0 }}">
                            <thead>
                                <tr>
                                    <th>No BAP</th>
                                    <th>Tanggal</th>
                                    <th>PO Jasa</th>
                                    <th>Supplier</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($baps as $bap)
                                    <tr>
                                        <td>{{ $bap->id_lpb }}</td>
                                        <td>{{ $bap->tanggal->format('d-m-Y') }}</td>
                                        <td>{{ $bap->no_po }}</td>
                                        <td>{{ $bap->pembelian->supplier->nama }}</td>
                                        <td><span
                                                class="badge bg-{{ $bap->is_cancelled ? 'danger' : ($bap->invoiceReceipts->isNotEmpty() ? 'success' : 'warning') }}">
                                                {{ $bap->is_cancelled ? 'Dibatalkan' : ($bap->invoiceReceipts->isNotEmpty() ? 'Selesai / Sudah Invoice' : 'Sedang Dikerjakan') }}
                                            </span>
                                        </td>
                                        <td class="text-end"><a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('service-baps.show', $bap) }}">Detail</a></td>
                                </tr>@empty<tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada BAP.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>{{ $baps->links() }}
                </div>
            </div>
        </div>
    </div>
    @include('layouts.template.page-help', [
        'title' => 'BAP Jasa',
        'items' => [
            'BAP menandai seluruh pekerjaan dalam PO Jasa mulai dikerjakan.',
            'Pembuatan BAP tidak membentuk jurnal.',
            'Saat BAP masuk invoice, pekerjaan menjadi selesai 100% dan beban/WIP serta hutang dijurnal.',
            'BAP belum di-invoice dapat dibatalkan type 5 atau 33.',
        ],
    ])
@endsection
