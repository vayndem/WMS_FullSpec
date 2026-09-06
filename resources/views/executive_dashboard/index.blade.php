@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Dashboard Eksekutif</h3>
                <p class="text-base-content/60">Ringkasan tren dan komposisi keuangan untuk pengambilan keputusan.</p>
            </div>
            <a href="{{ route('financial-statements.neraca-saldo') }}" class="btn btn-outline btn-primary">
                <i class="fa-solid fa-file-invoice"></i> Laporan Keuangan Detail
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @include('layouts.template.metric-chart', [
                'chartTitle' => 'Tren Nilai Persediaan (6 Bulan Terakhir)',
                'chartType' => 'line',
                'horizontal' => false,
                'beginAtZero' => true,
                'labels' => $inventoryTrend['labels'],
                'data' => $inventoryTrend['data'],
                'seriesLabel' => 'Nilai Persediaan (Rp)',
            ])

            @include('layouts.template.metric-chart', [
                'chartTitle' => 'Aging Hutang Supplier',
                'chartType' => 'bar',
                'horizontal' => false,
                'beginAtZero' => true,
                'labels' => $apAging['labels'],
                'data' => $apAging['data'],
                'seriesLabel' => 'Sisa Tagihan (Rp)',
            ])

            @include('layouts.template.metric-chart', [
                'chartTitle' => 'Top 5 Supplier (Nilai Invoice)',
                'chartType' => 'bar',
                'horizontal' => true,
                'beginAtZero' => true,
                'labels' => $topSuppliers['labels'],
                'data' => $topSuppliers['data'],
                'seriesLabel' => 'Total Invoice (Rp)',
            ])

            @include('layouts.template.metric-chart', [
                'chartTitle' => 'Biaya per Kategori Bahan (Tahun Ini)',
                'chartType' => 'doughnut',
                'labels' => $costByCategory['labels'],
                'data' => $costByCategory['data'],
                'seriesLabel' => 'Beban (Rp)',
            ])
        </div>
    </div>
@endsection
