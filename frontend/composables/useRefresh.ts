export function useRefresh(callback: () => void, intervalMs: number = 30000) {
  const isActive = ref(true)
  let timer: ReturnType<typeof setInterval> | null = null

  function start() {
    if (timer) return
    timer = setInterval(() => {
      if (isActive.value) {
        callback()
      }
    }, intervalMs)
  }

  function stop() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  function pause() {
    isActive.value = false
  }

  function resume() {
    isActive.value = true
  }

  onMounted(() => start())
  onUnmounted(() => stop())

  return { pause, resume, stop, start }
}
