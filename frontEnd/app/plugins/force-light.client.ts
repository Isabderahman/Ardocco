export default defineNuxtPlugin(() => {
  try {
    const colorMode = useColorMode()
    colorMode.preference = 'light'
    colorMode.value = 'light'
  } catch {
    // If @nuxtjs/color-mode isn't available for any reason, fall back to DOM/localStorage.
  }

  localStorage.setItem('ardocco-color-mode', 'light')
  localStorage.removeItem('nuxt-color-mode')

  const root = document.documentElement
  root.classList.remove('dark')
  root.classList.add('light')
  root.style.colorScheme = 'light'
})
