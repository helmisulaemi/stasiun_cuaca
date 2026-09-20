<template>
  <div
    class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200 overflow-hidden"
  >
    <div class="p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold text-gray-900 truncate">{{ device.name }}</h3>
        <span
          class="px-2 py-1 text-xs font-medium rounded-full"
          :class="statusClass"
        >
          {{ statusLabel }}
        </span>
      </div>

      <p class="text-sm text-gray-500 mb-4">{{ device.location?.name || 'Lokasi tidak diketahui' }}</p>

      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Suhu</p>
          <p class="text-xl font-bold text-gray-900">
            {{ device.latest?.temp_air != null ? `${device.latest.temp_air}°C` : '--' }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Kelembapan</p>
          <p class="text-xl font-bold text-gray-900">
            {{ device.latest?.humidity != null ? `${device.latest.humidity}%` : '--' }}
          </p>
        </div>
      </div>

      <div class="flex items-center justify-between text-sm">
        <div class="flex items-center text-gray-500">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span :class="{ 'text-yellow-600 font-medium': stale }">
            {{ timeAgo(device.last_seen_at) }}
          </span>
        </div>

        <div v-if="device.last_battery_v != null" class="flex items-center">
          <svg class="w-4 h-4 mr-1" :class="batteryColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <span class="text-sm" :class="batteryColor">{{ device.last_battery_v }}V</span>
        </div>
      </div>
    </div>

    <NuxtLink
      :to="`/devices/${device.id}`"
      class="block bg-gray-50 px-5 py-3 text-center text-sm font-medium text-primary-600 hover:bg-gray-100 transition-colors"
    >
      Lihat Detail →
    </NuxtLink>
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

const props = defineProps<{
  device: Device
}>()

const { timeAgo, isStale } = useWib()

const stale = computed(() => isStale(props.device.last_seen_at))

const statusLabel = computed(() => {
  if (props.device.is_offline) return 'Offline'
  if (props.device.status === 'maintenance') return 'Maintenance'
  return 'Online'
})

const statusClass = computed(() => {
  if (props.device.is_offline) return 'bg-red-100 text-red-800'
  if (props.device.status === 'maintenance') return 'bg-yellow-100 text-yellow-800'
  return 'bg-green-100 text-green-800'
})

const batteryColor = computed(() => {
  if (!props.device.last_battery_v) return 'text-gray-400'
  if (props.device.last_battery_v < 3.5) return 'text-red-600'
  if (props.device.last_battery_v < 3.7) return 'text-yellow-600'
  return 'text-green-600'
})
</script>
