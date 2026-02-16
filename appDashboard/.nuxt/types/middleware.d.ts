import type { NavigationGuard } from 'vue-router'
export type MiddlewareKey = "admin" | "agent" | "auth" | "expert" | "guest" | "seller"
declare module 'nuxt/app' {
  interface PageMeta {
    middleware?: MiddlewareKey | NavigationGuard | Array<MiddlewareKey | NavigationGuard>
  }
}