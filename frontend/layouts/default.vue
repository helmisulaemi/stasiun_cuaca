<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <NuxtLink to="/" class="flex items-center">
              <span class="text-xl font-bold text-primary-600">WS Monitor</span>
            </NuxtLink>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <NuxtLink
                to="/"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.path === '/' ? 'text-gray-900 border-primary-500' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'"
              >
                Dashboard
              </NuxtLink>
              <NuxtLink
                to="/devices/manage"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.path === '/devices/manage' ? 'text-gray-900 border-primary-500' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'"
              >
                Management
              </NuxtLink>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <span v-if="token" class="text-sm text-gray-500">Tersedia</span>
            <button
              v-if="token"
              @click="logout"
              class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const token = useCookie('auth_token')
const router = useRouter()

function logout() {
  token.value = null
  router.push('/login')
}
</script>
