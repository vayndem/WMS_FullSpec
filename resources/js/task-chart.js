import Chart from 'chart.js/auto';

const PALETTE = ['#2563eb', '#f59e0b', '#dc2626', '#16a34a', '#7c3aed', '#0891b2', '#db2777', '#65a30d'];

export default function taskChart(config) {
    return {
        chart: null,
        init() {
            const labels = config.labels || [];
            const data = config.data || [];
            const type = config.type || 'bar';

            this.chart = new Chart(this.$refs.canvas, {
                type,
                data: {
                    labels,
                    datasets: [{
                        label: config.label || 'Jumlah',
                        data,
                        backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
                        borderRadius: type === 'bar' ? 6 : 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: config.horizontal ? 'y' : 'x',
                    plugins: {
                        legend: { display: type !== 'bar', position: 'bottom' },
                    },
                    scales: type === 'bar' ? {
                        x: config.horizontal ? { beginAtZero: true, ticks: { precision: 0 } } : {},
                        y: config.horizontal ? {} : { beginAtZero: true, ticks: { precision: 0 } },
                    } : {},
                },
            });
        },
    };
}
