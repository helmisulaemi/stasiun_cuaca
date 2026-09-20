<template>
  <div class="bg-white rounded-lg shadow p-4">
    <h3 class="text-sm font-medium text-gray-700 mb-4">Kecepatan Angin (m/s)</h3>
    <div class="h-48">
      <Line v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Line } from 'vue-chartjs'

const props = defineProps<{
  timestamps: string[]
  values: number[]
}>()

const { toWIB, toWIBShort } = useWib()

const chartData = computed(() => {
  if (!props.timestamps.length) return null

  return {
    labels: props.timestamps,
    datasets: [
      {
        label: 'Wind Speed',
        data: props.values,
        borderColor: '#10b981',
        backgroundColor: '#10b98120',
        borderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 5,
        tension: 0.1,
        spanGaps: false,
        fill: true,
      },
    ],
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: {
      ticks: {
        maxTicksLimit: 10,
        callback: function(val: any) {
          const ts = props.timestamps[val]
          return ts ? toWIBShort(ts) : ''
        },
      },
    },
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'm/s',
      },
    },
  },
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        title: function(items: any[]) {
          const ts = props.timestamps[items[0].dataIndex]
          return ts ? toWIB(ts) : ''
        },
      },
    },
  },
}))
</script>
