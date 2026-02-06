const STORAGE_KEY = 'ardocco:favorites:listings'

function safeParseJsonArray(value: string | null): string[] {
  if (!value) return []
  try {
    const parsed = JSON.parse(value)
    if (!Array.isArray(parsed)) return []
    return parsed.map(String).filter(Boolean)
  } catch {
    return []
  }
}

export function useFavoriteListings() {
  const favoriteIds = useState<string[]>('favorites:listings', () => [])
  const hasLoaded = useState<boolean>('favorites:listings:loaded', () => false)
  const hasWatch = useState<boolean>('favorites:listings:watch', () => false)

  if (import.meta.client && !hasLoaded.value) {
    hasLoaded.value = true
    favoriteIds.value = Array.from(new Set(safeParseJsonArray(localStorage.getItem(STORAGE_KEY))))
  }

  if (import.meta.client && !hasWatch.value) {
    hasWatch.value = true
    watch(
      favoriteIds,
      (value) => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(new Set(value))))
      },
      { deep: true }
    )
  }

  const favoriteSet = computed(() => new Set(favoriteIds.value))

  function isFavorite(id: string) {
    return favoriteSet.value.has(String(id))
  }

  function toggleFavorite(id: string) {
    const normalized = String(id)
    if (!normalized) return

    if (isFavorite(normalized)) {
      favoriteIds.value = favoriteIds.value.filter(x => x !== normalized)
      return
    }

    favoriteIds.value = Array.from(new Set([...favoriteIds.value, normalized]))
  }

  return {
    favoriteIds,
    isFavorite,
    toggleFavorite
  }
}
