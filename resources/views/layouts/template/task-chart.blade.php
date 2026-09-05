<div class="card border border-base-300 bg-base-100 p-4 shadow-sm"
    x-data="taskChart({ type: '{{ $chartType ?? 'bar' }}', horizontal: {{ ($horizontal ?? true) ? 'true' : 'false' }}, labels: @js($tasks->pluck('label')), data: @js($tasks->pluck('count')), label: 'Jumlah' })">
    <h6 class="mb-2 font-bold">{{ $chartTitle ?? 'Ringkasan Tugas' }}</h6>
    <div style="height: 280px;"><canvas x-ref="canvas"></canvas></div>
</div>
