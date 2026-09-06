<div class="card border border-base-300 bg-base-100 p-4 shadow-sm" x-data="taskChart({ type: '{{ $chartType ?? 'bar' }}', horizontal: {{ $horizontal ?? false ? 'true' : 'false' }}, beginAtZero: {{ $beginAtZero ?? true ? 'true' : 'false' }}, labels: @js($labels), data: @js($data), label: '{{ $seriesLabel ?? 'Nilai' }}' })">
    <h6 class="mb-2 font-bold">{{ $chartTitle ?? 'Grafik' }}</h6>
    <div style="height: 280px;"><canvas x-ref="canvas"></canvas></div>
</div>
