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
                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900 border-b-2"
                :class="$route.path === '/' ? 'border-primary-500' : 'border-transparent hover:border-gray-300'"
              >
                Dashboard
              </NuxtLink>
              <NuxtLink
                to="/devices/manage"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900 border-b-2"
                :class="$route.path === '/devices/manage' ? 'border-primary-500' : 'border-transparent hover:border-gray-300'"
              >
                Management
              </NuxtLink>
            </div>
          </div>
          <div class="flex items-center">
            <button
              v-if="token"
              @click="logout"
              class="text-sm text-gray-500 hover:text-gray-700"
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

function logout() {
  token.value = null
  navigateTo('/login')
}
</script>
