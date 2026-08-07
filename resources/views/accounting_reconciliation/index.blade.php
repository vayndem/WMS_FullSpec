@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="mb-4">
                <h4 class="mb-1">Rekonsiliasi WMS</h4>
                <p class="text-muted mb-0">Pemeriksaan otomatis konsistensi stok, layer, invoice, dan jurnal.</p>
            </div>
            <div class="row g-3">
                @foreach ($checks as $check)
                    <div class="col-12 col-md-6 col-xl-4">
                        <a href="{{ route('reconciliation.show', $check['key']) }}"
                            class="card h-100 border-0 shadow-sm text-decoration-none text-reset">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h6 class="mb-2">{{ $check['label'] }}</h6><span
                                            class="badge {{ $check['invalid'] ? 'bg-danger' : 'bg-success' }}">{{ $check['invalid'] ? 'TIDAK VALID' : 'VALID' }}</span>
                                    </div>
                                    <span
                                        class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $check['invalid'] ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}"
                                        style="width:42px;height:42px"><i
                                            class="fa-solid {{ $check['invalid'] ? 'fa-triangle-exclamation' : 'fa-check' }}"></i></span>
                                </div>
                                <hr>
                                <div class="small text-muted">{{ $check['invalid'] }} bermasalah dari {{ $check['total'] }}
                                    pemeriksaan</div>
                                @if ($financial && array_key_exists('amount', $check) && $check['amount'] !== null)
                                    <div class="mt-2 fw-semibold">Aktual: Rp
                                        {{ number_format($check['amount'], 2, ',', '.') }}
                                    </div>
                                @endif
                                @if ($financial && array_key_exists('expected', $check) && $check['expected'] !== null)
                                    <div class="small text-muted">Seharusnya: Rp
                                        {{ number_format($check['expected'], 2, ',', '.') }}</div>
                                @endif
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
            @unless ($financial)
                <div class="rounded-3 border border-info-subtle bg-info-subtle text-info-emphasis p-3 mt-4"><i
                        class="fa-solid fa-shield-halved me-2"></i>Nilai rupiah hanya tersedia untuk Accounting (type 33).
                    Data kuantitas tetap dapat diperiksa.</div>
            @endunless
        </div>
    </div>
@endsection
