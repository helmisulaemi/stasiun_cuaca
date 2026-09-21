<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="$emit('close')" />

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Ubah Status — {{ device?.name }}
        </h3>

        <div class="mb-4">
          <p class="text-sm text-gray-500 mb-2">
            Status saat ini: <span class="font-medium text-gray-900">{{ device?.status }}</span>
          </p>

          <label class="block text-sm font-medium text-gray-700 mb-1">Status Baru</label>
          <select
            v-model="newStatus"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="">Pilih status...</option>
            <option v-for="s in allowedTransitions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Alasan (opsional)</label>
          <input
            v-model="reason"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            placeholder="Contoh: Maintenance rutin"
          />
        </div>

        <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
          {{ error }}
        </div>

        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Batal
          </button>
          <button
            :disabled="!newStatus || loading"
            @click="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50"
          >
            {{ loading ? 'Menyimpan...' : 'Ubah Status' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const transitions: Record<string, string[]> = {
  provisioned: ['active'],
  active: ['maintenance'],
  maintenance: ['active', 'decommissioned'],
  decommissioned: [],
}

interface Device {
  id: string
  name: string
  status: string
}

const props = defineProps<{
  show: boolean
  device: Device | null
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const { post } = useApi()

const newStatus = ref('')
const reason = ref('')
const loading = ref(false)
const error = ref('')

const allowedTransitions = computed(() => {
  if (!props.device) return []
  return transitions[props.device.status] || []
})

watch(() => props.show, (val) => {
  if (val) {
    newStatus.value = ''
    reason.value = ''
    error.value = ''
  }
})

async function submit() {
  if (!props.device || !newStatus.value) return

  loading.value = true
  error.value = ''

  const { error: err } = await post(`/devices/${props.device.id}/status`, {
    status: newStatus.value,
    reason: reason.value || undefined,
  })

  if (err) {
    error.value = err
  } else {
    emit('saved')
    emit('close')
  }

  loading.value = false
}
</script>
