export class ApiError extends Error {
  readonly status: number
  readonly errors: Record<string, string[]>

  constructor(
    status: number,
    message: string,
    errors: Record<string, string[]> = {},
  ) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

function readCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`))

  return match ? decodeURIComponent(match[1]) : null
}

/**
 * Calls the Laravel backend using Sanctum's cookie-based SPA auth:
 * fetches the CSRF cookie once and echoes it back as X-XSRF-TOKEN.
 */
export async function api<T>(
  path: string,
  options: { method?: string; body?: unknown } = {},
): Promise<T> {
  const method = options.method ?? 'GET'

  if (method !== 'GET' && !readCookie('XSRF-TOKEN')) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
  }

  const response = await fetch(path, {
    method,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-XSRF-TOKEN': readCookie('XSRF-TOKEN') ?? '',
    },
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    throw new ApiError(
      response.status,
      payload?.message ?? 'Algo deu errado.',
      payload?.errors,
    )
  }

  return payload as T
}
