<template>
  <div>
    <div class="mb-6">
      <NuxtLink to="/" class="text-primary-600 hover:underline text-sm font-medium">
        ← Kembali ke Dashboard
      </NuxtLink>

      <div v-if="device" class="mt-4">
        <div class="flex items-center gap-4 flex-wrap">
          <h1 class="text-2xl font-bold text-gray-900">{{ device.name }}</h1>
          <span
            class="px-2 py-1 text-xs font-medium rounded-full"
            :class="device.is_offline ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
          >
            {{ device.is_offline ? 'Offline' : 'Online' }}
          </span>
          <span class="text-sm text-gray-500">{{ device.location?.name }}</span>
        </div>
        <div class="flex gap-6 mt-2 text-sm text-gray-500">
          <span>Firmware: {{ device.fw_version || '--' }}</span>
          <span>Battery: {{ device.last_battery_v ? device.last_battery_v + 'V' : '--' }}</span>
          <span>RSSI: {{ device.last_rssi ? device.last_rssi + ' dBm' : '--' }}</span>
          <span>Terakhir update: {{ timeAgo(device.last_seen_at) }}</span>
        </div>
      </div>
    </div>

    <LoadingState v-if="loading" :lines="8" />
    <ErrorState v-else-if="error" :message="error" @retry="fetchAll" />
    <template v-else>
      <SensorPanel :readings="latest" />

      <div class="mt-8">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Chart</h2>
          <div class="flex gap-2">
            <button
              v-for="range in rangeOptions"
              :key="range.value"
              @click="selectedRange = range.value"
              class="px-3 py-1 rounded text-sm font-medium transition-colors"
              :class="selectedRange === range.value
                ? 'bg-primary-600 text-white'
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
            >
              {{ range.label }}
            </button>
          </div>
        </div>

        <div class="space-y-4">
          <TimeSeriesChart
            title="Suhu & Kelembapan"
            :timestamps="tempHumidityTimestamps"
            :datasets="tempHumidityDatasets"
            unit-left="°C"
            unit-right="%"
          />

          <RainChart
            :timestamps="rainTimestamps"
            :values="rainValues"
          />

          <WindChart
            :timestamps="windTimestamps"
            :values="windValues"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
interface DeviceLocation {
  name: string | null
}

interface Device {
  id: string
  name: string
  status: string
  is_offline: boolean
  last_seen_at: string | null
  last_battery_v: number | null
  last_rssi: number | null
  fw_version: string | null
  location: DeviceLocation
}

interface LatestReading {
  sensor_type: string
  unit: string
  value: number
  ts: string
}

interface SeriesItem {
  sensor_type: string
  unit: string
  timestamps: string[]
  values: number[]
}

const route = useRoute()
const deviceId = route.params.id as string
const { get } = useApi()
const { timeAgo } = useWib()

const device = ref<Device | null>(null)
const latest = ref<LatestReading[]>([])
const series = ref<SeriesItem[]>([])
const selectedRange = ref('24h')
const loading = ref(true)
const error = ref('')

const rangeOptions = [
  { label: '24 jam', value: '24h' },
  { label: '7 hari', value: '7d' },
  { label: '30 hari', value: '30d' },
]

const rangeToInterval: Record<string, string> = {
  '24h': '1m',
  '7d': '1h',
  '30d': '1d',
}

const rangeHours: Record<string, number> = {
  '24h': 24,
  '7d': 168,
  '30d': 720,
}

async function fetchDevice() {
  const { data, error: err } = await get<Device>(`/devices/${deviceId}`)
  if (err) {
    error.value = err
    return
  }
  device.value = data
}

async function fetchLatest() {
  const { data, error: err } = await get<{ device_id: string; readings: LatestReading[] }>(
    `/devices/${deviceId}/readings/latest`,
  )
  if (err) {
    error.value = err
    return
  }
  latest.value = data?.readings || []
}

async function fetchSeries() {
  const interval = rangeToInterval[selectedRange.value]
  const hours = rangeHours[selectedRange.value]
  const from = new Date(Date.now() - hours * 60 * 60 * 1000).toISOString()
  const to = new Date().toISOString()

  const { data, error: err } = await get<{
    device_id: string
    interval: string
    series: SeriesItem[]
  }>('/readings', {
    device_id: deviceId,
    interval,
    from,
    to,
  })
  if (err) {
    error.value = err
    return
  }
  series.value = data?.series || []
}

async function fetchAll() {
  loading.value = true
  error.value = ''
  await Promise.all([fetchDevice(), fetchLatest()])
  if (!error.value) {
    await fetchSeries()
  }
  loading.value = false
}

const tempHumidityTimestamps = computed(() => {
  const tempSeries = series.value.find(s => s.sensor_type === 'temp_air')
  return tempSeries?.timestamps || []
})

const tempHumidityDatasets = computed(() => {
  const tempSeries = series.value.find(s => s.sensor_type === 'temp_air')
  const humSeries = series.value.find(s => s.sensor_type === 'humidity')

  return [
    {
      label: 'Suhu (°C)',
      values: tempSeries?.values || [],
      color: '#ef4444',
      yAxisID: 'y',
    },
    {
      label: 'Kelembapan (%)',
      values: humSeries?.values || [],
      color: '#3b82f6',
      yAxisID: 'y1',
    },
  ]
})

const rainTimestamps = computed(() => {
  const rainSeries = series.value.find(s => s.sensor_type === 'rain_counter')
  return rainSeries?.timestamps || []
})

const rainValues = computed(() => {
  const rainSeries = series.value.find(s => s.sensor_type === 'rain_counter')
  return rainSeries?.values || []
})

const windTimestamps = computed(() => {
  const windSeries = series.value.find(s => s.sensor_type === 'wind_speed')
  return windSeries?.timestamps || []
})

const windValues = computed(() => {
  const windSeries = series.value.find(s => s.sensor_type === 'wind_speed')
  return windSeries?.values || []
})

watch(selectedRange, () => {
  fetchSeries()
})

onMounted(() => {
  fetchAll()
})
</script>
