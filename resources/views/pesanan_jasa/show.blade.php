@extends('layouts.app')
@section('content')
    @php($hasActiveBap = $po->serviceDetails->flatMap->bapDetails->contains(fn($detail) => $detail->lpb?->status === \App\Models\PenerimaanBarang::POSTED))
    <div class="content-page">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">{{ $po->no_po }}</h3>
                <p class="text-base-content/60">{{ $po->supplier->nama }} · {{ $po->tanggal }}</p>
            </div>
            @unless($hasActiveBap)
                <a href="{{ route('penerimaan-jasa.create', ['po' => $po->id]) }}" class="btn btn-primary">Buat Penerimaan Jasa</a>
            @endunless
        </div>
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Uraian</th>
                            <th>Status Pekerjaan</th>
                            @if ($financial)
                                <th class="text-end">Nilai PO</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($po->serviceDetails as $d)
                            <tr>
                                <td>
                                    {{ $d->category->display_code }} - {{ $d->category->name }}
                                    <span class="block text-sm text-base-content/50">{{ $d->kategori->katnama ?? 'Belum dimapping ke kategori bahan' }}</span>
                                </td>
                                <td>{{ $d->description }}</td>
                                @php($bapDetail = $d->bapDetails->first())
                                <td>
                                    @if (!$bapDetail)
                                        <span class="badge badge-ghost">Belum dimulai</span>
                                    @elseif ($bapDetail->lpb?->no_invoice)
                                        <span class="badge badge-success">Selesai / Sudah invoice</span>
                                    @else
                                        <span class="badge badge-warning">Sedang dikerjakan</span>
                                    @endif
                                </td>
                                @if ($financial)
                                    <td class="text-end">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
