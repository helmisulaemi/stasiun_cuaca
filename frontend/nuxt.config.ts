export default defineNuxtConfig({
  devtools: { enabled: true },

  ssr: false,

  runtimeConfig: {
    public: {
      apiBase: '', // WAJIB diisi via .env (NUXT_PUBLIC_API_BASE)
    },
  },

  modules: ['@nuxtjs/tailwindcss'],

  app: {
    head: {
      title: 'Weather Station Monitor',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      ],
    },
  },
})
