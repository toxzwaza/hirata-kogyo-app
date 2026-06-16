<script setup>
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';
import { computed } from 'vue';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale);

const props = defineProps({
    labels: { type: Array, required: true },
    datasets: { type: Array, required: true }, // [{ label, data, borderColor, yAxisID }]
    dualAxis: { type: Boolean, default: false },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets,
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: true, position: 'bottom' },
    },
    scales: props.dualAxis
        ? {
              y: { type: 'linear', position: 'left', beginAtZero: true },
              y1: {
                  type: 'linear',
                  position: 'right',
                  beginAtZero: true,
                  grid: { drawOnChartArea: false },
              },
          }
        : {
              y: { beginAtZero: true },
          },
}));
</script>

<template>
    <div class="relative h-72">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
