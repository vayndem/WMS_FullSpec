@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">Penerimaan Jasa</h3>
                <p class="text-base-content/60">Penerimaan jasa menandai pekerjaan dimulai; invoice menandai pekerjaan selesai.</p>
            </div>
            <a href="{{ route('penerimaan-jasa.create') }}" class="btn btn-primary">+ Buat Penerimaan Jasa</a>
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="p-4">
                <form class="flex flex-wrap justify-end gap-2">
                    <select name="per_page" class="select select-bordered w-auto" onchange="this.form.submit()" aria-label="Jumlah data per halaman">
                        @foreach ([10, 20, 50, 100, 'all'] as $size)
                            <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size === 'all' ? 'Semua' : $size }} data</option>
                        @endforeach
                    </select>
                    <a class="btn btn-error" href="{{ route('penerimaan-jasa.report.pdf', request()->query()) }}"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                    <a class="btn btn-success" href="{{ route('penerimaan-jasa.report.excel', request()->query()) }}"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <input name="q" value="{{ request('q') }}" onchange="this.form.submit()" class="input input-bordered max-w-xs" placeholder="Cari nomor penerimaan jasa...">
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="table" data-row-start="{{ $baps->firstItem() ? $baps->firstItem() - 1 : 0 }}">
                    <thead>
                        <tr>
                            <th>No Penerimaan Jasa</th>
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
                                <td class="font-semibold">{{ $bap->id_lpb }}</td>
                                <td>{{ $bap->tanggal->format('d-m-Y') }}</td>
                                <td>{{ $bap->no_po }}</td>
                                <td>{{ $bap->pembelian->supplier->nama }}</td>
                                <td>
                                    <span class="badge {{ $bap->status === \App\Models\PenerimaanBarang::CANCELLED ? 'badge-error' : ($bap->invoiceReceipts->isNotEmpty() ? 'badge-success' : 'badge-warning') }}">
                                        {{ $bap->status === \App\Models\PenerimaanBarang::CANCELLED ? 'Dibatalkan' : ($bap->invoiceReceipts->isNotEmpty() ? 'Selesai / Sudah Invoice' : 'Sedang Dikerjakan') }}
                                    </span>
                                </td>
                                <td class="text-end"><a class="btn btn-outline btn-primary btn-sm" href="{{ route('penerimaan-jasa.show', $bap) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-base-content/50">Belum ada penerimaan jasa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $baps->links() }}</div>
        </div>
    </div>
@endsection
