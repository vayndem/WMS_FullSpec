@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Rekonsiliasi WMS</h3>
            <p class="text-base-content/60">Pemeriksaan otomatis konsistensi stok, layer, invoice, dan jurnal.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($checks as $check)
                <a href="{{ route('reconciliation.show', $check['key']) }}" class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h6 class="mb-2 font-bold">{{ $check['label'] }}</h6>
                                <span class="badge {{ $check['invalid'] ? 'badge-error' : 'badge-success' }}">{{ $check['invalid'] ? 'TIDAK VALID' : 'VALID' }}</span>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-full {{ $check['invalid'] ? 'bg-error/10 text-error' : 'bg-success/10 text-success' }}">
                                <i class="fa-solid {{ $check['invalid'] ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                            </span>
                        </div>
                        <div class="divider my-2"></div>
                        <p class="text-sm text-base-content/50">{{ $check['invalid'] }} bermasalah dari {{ $check['total'] }} pemeriksaan</p>
                        @if ($financial && array_key_exists('amount', $check) && $check['amount'] !== null)
                            <p class="mt-2 font-semibold">Aktual: Rp {{ number_format($check['amount'], 2, ',', '.') }}</p>
                        @endif
                        @if ($financial && array_key_exists('expected', $check) && $check['expected'] !== null)
                            <p class="text-sm text-base-content/50">Seharusnya: Rp {{ number_format($check['expected'], 2, ',', '.') }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        @unless ($financial)
            <div role="alert" class="alert alert-info mt-4">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Nilai rupiah hanya tersedia untuk role Accounting. Data kuantitas tetap dapat diperiksa.</span>
            </div>
        @endunless
    </div>
@endsection
