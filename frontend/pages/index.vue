<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <div class="flex items-center space-x-4">
        <span class="text-sm text-gray-500">
          Auto-refresh: {{ isPaused ? 'Paused' : `${remainingSeconds}s` }}
        </span>
        <button
          @click="toggleRefresh"
          class="text-sm font-medium text-primary-600 hover:text-primary-700"
        >
          {{ isPaused ? '▶ Resume' : '⏸ Pause' }}
        </button>
      </div>
    </div>

    <LoadingState v-if="loading" :lines="6" />
    <ErrorState v-else-if="error" :message="error" @retry="fetchData" />
    <EmptyState
      v-else-if="!devices.length"
      title="Belum ada device"
      message="Tambahkan device terlebih dahulu melalui halaman Management."
    />
    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-gray-900">{{ summary.total_devices }}</div>
          <div class="text-sm text-gray-500">Total Device</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-green-600">{{ summary.online }}</div>
          <div class="text-sm text-gray-500">Online</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-3xl font-bold text-red-600">{{ summary.offline }}</div>
          <div class="text-sm text-gray-500">Offline</div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <DeviceCard v-for="device in devices" :key="device.id" :device="device" />
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
  location: DeviceLocation
  latest: Record<string, number> | null
}

interface Summary {
  total_devices: number
  online: number
  offline: number
}

const { get } = useApi()

const devices = ref<Device[]>([])
const summary = ref<Summary>({ total_devices: 0, online: 0, offline: 0 })
const loading = ref(true)
const error = ref('')

const remainingSeconds = ref(30)
const isPaused = ref(false)
let countdownTimer: ReturnType<typeof setInterval> | null = null

async function fetchData() {
  remainingSeconds.value = 30
  loading.value = true
  error.value = ''

  const { data, error: err } = await get<{ devices: Device[]; summary: Summary }>('/dashboard/overview')

  if (err) {
    error.value = err
    loading.value = false
    return
  }

  if (data) {
    devices.value = data.devices
    summary.value = data.summary
  }

  loading.value = false
}

function toggleRefresh() {
  isPaused.value = !isPaused.value
}

onMounted(async () => {
  await fetchData()

  countdownTimer = setInterval(() => {
    if (!isPaused.value && !loading.value) {
      remainingSeconds.value--
      if (remainingSeconds.value <= 0) {
        fetchData()
      }
    }
  }, 1000)
})

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})
</script>
