<template>
  <div class="bg-white rounded-lg shadow p-4">
    <h3 class="text-sm font-medium text-gray-700 mb-4">{{ title }}</h3>
    <div class="h-64">
      <Line v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Line } from 'vue-chartjs'

interface Dataset {
  label: string
  values: number[]
  color: string
  yAxisID?: string
}

const props = defineProps<{
  title?: string
  timestamps: string[]
  datasets: Dataset[]
  unitLeft?: string
  unitRight?: string
}>()

const { toWIB, toWIBShort } = useWib()

const chartData = computed(() => {
  if (!props.timestamps.length) return null

  return {
    labels: props.timestamps,
    datasets: props.datasets.map(ds => ({
      label: ds.label,
      data: ds.values,
      borderColor: ds.color,
      backgroundColor: ds.color + '20',
      borderWidth: 2,
      pointRadius: 2,
      pointHoverRadius: 5,
      tension: 0.1,
      spanGaps: false,
      yAxisID: ds.yAxisID || 'y',
    })),
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index' as const,
    intersect: false,
  },
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
      position: 'left' as const,
      title: {
        display: !!props.unitLeft,
        text: props.unitLeft || '',
      },
    },
    ...(props.unitRight ? {
      y1: {
        position: 'right' as const,
        title: {
          display: true,
          text: props.unitRight,
        },
        grid: { drawOnChartArea: false },
      },
    } : {}),
  },
  plugins: {
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
