<template>
  <div class="bg-white rounded-lg shadow p-4">
    <h3 class="text-sm font-medium text-gray-700 mb-4">Curah Hujan (mm)</h3>
    <div class="h-48">
      <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Bar } from 'vue-chartjs'

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
        label: 'Rainfall',
        data: props.values,
        backgroundColor: '#3b82f6',
        borderColor: '#2563eb',
        borderWidth: 1,
        borderRadius: 2,
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
        text: 'mm',
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
