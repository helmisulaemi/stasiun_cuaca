<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Device Management</h1>
      <button
        @click="openCreate"
        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
      >
        + Tambah Device
      </button>
    </div>

    <LoadingState v-if="loading" />

    <template v-else>
      <ErrorState v-if="error" :message="error" />

      <template v-else>
        <div class="bg-white shadow rounded-lg">
          <div class="p-4 border-b border-gray-200">
            <div class="flex flex-wrap gap-3">
              <input
                v-model="filters.q"
                @input="debouncedFetch"
                type="text"
                placeholder="Cari nama device..."
                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 w-64"
              />
              <select
                v-model="filters.status"
                @change="fetchDevices"
                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option value="">Semua Status</option>
                <option value="provisioned">Provisioned</option>
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="decommissioned">Decommissioned</option>
              </select>
              <select
                v-model="filters.location_id"
                @change="fetchDevices"
                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option value="">Semua Lokasi</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                  {{ loc.name }}
                </option>
              </select>
            </div>
          </div>

          <EmptyState v-if="devices.length === 0" message="Tidak ada device ditemukan." />

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-4 py-3 font-medium text-gray-500">Nama</th>
                  <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                  <th class="text-left px-4 py-3 font-medium text-gray-500">Lokasi</th>
                  <th class="text-left px-4 py-3 font-medium text-gray-500">Terakhir Kirim</th>
                  <th class="text-right px-4 py-3 font-medium text-gray-500">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="device in devices" :key="device.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3">
                    <NuxtLink :to="`/devices/${device.id}`" class="font-medium text-gray-900 hover:text-primary-600">
                      {{ device.name }}
                    </NuxtLink>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="statusClass(device)"
                    >
                      {{ statusLabel(device) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-500">{{ device.location?.name || '-' }}</td>
                  <td class="px-4 py-3 text-gray-500">
                    {{ device.last_seen_at ? timeAgo(device.last_seen_at) : '-' }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <button
                      v-if="device.status !== 'decommissioned'"
                      @click="openEdit(device)"
                      class="text-primary-600 hover:text-primary-800 text-xs mr-3"
                    >
                      Edit
                    </button>
                    <button
                      v-if="device.status !== 'decommissioned'"
                      @click="openStatus(device)"
                      class="text-gray-600 hover:text-gray-800 text-xs mr-3"
                    >
                      Status
                    </button>
                    <button
                      v-if="device.status !== 'decommissioned'"
                      @click="openSensors(device)"
                      class="text-gray-600 hover:text-gray-800 text-xs mr-3"
                    >
                      Sensor
                    </button>
                    <button
                      v-if="device.status !== 'decommissioned'"
                      @click="confirmDelete(device)"
                      class="text-red-600 hover:text-red-800 text-xs"
                    >
                      Hapus
                    </button>
                    <span v-if="device.status === 'decommissioned'" class="text-xs text-gray-400">
                      Decommissioned
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

           <!-- v-if="pagination.last_page > 1" -->
          <div class="px-4 py-3 border-t flex items-center justify-between">
            <span class="text-sm text-gray-500">
              Halaman {{ pagination.current_page }} dari {{ pagination.last_page }} ({{ pagination.total }} device)
            </span>
            <div class="flex space-x-1">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="goToPage(page)"
                class="px-3 py-1 text-sm rounded"
                :class="page === pagination.current_page
                  ? 'bg-primary-600 text-white'
                  : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'"
              >
                {{ page }}
              </button>
            </div>
          </div>
        </div>
      </template>
    </template>

    <DeviceForm
      :show="showForm"
      :device="editingDevice"
      @close="closeForm"
      @saved="onSaved"
    />

    <DeviceStatusModal
      :show="showStatusModal"
      :device="statusDevice"
      @close="showStatusModal = false"
      @saved="fetchDevices"
    />

    <DeviceSensors
      :show="showSensorsModal"
      :device="sensorsDevice"
      @close="showSensorsModal = false"
      @saved="fetchDevices"
    />

    <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showDeleteConfirm = false" />
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Hapus Device?</h3>
          <p class="text-sm text-gray-500 mb-4">
            Device <strong>{{ deletingDevice?.name }}</strong> akan dihapus (soft delete). Data historis tetap tersimpan.
          </p>
          <div class="flex justify-end space-x-3">
            <button
              @click="showDeleteConfirm = false"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Batal
            </button>
            <button
              @click="deleteDevice"
              :disabled="deleting"
              class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
            >
              {{ deleting ? 'Menghapus...' : 'Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </div>
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
  last_seen_at: string | null
  location: DeviceLocation
}

interface Location {
  id: string
  name: string
}

const { get, del } = useApi()
const { timeAgo, isStale } = useWib()

const devices = ref<Device[]>([])
const locations = ref<Location[]>([])
const loading = ref(true)
const error = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const filters = reactive({ q: '', status: '', location_id: '' })

const showForm = ref(false)
const editingDevice = ref<Device | null>(null)
const showStatusModal = ref(false)
const statusDevice = ref<Device | null>(null)
const showSensorsModal = ref(false)
const sensorsDevice = ref<Device | null>(null)
const showDeleteConfirm = ref(false)
const deletingDevice = ref<Device | null>(null)
const deleting = ref(false)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

const visiblePages = computed(() => {
  const pages: number[] = []
  const total = pagination.value.last_page
  const current = pagination.value.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

onMounted(async () => {
  const { data } = await get<Location[]>('/locations')
  if (data) locations.value = data
  await fetchDevices()
})

async function fetchDevices(page = 1) {
  loading.value = true
  error.value = ''

  const params: Record<string, string> = { per_page: '10' }
  if (page > 1) params.page = String(page)
  if (filters.q) params.q = filters.q
  if (filters.status) params.status = filters.status
  if (filters.location_id) params.location_id = filters.location_id

  const { data, error: err } = await get<{
    data: Device[]
    current_page: number
    last_page: number
    total: number
  }>('/devices', params)

  if (err) {
    error.value = err
  } else if (data) {
    devices.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
    }
  }

  loading.value = false
}

function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchDevices(), 300)
}

function goToPage(page: number) {
  fetchDevices(page)
}

function statusClass(device: Device) {
  if (device.status === 'decommissioned') return 'bg-gray-100 text-gray-600'
  if (device.status === 'maintenance') return 'bg-yellow-100 text-yellow-800'
  if (device.status === 'provisioned') return 'bg-blue-100 text-blue-800'
  if (isStale(device.last_seen_at)) return 'bg-red-100 text-red-800'
  return 'bg-green-100 text-green-800'
}

function statusLabel(device: Device) {
  if (device.status === 'decommissioned') return 'Decommissioned'
  if (device.status === 'maintenance') return 'Maintenance'
  if (device.status === 'provisioned') return 'Provisioned'
  if (isStale(device.last_seen_at)) return 'Offline'
  return 'Online'
}

function openCreate() {
  editingDevice.value = null
  showForm.value = true
}

function openEdit(device: Device) {
  editingDevice.value = device
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingDevice.value = null
}

function onSaved() {
  fetchDevices(pagination.value.current_page)
}

function openStatus(device: Device) {
  statusDevice.value = device
  showStatusModal.value = true
}

function openSensors(device: Device) {
  sensorsDevice.value = device
  showSensorsModal.value = true
}

function confirmDelete(device: Device) {
  deletingDevice.value = device
  showDeleteConfirm.value = true
}

async function deleteDevice() {
  if (!deletingDevice.value) return
  deleting.value = true

  const { error: err } = await del(`/devices/${deletingDevice.value.id}`)

  if (!err) {
    showDeleteConfirm.value = false
    deletingDevice.value = null
    await fetchDevices(pagination.value.current_page)
  }

  deleting.value = false
}
</script>
