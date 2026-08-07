@extends('layouts.app')
@section('content')
    @php($hasActiveBap = $po->serviceDetails->flatMap->bapDetails->contains(fn ($detail) => !$detail->lpb?->is_cancelled))
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h4>{{ $po->no_po }}</h4>
                    <p class="text-muted">{{ $po->supplier->nama }} · {{ $po->tanggal }}</p>
                </div>
                @unless($hasActiveBap)
                    <a href="{{ route('service-baps.create', ['po' => $po->id]) }}" class="btn btn-primary">Buat BAP</a>
                @endunless
            </div>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
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
                                        <small class="d-block text-muted">{{ $d->kategori->katnama ?? 'Belum dimapping ke kategori bahan' }}</small>
                                    </td>
                                    <td>{{ $d->description }}</td>
                                    @php($bapDetail = $d->bapDetails->first())
                                    <td>
                                        @if (!$bapDetail)
                                            <span class="badge bg-secondary">Belum dimulai</span>
                                        @elseif ($bapDetail->lpb?->no_invoice)
                                            <span class="badge bg-success">Selesai / Sudah invoice</span>
                                        @else
                                            <span class="badge bg-warning">Sedang dikerjakan</span>
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
    </div>
@endsection
