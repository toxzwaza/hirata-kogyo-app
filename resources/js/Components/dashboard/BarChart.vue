<script setup>
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';
import { computed } from 'vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    labels: { type: Array, required: true },
    datasets: { type: Array, required: true }, // [{ label, data, backgroundColor }]
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets,
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'bottom' },
    },
    scales: {
        y: { beginAtZero: true },
    },
};
</script>

<template>
    <div class="relative h-72">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
