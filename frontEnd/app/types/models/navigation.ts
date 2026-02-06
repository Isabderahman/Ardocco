import type { SidebarItemType } from '~/types/enums/ui'

export type NavItem = {
  label: string
  to?: string
  icon?: string
  children?: NavItem[]
}

export type ASidebarItem = {
  label?: string
  /**
   * @IconifyIcon
   * Example: `i-lucide-layout-dashboard`
   */
  icon?: string
  to?: string
  badge?: string | number
  children?: ASidebarItem[]
  type?: SidebarItemType
  disabled?: boolean
  onSelect?: (e: Event) => void
  value?: string
}
