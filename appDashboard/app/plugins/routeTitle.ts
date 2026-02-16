export default defineNuxtPlugin(() => {
  const route = useRoute()

  useHead(() => {
    const metaTitle = route.meta?.title
    const title = typeof metaTitle === 'string' ? metaTitle.trim() : ''
    if (!title) return {}

    return { title }
  })
})
