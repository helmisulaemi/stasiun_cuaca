<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')" />

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ device ? 'Edit Device' : 'Tambah Device' }}
        </h3>

        <form @submit.prevent="submit">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Device</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="Contoh: WS-BDO-002"
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
            <select
              v-model="form.location_id"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="">Pilih lokasi...</option>
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                {{ loc.name }}
              </option>
            </select>
          </div>

          <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            {{ error }}
          </div>

          <div v-if="secret" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm font-medium text-yellow-800 mb-2">Secret API Key (tampilkan sekali saja):</p>
            <code class="block text-xs bg-white p-2 rounded border break-all text-gray-800">{{ secret }}</code>
            <p class="text-xs text-yellow-700 mt-2">Simpan secret ini. Tidak akan ditampilkan lagi.</p>
          </div>

          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              {{ secret ? 'Tutup' : 'Batal' }}
            </button>
            <button
              v-if="!secret"
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50"
            >
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Location {
  id: string
  name: string
}

interface Device {
  id: string
  name: string
  location_id: string
}

const props = defineProps<{
  show: boolean
  device?: Device | null
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const { get, post, patch } = useApi()

const locations = ref<Location[]>([])
const loading = ref(false)
const error = ref('')
const secret = ref('')

const form = reactive({
  name: '',
  location_id: '',
})

watch(() => props.show, async (val) => {
  if (val) {
    error.value = ''
    secret.value = ''
    if (props.device) {
      form.name = props.device.name
      form.location_id = props.device.location_id
    } else {
      form.name = ''
      form.location_id = ''
    }
    const { data } = await get<Location[]>('/locations')
    if (data) locations.value = data
  }
})

async function submit() {
  loading.value = true
  error.value = ''

  if (props.device) {
    const { error: err } = await patch(`/devices/${props.device.id}`, {
      name: form.name,
      location_id: form.location_id,
    })
    if (err) {
      error.value = err
    } else {
      emit('saved')
      emit('close')
    }
  } else {
    const { data, error: err } = await post<{ device: Device; secret: string }>('/devices', {
      name: form.name,
      location_id: form.location_id,
    })
    if (err) {
      error.value = err
    } else if (data) {
      secret.value = data.secret
      emit('saved')
    }
  }

  loading.value = false
}
</script>
