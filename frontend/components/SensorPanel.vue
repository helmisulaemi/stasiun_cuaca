<template>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    <div
      v-for="reading in sortedReadings"
      :key="reading.sensor_type"
      class="bg-white rounded-lg shadow p-4"
    >
      <div class="flex items-center gap-2 mb-2">
        <span class="text-lg">{{ getIcon(reading.sensor_type) }}</span>
        <span class="text-xs text-gray-500 uppercase tracking-wide">{{ getLabel(reading.sensor_type) }}</span>
      </div>
      <div class="text-2xl font-bold text-gray-900">
        {{ formatValue(reading.value, reading.sensor_type) }}
      </div>
      <div class="text-sm text-gray-500">{{ reading.unit }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Reading {
  sensor_type: string
  unit: string
  value: number
  ts: string
}

const props = defineProps<{
  readings: Reading[]
}>()

const sensorOrder = ['temp_air', 'humidity', 'pressure', 'wind_speed', 'wind_dir', 'rain_counter', 'solar_rad']

const sortedReadings = computed(() => {
  return [...props.readings].sort((a, b) => {
    return sensorOrder.indexOf(a.sensor_type) - sensorOrder.indexOf(b.sensor_type)
  })
})

function getIcon(type: string): string {
  const icons: Record<string, string> = {
    temp_air: '🌡️',
    humidity: '💧',
    pressure: '📊',
    wind_speed: '💨',
    wind_dir: '🧭',
    rain_counter: '🌧️',
    solar_rad: '☀️',
  }
  return icons[type] || '📈'
}

function getLabel(type: string): string {
  const labels: Record<string, string> = {
    temp_air: 'Suhu',
    humidity: 'Kelembapan',
    pressure: 'Tekanan',
    wind_speed: 'Kecepatan Angin',
    wind_dir: 'Arah Angin',
    rain_counter: 'Curah Hujan',
    solar_rad: 'Radiasi Matahari',
  }
  return labels[type] || type
}

function formatValue(value: number, type: string): string {
  if (type === 'wind_dir') {
    const dirs = ['U', 'UT', 'T', 'TT', 'Timur', 'TB', 'B', 'BB', 'Barat', 'BD', 'D', 'DT', 'Selatan']
    const index = Math.round(value / 27.69) % 12
    return `${Math.round(value)}° (${dirs[index]})`
  }
  return value.toFixed(1)
}
</script>
