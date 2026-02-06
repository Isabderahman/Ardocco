export type BackendResponse<T> = {
  success: boolean
  message?: string
  data: T
}

export type LaravelPage<T> = {
  current_page: number
  data: T[]
  per_page: number
  total: number
  last_page: number
}

export type ApiResponse<T> = {
  success: boolean
  message?: string
  data?: T
  total?: number
  region_code?: string
}
