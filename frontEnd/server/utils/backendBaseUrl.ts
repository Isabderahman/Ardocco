export function normalizeBackendBaseUrl(raw: unknown): string {
  const normalized = String(raw || '').trim().replace(/\/+$/, '')

  if (!normalized) return 'http://localhost:8000'
  if (/^https?:\/\//.test(normalized)) return normalized
  if (normalized.startsWith('/')) return normalized

  const hostPort = normalized.split('/')[0] || normalized
  const portMatch = hostPort.match(/:(\d+)$/)
  const port = portMatch ? Number(portMatch[1]) : null
  const host = hostPort.replace(/:(\d+)$/, '')

  const isLocal = /^(localhost|127\.|0\.0\.0\.0|::1|backend)\b/.test(host)
  const scheme = isLocal
    ? 'http'
    : port === 80
      ? 'http'
      : port === 443
        ? 'https'
        : port
          ? 'http'
          : 'https'

  return `${scheme}://${normalized}`
}

