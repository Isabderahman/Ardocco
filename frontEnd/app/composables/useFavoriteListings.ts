import { favoritesService } from '~/services/favoritesService'

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
  const { token } = useAuth()
  const favoriteIds = useState<string[]>('favorites:listings', () => [])
  const hasLoaded = useState<boolean>('favorites:listings:loaded', () => false)
  const hasWatch = useState<boolean>('favorites:listings:watch', () => false)

  // Load from localStorage for guests, or from API for authenticated users
  if (import.meta.client && !hasLoaded.value) {
    hasLoaded.value = true
    if (token.value) {
      // Fetch from API for authenticated users
      favoritesService.fetchIndex(undefined, token.value).then((res) => {
        if (res.success && res.data?.data) {
          favoriteIds.value = res.data.data.map(f => f.listing_id)
        }
      }).catch(() => {
        // Fallback to localStorage
        favoriteIds.value = Array.from(new Set(safeParseJsonArray(localStorage.getItem(STORAGE_KEY))))
      })
    } else {
      favoriteIds.value = Array.from(new Set(safeParseJsonArray(localStorage.getItem(STORAGE_KEY))))
    }
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
      removeFromFavorites(normalized)
      return
    }

    addToFavorites(normalized)
  }

  async function addToFavorites(listingId: string) {
    const normalized = String(listingId)
    if (!normalized || isFavorite(normalized)) return

    // Optimistic update
    favoriteIds.value = Array.from(new Set([...favoriteIds.value, normalized]))

    // Sync with backend if authenticated
    if (token.value) {
      try {
        await favoritesService.create({ listing_id: normalized }, token.value)
      } catch (error) {
        // Revert on error
        favoriteIds.value = favoriteIds.value.filter(x => x !== normalized)
        console.error('Failed to add favorite:', error)
      }
    }
  }

  async function removeFromFavorites(listingId: string) {
    const normalized = String(listingId)
    if (!normalized) return

    // Optimistic update
    const previousIds = [...favoriteIds.value]
    favoriteIds.value = favoriteIds.value.filter(x => x !== normalized)

    // Sync with backend if authenticated
    if (token.value) {
      try {
        await favoritesService.remove(normalized, token.value)
      } catch (error) {
        // Revert on error
        favoriteIds.value = previousIds
        console.error('Failed to remove favorite:', error)
      }
    }
  }

  return {
    favoriteIds,
    isFavorite,
    toggleFavorite,
    addToFavorites,
    removeFromFavorites
  }
}
