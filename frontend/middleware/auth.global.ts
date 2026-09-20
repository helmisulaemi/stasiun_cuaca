export default defineNuxtRouteMiddleware((to) => {
  const token = useCookie('auth_token')

  const publicPages = ['/login']

  if (!publicPages.includes(to.path) && !token.value) {
    return navigateTo('/login')
  }

  if (to.path === '/login' && token.value) {
    return navigateTo('/')
  }
})
