export function useWib() {
  function toWIBShort(utcString: string | null): string {
    if (!utcString) return '--'
    return new Date(utcString).toLocaleString('id-ID', {
      timeZone: 'Asia/Jakarta',
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function toWIB(utcString: string | null): string {
    if (!utcString) return '--'
    return new Date(utcString).toLocaleString('id-ID', {
      timeZone: 'Asia/Jakarta',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function timeAgo(utcString: string | null): string {
    if (!utcString) return 'Tidak diketahui'
    const diff = Date.now() - new Date(utcString).getTime()
    const seconds = Math.floor(diff / 1000)
    if (seconds < 60) return 'Baru saja'
    const minutes = Math.floor(seconds / 60)
    if (minutes < 60) return `${minutes} menit lalu`
    const hours = Math.floor(minutes / 60)
    if (hours < 24) return `${hours} jam lalu`
    const days = Math.floor(hours / 24)
    return `${days} hari lalu`
  }

  function isStale(utcString: string | null, thresholdMinutes: number = 15): boolean {
    if (!utcString) return true
    const diff = Date.now() - new Date(utcString).getTime()
    const minutes = Math.floor(diff / 60000)
    return minutes > thresholdMinutes
  }

  return { toWIB, toWIBShort, timeAgo, isStale }
}
