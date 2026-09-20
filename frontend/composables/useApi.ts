interface ApiResponse<T = any> {
  success: boolean
  data?: T
  error?: {
    code: string
    message: string
    details?: any
  }
  meta?: {
    request_id: string
  }
}

interface PaginatedData<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function useApi() {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  const token = useCookie('auth_token')

  async function request<T = any>(
    path: string,
    options: {
      method?: string
      body?: any
      params?: Record<string, string>
      auth?: boolean
    } = {},
  ): Promise<{ data: T | null; error: string | null }> {
    const { method = 'GET', body, params, auth = true } = options

    let url = `${apiBase}${path}`

    if (params) {
      const searchParams = new URLSearchParams()
      for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== '') {
          searchParams.append(key, value)
        }
      }
      const qs = searchParams.toString()
      if (qs) url += `?${qs}`
    }

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    }

    if (auth && token.value) {
      headers.Authorization = `Bearer ${token.value}`
    }

    try {
      const res = await $fetch<ApiResponse<T>>(url, {
        method: method as any,
        headers,
        body: body ? JSON.stringify(body) : undefined,
      })

      if (!res.success) {
        return { data: null, error: res.error?.message || 'Terjadi kesalahan' }
      }

      return { data: res.data ?? null, error: null }
    } catch (err: any) {
      const message = err?.data?.error?.message || err?.message || 'Gagal terhubung ke server'
      return { data: null, error: message }
    }
  }

  function get<T = any>(path: string, params?: Record<string, string>) {
    return request<T>(path, { params })
  }

  function post<T = any>(path: string, body?: any) {
    return request<T>(path, { method: 'POST', body })
  }

  function patch<T = any>(path: string, body?: any) {
    return request<T>(path, { method: 'PATCH', body })
  }

  function del<T = any>(path: string) {
    return request<T>(path, { method: 'DELETE' })
  }

  return { get, post, patch, del }
}
