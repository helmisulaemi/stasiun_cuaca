<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')" />

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Sensor — {{ device?.name }}
        </h3>

        <div v-if="loading" class="text-center py-4 text-gray-500">Memuat...</div>

        <div v-else>
          <div v-if="installed.length === 0" class="text-sm text-gray-500 mb-4">
            Belum ada sensor terpasang.
          </div>

          <div v-else class="mb-4">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 border-b">
                  <th class="pb-2">Serial Number</th>
                  <th class="pb-2">Tipe</th>
                  <th class="pb-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in installed" :key="s.installation_id" class="border-b">
                  <td class="py-2 text-gray-900">{{ s.serial_number }}</td>
                  <td class="py-2 text-gray-500">{{ s.sensor_type_name }} ({{ s.unit }})</td>
                  <td class="py-2 text-right">
                    <button
                      @click="openCalibration(s)"
                      class="text-xs text-primary-600 hover:text-primary-800 mr-2"
                    >
                      Kalibrasi
                    </button>
                    <button
                      @click="removeSensor(s)"
                      :disabled="removing === s.installation_id"
                      class="text-xs text-red-600 hover:text-red-800"
                    >
                      {{ removing === s.installation_id ? '...' : 'Lepas' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="border-t pt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Pasang Sensor Baru</label>
            <div class="flex space-x-2">
              <select
                v-model="selectedSensorId"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option value="">Pilih sensor...</option>
                <option v-for="s in availableSensors" :key="s.id" :value="s.id">
                  {{ s.serial_number }} ({{ s.sensor_type?.name || '?' }})
                </option>
              </select>
              <button
                @click="installSensor"
                :disabled="!selectedSensorId || installing"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
              >
                {{ installing ? '...' : 'Pasang' }}
              </button>
            </div>
          </div>

          <div v-if="error" class="mt-3 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            {{ error }}
          </div>
        </div>

        <div class="flex justify-end mt-4">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

  <CalibrationModal
    v-if="calibrationSensor"
    :show="true"
    :sensor="calibrationSensor"
    @close="calibrationSensor = null"
  />
</template>

<script setup lang="ts">
interface InstalledSensor {
  installation_id: string
  sensor_id: string
  serial_number: string
  model: string | null
  sensor_type_name: string
  unit: string
  installed_at: string
}

interface SensorType {
  id: string
  name: string
  unit: string
}

interface SensorItem {
  id: string
  serial_number: string
  sensor_type?: SensorType
}

interface Device {
  id: string
  name: string
}

const props = defineProps<{
  show: boolean
  device: Device | null
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const { get, post, del } = useApi()

const installed = ref<InstalledSensor[]>([])
const availableSensors = ref<SensorItem[]>([])
const selectedSensorId = ref('')
const loading = ref(true)
const installing = ref(false)
const removing = ref('')
const error = ref('')
const calibrationSensor = ref<{ id: string; name: string } | null>(null)

async function fetchDeviceSensors() {
  if (!props.device) return
  loading.value = true
  error.value = ''
  selectedSensorId.value = ''

  const [installedRes, availableRes] = await Promise.all([
    get<InstalledSensor[]>(`/devices/${props.device.id}/sensors`),
    get<SensorItem[]>('/sensors/available'),
  ])

  if (installedRes.data) installed.value = installedRes.data
  if (availableRes.data) availableSensors.value = availableRes.data

  loading.value = false
}

watch(() => props.show, async (val) => {
  if (val && props.device) {
    await fetchDeviceSensors()
  }
}, { immediate: true })

async function installSensor() {
  if (!props.device || !selectedSensorId.value) return
  installing.value = true
  error.value = ''

  const { error: err } = await post(`/devices/${props.device.id}/sensors`, {
    sensor_id: selectedSensorId.value,
  })

  if (err) {
    error.value = err
  } else {
    selectedSensorId.value = ''
    await refreshInstalled()
    await refreshAvailable()
    emit('saved')
  }

  installing.value = false
}

async function removeSensor(sensor: InstalledSensor) {
  if (!props.device) return
  removing.value = sensor.installation_id
  error.value = ''

  const { error: err } = await del(`/devices/${props.device.id}/sensors/${sensor.sensor_id}`)

  if (err) {
    error.value = err
  } else {
    await refreshInstalled()
    await refreshAvailable()
    emit('saved')
  }

  removing.value = ''
}

async function refreshInstalled() {
  if (!props.device) return
  const { data } = await get<InstalledSensor[]>(`/devices/${props.device.id}/sensors`)
  if (data) installed.value = data
}

async function refreshAvailable() {
  const { data } = await get<SensorItem[]>('/sensors/available')
  if (data) availableSensors.value = data
}

function openCalibration(sensor: InstalledSensor) {
  calibrationSensor.value = {
    id: sensor.sensor_id,
    name: `${sensor.serial_number} (${sensor.sensor_type_name})`,
  }
}
</script>
