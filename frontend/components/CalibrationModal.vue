<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')" />

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Kalibrasi Sensor</h3>
        <p class="text-sm text-gray-500 mb-4">{{ sensor?.name }}</p>

        <div v-if="loading" class="text-center py-4 text-gray-500">Memuat...</div>

        <div v-else>
          <div v-if="calibrations.length === 0" class="text-sm text-gray-500 mb-4">
            Belum ada riwayat kalibrasi.
          </div>

          <div v-else class="mb-4 max-h-48 overflow-y-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 border-b">
                  <th class="pb-2">Tanggal</th>
                  <th class="pb-2">Offset</th>
                  <th class="pb-2">Scale</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in calibrations" :key="c.id" class="border-b">
                  <td class="py-2 text-gray-900">{{ formatDate(c.effective_from) }}</td>
                  <td class="py-2 text-gray-600">{{ c.offset }}</td>
                  <td class="py-2 text-gray-600">{{ c.scale }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="border-t pt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tambah Kalibrasi Baru</label>
            <div class="grid grid-cols-3 gap-3 mb-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Offset</label>
                <input
                  v-model.number="form.offset"
                  type="number"
                  step="0.01"
                  class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="0.0"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Scale</label>
                <input
                  v-model.number="form.scale"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="1.0"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">Effective From</label>
                <input
                  v-model="form.effective_from"
                  type="datetime-local"
                  class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>
            </div>
            <button
              @click="addCalibration"
              :disabled="!isValid || saving"
              class="w-full px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
            >
              {{ saving ? 'Menyimpan...' : 'Tambah Kalibrasi' }}
            </button>
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
</template>

<script setup lang="ts">
interface Calibration {
  id: string
  offset: number
  scale: number
  effective_from: string
}

interface Sensor {
  id: string
  name: string
}

const props = defineProps<{
  show: boolean
  sensor: Sensor | null
}>()

const emit = defineEmits<{
  close: []
}>()

const { get, post } = useApi()

const calibrations = ref<Calibration[]>([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')

const form = reactive({
  offset: 0,
  scale: 1,
  effective_from: '',
})

const isValid = computed(() =>
  form.scale > 0 && form.effective_from !== ''
)

async function fetchCalibrations() {
  if (!props.sensor) return
  loading.value = true
  error.value = ''
  form.offset = 0
  form.scale = 1
  form.effective_from = new Date().toISOString().slice(0, 16)

  const { data } = await get<{ data: Calibration[] }>(`/sensors/${props.sensor.id}/calibrations`)
  if (data) {
    calibrations.value = data.data || (data as any)
  }
  loading.value = false
}

watch(() => props.show, async (val) => {
  if (val && props.sensor) {
    await fetchCalibrations()
  }
}, { immediate: true })

function formatDate(ts: string) {
  return new Date(ts).toLocaleDateString('id-ID', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

async function addCalibration() {
  if (!props.sensor || !isValid.value) return
  saving.value = true
  error.value = ''

  const { error: err } = await post(`/sensors/${props.sensor.id}/calibrations`, {
    offset: form.offset,
    scale: form.scale,
    effective_from: new Date(form.effective_from).toISOString(),
  })

  if (err) {
    error.value = err
  } else {
    const { data } = await get<{ data: Calibration[] }>(`/sensors/${props.sensor.id}/calibrations`)
    if (data) calibrations.value = data.data || (data as any)
  }

  saving.value = false
}
</script>
