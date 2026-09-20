<template>
  <div class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">Terjadi Kesalahan</h3>
    <p class="mt-1 text-sm text-gray-500">{{ message }}</p>
    <div class="mt-6">
      <button
        @click="$emit('retry')"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        Coba Lagi
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  message?: string
}>()

defineEmits<{
  retry: []
}>()
</script>
