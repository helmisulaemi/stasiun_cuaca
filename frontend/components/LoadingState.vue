<template>
  <div class="animate-pulse space-y-4" :class="className">
    <div v-for="i in lines" :key="i" class="h-4 bg-gray-200 rounded" :style="{ width: i === lines ? '60%' : '100%' }"></div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  lines?: number
  className?: string
}>(), {
  lines: 3,
  className: '',
})
</script>
