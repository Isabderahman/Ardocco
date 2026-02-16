import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing } from '~/types/models/listing'

export type ExpertiseType = 'all' | 'technique' | 'financiere' | 'juridique'

export type ExpertPendingListingsResponse = BackendResponse<LaravelPage<BackendListing>>
